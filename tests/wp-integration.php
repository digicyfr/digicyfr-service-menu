<?php
/**
 * Exercise the exact Service Menu ZIP after installation.
 *
 * @package Digicyfr_Service_Menu
 */

declare( strict_types=1 );

$port                   = getenv( 'DIGICYFR_TEST_PORT' );
$_SERVER['HTTP_HOST']   = '127.0.0.1:' . $port;
$_SERVER['SERVER_NAME'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SERVER_PORT'] = $port;
$_SERVER['HTTPS']       = 'off';

require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/template.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
require_once ABSPATH . 'wp-admin/includes/screen.php';

/**
 * Fail the fixture with a message on STDERR when a condition does not hold.
 *
 * @param bool   $condition Condition that must be true.
 * @param string $message Failure description.
 */
function check( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: $message\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- STDERR diagnostic in a CLI test harness, not a WordPress filesystem path.
		exit( 1 );
	}
}

/**
 * Insert one fixture service post.
 *
 * @param string $title Service title.
 * @param int    $order Menu order.
 * @param string $status Post status.
 * @param string $description Plain-text excerpt.
 * @return int Inserted post ID.
 */
function service( string $title, int $order, string $status = 'publish', string $description = '' ): int {
	$id = wp_insert_post(
		array(
			'post_type'    => Digicyfr_Service_Menu::POST_TYPE,
			'post_title'   => $title,
			'post_excerpt' => $description,
			'post_status'  => $status,
			'menu_order'   => $order,
		),
		true
	);
	check( is_int( $id ) && $id > 0, 'service inserted: ' . $title );
	return $id;
}

/**
 * Find a needle's position in rendered HTML, failing the fixture when absent.
 *
 * @param string $html Rendered menu HTML.
 * @param string $needle Expected substring.
 * @return int Position of the needle.
 */
function menu_pos( string $html, string $needle ): int {
	$position = strpos( $html, $needle );
	check( false !== $position, 'render includes ' . $needle );
	return $position;
}

$plugin_file = 'digicyfr-service-menu/digicyfr-service-menu.php';
check( is_plugin_active( $plugin_file ), 'exact ZIP active' );
$service_post_type = get_post_type_object( Digicyfr_Service_Menu::POST_TYPE );
check( $service_post_type instanceof WP_Post_Type, 'post type registered' );
check( ! $service_post_type->public && ! $service_post_type->publicly_queryable && $service_post_type->show_ui && ! $service_post_type->show_in_rest && $service_post_type->exclude_from_search && ! $service_post_type->has_archive && 'post' === $service_post_type->capability_type && $service_post_type->map_meta_cap, 'private CPT contract' );
check( array( 'title', 'excerpt', 'author', 'page-attributes' ) === array_values( get_all_post_type_supports( Digicyfr_Service_Menu::POST_TYPE ) ? array_keys( get_all_post_type_supports( Digicyfr_Service_Menu::POST_TYPE ) ) : array() ), 'CPT native fields' );
$taxonomy_object = get_taxonomy( Digicyfr_Service_Menu::TAXONOMY );
check( $taxonomy_object instanceof WP_Taxonomy && $taxonomy_object->hierarchical && $taxonomy_object->show_ui && ! $taxonomy_object->publicly_queryable && ! $taxonomy_object->show_in_rest, 'private hierarchical taxonomy' );
check( shortcode_exists( 'digicyfr_service_menu' ), 'shortcode registered' );
$block = WP_Block_Type_Registry::get_instance()->get_registered( 'digicyfr/service-menu' );
check( $block instanceof WP_Block_Type && in_array( 'digicyfr-service-menu-editor', $block->editor_script_handles, true ), 'dynamic block registered' );
$meta = get_registered_meta_keys( 'post', Digicyfr_Service_Menu::POST_TYPE );
check( isset( $meta['_digicyfr_service_duration'], $meta['_digicyfr_service_price'] ), 'two post meta keys registered' );
check( false === $meta['_digicyfr_service_price']['show_in_rest'], 'post meta private from REST' );

