<?php
/**
 * FlowSoft WP — Deactivator
 *
 * Clears scheduled cron events on deactivation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FlowSoft_Deactivator {

    /**
     * Run deactivation routines.
     */
    public static function deactivate() {
        self::clear_scheduled_events();
    }

    /**
     * Remove all scheduled cron events.
     */
    private static function clear_scheduled_events() {
        $events = array(
            'flowsoft_daily_optimization',
            'flowsoft_sixhours_optimization',
            'flowsoft_weekly_optimization',
            'flowsoft_monthly_log_cleanup',
        );

        foreach ( $events as $event ) {
            $timestamp = wp_next_scheduled( $event );
            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, $event );
            }
        }

        // Clear all flowsoft cron events
        wp_clear_scheduled_hook( 'flowsoft_daily_optimization' );
        wp_clear_scheduled_hook( 'flowsoft_sixhours_optimization' );
        wp_clear_scheduled_hook( 'flowsoft_weekly_optimization' );
        wp_clear_scheduled_hook( 'flowsoft_monthly_log_cleanup' );
    }
}
