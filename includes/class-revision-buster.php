<?php
/**
 * Main class for the Revision Buster plugin.
 *
 * @package RevisionBuster
 */


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
    private $all_posts; // Store all posts and pages.

    /**
     * Constructor.
     * Fetches all posts and sets up hooks.
     */
    public function __construct() {
        $this->fetch_all_posts();
        $this->setup_hooks();
    }

    /**
     * Fetch all posts and pages using WP_Query with caching.
     *
     * @return void
     */
    private function fetch_all_posts(): void {

        // Get the cached posts of current site
        $cached_posts = get_transient( 'all_posts_cache' );
        
        if ( false === $cached_posts ) {
            $this->all_posts = [];
            $batch_size = 100;
            $page = 1;

            $query = new \WP_Query(
                [
                    'post_type'      => [ 'post', 'page' ],
                    'posts_per_page' => $batch_size,
                    'paged'          => $page,
                    'fields'         => 'ids',
                ]
            );

            while ( $query->have_posts() ) {
                $this->all_posts = array_merge( $this->all_posts, $query->posts );

                $page++;

                $query = new \WP_Query(
                    [
                        'post_type'      => [ 'post', 'page' ],
                        'posts_per_page' => $batch_size,
                        'paged'          => $page,
                        'fields'         => 'ids',
                    ]
                );
            }

            // Cache the result for 12 hours.
            set_transient( 'all_posts_cache' , $this->all_posts, 12 * HOUR_IN_SECONDS );
        } else {
            $this->all_posts = $cached_posts;
        }
    }

    /**
     * Invalidate cache when any post or page is updated, created, or deleted.
     *
     * @return void
     */
    public function invalidate_cache_on_post_update(): void {
        delete_transient( 'all_posts_cache' );
    }

    /**
     * Setup hooks for admin menu and cron event.
     *
     * @return void
     */
    public function setup_hooks(): void {
        add_action( 'admin_menu', [ $this, 'revision_cleanup_admin_menu' ] );
        add_action( 'run_revision_cleanup_cron', [ $this, 'run_revision_cleanup' ] );

        // Invalidate cache on post save or delete.
        add_action( 'save_post', [ $this, 'invalidate_cache_on_post_update' ] );
        add_action( 'delete_post', [ $this, 'invalidate_cache_on_post_update' ] );

        // Add monthly and yearly schedule
        add_filter( 'cron_schedules', [ $this, 'rb_add_cron_interval' ] );
    }

    /**
     * Add monthly and yearly schedule
     * 
     * @param array $schedules
     * @return array
     */
    public function rb_add_cron_interval( $schedules ){
        $schedules['monthly'] = array(
            'interval' => 30 * DAY_IN_SECONDS,
            'display'  => esc_html__( 'Every Month' , 'revision-buster' ), 
        );
        $schedules['yearly'] = array(
            'interval' => 365 * DAY_IN_SECONDS,
            'display'  => esc_html__( 'Every Year', 'revision-buster' ),
        );

        return $schedules;
    }

    /**
     * Register the admin menu and page for revision cleanup.
     *
     * @return void
     */
    public function revision_cleanup_admin_menu(): void {
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
        $selected_pages    = get_option( 'revision_cleanup_pages', [] ) ?: [];
        $revisions_to_keep = get_option( 'revision_cleanup_revisions_to_keep', 10 );
        $cleanup_interval  = get_option( 'revision_cleanup_interval', 'monthly' );

        $revision_cleanup_submit = rb_filter_input( INPUT_POST, 'revision_cleanup_submit', RB_FILTER_SANITIZE_STRING );
        $delete_all_revisions    = rb_filter_input( INPUT_POST, 'delete_all_revisions', RB_FILTER_SANITIZE_STRING );
        $delete_single_revision  = rb_filter_input( INPUT_POST, 'delete_single_revision', RB_FILTER_SANITIZE_STRING );

        // Handle form submission.
        if ( ! empty( $revision_cleanup_submit ) && check_admin_referer( 'revision_cleanup_nonce' ) ) {
            // Sanitize input fields.
            $selected_pages    = rb_filter_input( INPUT_POST, 'selected_pages', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY ) ?: [];
            $revisions_to_keep = rb_filter_input( INPUT_POST, 'revisions_to_keep', FILTER_VALIDATE_INT );
            $cleanup_interval  = rb_filter_input( INPUT_POST, 'cleanup_interval', RB_FILTER_SANITIZE_STRING );

            update_option( 'revision_cleanup_pages', $selected_pages );
            update_option( 'revision_cleanup_revisions_to_keep', absint($revisions_to_keep) );
            update_option( 'revision_cleanup_interval', $cleanup_interval );

            $this->clear_scheduled_revision_cleanup();
            wp_schedule_event( time(), $cleanup_interval, 'run_revision_cleanup_cron' );

            echo '<div class="updated"><p>' . esc_html__( 'Settings saved!', 'revision-buster' ) . '</p></div>';
        }

        // Handle delete all revisions request.
        if ( ! empty( $delete_all_revisions ) && check_admin_referer( 'revision_cleanup_nonce' ) ) {
            $this->delete_all_revisions();
            echo '<div class="updated"><p>' . esc_html__( 'All revisions deleted!', 'revision-buster' ) . '</p></div>';
        }

        // Handle delete single revisions request.
        if ( ! empty( $delete_single_revision ) && check_admin_referer( 'revision_cleanup_nonce' ) ) {
            $single_post_id = filter_input( INPUT_POST, 'single_post_id', FILTER_VALIDATE_INT );

            if ( $single_post_id ) {
                $this->delete_single_post_revisions( $single_post_id );
                echo '<div class="updated"><p>' . esc_html__( 'Revisions for the selected post/page deleted!', 'revision-buster' ) . '</p></div>';
            }
        }

        // Render admin page.
        $this->render_admin_page( $selected_pages, $revisions_to_keep, $cleanup_interval );
    }

    /**
     * Renders the admin settings page.
     *
     * @param array  $selected_pages An array of post IDs.
     * @param int    $revisions_to_keep The number of revisions to keep.
     * @param string $cleanup_interval The cleanup interval.
     *
     * @return void
     */
    private function render_admin_page( array $selected_pages, int $revisions_to_keep, string $cleanup_interval ): void {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Revision Cleanup Settings', 'revision-buster' ); ?></h1>
        <form method="POST" action="">
            <?php wp_nonce_field( 'revision_cleanup_nonce' ); ?>

            <!-- Single post select at top -->
            <h2><?php esc_html_e( 'Delete Revisions for a Single Post/Page', 'revision-buster' ); ?></h2>
            <select name="single_post_id" style="width: 100%;">
                <option value=""><?php esc_html_e( 'Select a Post/Page', 'revision-buster' ); ?></option>
                <?php foreach ( $this->all_posts as $single_post ) { ?>
                    <option value="<?php echo esc_attr( $single_post ); ?>">
                        <?php echo esc_html( get_the_title( $single_post ) ); ?>
                    </option>
                <?php } ?>
            </select>
            <p>
                <input type="submit" name="delete_single_revision" class="button button-secondary" value="<?php esc_attr_e( 'Delete Revisions for Selected Post/Page', 'revision-buster' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete revisions for this post/page?', 'revision-buster' ); ?>');">
            </p>

            <h2><?php esc_html_e( 'Number of Revisions to Keep', 'revision-buster' ); ?></h2>
            <input type="number" name="revisions_to_keep" value="<?php echo esc_attr( $revisions_to_keep ); ?>" min="0">

            <h2><?php esc_html_e( 'Cleanup Interval', 'revision-buster' ); ?></h2>
            <p><?php esc_html_e( 'Choose the frequency of the cleanup.', 'revision-buster' ); ?></p>
            <select name="cleanup_interval">
                <option value="hourly" <?php selected( $cleanup_interval, 'hourly' ); ?>><?php esc_html_e( 'Hourly', 'revision-buster' ); ?></option>
                <option value="daily" <?php selected( $cleanup_interval, 'daily' ); ?>><?php esc_html_e( 'Daily', 'revision-buster' ); ?></option>
                <option value="weekly" <?php selected( $cleanup_interval, 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'revision-buster' ); ?></option>
                <option value="monthly" <?php selected( $cleanup_interval, 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'revision-buster' ); ?></option>
                <option value="yearly" <?php selected( $cleanup_interval, 'yearly' ); ?>><?php esc_html_e( 'Yearly', 'revision-buster' ); ?></option>
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
        global $wpdb;

        $wpdb->query(
            "
            DELETE FROM $wpdb->posts
            WHERE post_type = 'revision'
            "
        );
    }

    /**
     * Deletes all revisions for a single post or page.
     *
     * @param int $post_id The ID of the post or page.
     *
     * @return void
     */
    public function delete_single_post_revisions( int $post_id ): void {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "
                DELETE FROM $wpdb->posts
                WHERE post_type = 'revision'
                AND post_parent = %d
                ",
                $post_id
            )
        );
    }

    /**
     * Clears any scheduled revision cleanup events.
     *
     * @return void
     */
    public function clear_scheduled_revision_cleanup(): void {
        $timestamp = wp_next_scheduled( 'run_revision_cleanup_cron' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'run_revision_cleanup_cron' );
        }
    }

    /**
     * Runs the revision cleanup process based on settings.
     *
     * @return void
     */
    public function run_revision_cleanup(): void {
        $selected_pages    = get_option( 'revision_cleanup_pages', [] );
        $revisions_to_keep = get_option( 'revision_cleanup_revisions_to_keep', 10 );

        foreach ( $selected_pages as $post_id ) {
            $this->delete_revisions_for_post( $post_id, $revisions_to_keep );
        }
    }

    /**
     * Deletes revisions for a single post, keeping the specified number of revisions.
     *
     * @param int $post_id The ID of the post.
     * @param int $revisions_to_keep The number of revisions to keep.
     *
     * @return void
     */
    private function delete_revisions_for_post( int $post_id, int $revisions_to_keep ): void {
        global $wpdb;

        $revisions = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT ID FROM $wpdb->posts
                WHERE post_type = 'revision'
                AND post_parent = %d
                ORDER BY post_date DESC
                ",
                $post_id
            ),
            ARRAY_A
        );

        if ( count( $revisions ) > $revisions_to_keep ) {
            $revisions_to_delete = array_slice( $revisions, $revisions_to_keep );

            foreach ( $revisions_to_delete as $revision ) {
                wp_delete_post( $revision['ID'], true );
            }
        }
    }
}

