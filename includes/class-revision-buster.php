<?php
/**
 * Main class for the Revision Buster plugin.
 *
 * @package RevisionBuster
 */

namespace RevisionBuster;

/**
 * Class RemoveRevisions
 * Provides functionality to clean up revisions for posts and pages.
 */
class RemoveRevisions {
    /**
     * Stores all posts and pages.
     *
     * @var array
     */
    private $revision_buster_revision_buster_reall_posts; // Store all posts and pages.

    /**
     * Constructor.
     * Fetches all posts and sets up hooks.
     */
    public function __construct() {
        $this->revision_buster_fetch_all_posts();
        $this->revision_buster_setup_hooks();
    }

    /**
     * Fetch all posts and pages using WP_Query with caching.
     *
     * @return void
     */
    private function revision_buster_fetch_all_posts(): void {

        // Get the cached posts of current site
        $revision_buster_revision_buster_cached_posts = get_transient( 'revision_buster_all_posts_cache' );
        
        if ( false === $revision_buster_revision_buster_cached_posts ) {
            $this->revision_buster_reall_posts = [];
            $revision_buster_batch_size = 100;
            $revision_buster_page = 1;

            $revision_buster_query = new \WP_Query(
                [
                    'post_type'      => [ 'post', 'page' ],
                    'posts_per_page' => $revision_buster_batch_size,
                    'paged'          => $revision_buster_page,
                    'fields'         => 'ids',
                ]
            );

            while ( $revision_buster_query->have_posts() ) {
                $this->revision_buster_reall_posts = array_merge( $this->revision_buster_reall_posts, $revision_buster_query->posts );

                $revision_buster_page++;

                $revision_buster_query = new \WP_Query(
                    [
                        'post_type'      => [ 'post', 'page' ],
                        'posts_per_page' => $revision_buster_batch_size,
                        'paged'          => $revision_buster_page,
                        'fields'         => 'ids',
                    ]
                );
            }

            // Cache the result for 12 hours.
            set_transient( 'revision_buster_all_posts_cache' , $this->revision_buster_reall_posts, 12 * HOUR_IN_SECONDS );
        } else {
            $this->revision_buster_reall_posts = $revision_buster_revision_buster_cached_posts;
        }
    }

    /**
     * Invalidate cache when any post or page is updated, created, or deleted.
     *
     * @return void
     */
    public function revision_buster_invalidate_cache_on_post_update(): void {
        delete_transient( 'revision_buster_all_posts_cache' );
    }

    /**
     * Setup hooks for admin menu and cron event.
     *
     * @return void
     */
    public function revision_buster_setup_hooks(): void {
        add_action( 'admin_menu', [ $this, 'revision_buster_revision_cleanup_admin_menu' ] );
        add_action( 'revision_buster_run_revision_cleanup_cron', [ $this, 'revision_buster_run_revision_cleanup' ] );

        // Invalidate cache on post save or delete.
        add_action( 'save_post', [ $this, 'revision_buster_invalidate_cache_on_post_update' ] );
        add_action( 'delete_post', [ $this, 'revision_buster_invalidate_cache_on_post_update' ] );

        // Add monthly and yearly schedule
        add_filter( 'cron_schedules', [ $this, 'revision_buster_add_cron_interval' ] );
    }

    /**
     * Add monthly and yearly schedule
     * 
     * @param array $revision_buster_schedules
     * @return array
     */
    public function revision_buster_add_cron_interval( $revision_buster_schedules ){
        $revision_buster_schedules['monthly'] = array(
            'interval' => 30 * DAY_IN_SECONDS,
            'display'  => esc_html__( 'Every Month' , 'revision-buster' ), 
        );
        $revision_buster_schedules['yearly'] = array(
            'interval' => 365 * DAY_IN_SECONDS,
            'display'  => esc_html__( 'Every Year', 'revision-buster' ),
        );

        return $revision_buster_schedules;
    }

    /**
     * Register the admin menu and page for revision cleanup.
     *
     * @return void
     */
    public function revision_buster_revision_cleanup_admin_menu(): void {
        add_menu_page(
            'Revision Cleanup',
            'Revision Cleanup',
            'manage_options',
            'revision-cleanup',
            [ $this, 'revision_cleanup_page' ],
            'dashicons-trash',
            80
        );
    }

    /**
     * Admin page content for revision cleanup settings.
     *
     * @return void
     */
    public function revision_cleanup_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Get stored options.
        $revision_buster_selected_pages    = get_option( 'revision_cleanup_pages', [] ) ?: [];
        $revision_buster_revisions_to_keep = get_option( 'revision_cleanup_revisions_to_keep', 10 );
        $revision_buster_cleanup_interval  = get_option( 'revision_cleanup_interval', 'monthly' );

