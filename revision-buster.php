<?php
/**
 * Plugin Name: Revision Buster
 * Plugin URI: https://github.com/HILAYTRIVEDI/revision-buster
 * Description: A powerful plugin to clean up WordPress post and page revisions, with scheduling and custom cleanup options.
 * Version: 1.0.0
 * Author: Hilay Trivedi
 * Co-Author: Sabbir Ahmed
 * Author URI: https://github.com/HILAYTRIVEDI/
 * Text Domain: revision-buster
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if( ! defined('REVISION_BUSTER_VERSION') ):
    define( 'REVISION_BUSTER_VERSION', '1.0.0' );
endif;

if( !defined('REVISION_BUSTER_PLUGIN_DIR') ):
    define( 'REVISION_BUSTER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
endif;

if( !defined('REVISION_BUSTER_PLUGIN_URL') ):
    define( 'REVISION_BUSTER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
endif;

if ( !defined( 'REVISION_BUSTER__FILTER_SANITIZE_STRING' ) ) :
	define( 'REVISION_BUSTER__FILTER_SANITIZE_STRING', 'filter-sanitize-string' );
endif;


// Include custom functions.
if ( file_exists( REVISION_BUSTER_PLUGIN_DIR . 'helpers/custom-functions.php' ) ) {
    require_once REVISION_BUSTER_PLUGIN_DIR . 'helpers/custom-functions.php';
}

// Incude Assets class.
if ( file_exists( REVISION_BUSTER_PLUGIN_DIR . 'includes/class-assets.php' ) ) {
    require_once REVISION_BUSTER_PLUGIN_DIR . 'includes/class-assets.php';
}

// Include the main class.
if ( file_exists( REVISION_BUSTER_PLUGIN_DIR . 'includes/class-revision-buster.php' ) ) {
    require_once REVISION_BUSTER_PLUGIN_DIR . 'includes/class-revision-buster.php';
}

// Initialize the plugin.
function revision_buster_init() {
    new \RevisionBuster\Assets();

    $rb_class_instance = new \RevisionBuster\RemoveRevisions();
    $rb_class_instance->revision_buster_setup_hooks();
}

add_action( 'plugins_loaded', 'revision_buster_init' );
