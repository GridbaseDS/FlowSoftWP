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
    }

    public function add_menu_page() {
        add_submenu_page(
            $this->plugin_slug,
            $this->plugin_name . ' — Licencia',
            '🔑 Licencia',
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
        // Fetch raw HTML from Laravel
        $remote_html = $this->manager->fetch_remote_ui();

        // Pass WP variables locally so the remote HTML can use strings like %%NONCE%% if needed.
        // The server sends `%%NONCE%%`, we replace it here with the real WP nonce so forms work.
        $nonce = wp_create_nonce('gridbase_license_nonce');
        $remote_html = str_replace('%%NONCE%%', $nonce, $remote_html);

        ?>
        <div class="wrap">
            <h1><?php echo esc_html($this->plugin_name . ' License'); ?></h1>
            <?php settings_errors('gridbase_license_messages'); ?>

            <div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
                <?php echo $remote_html; // OUTPUT SERVER-DRIVEN UI ?>
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
}
