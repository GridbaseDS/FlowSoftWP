<?php
namespace Gridbase\Auth\Api;

if (!defined('ABSPATH')) {
    exit;
}

class Client {
    private $api_url;
    private $plugin_slug;
    private $plugin_name;
    private $plugin_version;
    private $domain;

    public function __construct($api_url, $plugin_slug, $plugin_name, $plugin_version) {
        $this->api_url = rtrim($api_url, '/');
        $this->plugin_slug = $plugin_slug;
        $this->plugin_name = $plugin_name;
        $this->plugin_version = $plugin_version;
        $this->domain = wp_parse_url(home_url(), PHP_URL_HOST);
    }

    /**
     * Common method to make API requests
     */
    public function request($endpoint, $body_params = array()) {
        $url = $this->api_url . '/api/v1/licenses/' . ltrim($endpoint, '/');

        $default_params = array(
            'domain'         => $this->domain,
            'plugin_slug'    => $this->plugin_slug,
            'plugin_version' => $this->plugin_version,
            'url'            => home_url(),
        );

        $body = array_merge($default_params, $body_params);

        $args = array(
            'body'        => json_encode($body),
            'headers'     => array(
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ),
            'timeout'     => 15,
            'data_format' => 'body',
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
                'code'    => 'http_error'
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body_response = wp_remote_retrieve_body($response);
        $data = json_decode($body_response, true);

        if ($response_code >= 200 && $response_code < 300) {
            return array(
                'success' => true,
                'data'    => isset($data['data']) ? $data['data'] : $data,
            );
        }

        return array(
            'success' => false,
            'message' => isset($data['message']) ? $data['message'] : 'API Error ' . $response_code,
            'code'    => isset($data['code']) ? $data['code'] : 'api_error_' . $response_code,
        );
    }

    /**
     * Activate the license
     */
    public function activate($license_key, $email = '') {
        return $this->request('activate', array(
            'license_key' => $license_key,
            'email'       => $email,
        ));
    }

    /**
     * Auto register for free plugins
     */
    public function auto_register() {
        return $this->request('auto-register');
    }

    /**
     * Verify the license condition (used by cron or transient cache miss)
     */
    public function verify($license_key) {
        return $this->request('verify', array(
            'license_key' => $license_key,
        ));
    }

    /**
     * Deactivate the license from this domain
     */
    public function deactivate($license_key) {
        return $this->request('deactivate', array(
            'license_key' => $license_key,
        ));
    }
}