$admin = get_user_by( 'login', 'digicyfr_test_admin' );
check( $admin instanceof WP_User, 'test admin exists' );
wp_set_current_user( $admin->ID );
$term_a = wp_insert_term( 'Konsultacje', Digicyfr_Service_Menu::TAXONOMY, array( 'slug' => 'konsultacje' ) );
$term_b = wp_insert_term( 'Biznes', Digicyfr_Service_Menu::TAXONOMY, array( 'slug' => 'biznes' ) );
check( ! is_wp_error( $term_a ) && ! is_wp_error( $term_b ), 'categories created' );
$consultation = service( 'Konsultacja', 20, 'publish', "Pierwszy akapit\nDrugi akapit" );
$audit        = service( 'Audyt', 10 );
wp_set_object_terms( $consultation, array( $term_a['term_id'], $term_b['term_id'] ), Digicyfr_Service_Menu::TAXONOMY );
$_POST = array(
	'digicyfr_service_menu_nonce' => wp_create_nonce( 'digicyfr_service_menu_save_' . $consultation ),
	'digicyfr_service_duration'   => '45 min',
	'digicyfr_service_price'      => wp_slash( 'od 100 zł C:\\Usługa' ),
);
Digicyfr_Service_Menu::save_meta( $consultation, get_post( $consultation ) );
$_POST = array();
check( '45 min' === get_post_meta( $consultation, '_digicyfr_service_duration', true ), 'duration saved through nonce path' );
check( str_repeat( 'a', 119 ) . 'ą' === Digicyfr_Service_Menu::sanitize_duration( str_repeat( 'a', 119 ) . 'ąb' ), 'duration bound keeps UTF-8 codepoint intact' );
check( str_repeat( 'a', 199 ) . 'ż' === Digicyfr_Service_Menu::sanitize_price( str_repeat( 'a', 199 ) . 'żb' ), 'price bound keeps UTF-8 codepoint intact' );
check( 'od 100 zł C:\\Usługa' === get_post_meta( $consultation, '_digicyfr_service_price', true ), 'price saved through nonce path' );
$menu_html = Digicyfr_Service_Menu::render();
check( menu_pos( $menu_html, 'Audyt' ) < menu_pos( $menu_html, 'Konsultacja' ), 'order ascending' );
check( 1 === substr_count( $menu_html, 'Konsultacja' ), 'multi-category service appears once' );
check( str_contains( $menu_html, "Pierwszy akapit\nDrugi akapit" ), 'multiline plain description preserved' );
check( str_contains( $menu_html, '45 min' ) && str_contains( $menu_html, 'od 100 zł C:\\Usługa' ), 'merchant-written detail labels render' );
check( str_contains( $menu_html, 'role="list"' ), 'semantic list role retained without CSS markers' );
check( 1 === substr_count( $menu_html, 'Duration:' ) && 1 === substr_count( $menu_html, 'Price:' ), 'empty details omit labels/wrappers' );
check( Digicyfr_Service_Menu::render( 'konsultacje' ) === Digicyfr_Service_Menu::render( 'biznes' ), 'either category shows consultation' );
check( ! str_contains( Digicyfr_Service_Menu::render( 'konsultacje' ), 'Audyt' ), 'category excludes unrelated service' );
check( ! str_contains( Digicyfr_Service_Menu::render( 'not-a-term' ), 'Konsultacja' ), 'unknown category empty' );
check( do_shortcode( '[digicyfr_service_menu]' ) === do_blocks( '<!-- wp:digicyfr/service-menu /-->' ), 'block and shortcode parity' );
check( do_shortcode( '[digicyfr_service_menu category="konsultacje"]' ) === do_blocks( '<!-- wp:digicyfr/service-menu {"category":"konsultacje"} /-->' ), 'filtered block and shortcode parity' );

$blank = service( '   ', 0 );
check( 'draft' === get_post_status( $blank ), 'blank name cannot publish' );
$bad_order = service( 'Bad order', -5 );
check( 0 === (int) get_post( $bad_order )->menu_order, 'negative order reset to zero' );
wp_update_post(
	array(
		'ID'         => $audit,
		'menu_order' => 10000,
	)
);
check( 10 === (int) get_post( $audit )->menu_order, 'out-of-range order keeps old value' );
$hostile = service( '<script>alert(1)</script>', 30, 'publish', "<script>alert(2)</script>\nOpis" );
update_post_meta( $hostile, '_digicyfr_service_duration', '<script>alert(3)</script>' );
update_post_meta( $hostile, '_digicyfr_service_price', '<img src=x onerror=alert(4)>' );
$menu_html = Digicyfr_Service_Menu::render();
check( ! str_contains( $menu_html, '<script' ) && ! str_contains( $menu_html, '<img' ), 'hostile title, description, duration and price HTML excluded' );
check( str_contains( $menu_html, 'Bad order' ), 'invalid order does not hide a valid service' );

