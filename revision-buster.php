<?php
/**
 * Plugin Name: Revision Buster
 * Plugin URI: https://github.com/HILAYTRIVEDI/
 * Description: A powerful plugin to clean up WordPress post and page revisions, with scheduling and custom cleanup options.
 * Version: 1.0.0
 * Author: Hilay Trivedi
 * Co-Author: Sabbir Ahmed
 * Author URI: https://example.com/hilay-trivedi
 * Text Domain: revision-buster
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if( !defined('REVISION_BUSTER_VERSION') ):
    define( 'REVISION_BUSTER_VERSION', '1.0.0' );
endif;

if( !defined('REVISION_BUSTER_PLUGIN_DIR') ):
    define( 'REVISION_BUSTER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
endif;

if( !defined('REVISION_BUSTER_PLUGIN_URL') ):
    define( 'REVISION_BUSTER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
endif;

if ( !defined( 'RB_FILTER_SANITIZE_STRING' ) ) :
	define( 'RB_FILTER_SANITIZE_STRING', 'filter-sanitize-string' );
endif;


// Include custom functions.
require_once REVISION_BUSTER_PLUGIN_DIR . 'helpers/custom-functions.php';

// Incude Assets class.
require_once REVISION_BUSTER_PLUGIN_DIR . 'includes/class-assets.php';

// Include the main class.
require_once REVISION_BUSTER_PLUGIN_DIR . 'includes/class-revision-buster.php';

// Initialize the plugin.
function revision_buster_init() {
    $rb_class_assets_instance = new Assets();

    $rb_class_instance = new RemoveRevisions();
    $rb_class_instance->setup_hooks();
}

add_action( 'plugins_loaded', 'revision_buster_init' );
