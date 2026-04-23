<?php
/**
 * FlowSoft WP — Database Optimization Module
 *
 * Cleans revisions, auto-drafts, trash, spam, and optimizes tables.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FlowSoft_Module_Database implements FlowSoft_Module_Interface {

    private $logger;

    public function __construct( $logger ) {
        $this->logger = $logger;
    }

    public function get_id()          { return 'database'; }
    public function get_name()        { return __( 'Optimizador de Base de Datos', 'flowsoft-wp' ); }
    public function get_description() { return __( 'Limpia revisiones, borradores automáticos, papelera, comentarios spam y optimiza las tablas de la base de datos.', 'flowsoft-wp' ); }
    public function get_schedule()    { return 'daily'; }

    public function get_icon() {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/></svg>';
    }

    /**
     * Run database optimization.
     *
     * @return array
     */
    public function run() {
        global $wpdb;

        $total_items = 0;
        $total_bytes = 0;
        $details     = array();

        try {
            // 1. Clean excess post revisions (keeps max_revisions newest per post)
            $options       = get_option( 'flowsoft_modules', array() );
            $max_revisions = isset( $options['database']['max_revisions'] ) ? (int) $options['database']['max_revisions'] : 5;

            $posts_with_excess = $wpdb->get_results( $wpdb->prepare(
                "SELECT post_parent, COUNT(*) as rev_count
                 FROM {$wpdb->posts}
                 WHERE post_type = %s AND post_parent > 0
                 GROUP BY post_parent
                 HAVING rev_count > %d",
                'revision',
                $max_revisions
            ) );

            foreach ( $posts_with_excess as $post ) {
                $keep_ids = $wpdb->get_col( $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts}
                     WHERE post_type = %s AND post_parent = %d
                     ORDER BY post_date DESC LIMIT %d",
                    'revision',
                    $post->post_parent,
                    $max_revisions
                ) );

                if ( ! empty( $keep_ids ) ) {
                    $keep_ids_str = implode( ',', array_map( 'absint', $keep_ids ) );
                    $deleted = $wpdb->query( $wpdb->prepare(
                        "DELETE FROM {$wpdb->posts}
                         WHERE post_type = %s AND post_parent = %d AND ID NOT IN ({$keep_ids_str})",
                        'revision',
                        $post->post_parent
                    ) );
                    $total_items += $deleted;
                }
            }

            if ( $total_items > 0 ) {
                $details[] = sprintf( '%d revisiones excedentes eliminadas (manteniendo %d por entrada)', $total_items, $max_revisions );
            }

            // 2. Clean auto-drafts (FIXED: Using prepared statements)
            $auto_drafts = $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$wpdb->posts} WHERE post_status = %s",
                'auto-draft'
            ) );
            if ( $auto_drafts > 0 ) {
                $total_items += $auto_drafts;
                $details[]    = sprintf( '%d borradores automáticos eliminados', $auto_drafts );
            }

            // 3. Clean trashed posts (FIXED: Using prepared statements)
            $trashed = $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$wpdb->posts} WHERE post_status = %s",
                'trash'
            ) );
            if ( $trashed > 0 ) {
                $total_items += $trashed;
                $details[]    = sprintf( '%d entradas en papelera eliminadas', $trashed );
            }

            // 4. Clean spam comments (FIXED: Using prepared statements)
            $spam = $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$wpdb->comments} WHERE comment_approved = %s",
                'spam'
            ) );
            if ( $spam > 0 ) {
                $total_items += $spam;
                $details[]    = sprintf( '%d comentarios spam eliminados', $spam );
            }

            // 5. Clean trashed comments (FIXED: Using prepared statements)
            $trashed_comments = $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$wpdb->comments} WHERE comment_approved = %s",
                'trash'
            ) );
            if ( $trashed_comments > 0 ) {
                $total_items += $trashed_comments;
                $details[]    = sprintf( '%d comentarios en papelera eliminados', $trashed_comments );
            }

            // 6. Clean orphaned post meta
            $orphan_postmeta = $wpdb->query(
                "DELETE pm FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE p.ID IS NULL"
            );
            if ( $orphan_postmeta > 0 ) {
                $total_items += $orphan_postmeta;
                $details[]    = sprintf( '%d postmeta huérfanos eliminados', $orphan_postmeta );
            }

            // 7. Clean orphaned comment meta
            $orphan_commentmeta = $wpdb->query(
                "DELETE cm FROM {$wpdb->commentmeta} cm LEFT JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID WHERE c.comment_ID IS NULL"
            );
            if ( $orphan_commentmeta > 0 ) {
                $total_items += $orphan_commentmeta;
                $details[]    = sprintf( '%d commentmeta huérfanos eliminados', $orphan_commentmeta );
            }

            // 8. Clean orphaned relationship data
            $orphan_rel = $wpdb->query(
                "DELETE tr FROM {$wpdb->term_relationships} tr LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID WHERE p.ID IS NULL"
            );
            if ( $orphan_rel > 0 ) {
                $total_items += $orphan_rel;
                $details[]    = sprintf( '%d relaciones huérfanas eliminadas', $orphan_rel );
            }

            // 9. Optimize tables
            $tables = $wpdb->get_results( "SHOW TABLE STATUS WHERE Data_free > 0" );
            foreach ( $tables as $table ) {
                $total_bytes += (int) $table->Data_free;
                $safe_table   = preg_replace( '/[^a-zA-Z0-9_]/', '', $table->Name );
                $wpdb->query( "OPTIMIZE TABLE `{$safe_table}`" );
            }

            if ( count( $tables ) > 0 ) {
                $details[] = sprintf( '%d tablas optimizadas, %s liberados', count( $tables ), size_format( $total_bytes ) );
            }

            $message = ! empty( $details ) ? implode( '; ', $details ) : __( 'La base de datos ya está limpia', 'flowsoft-wp' );
            $this->logger->log( $this->get_id(), 'optimize', $message, $total_items, $total_bytes );

            return array( 'success' => true, 'message' => $message, 'items' => $total_items, 'bytes' => $total_bytes );

        } catch ( Exception $e ) {
            $error_msg = sprintf(
                'Database optimization error: %s (Line: %d, File: %s)',
                $e->getMessage(),
                $e->getLine(),
                basename( $e->getFile() )
            );
            $this->logger->log( $this->get_id(), 'error', $error_msg, 0, 0, 'error' );
            return array( 'success' => false, 'message' => __( 'Error durante la optimización. Revisa los registros para más detalles.', 'flowsoft-wp' ) );
        }
    }

    /**
     * Get current stats for the module card.
     *
     * @return array
     */
    public function get_stats() {
        $cached = get_transient( 'flowsoft_db_module_stats' );
        if ( false !== $cached ) {
            return $cached;
        }

        global $wpdb;

        $stats = array(
            'revisions'      => (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
                'revision'
            ) ),
            'auto_drafts'    => (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s",
                'auto-draft'
            ) ),
            'trashed_posts'  => (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s",
                'trash'
            ) ),
            'spam_comments'  => (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = %s",
                'spam'
            ) ),
            'trash_comments' => (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = %s",
                'trash'
            ) ),
            'db_size'        => $wpdb->get_var( $wpdb->prepare(
                "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) FROM information_schema.tables WHERE table_schema = %s",
                $wpdb->dbname
            ) ),
            'overhead'       => $wpdb->get_var( $wpdb->prepare(
                "SELECT ROUND(SUM(data_free) / 1024 / 1024, 2) FROM information_schema.tables WHERE table_schema = %s",
                $wpdb->dbname
            ) ),
        );

        set_transient( 'flowsoft_db_module_stats', $stats, FLOWSOFT_HEALTH_CACHE_TTL );

        return $stats;
    }

    /**
     * Get settings fields for the configuration panel.
     */
    public function get_settings_fields() {
        return array(
            array(
                'id'          => 'max_revisions',
                'label'       => __( 'Mantener últimas N revisiones', 'flowsoft-wp' ),
                'description' => __( 'Número de revisiones a conservar por entrada. 0 = eliminar todas las revisiones.', 'flowsoft-wp' ),
                'type'        => 'number',
                'default'     => 5,
                'min'         => 0,
                'max'         => 100,
            ),
        );
    }
}