$alpha     = service( 'alpha', 50 );
$beta      = service( 'Beta', 50 );
$beta2     = service( 'beta', 50 );
$menu_html = Digicyfr_Service_Menu::render();
check( menu_pos( $menu_html, 'alpha' ) < menu_pos( $menu_html, 'Beta' ), 'equal-order titles sorted case-insensitively' );
check( menu_pos( $menu_html, 'Beta' ) < menu_pos( $menu_html, 'beta' ), 'case-fold tie sorted by ID' );
wp_update_post(
	array(
		'ID'         => $consultation,
		'menu_order' => 0,
	)
);
check( menu_pos( Digicyfr_Service_Menu::render(), 'Konsultacja' ) < menu_pos( Digicyfr_Service_Menu::render(), 'Audyt' ), 'changed order reflected without page edit' );
check( Digicyfr_Service_Menu::archive_post( $consultation ), 'published service archived to draft' );
check( 'draft' === get_post_status( $consultation ), 'archive status is draft' );
check( ! str_contains( Digicyfr_Service_Menu::render(), 'Konsultacja' ), 'archived service absent from public menu' );
wp_update_post(
	array(
		'ID'          => $consultation,
		'post_status' => 'publish',
	)
);
check( str_contains( Digicyfr_Service_Menu::render(), 'Konsultacja' ), 'native Publish restores archived service' );
$draft = service( 'Private draft', 2, 'draft' );
$trash = service( 'Trashed service', 3 );
wp_trash_post( $trash );
wp_set_current_user( 0 );
$public = Digicyfr_Service_Menu::render();
check( ! str_contains( $public, 'Private draft' ) && ! str_contains( $public, 'Trashed service' ), 'public visitor cannot see draft or Trash' );
wp_set_current_user( $admin->ID );
wp_delete_term( $term_a['term_id'], Digicyfr_Service_Menu::TAXONOMY );
check( ! str_contains( Digicyfr_Service_Menu::render( 'konsultacje' ), 'Konsultacja' ), 'deleted category does not broaden filter' );

$formula = service( '=2+2', 80, 'publish', "First, second\nThird" );
update_post_meta( $formula, '_digicyfr_service_price', '+SUM(1,1)' );
$csv = Digicyfr_Service_Menu::csv_contents();
check( str_starts_with( $csv, '"id","status","menu_order","name","description","category_slugs","duration_text","price_text"' . "\r\n" ), 'exact quoted CSV header' );
check( str_contains( $csv, 'Konsultacja' ) && str_contains( $csv, 'od 100 zł' ), 'CSV preserves Polish UTF-8' );
check( str_contains( $csv, '"draft"' ) && str_contains( $csv, '"publish"' ), 'CSV includes draft and publish statuses' );
check( ! str_contains( $csv, 'Trashed service' ), 'CSV excludes Trash' );
check( str_contains( $csv, '"\'=2+2"' ) && str_contains( $csv, '"\'+SUM(1,1)"' ), 'CSV neutralizes saved formula text' );
$guarded = Digicyfr_Service_Menu::csv_row( array( '=x', '+x', '-x', '@x', "\tx", "\rx", '  =x', "a,b\nc", '"quoted"' ), range( 0, 8 ) );
check( str_contains( $guarded, '"\'=x"' ) && str_contains( $guarded, '"\'+x"' ) && str_contains( $guarded, '"\'-x"' ) && str_contains( $guarded, '"\'@x"' ), 'CSV formula prefixes guarded' );
check( str_contains( $guarded, "\"'\tx\"" ) && str_contains( $guarded, "\"'\rx\"" ) && str_contains( $guarded, '"\'  =x"' ), 'CSV leading control/whitespace guarded' );
check( str_contains( $guarded, '"a,b' . "\n" . 'c"' ) && str_contains( $guarded, '"""quoted"""' ), 'CSV commas, newlines and quotes encoded' );

