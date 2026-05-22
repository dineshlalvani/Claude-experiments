<?php
/**
 * Plugin Name:       TranslatePress CSV Export
 * Plugin URI:        https://example.com/translatepress-csv-export
 * Description:       Export every TranslatePress string and translation to CSV, grouped by page and section (block type). Supports dictionary (regular strings), gettext (theme/plugin) strings, and post slugs/meta. Streams output so it works on large sites.
 * Version:           1.0.0
 * Requires at least: 5.6
 * Requires PHP:      7.2
 * Author:            Euryka
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tp-csv-export
 *
 * @package TPCE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TPCE_VERSION', '1.0.0' );
define( 'TPCE_PLUGIN_FILE', __FILE__ );
define( 'TPCE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TPCE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TPCE_CAPABILITY', 'manage_options' );

require_once TPCE_PLUGIN_DIR . 'includes/class-tpce-tp-data.php';
require_once TPCE_PLUGIN_DIR . 'includes/class-tpce-exporter.php';
require_once TPCE_PLUGIN_DIR . 'includes/class-tpce-admin.php';

add_action( 'plugins_loaded', static function () {
	load_plugin_textdomain( 'tp-csv-export', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	( new TPCE_Admin() )->register();
} );
