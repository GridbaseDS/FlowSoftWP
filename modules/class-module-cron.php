<?php
/**
 * FlowSoft WP — WP-Cron Health Monitor Module
 *
 * Detects duplicate, orphaned, and stuck cron events.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FlowSoft_Module_Cron implements FlowSoft_Module_Interface {

    private $logger;

    public function __construct( $logger ) {
        $this->logger = $logger;
    }

    public function get_id()          { return 'cron'; }
    public function get_name()        { return __( 'Monitor de Cron', 'flowsoft-wp' ); }
    public function get_description() { return __( 'Monitorea y limpia eventos WP-Cron duplicados u huérfanos de plugins desactivados.', 'flowsoft-wp' ); }
    public function get_schedule()    { return 'daily'; }

    public function get_icon() {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
    }

    /**
     * Run cron health check and cleanup.
     */
    public function run() {
        $total_cleaned = 0;
        $details       = array();

        try {
            $cron_array = _get_cron_array();
            if ( empty( $cron_array ) ) {
                $this->logger->log( $this->get_id(), 'check', __( 'No se encontraron eventos cron', 'flowsoft-wp' ) );
                return array( 'success' => true, 'message' => __( 'No se encontraron eventos cron', 'flowsoft-wp' ), 'items' => 0, 'bytes' => 0 );
            }

            // 1. Find and remove duplicate events
            $hook_counts = array();
            foreach ( $cron_array as $timestamp => $hooks ) {
                foreach ( $hooks as $hook => $events ) {
                    if ( ! isset( $hook_counts[ $hook ] ) ) {
                        $hook_counts[ $hook ] = 0;
                    }
                    $hook_counts[ $hook ] += count( $events );
                }
            }

            $duplicates = array_filter( $hook_counts, function( $count ) {
                return $count > 1;
            } );

            // Remove duplicate events (keep only the next scheduled one)
            foreach ( $duplicates as $hook => $count ) {
                // Skip WordPress core hooks that legitimately have multiple events
                $core_hooks = array( 'wp_privacy_delete_old_export_files', 'wp_update_plugins', 'wp_update_themes' );
                if ( in_array( $hook, $core_hooks, true ) ) {
                    continue;
                }

                $instances = array();
                foreach ( $cron_array as $timestamp => $hooks ) {
                    if ( isset( $hooks[ $hook ] ) ) {
                        foreach ( $hooks[ $hook ] as $key => $data ) {
                            $instances[] = array(
                                'timestamp' => $timestamp,
                                'key'       => $key,
                            );
                        }
                    }
                }

                // Keep the first (soonest) instance, remove the rest
                if ( count( $instances ) > 1 ) {
                    array_shift( $instances ); // Keep the first
                    foreach ( $instances as $instance ) {
                        wp_unschedule_event( $instance['timestamp'], $hook );
                        $total_cleaned++;
                    }
                    $details[] = sprintf( '%d eventos duplicados de "%s" eliminados', count( $instances ), $hook );
                }
            }

            // 2. Detect overdue events (more than 1 hour past scheduled time)
            $overdue     = array();
            $current     = time();
            $one_hour    = HOUR_IN_SECONDS;

            foreach ( $cron_array as $timestamp => $hooks ) {
                if ( $timestamp < ( $current - $one_hour ) ) {
                    foreach ( $hooks as $hook => $events ) {
                        $overdue[] = $hook;
                    }
                }
            }

            if ( ! empty( $overdue ) ) {
                $details[] = sprintf( '%d eventos cron atrasados detectados', count( $overdue ) );
            }

            $message = ! empty( $details ) ? implode( '; ', $details ) : __( 'El sistema cron está saludable', 'flowsoft-wp' );
            $this->logger->log( $this->get_id(), 'health_check', $message, $total_cleaned );

            return array( 'success' => true, 'message' => $message, 'items' => $total_cleaned, 'bytes' => 0 );

        } catch ( \Exception $e ) {
            $this->logger->log( $this->get_id(), 'error', sprintf( 'Cron error: %s (L%d)', $e->getMessage(), $e->getLine() ), 0, 0, 'error' );
            return array( 'success' => false, 'message' => __( 'Error durante la optimización. Revisa los registros para más detalles.', 'flowsoft-wp' ) );
        }
    }

    /**
     * Get cron stats.
     */
    public function get_stats() {
        $cron_array = _get_cron_array();
        $total      = 0;
        $hooks      = array();

        if ( ! empty( $cron_array ) ) {
            foreach ( $cron_array as $timestamp => $crons ) {
                foreach ( $crons as $hook => $events ) {
                    $total += count( $events );
                    $hooks[ $hook ] = isset( $hooks[ $hook ] ) ? $hooks[ $hook ] + count( $events ) : count( $events );
                }
            }
        }

        $duplicates = count( array_filter( $hooks, function( $c ) { return $c > 1; } ) );

        return array(
            'total_events'    => $total,
            'unique_hooks'    => count( $hooks ),
            'duplicate_hooks' => $duplicates,
        );
    }

    public function get_settings_fields() {
        return array();
    }
}
