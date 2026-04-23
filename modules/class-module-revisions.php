<?php
/**
 * FlowSoft WP — Post Revisions Manager Module
 *
 * Limits revision count and cleans excess revisions.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FlowSoft_Module_Revisions implements FlowSoft_Module_Interface {

    private $logger;

    public function __construct( $logger ) {
        $this->logger = $logger;
    }

    public function get_id()          { return 'revisions'; }
    public function get_name()        { return __( 'Gestor de Revisiones', 'flowsoft-wp' ); }
    public function get_description() { return __( 'Limita el número de revisiones por entrada y limpia las revisiones excedentes semanalmente.', 'flowsoft-wp' ); }
    public function get_schedule()    { return 'weekly'; }

    public function get_icon() {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>';
    }

    /**
     * Apply revision limiter (called on every page load).
     *
     * @param array $settings Module settings.
     */
    public function apply( $settings ) {
        $max = isset( $settings['max_revisions'] ) ? (int) $settings['max_revisions'] : 5;

        add_filter( 'wp_revisions_to_keep', function() use ( $max ) {
            return $max;
        }, 999 );
    }

    /**
     * Run revision cleanup.
     */
    public function run() {
        global $wpdb;

        $options       = get_option( 'flowsoft_modules', array() );
        $max_revisions = isset( $options['revisions']['max_revisions'] ) ? (int) $options['revisions']['max_revisions'] : 5;

        $total_deleted = 0;

        try {
            // Get all posts that have more than $max_revisions revisions
            $posts_with_excess = $wpdb->get_results( $wpdb->prepare(
                "SELECT post_parent, COUNT(*) as rev_count 
                 FROM {$wpdb->posts} 
                 WHERE post_type = 'revision' AND post_parent > 0
                 GROUP BY post_parent 
                 HAVING rev_count > %d",
                $max_revisions
            ) );

            foreach ( $posts_with_excess as $post ) {
                // Get IDs of revisions to keep (newest N)
                $keep_ids = $wpdb->get_col( $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} 
                     WHERE post_type = 'revision' AND post_parent = %d 
                     ORDER BY post_date DESC LIMIT %d",
                    $post->post_parent,
                    $max_revisions
                ) );

                if ( ! empty( $keep_ids ) ) {
                    $keep_ids_str = implode( ',', array_map( 'absint', $keep_ids ) );

                    // Delete excess revisions
                    $deleted = $wpdb->query( $wpdb->prepare(
                        "DELETE FROM {$wpdb->posts} 
                         WHERE post_type = %s AND post_parent = %d AND ID NOT IN ({$keep_ids_str})",
                        'revision',
                        $post->post_parent
                    ) );

                    $total_deleted += $deleted;
                }
            }

            $message = $total_deleted > 0
                ? sprintf( __( '%d revisiones excedentes limpiadas (manteniendo %d por entrada)', 'flowsoft-wp' ), $total_deleted, $max_revisions )
                : __( 'No se encontraron revisiones excedentes', 'flowsoft-wp' );

            $this->logger->log( $this->get_id(), 'cleanup', $message, $total_deleted );

            return array( 'success' => true, 'message' => $message, 'items' => $total_deleted, 'bytes' => 0 );

        } catch ( \Exception $e ) {
            $this->logger->log( $this->get_id(), 'error', sprintf( 'Revisions error: %s (L%d)', $e->getMessage(), $e->getLine() ), 0, 0, 'error' );
            return array( 'success' => false, 'message' => __( 'Error durante la optimización. Revisa los registros para más detalles.', 'flowsoft-wp' ) );
        }
    }

    /**
     * Get revision stats.
     */
    public function get_stats() {
        global $wpdb;

        $options       = get_option( 'flowsoft_modules', array() );
        $max_revisions = isset( $options['revisions']['max_revisions'] ) ? (int) $options['revisions']['max_revisions'] : 5;

        // FIXED: Using prepared statements
        return array(
            'total_revisions' => (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
                'revision'
            ) ),
            'max_allowed'     => $max_revisions,
            'posts_with_excess' => (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(DISTINCT post_parent) FROM {$wpdb->posts} 
                 WHERE post_type = 'revision' AND post_parent > 0
                 GROUP BY post_parent HAVING COUNT(*) > %d",
                $max_revisions
            ) ),
        );
    }

    public function get_settings_fields() {
        return array(
            array(
                'id'          => 'max_revisions',
                'label'       => __( 'Máx. revisiones por entrada', 'flowsoft-wp' ),
                'description' => __( 'Número máximo de revisiones a guardar por entrada. Las revisiones antiguas se eliminarán automáticamente.', 'flowsoft-wp' ),
                'type'        => 'number',
                'default'     => 5,
                'min'         => 0,
                'max'         => 100,
            ),
        );
    }
}
