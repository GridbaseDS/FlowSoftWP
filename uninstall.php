<?php
/**
 * FlowSoft WP — Uninstall
 *
 * Fired when the plugin is deleted from WordPress.
 * Removes all plugin data including options and custom tables.
 */

// If uninstall not called from WordPress, abort.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// 1. Delete plugin options
delete_option( 'flowsoft_modules' );
delete_option( 'flowsoft_stats' );
delete_option( 'flowsoft_activated_at' );
delete_option( 'flowsoft_version' );

// 2. Drop custom tables
$table_name = $wpdb->prefix . 'flowsoft_logs';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

// 3. Clear scheduled cron events
wp_clear_scheduled_hook( 'flowsoft_daily_optimization' );
wp_clear_scheduled_hook( 'flowsoft_sixhours_optimization' );
wp_clear_scheduled_hook( 'flowsoft_weekly_optimization' );
wp_clear_scheduled_hook( 'flowsoft_monthly_log_cleanup' );

// 4. Clear plugin transients
delete_transient( 'flowsoft_health_score' );
delete_transient( 'flowsoft_db_overhead' );
