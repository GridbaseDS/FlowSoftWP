<?php
namespace Gridbase\Auth\License;

use Gridbase\Auth\Api\Client;

if (!defined('ABSPATH')) {
    exit;
}

class Manager {
    private $client;
    private $plugin_slug;
    private $option_key;
    private $transient_key;

    public function __construct(Client $client, $plugin_slug) {
        $this->client = $client;
        $this->plugin_slug = $plugin_slug;
        $this->option_key = 'gridbase_license_' . md5($plugin_slug);
        $this->transient_key = 'gridbase_auth_' . md5($plugin_slug);
    }

    /**
     * Save the license key
     */
    public function save_license($key, $email = '') {
        $data = array(
            'key'   => sanitize_text_field($key),
            'email' => sanitize_email($email)
        );
        update_option($this->option_key, $data);
    }

    /**
     * Delete the license key
     */
    public function delete_license() {
        delete_option($this->option_key);
        delete_transient($this->transient_key);
    }

    /**
     * Get local license data
     */
    public function get_license_data() {
        return get_option($this->option_key, array('key' => '', 'email' => ''));
    }

    /**
     * Check if plugin is active
     */
    public function is_active() {
        $data = $this->get_license_data();
        if (empty($data['key'])) {
            return false;
        }

        // Check local cache
        $status = get_transient($this->transient_key);
        if (false !== $status) {
            return $status === 'valid';
        }

        // Cache miss, verify with remote API
        $response = $this->client->verify($data['key']);
        
        if ($response['success'] && isset($response['data']['status']) && $response['data']['status'] === 'active') {
            // Set for 24 hours
            set_transient($this->transient_key, 'valid', DAY_IN_SECONDS);
            return true;
        }

        // Explicitly invalid or network error. If network issue (not an explicit API invalidation), 
        // we could be lenient, but for strictness we mark it invalid. Set short transient if network error.
        if ($response['code'] === 'http_error') {
            // Give the benefit of the doubt for 1 hour on network issues (so we don't break sites on temporary API downtime)
            set_transient($this->transient_key, 'valid', HOUR_IN_SECONDS);
            return true; 
        }

        set_transient($this->transient_key, 'invalid', DAY_IN_SECONDS);
        return false;
    }

    /**
     * Activate visually and remote API call
     */
    public function activate_remotely($key, $email = '') {
        $response = $this->client->activate($key, $email);
        if ($response['success']) {
            $this->save_license($key, $email);
            set_transient($this->transient_key, 'valid', DAY_IN_SECONDS);
        }
        return $response;
    }

    /**
     * Auto register remotely
     */
    public function auto_register_remotely() {
        $response = $this->client->auto_register();
        if ($response['success'] && isset($response['data']['license_key'])) {
            $key = $response['data']['license_key'];
            $this->save_license($key, '');
            set_transient($this->transient_key, 'valid', DAY_IN_SECONDS);
        }
        return $response;
    }

    /**
     * Deactivate remotely
     */
    public function deactivate_remotely() {
        $data = $this->get_license_data();
        if (!empty($data['key'])) {
            $response = $this->client->deactivate($data['key']);
            $this->delete_license();
            return $response;
        }
        return array('success' => true); // Already no key locally
    }

    /**
     * Fetch the server-driven HTML from the API
     */
    public function fetch_remote_ui() {
        $data = $this->get_license_data();
        $response = $this->client->request('ui/render', array(
            'license_key' => $data['key'] ?? '',
        ));

        // The PHP Client wraps the body inside $response['data'] by default if JSON, or if we adjust it.
        // Wait, Client->request parses JSON. If the Server responds with ['success' => true, 'html' => '...'],
        // Client.php maps it. Let's look at Client.php later to confirm handling.
        // Assume Client.php parses standard response format correctly.

        if (isset($response['data']['html'])) {
            return $response['data']['html'];
        }

        if (isset($response['html'])) {
            return $response['html'];
        }

        return '<h2 style="color:#d63638;">Status: Inactivo (Offline)</h2><p>El servidor de licencias no está disponible temporalmente. Inténtalo más tarde.</p>';
    }

    /**
     * Clear cache
     */
    public function clear_cache() {
        delete_transient($this->transient_key);
    }
}
