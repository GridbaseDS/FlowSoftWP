<?php
/**
 * FlowSoft WP — Transient Cleanup Module
 *
 * Cleans expired transients and monitors bloated ones.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FlowSoft_Module_Transients implements FlowSoft_Module_Interface {

    private $logger;

    public function __construct( $logger ) {
        $this->logger = $logger;
    }

    public function get_id()          { return 'transients'; }
    public function get_name()        { return __( 'Gestor de Transients', 'flowsoft-wp' ); }
    public function get_description() { return __( 'Limpia transients expirados y monitorea transients grandes sin fecha de expiración.', 'flowsoft-wp' ); }
    public function get_schedule()    { return 'sixhours'; }

    public function get_icon() {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M2 12h20"/><circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 14.14 14.14"/></svg>';
    }

    /**
     * Run transient cleanup.
     */
    public function run() {
        global $wpdb;

        $total_items = 0;
        $total_bytes = 0;
        $details     = array();

        try {
            // 1. Delete expired transients
            $time = time();

            // Get size of expired transients before deleting
            $expired_size = $wpdb->get_var( $wpdb->prepare(
                "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} 
                 WHERE option_name LIKE %s 
                 AND option_name NOT LIKE %s
                 AND option_name IN (
                     SELECT REPLACE(option_name, '_timeout_', '_') FROM {$wpdb->options}
                     WHERE option_name LIKE %s AND option_value < %d
                 )",
                $wpdb->esc_like( '_transient_' ) . '%',
                $wpdb->esc_like( '_transient_timeout_' ) . '%',
                $wpdb->esc_like( '_transient_timeout_' ) . '%',
                $time
            ) );

            // Delete expired transient timeouts
            $expired_timeouts = $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
                $wpdb->esc_like( '_transient_timeout_' ) . '%',
                $time
            ) );

            // Delete expired site transient timeouts
            $expired_site_timeouts = $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
                $wpdb->esc_like( '_site_transient_timeout_' ) . '%',
                $time
            ) );

            // Delete orphaned transients (no timeout pair)
            $orphaned = $wpdb->query(
                "DELETE a FROM {$wpdb->options} a
                 WHERE a.option_name LIKE '_transient_%'
                 AND a.option_name NOT LIKE '_transient_timeout_%'
                 AND a.option_name NOT LIKE '_site_transient_%'
                 AND NOT EXISTS (
                     SELECT 1 FROM (SELECT option_name FROM {$wpdb->options}) b 
                     WHERE b.option_name = CONCAT('_transient_timeout_', SUBSTRING(a.option_name, 12))
                 )"
            );

            $total_deleted = $expired_timeouts + $expired_site_timeouts + $orphaned;
            $total_items   = $total_deleted;
            $total_bytes   = (int) $expired_size;

            if ( $total_deleted > 0 ) {
                $details[] = sprintf( '%d transients expirados/huérfanos limpiados', $total_deleted );
            }

            if ( $expired_size > 0 ) {
                $details[] = sprintf( '%s liberados', size_format( $expired_size ) );
            }

            $message = ! empty( $details ) ? implode( '; ', $details ) : __( 'No se encontraron transients expirados', 'flowsoft-wp' );
            $this->logger->log( $this->get_id(), 'cleanup', $message, $total_items, $total_bytes );

            return array( 'success' => true, 'message' => $message, 'items' => $total_items, 'bytes' => $total_bytes );

        } catch ( \Exception $e ) {
            $this->logger->log( $this->get_id(), 'error', sprintf( 'Transients error: %s (L%d)', $e->getMessage(), $e->getLine() ), 0, 0, 'error' );
            return array( 'success' => false, 'message' => __( 'Error durante la optimización. Revisa los registros para más detalles.', 'flowsoft-wp' ) );
        }
    }

    /**
     * Get transient stats.
     */
    public function get_stats() {
        global $wpdb;

        $time = time();

        // FIXED: Using prepared statements for all queries
        return array(
            'total_transients' => (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name NOT LIKE %s",
                $wpdb->esc_like( '_transient_' ) . '%',
                $wpdb->esc_like( '_transient_timeout_' ) . '%'
            ) ),
            'expired'          => (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
                $wpdb->esc_like( '_transient_timeout_' ) . '%',
                $time
            ) ),
            'no_expiry'        => (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} a
                 WHERE a.option_name LIKE %s
                 AND a.option_name NOT LIKE %s
                 AND a.option_name NOT LIKE %s
                 AND NOT EXISTS (
                     SELECT 1 FROM (SELECT option_name FROM {$wpdb->options}) b 
                     WHERE b.option_name = CONCAT('_transient_timeout_', SUBSTRING(a.option_name, 12))
                 )",
                $wpdb->esc_like( '_transient_' ) . '%',
                $wpdb->esc_like( '_transient_timeout_' ) . '%',
                $wpdb->esc_like( '_site_transient_' ) . '%'
            ) ),
            'total_size'       => $wpdb->get_var( $wpdb->prepare(
                "SELECT ROUND(SUM(LENGTH(option_value)) / 1024, 2) FROM {$wpdb->options} 
                 WHERE option_name LIKE %s AND option_name NOT LIKE %s",
                $wpdb->esc_like( '_transient_' ) . '%',
                $wpdb->esc_like( '_transient_timeout_' ) . '%'
            ) ),
        );
    }

    public function get_settings_fields() {
        return array();
    }
}
