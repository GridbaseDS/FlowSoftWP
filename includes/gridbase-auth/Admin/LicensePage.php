<?php
namespace Gridbase\Auth\Admin;

use Gridbase\Auth\License\Manager;

if (!defined('ABSPATH')) {
    exit;
}

class LicensePage {
    private $manager;
    private $plugin_name;
    private $plugin_slug;
    private $menu_slug;

    public function __construct(Manager $manager, $plugin_name, $plugin_slug) {
        $this->manager = $manager;
        $this->plugin_name = $plugin_name;
        $this->plugin_slug = $plugin_slug;
        $this->menu_slug = $plugin_slug . '-license';

        add_action('admin_menu', array($this, 'add_menu_page'), 99);
        add_action('admin_init', array($this, 'handle_form_submission'));
        add_action('admin_notices', array($this, 'display_notices'));
        add_action('admin_footer', array($this, 'display_glass_overlay'));
    }

    public function add_menu_page() {
        add_submenu_page(
            'options-general.php',
            $this->plugin_name . ' License',
            $this->plugin_name . ' License',
            'manage_options',
            $this->menu_slug,
            array($this, 'render_page')
        );
    }

    public function handle_form_submission() {
        if (!isset($_POST['gridbase_license_action']) || !isset($_POST['_wpnonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['_wpnonce'], 'gridbase_license_nonce')) {
            add_settings_error('gridbase_license_messages', 'gridbase_license_error', __('Security check failed.', 'gridbase-auth'), 'error');
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $action = sanitize_text_field($_POST['gridbase_license_action']);

        if ($action === 'activate') {
            $key = sanitize_text_field($_POST['gridbase_license_key']);
            $email = isset($_POST['gridbase_license_email']) ? sanitize_email($_POST['gridbase_license_email']) : '';

            if (empty($key)) {
                add_settings_error('gridbase_license_messages', 'gridbase_license_error', __('Please enter a license key.', 'gridbase-auth'), 'error');
                return;
            }

            $response = $this->manager->activate_remotely($key, $email);

            if ($response['success']) {
                add_settings_error('gridbase_license_messages', 'gridbase_license_success', __('License activated successfully.', 'gridbase-auth'), 'updated');
            } else {
                add_settings_error('gridbase_license_messages', 'gridbase_license_error', $response['message'], 'error');
            }
        } elseif ($action === 'deactivate') {
            $response = $this->manager->deactivate_remotely();
            
            if ($response['success']) {
                add_settings_error('gridbase_license_messages', 'gridbase_license_success', __('License deactivated.', 'gridbase-auth'), 'updated');
            } else {
                add_settings_error('gridbase_license_messages', 'gridbase_license_error', $response['message'], 'error');
            }
        } elseif ($action === 'auto_register') {
            $response = $this->manager->auto_register_remotely();
            if ($response['success']) {
                add_settings_error('gridbase_license_messages', 'gridbase_license_success', __('Plugin registered and activated successfully.', 'gridbase-auth'), 'updated');
            } else {
                add_settings_error('gridbase_license_messages', 'gridbase_license_error', $response['message'] ?? 'Failed to auto-register.', 'error');
            }
        }
    }

    public function render_page() {
        $is_active = $this->manager->is_active();
        $data = $this->manager->get_license_data();
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($this->plugin_name . ' License'); ?></h1>
            <?php settings_errors('gridbase_license_messages'); ?>

            <div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
                <?php if ($is_active): ?>
                    <h2 style="color: green;"><?php esc_html_e('Status: Active', 'gridbase-auth'); ?></h2>
                    <p><?php esc_html_e('Your plugin is activated and receiving updates & full features.', 'gridbase-auth'); ?></p>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('gridbase_license_nonce'); ?>
                        <input type="hidden" name="gridbase_license_action" value="deactivate">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('License Key', 'gridbase-auth'); ?></th>
                                <td>
                                    <input type="password" value="<?php echo esc_attr(str_repeat('*', strlen($data['key']) - 4) . substr($data['key'], -4)); ?>" class="regular-text" disabled>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-secondary"><?php esc_html_e('Deactivate License', 'gridbase-auth'); ?></button>
                        </p>
                    </form>
                <?php else: ?>
                    <h2 style="color: #d63638;"><?php esc_html_e('Status: Inactive', 'gridbase-auth'); ?></h2>
                    <p><?php esc_html_e('This plugin requires activation. Please click "Registrar" below to automatically activate it.', 'gridbase-auth'); ?></p>

                    <form method="post" action="">
                        <?php wp_nonce_field('gridbase_license_nonce'); ?>
                        <input type="hidden" name="gridbase_license_action" value="auto_register">
                        <p class="submit">
                            <button type="submit" class="button button-primary button-hero"><?php esc_html_e('Registrar', 'gridbase-auth'); ?></button>
                        </p>
                    </form>

                    <p style="margin-top:20px; font-size:12px; color:#666;">
                        <em><?php esc_html_e('Si ya tienes una clave de licencia (Plugins de Pago), la podrás introducir aquí en el futuro.', 'gridbase-auth'); ?></em>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function display_notices() {
        if (isset($_GET['page']) && $_GET['page'] === $this->menu_slug) {
            return; // Don't show notice on the license page itself
        }

        if (!$this->manager->is_active()) {
            $url = admin_url('options-general.php?page=' . $this->menu_slug);
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <strong><?php echo esc_html($this->plugin_name); ?>:</strong>
                    <?php esc_html_e('Your license is invalid or inactive. Please activate it to continue using all features.', 'gridbase-auth'); ?>
                    <a href="<?php echo esc_url($url); ?>"><?php esc_html_e('Activate Now', 'gridbase-auth'); ?></a>
                </p>
            </div>
            <?php
        }
    }

    public function display_glass_overlay() {
        if ($this->manager->is_active()) {
            return;
        }

        // Only show overlay on pages belonging to this plugin, but NOT the license page itself
        if (!isset($_GET['page']) || strpos($_GET['page'], $this->plugin_slug) === false || $_GET['page'] === $this->menu_slug) {
            return;
        }

        $url = admin_url('options-general.php?page=' . $this->menu_slug);
        
        ?>
        <style>
            .gridbase-auth-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 255, 0.6);
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                z-index: 9999;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
                pointer-events: auto; /* catch clicks inside overlay */
            }
            .gridbase-auth-overlay-box {
                background: #ffffff;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.15);
                border: 1px solid rgba(0,0,0,0.08);
                max-width: 400px;
            }
            .gridbase-auth-overlay-box h2 {
                margin-top: 0;
                font-size: 24px;
                color: #1d2327;
                font-weight: 600;
            }
            .gridbase-auth-overlay-box p {
                font-size: 15px;
                color: #50575e;
                margin-bottom: 25px;
                line-height: 1.5;
            }
            .gridbase-auth-overlay-box .button-hero {
                text-decoration: none;
                border-radius: 6px;
                display: inline-block;
            }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var wrap = document.querySelector('.wrap');
                if (wrap) {
                    wrap.style.pointerEvents = 'none'; // Lock all underlying forms/links
                    wrap.style.userSelect = 'none';
                    wrap.style.position = 'relative'; // Ensure overlay absolute positioning bounds to wrap
                    
                    var overlay = document.createElement('div');
                    overlay.className = 'gridbase-auth-overlay';
                    
                    overlay.innerHTML = `
                        <div class="gridbase-auth-overlay-box">
                            <span style="font-size: 48px; margin-bottom: 15px; display: block;">🔒</span>
                            <h2><?php esc_html_e('Licencia Requerida', 'gridbase-auth'); ?></h2>
                            <p><?php printf(esc_html__('El plugin %s se encuentra inactivo. Regístralo (GRATIS) para habilitar esta pantalla y comenzar a optimizar.', 'gridbase-auth'), '<strong>' . esc_html($this->plugin_name) . '</strong>'); ?></p>
                            <a href="<?php echo esc_url($url); ?>" class="button button-primary button-hero" style="pointer-events: auto;"><?php esc_html_e('Activar Plugin', 'gridbase-auth'); ?></a>
                        </div>
                    `;
                    
                    wrap.appendChild(overlay);
                }
            });
        </script>
        <?php
    }
}