        $revision_buster_revision_cleanup_submit = revision_buster_filter_input( INPUT_POST, 'revision_cleanup_submit', RB_FILTER_SANITIZE_STRING );
        $revision_buster_delete_all_revisions    = revision_buster_filter_input( INPUT_POST, 'delete_all_revisions', RB_FILTER_SANITIZE_STRING );
        $revision_buster_delete_single_revision  = revision_buster_filter_input( INPUT_POST, 'delete_single_revision', RB_FILTER_SANITIZE_STRING );

        // Handle form submission.
        if ( ! empty( $revision_buster_revision_cleanup_submit ) && check_admin_referer( 'revision_cleanup_nonce' ) ) {
            // Sanitize input fields.
            $revision_buster_selected_pages    = revision_buster_filter_input( INPUT_POST, 'selected_pages', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY ) ?: [];
            $revision_buster_revisions_to_keep = revision_buster_filter_input( INPUT_POST, 'revisions_to_keep', FILTER_VALIDATE_INT );
            $revision_buster_cleanup_interval  = revision_buster_filter_input( INPUT_POST, 'cleanup_interval', RB_FILTER_SANITIZE_STRING );

            update_option( 'revision_cleanup_pages', $revision_buster_selected_pages );
            update_option( 'revision_cleanup_revisions_to_keep', absint($revision_buster_revisions_to_keep) );
            update_option( 'revision_cleanup_interval', $revision_buster_cleanup_interval );

            $this->clear_scheduled_revision_cleanup();
            wp_schedule_event( time(), $revision_buster_cleanup_interval, 'rb_run_revision_cleanup_cron' );

            echo '<div class="updated"><p>' . esc_html__( 'Settings saved!', 'revision-buster' ) . '</p></div>';
        }

        // Handle delete all revisions request.
        if ( ! empty( $revision_buster_delete_all_revisions ) && check_admin_referer( 'revision_cleanup_nonce' ) ) {
            $this->delete_all_revisions();
            echo '<div class="updated"><p>' . esc_html__( 'All revisions deleted!', 'revision-buster' ) . '</p></div>';
        }

        // Handle delete single revisions request.
        if ( ! empty( $revision_buster_delete_single_revision ) && check_admin_referer( 'revision_cleanup_nonce' ) ) {
            $revision_buster_single_post_id = filter_input( INPUT_POST, 'single_post_id', FILTER_VALIDATE_INT );

            if ( $revision_buster_single_post_id ) {
                $this->delete_single_post_revisions( $revision_buster_single_post_id );
                echo '<div class="updated"><p>' . esc_html__( 'Revisions for the selected post/page deleted!', 'revision-buster' ) . '</p></div>';
            }
        }

