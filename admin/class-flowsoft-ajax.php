<?php
/**
 * FlowSoft WP — AJAX Handler
 *
 * Handles all AJAX requests from the admin interface.
 * Includes rate limiting, security logging, and numeric validation.
 *
 * @package FlowSoft_WP
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FlowSoft_Ajax {

    /** @var FlowSoft_Ajax|null */
    private static $instance = null;

    private function __construct() {}

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register AJAX hooks.
     */
    public function init() {
        add_action( 'wp_ajax_flowsoft_run_module',     array( $this, 'run_module' ) );
        add_action( 'wp_ajax_flowsoft_toggle_module',  array( $this, 'toggle_module' ) );
        add_action( 'wp_ajax_flowsoft_save_settings',  array( $this, 'save_settings' ) );
        add_action( 'wp_ajax_flowsoft_get_stats',      array( $this, 'get_stats' ) );
        add_action( 'wp_ajax_flowsoft_get_logs',       array( $this, 'get_logs' ) );
        add_action( 'wp_ajax_flowsoft_clear_logs',     array( $this, 'clear_logs' ) );
        add_action( 'wp_ajax_flowsoft_run_all',        array( $this, 'run_all' ) );
        add_action( 'wp_ajax_flowsoft_export_logs',    array( $this, 'export_logs' ) );
    }

    /**
     * Verify nonce, check permissions, enforce rate limiting, and log security events.
     */
    private function verify_nonce() {
        // Nonce check
        if ( ! check_ajax_referer( 'flowsoft_nonce', 'nonce', false ) ) {
            $this->log_security_event( 'nonce_failed', 'Nonce verification failed' );
            wp_send_json_error( array( 'message' => __( 'Verificación de seguridad fallida.', 'flowsoft-wp' ) ) );
        }

        // Capability check
        if ( ! current_user_can( 'manage_options' ) ) {
            $this->log_security_event( 'unauthorized_access', 'User lacks manage_options capability' );
            wp_send_json_error( array( 'message' => __( 'Permisos insuficientes.', 'flowsoft-wp' ) ) );
        }

        // Rate limiting
        $user_id       = get_current_user_id();
        $transient_key = 'flowsoft_rate_' . $user_id;
        $request_count = (int) get_transient( $transient_key );

        if ( $request_count >= FLOWSOFT_RATE_LIMIT_MAX ) {
            $this->log_security_event( 'rate_limited', sprintf( 'User %d exceeded rate limit (%d/%d)', $user_id, $request_count, FLOWSOFT_RATE_LIMIT_MAX ) );
            wp_send_json_error( array( 'message' => __( 'Demasiadas solicitudes. Intenta de nuevo en un momento.', 'flowsoft-wp' ) ) );
        }

        set_transient( $transient_key, $request_count + 1, FLOWSOFT_RATE_LIMIT_WINDOW );
    }

    /**
     * Log security events.
     *
     * @param string $event_type Event identifier.
     * @param string $details    Event details.
     */
    private function log_security_event( $event_type, $details ) {
        $logger = FlowSoft_Logger::get_instance();
        $logger->log_security_event( $event_type, $details );
    }

    /**
     * Run a specific optimization module.
     */
    public function run_module() {
        $this->verify_nonce();

        $module_id = isset( $_POST['module_id'] ) ? sanitize_text_field( $_POST['module_id'] ) : '';

        if ( empty( $module_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Se requiere el ID del módulo.', 'flowsoft-wp' ) ) );
        }

        try {
            $core   = FlowSoft_Core::get_instance();
            $result = $core->run_module( $module_id );

            if ( $result['success'] ) {
                wp_send_json_success( $result );
            } else {
                wp_send_json_error( $result );
            }
        } catch ( \Exception $e ) {
            $logger = FlowSoft_Logger::get_instance();
            $logger->log( $module_id, 'error', sprintf( 'Exception in run_module: %s (L%d)', $e->getMessage(), $e->getLine() ), 0, 0, 'error' );
            wp_send_json_error( array( 'message' => __( 'Error durante la optimización. Revisa los registros para más detalles.', 'flowsoft-wp' ) ) );
        }
    }

    /**
     * Toggle a module on/off.
     */
    public function toggle_module() {
        $this->verify_nonce();

        $module_id = isset( $_POST['module_id'] ) ? sanitize_text_field( $_POST['module_id'] ) : '';
        $enabled   = isset( $_POST['enabled'] ) ? ( $_POST['enabled'] === 'true' || $_POST['enabled'] === '1' ) : false;

        if ( empty( $module_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Se requiere el ID del módulo.', 'flowsoft-wp' ) ) );
        }

        $options = get_option( 'flowsoft_modules', array() );

        if ( ! isset( $options[ $module_id ] ) ) {
            $options[ $module_id ] = array();
        }

        $options[ $module_id ]['enabled'] = $enabled;
        update_option( 'flowsoft_modules', $options );

        wp_send_json_success( array(
            'message' => $enabled
                ? sprintf( __( 'Módulo %s activado.', 'flowsoft-wp' ), ucfirst( $module_id ) )
                : sprintf( __( 'Módulo %s desactivado.', 'flowsoft-wp' ), ucfirst( $module_id ) ),
            'enabled' => $enabled,
        ) );
    }

    /**
     * Save module settings with whitelist and numeric range validation.
     *
     * @since 1.3.0 Added numeric range validation.
     */
    public function save_settings() {
        $this->verify_nonce();

        $settings = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array();

        if ( empty( $settings ) || ! is_array( $settings ) ) {
            wp_send_json_error( array( 'message' => __( 'No se proporcionaron configuraciones.', 'flowsoft-wp' ) ) );
        }

        // Define allowed fields per module for security
        $allowed_fields = array(
            'database'   => array( 'enabled', 'schedule', 'time', 'max_revisions' ),
            'transients' => array( 'enabled', 'schedule', 'time' ),
            'heartbeat'  => array( 'enabled', 'dashboard_interval', 'editor_interval', 'frontend_disable' ),
            'revisions'  => array( 'enabled', 'max_revisions', 'schedule' ),
            'assets'     => array( 'enabled', 'disable_emojis', 'disable_embeds', 'remove_query_strings', 'defer_js' ),
            'cron'       => array( 'enabled', 'schedule' ),
            'media'      => array( 'enabled', 'schedule', 'max_image_size' ),
            'cache'      => array( 'enabled', 'browser_cache', 'browser_cache_ttl', 'page_cache_headers', 'page_cache_ttl', 'object_cache_prewarm', 'dns_prefetch' ),
        );

        // Numeric field validation ranges
        $numeric_ranges = array(
            'heartbeat' => array(
                'dashboard_interval' => array( FLOWSOFT_HEARTBEAT_MIN, FLOWSOFT_HEARTBEAT_MAX ),
                'editor_interval'    => array( FLOWSOFT_HEARTBEAT_MIN, FLOWSOFT_HEARTBEAT_MAX ),
            ),
            'revisions' => array(
                'max_revisions'      => array( FLOWSOFT_MAX_REVISIONS_MIN, FLOWSOFT_MAX_REVISIONS_MAX ),
            ),
            'database' => array(
                'max_revisions'      => array( FLOWSOFT_MAX_REVISIONS_MIN, FLOWSOFT_MAX_REVISIONS_MAX ),
            ),
            'media' => array(
                'max_image_size'     => array( FLOWSOFT_MAX_IMAGE_SIZE_MIN, FLOWSOFT_MAX_IMAGE_SIZE_MAX ),
            ),
            'cache' => array(
                'browser_cache_ttl'  => array( FLOWSOFT_CACHE_TTL_MIN, FLOWSOFT_CACHE_TTL_MAX ),
                'page_cache_ttl'     => array( FLOWSOFT_CACHE_TTL_MIN, 86400 ),
            ),
        );

        $options = get_option( 'flowsoft_modules', array() );

        foreach ( $settings as $module_id => $module_settings ) {
            $module_id = sanitize_text_field( $module_id );

            // Skip if module is not recognized
            if ( ! isset( $allowed_fields[ $module_id ] ) ) {
                continue;
            }

            if ( ! isset( $options[ $module_id ] ) ) {
                $options[ $module_id ] = array();
            }

            foreach ( $module_settings as $key => $value ) {
                $key = sanitize_text_field( $key );

                // SECURITY: Only allow whitelisted fields
                if ( ! in_array( $key, $allowed_fields[ $module_id ], true ) ) {
                    continue;
                }

                // Type casting and sanitization
                if ( is_numeric( $value ) ) {
                    $int_value = (int) $value;

                    // Validate numeric ranges
                    if ( isset( $numeric_ranges[ $module_id ][ $key ] ) ) {
                        $range     = $numeric_ranges[ $module_id ][ $key ];
                        $int_value = max( $range[0], min( $range[1], $int_value ) );
                    }

                    $options[ $module_id ][ $key ] = $int_value;
                } elseif ( $value === 'true' || $value === '1' ) {
                    $options[ $module_id ][ $key ] = true;
                } elseif ( $value === 'false' || $value === '0' ) {
                    $options[ $module_id ][ $key ] = false;
                } else {
                    $options[ $module_id ][ $key ] = sanitize_text_field( $value );
                }
            }
        }

        update_option( 'flowsoft_modules', $options );

        // Clear health score cache when settings change
        delete_transient( 'flowsoft_health_score' );

        wp_send_json_success( array( 'message' => __( 'Configuración guardada exitosamente.', 'flowsoft-wp' ) ) );
    }

    /**
     * Get live stats for the dashboard.
     */
    public function get_stats() {
        $this->verify_nonce();

        $core   = FlowSoft_Core::get_instance();
        $logger = FlowSoft_Logger::get_instance();

        $health = $core->calculate_health_score();
        $stats  = $logger->get_dashboard_stats();

        // Get per-module stats
        $module_stats = array();
        foreach ( $core->get_modules() as $id => $module ) {
            $module_stats[ $id ] = $module->get_stats();
        }

        wp_send_json_success( array(
            'health'       => $health,
            'stats'        => $stats,
            'module_stats' => $module_stats,
        ) );
    }

    /**
     * Get paginated logs.
     */
    public function get_logs() {
        $this->verify_nonce();

        $logger = FlowSoft_Logger::get_instance();

        $args = array(
            'module'   => isset( $_POST['module'] ) ? sanitize_text_field( $_POST['module'] ) : '',
            'status'   => isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '',
            'per_page' => isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 20,
            'page'     => isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1,
        );

        $logs  = $logger->get_logs( $args );
        $total = $logger->get_logs_count( $args );

        wp_send_json_success( array(
            'logs'       => $logs,
            'total'      => $total,
            'total_pages'=> ceil( $total / $args['per_page'] ),
            'page'       => $args['page'],
        ) );
    }

    /**
     * Clear all logs.
     */
    public function clear_logs() {
        $this->verify_nonce();

        $logger  = FlowSoft_Logger::get_instance();
        $deleted = $logger->clear_logs();

        wp_send_json_success( array(
            'message' => sprintf( __( 'Registros borrados. %d registros eliminados.', 'flowsoft-wp' ), $deleted ),
        ) );
    }

    /**
     * Run all enabled modules.
     */
    public function run_all() {
        $this->verify_nonce();

        try {
            $core    = FlowSoft_Core::get_instance();
            $options = get_option( 'flowsoft_modules', array() );
            $results = array();

            foreach ( $core->get_modules() as $id => $module ) {
                if ( ! empty( $options[ $id ]['enabled'] ) ) {
                    $results[ $id ] = $core->run_module( $id );
                }
            }

            wp_send_json_success( array(
                'message' => __( 'Todos los módulos ejecutados.', 'flowsoft-wp' ),
                'results' => $results,
            ) );
        } catch ( \Exception $e ) {
            $logger = FlowSoft_Logger::get_instance();
            $logger->log( 'system', 'error', sprintf( 'Exception in run_all: %s (L%d)', $e->getMessage(), $e->getLine() ), 0, 0, 'error' );
            wp_send_json_error( array( 'message' => __( 'Error durante la optimización. Revisa los registros para más detalles.', 'flowsoft-wp' ) ) );
        }
    }

    /**
     * Export logs as CSV.
     *
     * @since 1.3.0
     */
    public function export_logs() {
        $this->verify_nonce();

        $logger = FlowSoft_Logger::get_instance();
        $csv    = $logger->export_logs_csv();

        wp_send_json_success( array(
            'csv'      => $csv,
            'filename' => 'flowsoft-logs-' . gmdate( 'Y-m-d' ) . '.csv',
        ) );
    }
}
