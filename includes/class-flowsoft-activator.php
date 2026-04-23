<?php
/**
 * FlowSoft WP — Activator
 *
 * Runs on plugin activation: creates custom tables and sets default options.
 *
 * @package FlowSoft_WP
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FlowSoft_Activator {

    /**
     * Run activation routines.
     */
    public static function activate() {
        self::create_log_table();
        self::set_default_options();
        self::schedule_events();

        // Store activation timestamp
        update_option( 'flowsoft_activated_at', time(), false );
        update_option( 'flowsoft_version', FLOWSOFT_VERSION, false );
    }

    /**
     * Create the activity log table with composite indexes.
     */
    private static function create_log_table() {
        global $wpdb;

        $table_name      = $wpdb->prefix . 'flowsoft_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            module VARCHAR(50) NOT NULL DEFAULT '',
            action_type VARCHAR(100) NOT NULL DEFAULT '',
            message TEXT NOT NULL,
            items_affected INT(11) NOT NULL DEFAULT 0,
            bytes_freed BIGINT(20) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'success',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY module (module),
            KEY created_at (created_at),
            KEY status (status),
            KEY module_status (module, status),
            KEY created_status (created_at, status)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Set default plugin options.
     */
    private static function set_default_options() {
        $defaults = array(
            'flowsoft_modules' => array(
                'database'   => array( 'enabled' => true,  'schedule' => 'daily',      'time' => '03:00' ),
                'transients' => array( 'enabled' => true,  'schedule' => 'sixhours',    'time' => '' ),
                'heartbeat'  => array( 'enabled' => true,  'dashboard_interval' => 60,  'editor_interval' => 30, 'frontend_disable' => true ),
                'revisions'  => array( 'enabled' => true,  'max_revisions' => 5,        'schedule' => 'weekly' ),
                'assets'     => array( 'enabled' => true,  'disable_emojis' => true,    'disable_embeds' => true, 'remove_query_strings' => true, 'defer_js' => false ),
                'cron'       => array( 'enabled' => true,  'schedule' => 'daily' ),
                'media'      => array( 'enabled' => true,  'schedule' => 'weekly',      'max_image_size' => 2048 ),
                'cache'      => array( 'enabled' => true,  'browser_cache' => true, 'browser_cache_ttl' => 2592000, 'page_cache_headers' => true, 'page_cache_ttl' => 3600, 'object_cache_prewarm' => true, 'dns_prefetch' => true ),
            ),
            'flowsoft_stats' => array(
                'total_bytes_freed'    => 0,
                'total_items_cleaned'  => 0,
                'total_optimizations'  => 0,
                'last_optimization'    => '',
            ),
        );

        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value, '', 'no' );
            }
        }
    }

    /**
     * Schedule initial cron events.
     */
    private static function schedule_events() {
        // Manually register custom intervals before scheduling
        add_filter( 'cron_schedules', array( __CLASS__, 'register_custom_intervals' ) );

        if ( ! wp_next_scheduled( 'flowsoft_daily_optimization' ) ) {
            $timestamp = strtotime( 'tomorrow 3:00 AM' );
            wp_schedule_event( $timestamp, 'daily', 'flowsoft_daily_optimization' );
        }
        
        // Schedule 6-hour optimizations using custom interval
        if ( ! wp_next_scheduled( 'flowsoft_sixhours_optimization' ) ) {
            wp_schedule_event( time(), 'flowsoft_six_hours', 'flowsoft_sixhours_optimization' );
        }
        
        if ( ! wp_next_scheduled( 'flowsoft_weekly_optimization' ) ) {
            wp_schedule_event( time(), 'weekly', 'flowsoft_weekly_optimization' );
        }

        // Monthly log cleanup
        if ( ! wp_next_scheduled( 'flowsoft_monthly_log_cleanup' ) ) {
            wp_schedule_event( time(), 'monthly', 'flowsoft_monthly_log_cleanup' );
        }
    }

    /**
     * Register custom cron intervals during activation.
     *
     * @param array $schedules Existing schedules.
     * @return array Modified schedules.
     */
    public static function register_custom_intervals( $schedules ) {
        $schedules['flowsoft_six_hours'] = array(
            'interval' => 6 * HOUR_IN_SECONDS,
            'display'  => __( 'Cada 6 Horas (FlowSoft)', 'flowsoft-wp' ),
        );
        if ( ! isset( $schedules['monthly'] ) ) {
            $schedules['monthly'] = array(
                'interval' => 30 * DAY_IN_SECONDS,
                'display'  => __( 'Mensual (FlowSoft)', 'flowsoft-wp' ),
            );
        }
        return $schedules;
    }
}