        // Render admin page.
        $this->render_admin_page( $revision_buster_selected_pages, $revision_buster_revisions_to_keep, $revision_buster_cleanup_interval );
    }

    /**
     * Renders the admin settings page.
     *
     * @param array  $revision_buster_selected_pages An array of post IDs.
     * @param int    $revision_buster_revisions_to_keep The number of revisions to keep.
     * @param string $revision_buster_cleanup_interval The cleanup interval.
     *
     * @return void
     */
    private function render_admin_page( array $revision_buster_selected_pages, int $revision_buster_revisions_to_keep, string $revision_buster_cleanup_interval ): void {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Revision Cleanup Settings', 'revision-buster' ); ?></h1>
        <form method="POST" action="">
            <?php wp_nonce_field( 'revision_cleanup_nonce' ); ?>

            <!-- Single post select at top -->
            <h2><?php esc_html_e( 'Delete Revisions for a Single Post/Page', 'revision-buster' ); ?></h2>
            <select name="single_post_id" style="width: 100%;">
                <option value=""><?php esc_html_e( 'Select a Post/Page', 'revision-buster' ); ?></option>
                <?php foreach ( $this->revision_buster_reall_posts as $revision_buster_single_post ) { ?>
                    <option value="<?php echo esc_attr( $revision_buster_single_post ); ?>">
                        <?php echo esc_html( get_the_title( $revision_buster_single_post ) ); ?>
                    </option>
                <?php } ?>
            </select>
            <p>
                <input type="submit" name="delete_single_revision" class="button button-secondary" value="<?php esc_attr_e( 'Delete Revisions for Selected Post/Page', 'revision-buster' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete revisions for this post/page?', 'revision-buster' ); ?>');">
            </p>

            <h2><?php esc_html_e( 'Number of Revisions to Keep', 'revision-buster' ); ?></h2>
            <input type="number" name="revisions_to_keep" value="<?php echo esc_attr( $revision_buster_revisions_to_keep ); ?>" min="0">

            <h2><?php esc_html_e( 'Cleanup Interval', 'revision-buster' ); ?></h2>
            <p><?php esc_html_e( 'Choose the frequency of the cleanup.', 'revision-buster' ); ?></p>
            <select name="cleanup_interval">
                <option value="hourly" <?php selected( $revision_buster_cleanup_interval, 'hourly' ); ?>><?php esc_html_e( 'Hourly', 'revision-buster' ); ?></option>
                <option value="daily" <?php selected( $revision_buster_cleanup_interval, 'daily' ); ?>><?php esc_html_e( 'Daily', 'revision-buster' ); ?></option>
                <option value="weekly" <?php selected( $revision_buster_cleanup_interval, 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'revision-buster' ); ?></option>
                <option value="monthly" <?php selected( $revision_buster_cleanup_interval, 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'revision-buster' ); ?></option>
                <option value="yearly" <?php selected( $revision_buster_cleanup_interval, 'yearly' ); ?>><?php esc_html_e( 'Yearly', 'revision-buster' ); ?></option>
            </select>

            <p><input type="submit" name="revision_cleanup_submit" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'revision-buster' ); ?>"></p>
        </form>

        <form method="POST" action="">
            <?php wp_nonce_field( 'revision_cleanup_nonce' ); ?>
            <h2><?php esc_html_e( 'Delete All Revisions', 'revision-buster' ); ?></h2>
            <p>
                <input type="submit" name="delete_all_revisions" class="button button-secondary" value="<?php esc_attr_e( 'Delete All Revisions', 'revision-buster' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete all revisions?', 'revision-buster' ); ?>');">
            </p>
        </form>
    </div>
    <?php
}


    /**
     * Deletes all revisions for all posts and pages.
     *
     * @return void
     */
    public function delete_all_revisions(): void {
        global $revision_buster_wpdb;

        $revision_buster_wpdb->query(
            "
            DELETE FROM $revision_buster_wpdb->posts
            WHERE post_type = 'revision'
            "
        );
    }

    /**
     * Deletes all revisions for a single post or page.
     *
     * @param int $revision_buster_post_id The ID of the post or page.
     *
     * @return void
     */
    public function delete_single_post_revisions( int $revision_buster_post_id ): void {
        global $revision_buster_wpdb;

        $revision_buster_wpdb->query(
            $revision_buster_wpdb->prepare(
                "
                DELETE FROM $revision_buster_wpdb->posts
                WHERE post_type = 'revision'
                AND post_parent = %d
                ",
                $revision_buster_post_id
            )
        );
    }

    /**
     * Clears any scheduled revision cleanup events.
     *
     * @return void
     */
    public function clear_scheduled_revision_cleanup(): void {
        $revision_buster_timestamp = wp_next_scheduled( 'rb_run_revision_cleanup_cron' );
        if ( $revision_buster_timestamp ) {
            wp_unschedule_event( $revision_buster_timestamp, 'rb_run_revision_cleanup_cron' );
        }
    }

    /**
     * Runs the revision cleanup process based on settings.
     *
     * @return void
     */
    public function revision_buster_run_revision_cleanup(): void {
        $revision_buster_selected_pages    = get_option( 'revision_cleanup_pages', [] );
        $revision_buster_revisions_to_keep = get_option( 'revision_cleanup_revisions_to_keep', 10 );

        foreach ( $revision_buster_selected_pages as $revision_buster_post_id ) {
            $this->delete_revisions_for_post( $revision_buster_post_id, $revision_buster_revisions_to_keep );
        }
    }

    /**
     * Deletes revisions for a single post, keeping the specified number of revisions.
     *
     * @param int $revision_buster_post_id The ID of the post.
     * @param int $revision_buster_revisions_to_keep The number of revisions to keep.
     *
     * @return void
     */
    private function delete_revisions_for_post( int $revision_buster_post_id, int $revision_buster_revisions_to_keep ): void {
        global $revision_buster_wpdb;

        $revision_buster_revisions = $revision_buster_wpdb->get_results(
            $revision_buster_wpdb->prepare(
                "
                SELECT ID FROM $revision_buster_wpdb->posts
                WHERE post_type = 'revision'
                AND post_parent = %d
                ORDER BY post_date DESC
                ",
                $revision_buster_post_id
            ),
            ARRAY_A
        );

        if ( count( $revision_buster_revisions ) > $revision_buster_revisions_to_keep ) {
            $revision_buster_revisions_to_delete = array_slice( $revision_buster_revisions, $revision_buster_revisions_to_keep );

            foreach ( $revision_buster_revisions_to_delete as $revision_buster_revision ) {
                wp_delete_post( $revision_buster_revision['ID'], true );
            }
        }
    }
}

