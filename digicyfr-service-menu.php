<?php
/**
 * Plugin Name: Digicyfr Service Menu
 * Description: Reusable service catalog with categories, text prices, and a simple public menu.
 * Version: 0.1.1
 * Requires at least: 7.1
 * Requires PHP: 8.3
 * Author: Digicyfr
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: digicyfr-service-menu
 * Domain Path: /languages
 * Update URI: https://digicyfr.com/plugins/digicyfr-service-menu/
 *
 * @package Digicyfr_Service_Menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DIGICYFR_SERVICE_MENU_VERSION', '0.1.1' );
define( 'DIGICYFR_SERVICE_MENU_PATH', plugin_dir_path( __FILE__ ) );
define( 'DIGICYFR_SERVICE_MENU_URL', plugin_dir_url( __FILE__ ) );

require_once DIGICYFR_SERVICE_MENU_PATH . 'includes/class-digicyfr-service-menu.php';
Digicyfr_Service_Menu::boot();