$contributor_id = wp_create_user( 'digicyfr_service_contributor', 'test-only-password', 'service-contributor@example.invalid' );
check( ! is_wp_error( $contributor_id ), 'contributor created' );
( new WP_User( $contributor_id ) )->set_role( 'contributor' );
wp_set_current_user( $contributor_id );
check( ! current_user_can( 'publish_posts' ) && ! current_user_can( 'edit_post', $consultation ), 'contributor cannot publish or edit another owner service' );
check( ! Digicyfr_Service_Menu::archive_post( $consultation ), 'contributor cannot archive other owner service' );
$_POST = array(
	'digicyfr_service_menu_nonce' => wp_create_nonce( 'digicyfr_service_menu_save_' . $consultation ),
	'digicyfr_service_price'      => 'tampered',
);
Digicyfr_Service_Menu::save_meta( $consultation, get_post( $consultation ) );
$_POST = array();
check( 'od 100 zł C:\\Usługa' === get_post_meta( $consultation, '_digicyfr_service_price', true ), 'contributor cannot change another service meta' );
add_filter(
	'wp_die_handler',
	static fn(): callable => static function (): void {
	throw new RuntimeException( 'denied' );
	}
);
$denied = false;
try {
	Digicyfr_Service_Menu::export_page();
} catch ( RuntimeException $error ) {
	$denied = true; }
check( $denied, 'contributor denied export page' );

wp_set_current_user( $admin->ID );
check( load_textdomain( 'digicyfr-service-menu', WP_PLUGIN_DIR . '/digicyfr-service-menu/languages/digicyfr-service-menu-pl_PL.mo' ), 'Polish MO loaded' );
check( 'Lista usług' === __( 'Service Menu', 'digicyfr-service-menu' ), 'Polish admin translation works' );
check( 'Cena:' === __( 'Price:', 'digicyfr-service-menu' ), 'Polish public translation works' );

for ( $i = 0; $i < 101; ++$i ) {
	service( sprintf( 'Extra %03d', $i ), 1000 + $i );
}
$limited = Digicyfr_Service_Menu::render();
check( 100 === substr_count( $limited, '<li class="digicyfr-service-menu__item">' ), 'public menu capped at 100' );
check( str_contains( $limited, 'digicyfr-service-menu__limit' ) && str_contains( $limited, '100' ), 'public limit stated visibly' );
set_current_screen( 'edit-' . Digicyfr_Service_Menu::POST_TYPE );
ob_start();
Digicyfr_Service_Menu::admin_notices();
$notices = ob_get_clean();
check( str_contains( $notices, 'notice-warning' ) && str_contains( $notices, '100' ), 'administrator limit warning visible' );

// All 105 numbered titles share one order, with two Polish accented titles after them.
// The SQL selection and rendered order must agree at the 100-item boundary.
$prior = get_posts(
	array(
		'post_type'      => Digicyfr_Service_Menu::POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
	)
);
$ahead = count( array_filter( $prior, static fn( WP_Post $post ): bool => (int) $post->menu_order < 500 ) );
$slots = 100 - $ahead;
check( $slots > 1 && $slots < 105, 'boundary fixture has room for same-order titles' );
for ( $i = 0; $i < 105; ++$i ) {
	service( sprintf( 'Case %03d', $i ), 500 );
}
service( 'Ąćę', 500 );
service( 'Żółć', 500 );
$boundary = Digicyfr_Service_Menu::render();
check( 100 === substr_count( $boundary, '<li class="digicyfr-service-menu__item">' ), 'same-order boundary still caps at 100' );
check( str_contains( $boundary, sprintf( 'Case %03d', $slots - 1 ) ), 'last in-range case-folded title selected' );
check( ! str_contains( $boundary, sprintf( 'Case %03d', $slots ) ), 'first out-of-range case-folded title omitted' );
check( ! str_contains( $boundary, 'Ąćę' ) && ! str_contains( $boundary, 'Żółć' ), 'accented titles after ASCII cases omitted consistently' );

deactivate_plugins( $plugin_file );
check( get_post( $consultation ) instanceof WP_Post, 'deactivation retains service data' );
uninstall_plugin( $plugin_file );
check( get_post( $consultation ) instanceof WP_Post, 'uninstall retains service data by policy' );
echo "Service Menu WordPress integration tests passed\n";
