<?php
/**
 * FlowSoft WP — Heartbeat Controller Module
 *
 * Controls WordPress Heartbeat API frequency to reduce server load.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FlowSoft_Module_Heartbeat implements FlowSoft_Module_Interface {

    private $logger;

    public function __construct( $logger ) {
        $this->logger = $logger;
    }

    public function get_id()          { return 'heartbeat'; }
    public function get_name()        { return __( 'Control de Heartbeat', 'flowsoft-wp' ); }
    public function get_description() { return __( 'Reduce la frecuencia del Heartbeat API en el panel y lo desactiva en el frontend para reducir la carga del servidor.', 'flowsoft-wp' ); }
    public function get_schedule()    { return 'immediate'; }

    public function get_icon() {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/><path d="M3.22 12H9.5l.5-1 2 4.5 2-7 1.5 3.5h5.27"/></svg>';
    }

    /**
     * Apply heartbeat controls (called on every page load).
     *
     * @param array $settings Module settings.
     */
    public function apply( $settings ) {
        // Disable heartbeat on frontend
        if ( ! empty( $settings['frontend_disable'] ) && ! is_admin() ) {
            add_action( 'init', array( $this, 'disable_heartbeat' ), 1 );
            return;
        }

        // Modify heartbeat frequency
        add_filter( 'heartbeat_settings', function( $heartbeat_settings ) use ( $settings ) {
            if ( is_admin() ) {
                // Check if we are in the post editor
                global $pagenow;
                if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
                    $heartbeat_settings['interval'] = isset( $settings['editor_interval'] ) ? (int) $settings['editor_interval'] : 30;
                } else {
                    $heartbeat_settings['interval'] = isset( $settings['dashboard_interval'] ) ? (int) $settings['dashboard_interval'] : 60;
                }
            }
            return $heartbeat_settings;
        } );
    }

    /**
     * Deregister heartbeat script entirely.
     */
    public function disable_heartbeat() {
        wp_deregister_script( 'heartbeat' );
    }

    /**
     * Run is not applicable for this module (it's immediate effect).
     */
    public function run() {
        return array(
            'success' => true,
            'message' => __( 'El control de Heartbeat se aplica en tiempo real.', 'flowsoft-wp' ),
            'items'   => 0,
            'bytes'   => 0,
        );
    }

    /**
     * Get heartbeat stats.
     */
    public function get_stats() {
        $options = get_option( 'flowsoft_modules', array() );
        $settings = isset( $options['heartbeat'] ) ? $options['heartbeat'] : array();

        return array(
            'dashboard_interval' => isset( $settings['dashboard_interval'] ) ? $settings['dashboard_interval'] : 60,
            'editor_interval'    => isset( $settings['editor_interval'] ) ? $settings['editor_interval'] : 30,
            'frontend_disabled'  => ! empty( $settings['frontend_disable'] ),
            'status'             => ! empty( $settings['enabled'] ) ? 'active' : 'inactive',
        );
    }

    public function get_settings_fields() {
        return array(
            array(
                'id'          => 'dashboard_interval',
                'label'       => __( 'Intervalo del panel (segundos)', 'flowsoft-wp' ),
                'description' => __( 'Frecuencia del Heartbeat en el panel de administración. Mayor valor = menos carga del servidor.', 'flowsoft-wp' ),
                'type'        => 'number',
                'default'     => 60,
                'min'         => 15,
                'max'         => 300,
            ),
            array(
                'id'          => 'editor_interval',
                'label'       => __( 'Intervalo del editor (segundos)', 'flowsoft-wp' ),
                'description' => __( 'Frecuencia del Heartbeat en el editor de entradas. Valores bajos mantienen autosave activo.', 'flowsoft-wp' ),
                'type'        => 'number',
                'default'     => 30,
                'min'         => 15,
                'max'         => 300,
            ),
            array(
                'id'          => 'frontend_disable',
                'label'       => __( 'Desactivar en el frontend', 'flowsoft-wp' ),
                'description' => __( 'Deshabilita completamente el Heartbeat API en el frontend del sitio para reducir carga.', 'flowsoft-wp' ),
                'type'        => 'toggle',
                'default'     => true,
            ),
        );
    }
}
