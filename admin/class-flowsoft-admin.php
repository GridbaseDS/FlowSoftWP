<?php
/**
 * FlowSoft WP — Admin Controller
 *
 * Registers admin menu, enqueues assets, and renders admin pages.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FlowSoft_Admin {

    /** @var FlowSoft_Admin|null */
    private static $instance = null;

    /** @var string */
    private $hook_suffix;

    private function __construct() {}

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize admin hooks.
     */
    public function init() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Register the admin menu page.
     */
    public function register_menu() {
        $this->hook_suffix = add_menu_page(
            __( 'FlowSoft WP', 'flowsoft-wp' ),
            __( 'FlowSoft WP', 'flowsoft-wp' ),
            'manage_options',
            'flowsoft-wp',
            array( $this, 'render_page' ),
            'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#a7aaad" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>' ),
            59
        );
    }

    /**
     * Enqueue CSS and JS only on the plugin page.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_assets( $hook ) {
        if ( $hook !== $this->hook_suffix ) {
            return;
        }

        // Google Fonts — Inter
        wp_enqueue_style(
            'flowsoft-google-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
            array(),
            FLOWSOFT_VERSION
        );

        // Plugin styles
        wp_enqueue_style(
            'flowsoft-admin-css',
            FLOWSOFT_PLUGIN_URL . 'admin/css/flowsoft-admin.css',
            array(),
            FLOWSOFT_VERSION
        );

        // Plugin scripts
        wp_enqueue_script(
            'flowsoft-admin-js',
            FLOWSOFT_PLUGIN_URL . 'admin/js/flowsoft-admin.js',
            array( 'jquery' ),
            FLOWSOFT_VERSION,
            true
        );

        // Localize script with AJAX url and nonce
        wp_localize_script( 'flowsoft-admin-js', 'flowsoftAdmin', array(
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'flowsoft_nonce' ),
            'strings'    => array(
                'confirm_run'    => __( '¿Ejecutar esta optimización ahora?', 'flowsoft-wp' ),
                'confirm_clear'  => __( '¿Borrar todos los registros? Esta acción no se puede deshacer.', 'flowsoft-wp' ),
                'running'        => __( 'Ejecutando...', 'flowsoft-wp' ),
                'success'        => __( '¡Completado exitosamente!', 'flowsoft-wp' ),
                'error'          => __( 'Ocurrió un error.', 'flowsoft-wp' ),
                'saved'          => __( '¡Configuración guardada!', 'flowsoft-wp' ),
            ),
        ) );
    }

    /**
     * Render the admin page (shell — the tabs load different views).
     */
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $core     = FlowSoft_Core::get_instance();
        $modules  = $core->get_modules();
        $options  = get_option( 'flowsoft_modules', array() );
        $stats    = get_option( 'flowsoft_stats', array() );
        $health   = $core->calculate_health_score();
        $tab      = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'dashboard';
        ?>
        <div id="flowsoft-app" class="flowsoft-wrap">
            <!-- Top Header Bar -->
            <header class="flowsoft-header">
                <div class="flowsoft-header__brand">
                    <svg class="flowsoft-header__brand-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1102.61 349.46" height="51">
                        <g>
                            <g>
                                <path fill="#00a460" d="M94.57,227.49c0-7.62-4.08-14.75-10.66-18.61L13.13,167.37C7.38,164,.13,168.06,0,174.73h0s1.24,66.18,1.24,66.18c.29,15.27,8.32,29.34,21.32,37.34l55.21,34h0c7.51,4.23,16.79-1.19,16.79-9.81v-74.95Z"/>
                                <path fill="#0b484c" d="M309.87,108.54c-.29-15.27-8.32-29.34-21.32-37.34l-55.21-34-55.76-31.43c-13.67-7.7-30.37-7.7-44.03,0l-55.76,31.43-55.21,34C9.56,79.21,1.53,93.28,1.24,108.54l-.26,13.94c-.06,3.15,1.59,6.09,4.31,7.69l92.84,54.46c15.14,8.88,24.55,25.3,24.55,42.86v103.49c0,4.06,2.19,7.81,5.73,9.81l5.13,2.89c13.67,7.7,30.37,7.7,44.03,0l42.26-23.82c4.77-2.69,7.72-7.74,7.72-13.22v-19.21c0-1.15-1.24-1.87-2.24-1.3l-61.86,35.56c-3.39,1.95-7.63-.5-7.63-4.41v-115.73c0-17.98-9.51-34.62-25.01-43.74l-78.8-46.39c-13.1-7.71-13.03-26.69.14-34.3l84.1-48.59c11.94-6.9,26.66-6.9,38.61,0l90.66,52.38c14.18,8.19,22.91,23.33,22.91,39.7v157.74c12.71-8.05,21.15-22.38,21.44-37.46l1.24-66.18-1.24-66.18Z"/>
                            </g>
                            <g>
                                <path fill="#0b484c" d="M383.97,223.3v-85.49h61.2v14.11h-44.24v22.8h34.2v13.84h-34.2v34.74h-16.96Z"/>
                                <path fill="#0b484c" d="M471.5,137.81v85.49h-16.28v-85.49h16.28Z"/>
                                <path fill="#0b484c" d="M513.15,224.93c-18.46,0-31.48-12.89-31.48-31.48s13.03-31.62,31.48-31.62,31.62,13.03,31.62,31.62-13.03,31.48-31.62,31.48ZM513.15,212.58c10.04,0,15.2-7.87,15.2-19.13s-5.16-19.27-15.2-19.27-15.06,7.87-15.06,19.27,5.16,19.13,15.06,19.13Z"/>
                                <path fill="#0b484c" d="M612.35,203.35h.54l12.35-39.89h14.79l-19,59.84h-15.74l-10.86-39.49h-.54l-10.86,39.49h-15.88l-19-59.84h16.15l12.48,39.89h.54l10.86-39.89h13.16l10.99,39.89Z"/>
                                <path fill="#00a460" d="M658.89,192.22c.68,13.3,10.31,19.27,22.25,19.27,10.58,0,17.37-4.21,17.37-11.4,0-6.51-5.16-8.96-14.11-10.72l-16.28-2.99c-13.3-2.44-22.53-9.77-22.53-23.88,0-16.01,12.62-26.33,32.57-26.33,22.39,0,35.01,11.53,35.28,30.53l-15.61.54c-.54-11.67-8.14-17.78-19.81-17.78-10.04,0-15.74,4.48-15.74,11.8,0,6.24,4.48,8.41,12.21,9.91l15.74,2.85c16.83,3.12,24.7,11.13,24.7,24.97,0,16.83-14.52,25.92-33.79,25.92-21.98,0-37.86-11.4-37.86-32.02l15.61-.68Z"/>
                                <path fill="#00a460" d="M754.56,224.93c-18.46,0-31.48-12.89-31.48-31.48s13.03-31.62,31.48-31.62,31.62,13.03,31.62,31.62-13.03,31.48-31.62,31.48ZM754.56,212.58c10.04,0,15.2-7.87,15.2-19.13s-5.16-19.27-15.2-19.27-15.06,7.87-15.06,19.27,5.16,19.13,15.06,19.13Z"/>
                                <path fill="#00a460" d="M789.97,174.86v-11.4h8.82v-5.02c0-13.98,8.41-22.25,21.85-22.25,10.86,0,17.5,5.16,20.08,14.66l-10.45,3.39c-1.36-3.8-3.94-6.38-8.14-6.38-5.02,0-7.19,3.8-7.19,9.36v6.24h16.01v11.4h-16.01v48.44h-16.15v-48.44h-8.82Z"/>
                                <path fill="#00a460" d="M840.31,174.86h-8.55v-9.63l4.07-.68c5.83-.95,7.46-3.93,8.82-9.5l1.9-8.14h9.91v16.56h16.28v11.4h-16.28v29.58c0,5.16,2.31,7.46,7.19,7.46,3.26,0,6.65-1.22,10.18-2.85v10.72c-4.34,3.26-9.23,5.16-16.55,5.16-9.23,0-16.96-4.07-16.96-16.83v-33.25Z"/>
                                <path fill="#00a460" d="M986.73,191.41h.54l13.57-53.6h22.25l-22.39,85.49h-24.56l-14.25-54.01h-.54l-14.25,54.01h-24.15l-22.39-85.49h23.07l13.57,53.6h.54l14.11-53.6h20.76l14.11,53.6Z"/>
                                <path fill="#00a460" d="M1052.54,223.3h-22.93v-85.49h37.59c22.39,0,35.42,10.45,35.42,29.31s-13.03,29.45-35.42,29.45h-14.66v26.73ZM1066.11,178.11c10.31,0,14.25-3.66,14.25-10.86s-3.93-10.99-14.25-10.99h-13.57v21.85h13.57Z"/>
                            </g>
                        </g>
                    </svg>
                </div>
                <nav class="flowsoft-header__nav">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=flowsoft-wp&tab=dashboard' ) ); ?>" 
                       class="flowsoft-header__nav-item <?php echo 'dashboard' === $tab ? 'is-active' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                        <?php esc_html_e( 'Panel', 'flowsoft-wp' ); ?>
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=flowsoft-wp&tab=modules' ) ); ?>"
                       class="flowsoft-header__nav-item <?php echo 'modules' === $tab ? 'is-active' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/></svg>
                        <?php esc_html_e( 'Módulos', 'flowsoft-wp' ); ?>
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=flowsoft-wp&tab=logs' ) ); ?>"
                       class="flowsoft-header__nav-item <?php echo 'logs' === $tab ? 'is-active' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><line x1="10" x2="8" y1="9" y2="9"/></svg>
                        <?php esc_html_e( 'Registros', 'flowsoft-wp' ); ?>
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=flowsoft-wp&tab=settings' ) ); ?>"
                       class="flowsoft-header__nav-item <?php echo 'settings' === $tab ? 'is-active' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                        <?php esc_html_e( 'Configuración', 'flowsoft-wp' ); ?>
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=flowsoft-wp&tab=docs' ) ); ?>"
                       class="flowsoft-header__nav-item <?php echo 'docs' === $tab ? 'is-active' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/><path d="M8 7h6"/><path d="M8 11h8"/></svg>
                        <?php esc_html_e( 'Documentación', 'flowsoft-wp' ); ?>
                    </a>
                </nav>
                <div class="flowsoft-header__status">
                    <span class="flowsoft-header__health flowsoft-health--<?php echo $health >= 80 ? 'good' : ( $health >= 50 ? 'warn' : 'bad' ); ?>">
                        <span class="flowsoft-health-dot"></span>
                        <?php printf( esc_html__( 'Salud: %d%%', 'flowsoft-wp' ), $health ); ?>
                    </span>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flowsoft-main">
                <?php
                switch ( $tab ) {
                    case 'modules':
                        include FLOWSOFT_PLUGIN_DIR . 'admin/views/modules.php';
                        break;
                    case 'logs':
                        include FLOWSOFT_PLUGIN_DIR . 'admin/views/logs.php';
                        break;
                    case 'settings':
                        include FLOWSOFT_PLUGIN_DIR . 'admin/views/settings.php';
                        break;
                    case 'docs':
                        include FLOWSOFT_PLUGIN_DIR . 'admin/views/docs.php';
                        break;
                    default:
                        include FLOWSOFT_PLUGIN_DIR . 'admin/views/dashboard.php';
                        break;
                }
                ?>
            </main>

            <!-- Toast Notification Container -->
            <div id="flowsoft-toast-container" class="flowsoft-toast-container"></div>
        </div>
        <?php
    }
}

