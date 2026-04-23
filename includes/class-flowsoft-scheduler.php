<?php
/**
 * FlowSoft WP — Scheduler
 *
 * Manages WP-Cron schedules and custom intervals.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FlowSoft_Scheduler {

    /** @var FlowSoft_Scheduler|null */
    private static $instance = null;

    private function __construct() {}

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register custom cron intervals.
     */
    public function register_intervals( $schedules ) {
        $schedules['flowsoft_six_hours'] = array(
            'interval' => 6 * HOUR_IN_SECONDS,
            'display'  => __( 'Cada 6 Horas (FlowSoft)', 'flowsoft-wp' ),
        );

        $schedules['flowsoft_twelve_hours'] = array(
            'interval' => 12 * HOUR_IN_SECONDS,
            'display'  => __( 'Cada 12 Horas (FlowSoft)', 'flowsoft-wp' ),
        );

        return $schedules;
    }

    /**
     * Get the next scheduled run for a given event hook.
     *
     * @param string $hook
     * @return string|false Human-readable time or false.
     */
    public function get_next_run( $hook ) {
        $timestamp = wp_next_scheduled( $hook );
        if ( ! $timestamp ) {
            return false;
        }

        $diff = $timestamp - time();

        if ( $diff < 0 ) {
            return __( 'Atrasado', 'flowsoft-wp' );
        }

        if ( $diff < HOUR_IN_SECONDS ) {
            $minutes = round( $diff / MINUTE_IN_SECONDS );
            /* translators: %d: number of minutes */
            return sprintf( __( 'En %d min', 'flowsoft-wp' ), $minutes );
        }

        if ( $diff < DAY_IN_SECONDS ) {
            $hours = round( $diff / HOUR_IN_SECONDS );
            /* translators: %d: number of hours */
            return sprintf( __( 'En %d hrs', 'flowsoft-wp' ), $hours );
        }

        $days = round( $diff / DAY_IN_SECONDS );
        /* translators: %d: number of days */
        return sprintf( __( 'En %d días', 'flowsoft-wp' ), $days );
    }

    /**
     * Get all scheduled FlowSoft events.
     *
     * @return array
     */
    public function get_all_events() {
        $cron_array = _get_cron_array();
        $events     = array();

        if ( empty( $cron_array ) ) {
            return $events;
        }

        foreach ( $cron_array as $timestamp => $hooks ) {
            foreach ( $hooks as $hook => $data ) {
                if ( 0 === strpos( $hook, 'flowsoft_' ) ) {
                    foreach ( $data as $key => $info ) {
                        $events[] = array(
                            'hook'      => $hook,
                            'timestamp' => $timestamp,
                            'schedule'  => isset( $info['schedule'] ) ? $info['schedule'] : 'once',
                            'next_run'  => $this->get_next_run( $hook ),
                        );
                    }
                }
            }
        }

        return $events;
    }

    /**
     * Reschedule an event with a new interval.
     *
     * @param string $hook
     * @param string $recurrence
     */
    public function reschedule_event( $hook, $recurrence ) {
        wp_clear_scheduled_hook( $hook );

        if ( ! wp_next_scheduled( $hook ) ) {
            wp_schedule_event( time(), $recurrence, $hook );
        }
    }
}
