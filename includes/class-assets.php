<?php
/**
 * Class to handle all the assets
 */

namespace RevisionBuster;

class Assets {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_enqueue_scripts', [ $this, 'revision_buster_enqueue_assets' ] );
    }

    /**
     * Enqueue scripts and styles
     */
    public function revision_buster_enqueue_assets() {
        $screen = get_current_screen();

        if ( ! empty( $screen->id ) && 'toplevel_page_revision-cleanup' === $screen->id ) {
            wp_enqueue_script( 'rb-admin-script', REVISION_BUSTER_PLUGIN_URL . 'assets/js/assets.js', [ 'jquery' ], REVISION_BUSTER_VERSION, true );
            wp_enqueue_style( 'rb-admin-style', REVISION_BUSTER_PLUGIN_URL . 'assets/css/assets.css', [], REVISION_BUSTER_VERSION );
        }

    }

}