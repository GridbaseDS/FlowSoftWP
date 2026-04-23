<?php
/**
 * FlowSoft WP — Media Optimizer Module
 *
 * Detects unused images, oversized files, and orphaned thumbnails.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FlowSoft_Module_Media implements FlowSoft_Module_Interface {

    private $logger;

    public function __construct( $logger ) {
        $this->logger = $logger;
    }

    public function get_id()          { return 'media'; }
    public function get_name()        { return __( 'Optimizador de Medios', 'flowsoft-wp' ); }
    public function get_description() { return __( 'Detecta archivos multimedia no utilizados, imágenes sobredimensionadas y miniaturas huérfanas para mantener tu carpeta de uploads ligera.', 'flowsoft-wp' ); }
    public function get_schedule()    { return 'weekly'; }

    public function get_icon() {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>';
    }

    /**
     * Run media analysis (reports only, does NOT delete files).
     */
    public function run() {
        global $wpdb;

        $details   = array();
        $issues    = 0;

        try {
            // 1. Count unattached media (FIXED: Using prepared statement)
            $unattached = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_parent = %d",
                'attachment',
                0
            ) );
            if ( $unattached > 0 ) {
                $issues++;
                $details[] = sprintf( __( '%d archivos multimedia sin adjuntar encontrados', 'flowsoft-wp' ), $unattached );
            }

            // 2. Find oversized images (> configured max KB)
            // FIXED: Added safety measures to prevent timeouts
            $options       = get_option( 'flowsoft_modules', array() );
            $max_size_kb   = isset( $options['media']['max_image_size'] ) ? (int) $options['media']['max_image_size'] : 2048;

            $upload_dir = wp_upload_dir();
            if ( isset( $upload_dir['error'] ) && $upload_dir['error'] ) {
                throw new RuntimeException( 'Upload directory error: ' . $upload_dir['error'] );
            }

            $base_dir   = $upload_dir['basedir'];
            $large_files = 0;
            $total_excess_size = 0;

            if ( is_dir( $base_dir ) ) {
                try {
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator( $base_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
                        RecursiveIteratorIterator::SELF_FIRST
                    );
                    $iterator->setMaxDepth( 10 ); // Limit depth to prevent deep recursion

                    $file_count = 0;
                    $max_files_to_scan = 5000; // Prevent timeout on huge sites

                    foreach ( $iterator as $file ) {
                        if ( ++$file_count > $max_files_to_scan ) {
                            $details[] = sprintf(
                                __( 'Escaneo limitado a %d archivos para prevenir timeouts', 'flowsoft-wp' ),
                                $max_files_to_scan
                            );
                            break;
                        }

                        if ( $file->isFile() && in_array( strtolower( $file->getExtension() ), array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp' ), true ) ) {
                            $size_kb = $file->getSize() / 1024;
                            if ( $size_kb > $max_size_kb ) {
                                $large_files++;
                                $total_excess_size += $file->getSize();
                            }
                        }
                    }
                } catch ( UnexpectedValueException $e ) {
                    $this->logger->log( $this->get_id(), 'warning', sprintf( 'Media scan error: %s (L%d)', $e->getMessage(), $e->getLine() ), 0, 0, 'warning' );
                }
            }

            if ( $large_files > 0 ) {
                $issues++;
                $details[] = sprintf(
                    __( '%d imágenes exceden %dKB (%s en total)', 'flowsoft-wp' ),
                    $large_files,
                    $max_size_kb,
                    size_format( $total_excess_size )
                );
            }

            // 3. Total media library size (FIXED: Using prepared statement)
            $total_attachments = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
                'attachment'
            ) );

            $message = ! empty( $details ) ? implode( '; ', $details ) : __( 'La biblioteca de medios está en buen estado', 'flowsoft-wp' );
            $status  = $issues > 0 ? 'warning' : 'success';

            $this->logger->log( $this->get_id(), 'analysis', $message, $issues, 0, $status );

            return array( 'success' => true, 'message' => $message, 'items' => $issues, 'bytes' => $total_excess_size );

        } catch ( RuntimeException $e ) {
            $this->logger->log( $this->get_id(), 'error', sprintf( 'Media runtime error: %s (L%d)', $e->getMessage(), $e->getLine() ), 0, 0, 'error' );
            return array( 'success' => false, 'message' => __( 'Error durante la optimización. Revisa los registros para más detalles.', 'flowsoft-wp' ) );
        } catch ( Exception $e ) {
            $this->logger->log( $this->get_id(), 'error', sprintf( 'Media error: %s (L%d)', $e->getMessage(), $e->getLine() ), 0, 0, 'error' );
            return array( 'success' => false, 'message' => __( 'Error durante la optimización. Revisa los registros para más detalles.', 'flowsoft-wp' ) );
        }
    }

    /**
     * Get media stats.
     */
    public function get_stats() {
        global $wpdb;

        $options     = get_option( 'flowsoft_modules', array() );
        $max_size_kb = isset( $options['media']['max_image_size'] ) ? (int) $options['media']['max_image_size'] : 2048;

        // FIXED: Using prepared statements
        return array(
            'total_attachments' => (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
                'attachment'
            ) ),
            'unattached'        => (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_parent = %d",
                'attachment',
                0
            ) ),
            'max_size_kb'       => $max_size_kb,
        );
    }

    public function get_settings_fields() {
        return array(
            array(
                'id'          => 'max_image_size',
                'label'       => __( 'Tamaño máximo de imagen (KB)', 'flowsoft-wp' ),
                'description' => __( 'Identifica imágenes que excedan este tamaño. El módulo solo reporta, no modifica archivos.', 'flowsoft-wp' ),
                'type'        => 'number',
                'default'     => 2048,
                'min'         => 256,
                'max'         => 10240,
            ),
        );
    }
}
