<?php
/**
 * FlowSoft WP — Logger
 *
 * Logs optimization activity to a custom database table.
 * Includes security event logging, automatic cleanup, and CSV export.
 *
 * @package FlowSoft_WP
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FlowSoft_Logger {

    /** @var FlowSoft_Logger|null */
    private static $instance = null;

    /** @var string */
    private $table_name;

    /**
     * Singleton constructor.
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'flowsoft_logs';
    }

    /**
     * Get singleton instance.
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Log an optimization event.
     *
     * @param string $module         Module ID (e.g. 'database', 'transients').
     * @param string $action_type    Short action identifier.
     * @param string $message        Human-readable message.
     * @param int    $items_affected Number of items cleaned/optimized.
     * @param int    $bytes_freed    Bytes freed (if applicable).
     * @param string $status         'success' or 'error'.
     */
    public function log( $module, $action_type, $message, $items_affected = 0, $bytes_freed = 0, $status = 'success' ) {
        global $wpdb;

        $wpdb->insert(
            $this->table_name,
            array(
                'module'         => sanitize_text_field( $module ),
                'action_type'    => sanitize_text_field( $action_type ),
                'message'        => sanitize_text_field( $message ),
                'items_affected' => absint( $items_affected ),
                'bytes_freed'    => absint( $bytes_freed ),
                'status'         => in_array( $status, array( 'success', 'error', 'warning' ), true ) ? $status : 'success',
                'created_at'     => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
        );

        // Update global stats
        $this->update_stats( $items_affected, $bytes_freed );
    }

    /**
     * Log a security event.
     *
     * @since 1.3.0
     * @param string $event_type Event identifier (e.g. 'nonce_failed', 'rate_limited').
     * @param string $details    Additional details.
     */
    public function log_security_event( $event_type, $details ) {
        global $wpdb;

        $user_id = get_current_user_id();
        $ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : 'unknown';

        $message = sprintf(
            '[Security] %s | User: %d | IP: %s | %s',
            $event_type,
            $user_id,
            $ip,
            $details
        );

        $wpdb->insert(
            $this->table_name,
            array(
                'module'         => 'security',
                'action_type'    => sanitize_text_field( $event_type ),
                'message'        => sanitize_text_field( $message ),
                'items_affected' => 0,
                'bytes_freed'    => 0,
                'status'         => 'warning',
                'created_at'     => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
        );
    }

    /**
     * Get logs with optional filters.
     *
     * @param array $args Query arguments.
     * @return array
     */
    public function get_logs( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'module'   => '',
            'status'   => '',
            'per_page' => 20,
            'page'     => 1,
            'orderby'  => 'created_at',
            'order'    => 'DESC',
        );

        $args   = wp_parse_args( $args, $defaults );
        $where  = array( '1=1' );
        $values = array();

        if ( ! empty( $args['module'] ) ) {
            $where[]  = 'module = %s';
            $values[] = $args['module'];
        }

        if ( ! empty( $args['status'] ) ) {
            $where[]  = 'status = %s';
            $values[] = $args['status'];
        }

        $where_clause = implode( ' AND ', $where );
        $orderby      = in_array( $args['orderby'], array( 'created_at', 'module', 'items_affected' ), true ) ? $args['orderby'] : 'created_at';
        $order        = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
        $offset       = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );
        $limit        = absint( $args['per_page'] );

        $sql = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $values[] = $limit;
        $values[] = $offset;

        if ( ! empty( $values ) ) {
            $sql = $wpdb->prepare( $sql, $values );
        }

        return $wpdb->get_results( $sql );
    }

    /**
     * Get total count of logs (for pagination).
     *
     * @param array $args Filter arguments.
     * @return int
     */
    public function get_logs_count( $args = array() ) {
        global $wpdb;

        $where  = array( '1=1' );
        $values = array();

        if ( ! empty( $args['module'] ) ) {
            $where[]  = 'module = %s';
            $values[] = $args['module'];
        }

        if ( ! empty( $args['status'] ) ) {
            $where[]  = 'status = %s';
            $values[] = $args['status'];
        }

        $where_clause = implode( ' AND ', $where );
        $sql          = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";

        if ( ! empty( $values ) ) {
            $sql = $wpdb->prepare( $sql, $values );
        }

        return (int) $wpdb->get_var( $sql );
    }

    /**
     * Clear all logs or logs older than a given number of days.
     *
     * @param int $days_old Delete logs older than X days. 0 = delete all.
     * @return int Number of rows deleted.
     */
    public function clear_logs( $days_old = 0 ) {
        global $wpdb;

        if ( $days_old > 0 ) {
            $date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_old} days" ) );
            return $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_name} WHERE created_at < %s", $date ) );
        }

        return $wpdb->query( "TRUNCATE TABLE {$this->table_name}" );
    }

    /**
     * Cleanup old logs automatically.
     * Deletes logs older than FLOWSOFT_MAX_LOG_AGE_DAYS and trims to FLOWSOFT_MAX_LOG_ENTRIES.
     *
     * @since 1.3.0
     * @return int Total rows deleted.
     */
    public function cleanup_old_logs() {
        global $wpdb;
        $total_deleted = 0;

        // 1. Delete logs older than max age
        $max_age_days = defined( 'FLOWSOFT_MAX_LOG_AGE_DAYS' ) ? FLOWSOFT_MAX_LOG_AGE_DAYS : 30;
        $cutoff_date  = gmdate( 'Y-m-d H:i:s', strtotime( "-{$max_age_days} days" ) );

        $deleted = (int) $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE created_at < %s",
            $cutoff_date
        ) );
        $total_deleted += $deleted;

        // 2. Cap total entries at max limit
        $max_entries = defined( 'FLOWSOFT_MAX_LOG_ENTRIES' ) ? FLOWSOFT_MAX_LOG_ENTRIES : 10000;
        $total_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );

        if ( $total_count > $max_entries ) {
            $excess = $total_count - $max_entries;
            $deleted = (int) $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$this->table_name} ORDER BY created_at ASC LIMIT %d",
                $excess
            ) );
            $total_deleted += $deleted;
        }

        if ( $total_deleted > 0 ) {
            $this->log(
                'system',
                'cleanup',
                sprintf( __( 'Limpieza automática: %d registros antiguos eliminados.', 'flowsoft-wp' ), $total_deleted ),
                $total_deleted
            );
        }

        return $total_deleted;
    }

    /**
     * Export logs to CSV string.
     *
     * @since 1.3.0
     * @param array $args Optional filter arguments.
     * @return string CSV content.
     */
    public function export_logs_csv( $args = array() ) {
        $logs = $this->get_logs( array_merge( $args, array( 'per_page' => 5000 ) ) );

        $handle = fopen( 'php://temp', 'r+' );
        fputcsv( $handle, array( 'ID', 'Module', 'Action', 'Message', 'Items Affected', 'Bytes Freed', 'Status', 'Date' ) );

        foreach ( $logs as $log ) {
            fputcsv( $handle, array(
                $log->id,
                $log->module,
                $log->action_type,
                $log->message,
                $log->items_affected,
                $log->bytes_freed,
                $log->status,
                $log->created_at,
            ) );
        }

        rewind( $handle );
        $csv = stream_get_contents( $handle );
        fclose( $handle );

        return $csv;
    }

    /**
     * Update global stats.
     */
    private function update_stats( $items, $bytes ) {
        $stats = get_option( 'flowsoft_stats', array() );

        $stats['total_items_cleaned']  = ( isset( $stats['total_items_cleaned'] ) ? $stats['total_items_cleaned'] : 0 ) + $items;
        $stats['total_bytes_freed']    = ( isset( $stats['total_bytes_freed'] ) ? $stats['total_bytes_freed'] : 0 ) + $bytes;
        $stats['total_optimizations']  = ( isset( $stats['total_optimizations'] ) ? $stats['total_optimizations'] : 0 ) + 1;
        $stats['last_optimization']    = current_time( 'mysql' );

        update_option( 'flowsoft_stats', $stats );
    }

    /**
     * Get summary stats for the dashboard.
     *
     * @return array
     */
    public function get_dashboard_stats() {
        global $wpdb;

        $stats = get_option( 'flowsoft_stats', array() );

        // Get today's stats
        $today = current_time( 'Y-m-d' );
        $today_stats = $wpdb->get_row( $wpdb->prepare(
            "SELECT COUNT(*) as total_ops, SUM(items_affected) as items, SUM(bytes_freed) as bytes 
             FROM {$this->table_name} WHERE DATE(created_at) = %s AND status = 'success'",
            $today
        ) );

        // Get last 7 days stats
        $week_stats = $wpdb->get_row( $wpdb->prepare(
            "SELECT COUNT(*) as total_ops, SUM(items_affected) as items, SUM(bytes_freed) as bytes 
             FROM {$this->table_name} WHERE created_at >= %s AND status = 'success'",
            gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) )
        ) );

        return array(
            'all_time'  => $stats,
            'today'     => $today_stats,
            'this_week' => $week_stats,
        );
    }
}
