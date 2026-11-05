<?php
/**
 * One-location service catalog using WordPress posts and terms.
 *
 * @package Digicyfr_Service_Menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Plugin implementation, isolated from the other Digicyfr plugins. */
final class Digicyfr_Service_Menu {
	public const POST_TYPE   = 'digicyfr_service';
	public const TAXONOMY    = 'digicyfr_service_cat';
	private const DURATION   = '_digicyfr_service_duration';
	private const PRICE      = '_digicyfr_service_price';
	private const MAX_PUBLIC = 100;

	/** Whether the latest post save had a rejected title.
	 *
	 * @var bool
	 */
	private static bool $name_rejected = false;

	/** Whether the latest post save had an invalid order.
	 *
	 * @var bool
	 */
	private static bool $order_rejected = false;

	/** Register hooks. */
	public static function boot(): void {
		add_action( 'init', array( __CLASS__, 'on_init' ) );
		add_action( 'add_meta_boxes_' . self::POST_TYPE, array( __CLASS__, 'register_meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta' ), 10, 2 );
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'filter_post_data' ), 10, 4 );
		add_filter( 'redirect_post_location', array( __CLASS__, 'append_save_notice' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'list_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'list_column_value' ), 10, 2 );
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_post_digicyfr_service_menu_archive', array( __CLASS__, 'archive_action' ) );
		add_action( 'admin_post_digicyfr_service_menu_export', array( __CLASS__, 'export_action' ) );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'category_filter' ), 10, 2 );
		add_action( 'pre_get_posts', array( __CLASS__, 'filter_admin_list' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_style' ) );
	}

	/** Register private service data and the two public embed surfaces. */
	public static function on_init(): void {
		load_plugin_textdomain( 'digicyfr-service-menu', false, dirname( plugin_basename( DIGICYFR_SERVICE_MENU_PATH . 'digicyfr-service-menu.php' ) ) . '/languages' );
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'Services', 'digicyfr-service-menu' ),
					'singular_name'      => __( 'Service', 'digicyfr-service-menu' ),
					'add_new_item'       => __( 'Add service', 'digicyfr-service-menu' ),
					'edit_item'          => __( 'Edit service', 'digicyfr-service-menu' ),
					'new_item'           => __( 'New service', 'digicyfr-service-menu' ),
					'view_items'         => __( 'View services', 'digicyfr-service-menu' ),
					'search_items'       => __( 'Search services', 'digicyfr-service-menu' ),
					'not_found'          => __( 'No services found.', 'digicyfr-service-menu' ),
					'not_found_in_trash' => __( 'No services found in Trash.', 'digicyfr-service-menu' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => true,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'supports'            => array( 'title', 'excerpt', 'author', 'page-attributes' ),
				'menu_icon'           => 'dashicons-list-view',
			)
		);
		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			array(
				'labels'             => array(
					'name'          => __( 'Service categories', 'digicyfr-service-menu' ),
					'singular_name' => __( 'Service category', 'digicyfr-service-menu' ),
					'search_items'  => __( 'Search service categories', 'digicyfr-service-menu' ),
					'all_items'     => __( 'All service categories', 'digicyfr-service-menu' ),
					'edit_item'     => __( 'Edit service category', 'digicyfr-service-menu' ),
					'add_new_item'  => __( 'Add service category', 'digicyfr-service-menu' ),
				),
				'hierarchical'       => true,
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_admin_column'  => true,
				'show_in_rest'       => false,
				'rewrite'            => false,
			)
		);
		foreach ( array( self::DURATION, self::PRICE ) as $key ) {
			register_post_meta(
				self::POST_TYPE,
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => false,
					'sanitize_callback' => self::DURATION === $key ? array( __CLASS__, 'sanitize_duration' ) : array( __CLASS__, 'sanitize_price' ),
					'auth_callback'     => array( __CLASS__, 'can_edit_meta' ),
				)
			);
		}
		wp_register_script(
			'digicyfr-service-menu-editor',
			DIGICYFR_SERVICE_MENU_URL . 'blocks/service-menu/editor.js',
			array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element' ),
			DIGICYFR_SERVICE_MENU_VERSION,
			true
		);
		wp_localize_script(
			'digicyfr-service-menu-editor',
			'digicyfrServiceMenuLabels',
			array(
				'title'        => __( 'Service Menu', 'digicyfr-service-menu' ),
				'description'  => __( 'Display published services, optionally filtered by a category slug.', 'digicyfr-service-menu' ),
				'settings'     => __( 'Menu settings', 'digicyfr-service-menu' ),
				'category'     => __( 'Category slug', 'digicyfr-service-menu' ),
				'categoryHelp' => __( 'Leave empty for all published services. An unknown slug shows an empty menu.', 'digicyfr-service-menu' ),
				'preview'      => __( 'The published menu appears on the page. Drafts remain private.', 'digicyfr-service-menu' ),
			)
		);
		wp_register_style( 'digicyfr-service-menu-style', DIGICYFR_SERVICE_MENU_URL . 'assets/service-menu.css', array(), DIGICYFR_SERVICE_MENU_VERSION );
		register_block_type( DIGICYFR_SERVICE_MENU_PATH . 'blocks/service-menu', array( 'render_callback' => array( __CLASS__, 'render_block' ) ) );
		add_shortcode( 'digicyfr_service_menu', array( __CLASS__, 'shortcode' ) );
	}

	/** Enqueue the small shared public style. */
	public static function enqueue_style(): void {
		wp_enqueue_style( 'digicyfr-service-menu-style' );
	}

	/**
	 * Object-aware post-meta permission.
	 *
	 * @param bool   $allowed Default permission.
	 * @param string $meta_key Meta key.
	 * @param int    $post_id Service ID.
	 * @param int    $user_id User ID.
	 * @return bool Whether the user may edit this service.
	 */
	public static function can_edit_meta( $allowed, $meta_key, $post_id, $user_id ): bool {
		return self::POST_TYPE === get_post_type( $post_id ) && user_can( $user_id, 'edit_post', $post_id );
	}

	/**
	 * Sanitize a short merchant-written duration label.
	 *
	 * @param mixed $value Raw label.
	 * @return string Plain bounded label.
	 */
	public static function sanitize_duration( $value ): string {
		return self::bounded_text( $value, 120 );
	}

	/**
	 * Sanitize a merchant-written price label without numerical assumptions.
	 *
	 * @param mixed $value Raw label.
	 * @return string Plain bounded label.
	 */
	public static function sanitize_price( $value ): string {
		return self::bounded_text( $value, 200 );
	}

	/**
	 * Strip HTML and bound a single-line merchant label.
	 *
	 * @param mixed $value Raw label.
	 * @param int   $limit Maximum characters.
	 * @return string Sanitized label.
	 */
	private static function bounded_text( $value, int $limit ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}
		$text = sanitize_text_field( $value );
		return preg_match( '/^.{0,' . $limit . '}/us', $text, $matches ) ? $matches[0] : '';
	}

	/**
	 * Enforce plain title/excerpt and safe order before WordPress stores the post.
	 *
	 * @param array $data Slashed processed post data.
	 * @param array $postarr Slashed sanitized post data.
	 * @param array $unsanitized Slashed original post data.
	 * @param bool  $update Whether this updates a post.
	 * @return array Safe slashed post data.
	 */
	public static function filter_post_data( array $data, array $postarr, array $unsanitized, bool $update ): array {
		if ( self::POST_TYPE !== ( $data['post_type'] ?? '' ) ) {
			return $data;
		}
		self::$name_rejected  = false;
		self::$order_rejected = false;
		$data['post_title']   = wp_slash( sanitize_text_field( wp_unslash( (string) ( $data['post_title'] ?? '' ) ) ) );
		$data['post_excerpt'] = wp_slash( sanitize_textarea_field( wp_unslash( (string) ( $data['post_excerpt'] ?? '' ) ) ) );
		$raw_order            = $unsanitized['menu_order'] ?? $data['menu_order'] ?? 0;
		$valid_order          = filter_var( $raw_order, FILTER_VALIDATE_INT );
		if ( false === $valid_order || $valid_order < 0 || $valid_order > 9999 ) {
			$previous             = $update && ! empty( $postarr['ID'] ) ? get_post( (int) $postarr['ID'] ) : null;
			$data['menu_order']   = $previous instanceof WP_Post ? (int) $previous->menu_order : 0;
			self::$order_rejected = true;
		} else {
			$data['menu_order'] = $valid_order;
		}
		if ( 'publish' === ( $data['post_status'] ?? '' ) ) {
			if ( '' === trim( wp_unslash( $data['post_title'] ) ) ) {
				$data['post_status'] = 'draft';
				self::$name_rejected = true;
			} elseif ( ! current_user_can( 'publish_posts' ) ) {
				$data['post_status'] = 'draft';
			}
		}
		return $data;
	}

	/**
	 * Put explicit validation notices on the normal editor redirect.
	 *
	 * @param string $location Redirect URL.
	 * @return string URL with safe notice flags.
	 */
	public static function append_save_notice( string $location ): string {
		if ( self::$name_rejected ) {
			$location = add_query_arg( 'digicyfr_name_required', '1', $location );
		}
		if ( self::$order_rejected ) {
			$location = add_query_arg( 'digicyfr_order_invalid', '1', $location );
		}
		return $location;
	}

	/** Show targeted service editor and list notices. */
	public static function admin_notices(): void {
		$screen = get_current_screen();
		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only status notice after a core editor redirect.
		if ( isset( $_GET['digicyfr_name_required'] ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'A service needs a name before it can be published. It was saved as a draft.', 'digicyfr-service-menu' ) . '</p></div>';
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only status notice after a core editor redirect.
		if ( isset( $_GET['digicyfr_order_invalid'] ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Order must be a whole number from 0 to 9999. The previous order was kept.', 'digicyfr-service-menu' ) . '</p></div>';
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only status notice after a confirmed archive.
		if ( isset( $_GET['digicyfr_archived'] ) ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Service archived as a draft.', 'digicyfr-service-menu' ) . '</p></div>';
		}
		if ( 'edit' === $screen->base && (int) ( wp_count_posts( self::POST_TYPE )->publish ?? 0 ) > self::MAX_PUBLIC ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'More than 100 services are published. Each embedded menu shows its first 100 matches; use categories or drafts to keep a complete menu.', 'digicyfr-service-menu' ) . '</p></div>';
		}
	}

	/** Register native editor fields for merchant-written text. */
	public static function register_meta_box(): void {
		add_meta_box( 'digicyfr-service-menu-details', __( 'Service details', 'digicyfr-service-menu' ), array( __CLASS__, 'meta_box' ), self::POST_TYPE, 'normal', 'default' );
	}

	/**
	 * Render the two optional text fields and nonce.
	 *
	 * @param WP_Post $post Current service.
	 */
	public static function meta_box( WP_Post $post ): void {
		wp_nonce_field( 'digicyfr_service_menu_save_' . $post->ID, 'digicyfr_service_menu_nonce' );
		$duration = (string) get_post_meta( $post->ID, self::DURATION, true );
		$price    = (string) get_post_meta( $post->ID, self::PRICE, true );
		?>
		<p><label for="digicyfr-service-duration"><strong><?php echo esc_html__( 'Duration (text)', 'digicyfr-service-menu' ); ?></strong></label><br />
		<input id="digicyfr-service-duration" name="digicyfr_service_duration" type="text" class="widefat" maxlength="120" value="<?php echo esc_attr( $duration ); ?>" /></p>
		<p><label for="digicyfr-service-price"><strong><?php echo esc_html__( 'Price (text)', 'digicyfr-service-menu' ); ?></strong></label><br />
		<input id="digicyfr-service-price" name="digicyfr_service_price" type="text" class="widefat" maxlength="200" value="<?php echo esc_attr( $price ); ?>" /></p>
		<p class="description"><?php echo esc_html__( 'Use the Excerpt box for a plain-text description, Categories for grouping, and the native Order field for a number from 0 to 9999. Price is your own wording; no currency, tax or checkout is calculated.', 'digicyfr-service-menu' ); ?></p>
		<?php
	}

	/**
	 * Save only after the service-specific nonce and mapped edit capability pass.
	 *
	 * @param int     $post_id Service ID.
	 * @param WP_Post $post Service post.
	 */
	public static function save_meta( int $post_id, WP_Post $post ): void {
		if ( self::POST_TYPE !== $post->post_type || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		$nonce = isset( $_POST['digicyfr_service_menu_nonce'] ) && is_string( $_POST['digicyfr_service_menu_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['digicyfr_service_menu_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'digicyfr_service_menu_save_' . $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		foreach ( array(
			'digicyfr_service_duration' => self::DURATION,
			'digicyfr_service_price'    => self::PRICE,
		) as $field => $key ) {
			if ( isset( $_POST[ $field ] ) && is_string( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $key, wp_slash( sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) ) );
			}
		}
	}

	/**
	 * Add the native list's visible order column.
	 *
	 * @param array $columns Existing columns.
	 * @return array Columns with order.
	 */
	public static function list_columns( array $columns ): array {
		$columns['digicyfr_service_order'] = __( 'Order', 'digicyfr-service-menu' );
		return $columns;
	}

	/**
	 * Render one list-table column.
	 *
	 * @param string $column Column key.
	 * @param int    $post_id Service ID.
	 */
	public static function list_column_value( string $column, int $post_id ): void {
		if ( 'digicyfr_service_order' === $column ) {
			echo esc_html( (string) (int) get_post( $post_id )->menu_order );
		}
	}

	/**
	 * Add an Archive link only when a published service can be edited and published.
	 *
	 * @param array   $actions Existing row actions.
	 * @param WP_Post $post Row post.
	 * @return array Row actions.
	 */
	public static function row_actions( array $actions, WP_Post $post ): array {
		if ( self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status || ! current_user_can( 'edit_post', $post->ID ) || ! current_user_can( 'publish_posts' ) ) {
			return $actions;
		}
		$url                         = add_query_arg(
			array(
				'page' => 'digicyfr-service-menu-archive',
				'post' => $post->ID,
			),
			admin_url( 'admin.php' )
		);
		$actions['digicyfr_archive'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Archive', 'digicyfr-service-menu' ) . '</a>';
		return $actions;
	}

	/** Register an archive confirmation and administrator-only export page. */
	public static function admin_menu(): void {
		add_submenu_page( null, __( 'Archive service', 'digicyfr-service-menu' ), __( 'Archive service', 'digicyfr-service-menu' ), 'edit_posts', 'digicyfr-service-menu-archive', array( __CLASS__, 'archive_confirmation' ) );
		add_submenu_page( 'edit.php?post_type=' . self::POST_TYPE, __( 'Export services', 'digicyfr-service-menu' ), __( 'Export CSV', 'digicyfr-service-menu' ), 'manage_options', 'digicyfr-service-menu-export', array( __CLASS__, 'export_page' ) );
	}

	/** Render a keyboard-usable confirmation before a publish-to-draft action. */
	public static function archive_confirmation(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Confirmation page is read-only; the mutation verifies its own nonce.
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		$post    = get_post( $post_id );
		if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status || ! current_user_can( 'edit_post', $post_id ) || ! current_user_can( 'publish_posts' ) ) {
			wp_die( esc_html__( 'You cannot archive this service.', 'digicyfr-service-menu' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Archive service?', 'digicyfr-service-menu' ); ?></h1>
			<p><?php echo esc_html__( 'Archiving changes this published service to a draft. You can publish it again later.', 'digicyfr-service-menu' ); ?></p>
			<p><strong><?php echo esc_html( get_the_title( $post ) ); ?></strong></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="digicyfr_service_menu_archive" />
				<input type="hidden" name="post" value="<?php echo esc_attr( (string) $post_id ); ?>" />
				<?php wp_nonce_field( 'digicyfr_service_archive_' . $post_id ); ?>
				<?php submit_button( __( 'Archive service', 'digicyfr-service-menu' ) ); ?>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . self::POST_TYPE ) ); ?>"><?php echo esc_html__( 'Cancel', 'digicyfr-service-menu' ); ?></a>
			</form>
		</div>
		<?php
	}

	/** Handle the confirmed archive action. */
	public static function archive_action(): void {
		$post_id = isset( $_POST['post'] ) ? absint( $_POST['post'] ) : 0;
		check_admin_referer( 'digicyfr_service_archive_' . $post_id );
		if ( ! self::archive_post( $post_id ) ) {
			wp_die( esc_html__( 'You cannot archive this service.', 'digicyfr-service-menu' ) );
		}
		wp_safe_redirect( add_query_arg( 'digicyfr_archived', '1', admin_url( 'edit.php?post_type=' . self::POST_TYPE ) ) );
		exit;
	}

	/**
	 * Change only a published, authorized service to a draft.
	 *
	 * @param int $post_id Service ID.
	 * @return bool Whether it was archived.
	 */
	public static function archive_post( int $post_id ): bool {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status || ! current_user_can( 'edit_post', $post_id ) || ! current_user_can( 'publish_posts' ) ) {
			return false;
		}
		$result = wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			),
			true
		);
		return ! is_wp_error( $result ) && $post_id === $result;
	}

	/** Render the administrator export form. */
	public static function export_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot export services.', 'digicyfr-service-menu' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Export services', 'digicyfr-service-menu' ); ?></h1>
			<p><?php echo esc_html__( 'Download a UTF-8 CSV of published and draft services for human review. Every cell is quoted; formula-like merchant text is prefixed with an apostrophe. No customer data is included.', 'digicyfr-service-menu' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="digicyfr_service_menu_export" />
				<?php wp_nonce_field( 'digicyfr_service_menu_export' ); ?>
				<?php submit_button( __( 'Download CSV', 'digicyfr-service-menu' ) ); ?>
			</form>
		</div>
		<?php
	}

	/** Send a CSV download after administrator permission and nonce checks. */
	public static function export_action(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot export services.', 'digicyfr-service-menu' ) );
		}
		check_admin_referer( 'digicyfr_service_menu_export' );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="digicyfr-service-menu.csv"' );
		echo self::csv_contents(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Quoted CSV, not HTML; formula guarded in csv_row.
		exit;
	}

	/**
	 * Quote every CSV cell and guard spreadsheet formula prefixes in merchant text.
	 *
	 * @param array $cells Cell values.
	 * @param array $merchant_indices Zero-based merchant-controlled column positions.
	 * @return string One CRLF-terminated CSV row.
	 */
	public static function csv_row( array $cells, array $merchant_indices = array() ): string {
		$quoted = array();
		foreach ( $cells as $index => $value ) {
			$text = (string) $value;
			if ( in_array( $index, $merchant_indices, true ) && preg_match( '/^(?:[\x00-\x20]*[=+\-@]|[\t\r])/u', $text ) ) {
				$text = "'" . $text;
			}
			$quoted[] = '"' . str_replace( '"', '""', $text ) . '"';
		}
		return implode( ',', $quoted ) . "\r\n";
	}

	/**
	 * Build the administrator's complete published-and-draft review export.
	 *
	 * @return string UTF-8 CSV with fixed columns.
	 */
	public static function csv_contents(): string {
		$csv  = self::csv_row( array( 'id', 'status', 'menu_order', 'name', 'description', 'category_slugs', 'duration_text', 'price_text' ) );
		$page = 1;
		do {
			$query = new WP_Query(
				array(
					'post_type'           => self::POST_TYPE,
					'post_status'         => array( 'publish', 'draft' ),
					'posts_per_page'      => 100,
					'paged'               => $page,
					'orderby'             => array(
						'menu_order' => 'ASC',
						'title'      => 'ASC',
						'ID'         => 'ASC',
					),
					'ignore_sticky_posts' => true,
				)
			);
			foreach ( $query->posts as $post ) {
				$terms = get_the_terms( $post, self::TAXONOMY );
				$slugs = is_array( $terms ) ? array_map( static fn( WP_Term $term ): string => $term->slug, $terms ) : array();
				sort( $slugs, SORT_STRING );
				$csv .= self::csv_row(
					array(
						$post->ID,
						$post->post_status,
						$post->menu_order,
						get_post_field( 'post_title', $post->ID, 'raw' ),
						get_post_field( 'post_excerpt', $post->ID, 'raw' ),
						implode( ',', $slugs ),
						get_post_meta( $post->ID, self::DURATION, true ),
						get_post_meta( $post->ID, self::PRICE, true ),
					),
					array( 3, 4, 5, 6, 7 )
				);
			}
			++$page;
		} while ( $page <= $query->max_num_pages );
		return $csv;
	}

	/**
	 * Add a native category selector above the service list.
	 *
	 * @param string $post_type Current post type.
	 * @param string $which Position.
	 */
	public static function category_filter( string $post_type, string $which ): void {
		if ( self::POST_TYPE !== $post_type || 'top' !== $which ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list filter.
		$selected = isset( $_GET['digicyfr_service_cat'] ) && is_string( $_GET['digicyfr_service_cat'] ) ? sanitize_title( wp_unslash( $_GET['digicyfr_service_cat'] ) ) : '';
		wp_dropdown_categories(
			array(
				'taxonomy'        => self::TAXONOMY,
				'name'            => 'digicyfr_service_cat',
				'id'              => 'digicyfr-service-cat-filter',
				'value_field'     => 'slug',
				'selected'        => $selected,
				'show_option_all' => __( 'All service categories', 'digicyfr-service-menu' ),
				'hide_empty'      => false,
			)
		);
	}

	/**
	 * Filter only the main administrator service list by selected category.
	 *
	 * @param WP_Query $query Query to inspect.
	 */
	public static function filter_admin_list( WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() || self::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list filter.
		$slug = isset( $_GET['digicyfr_service_cat'] ) && is_string( $_GET['digicyfr_service_cat'] ) ? sanitize_title( wp_unslash( $_GET['digicyfr_service_cat'] ) ) : '';
		if ( '' !== $slug ) {
			$query->set(
				'tax_query',
				array(
					array(
						'taxonomy'         => self::TAXONOMY,
						'field'            => 'slug',
						'terms'            => $slug,
						'include_children' => false,
					),
				)
			);
		}
	}

	/**
	 * Keep the bounded SQL selection and visible order identical across the 100-item edge.
	 *
	 * @param string   $orderby WordPress order clause.
	 * @param WP_Query $query Current query.
	 * @return string Deterministic service order clause.
	 */
	public static function menu_orderby( string $orderby, WP_Query $query ): string {
		if ( ! $query->get( 'digicyfr_service_menu_order' ) ) {
			return $orderby;
		}
		global $wpdb;
		return $wpdb->posts . '.menu_order ASC, CAST(LOWER(' . $wpdb->posts . '.post_title) AS BINARY) ASC, ' . $wpdb->posts . '.ID ASC';
	}

	/**
	 * Render a bounded, stable published menu for an optional existing category slug.
	 *
	 * @param string $category User-supplied category slug.
	 * @return string Escaped semantic HTML.
	 */
	public static function render( string $category = '' ): string {
		$category = trim( $category );
		if ( '' !== $category ) {
			$category = sanitize_title( $category );
			$term     = '' !== $category ? get_term_by( 'slug', $category, self::TAXONOMY ) : false;
			if ( ! $term instanceof WP_Term ) {
				return '<p class="digicyfr-service-menu__empty">' . esc_html__( 'No published services in this menu.', 'digicyfr-service-menu' ) . '</p>';
			}
		}
		$args = array(
			'post_type'           => self::POST_TYPE,
			'post_status'         => 'publish',
			'posts_per_page'      => self::MAX_PUBLIC + 1,
			'orderby'             => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
				'ID'         => 'ASC',
			),
			'ignore_sticky_posts' => true,
		);
		if ( '' !== $category ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Indexed, bounded term relation is the requested category filter.
			$args['tax_query'] = array(
				array(
					'taxonomy'         => self::TAXONOMY,
					'field'            => 'slug',
					'terms'            => $category,
					'include_children' => false,
				),
			);
		}
		$args['digicyfr_service_menu_order'] = true;
		add_filter( 'posts_orderby', array( __CLASS__, 'menu_orderby' ), 10, 2 );
		$query = new WP_Query( $args );
		remove_filter( 'posts_orderby', array( __CLASS__, 'menu_orderby' ), 10 );
		$posts = $query->posts;
		if ( array() === $posts ) {
			return '<p class="digicyfr-service-menu__empty">' . esc_html__( 'No published services in this menu.', 'digicyfr-service-menu' ) . '</p>';
		}
		$limited = count( $posts ) > self::MAX_PUBLIC;
		$posts   = array_slice( $posts, 0, self::MAX_PUBLIC );
		$html    = '<ul class="digicyfr-service-menu" role="list">';
		foreach ( $posts as $post ) {
			$name        = get_post_field( 'post_title', $post->ID, 'raw' );
			$description = get_post_field( 'post_excerpt', $post->ID, 'raw' );
			$duration    = get_post_meta( $post->ID, self::DURATION, true );
			$price       = get_post_meta( $post->ID, self::PRICE, true );
			$html       .= '<li class="digicyfr-service-menu__item"><span class="digicyfr-service-menu__name">' . esc_html( $name ) . '</span>';
			if ( '' !== $description ) {
				$html .= '<p class="digicyfr-service-menu__description">' . esc_html( $description ) . '</p>';
			}
			if ( '' !== $duration || '' !== $price ) {
				$html .= '<div class="digicyfr-service-menu__details">';
				if ( '' !== $duration ) {
					$html .= '<span class="digicyfr-service-menu__duration">' . esc_html__( 'Duration:', 'digicyfr-service-menu' ) . ' ' . esc_html( $duration ) . '</span>';
				}
				if ( '' !== $price ) {
					$html .= '<span class="digicyfr-service-menu__price">' . esc_html__( 'Price:', 'digicyfr-service-menu' ) . ' ' . esc_html( $price ) . '</span>';
				}
				$html .= '</div>';
			}
			$html .= '</li>';
		}
		$html .= '</ul>';
		if ( $limited ) {
			$html .= '<p class="digicyfr-service-menu__limit">' . esc_html__( 'Showing the first 100 services. More published services are available.', 'digicyfr-service-menu' ) . '</p>';
		}
		return $html;
	}

	/**
	 * Server-rendered block callback.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Menu HTML.
	 */
	public static function render_block( array $attributes = array() ): string {
		$category = $attributes['category'] ?? '';
		return self::render( is_string( $category ) ? $category : '' );
	}

	/**
	 * Shortcode callback with only a category slug argument.
	 *
	 * @param array|string $attributes Shortcode attributes.
	 * @return string Menu HTML.
	 */
	public static function shortcode( $attributes = array() ): string {
		$attributes = shortcode_atts( array( 'category' => '' ), is_array( $attributes ) ? $attributes : array(), 'digicyfr_service_menu' );
		return self::render( is_string( $attributes['category'] ) ? $attributes['category'] : '' );
	}
}
