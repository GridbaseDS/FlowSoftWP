<?php
namespace Gridbase\Auth;

use Gridbase\Auth\Api\Client;
use Gridbase\Auth\License\Manager;
use Gridbase\Auth\Admin\LicensePage;

if (!defined('ABSPATH')) {
    exit;
}

class GridbaseAuth {
    private static $instances = array();

    private $api_url;
    private $plugin_slug;
    private $plugin_name;
    private $plugin_version;

    private $client;
    private $manager;
    private $admin_page;

    /**
     * Initializes the Auth module for a specific plugin.
     * Use Singleton pattern per plugin slug to avoid multiple instantiations.
     */
    public static function init($plugin_slug, $plugin_name, $plugin_version, $api_url = 'https://auth.gridbase.com.do') {
        if (!isset(self::$instances[$plugin_slug])) {
            self::$instances[$plugin_slug] = new self($plugin_slug, $plugin_name, $plugin_version, $api_url);
        }
        return self::$instances[$plugin_slug];
    }

    private function __construct($plugin_slug, $plugin_name, $plugin_version, $api_url) {
        $this->plugin_slug = $plugin_slug;
        $this->plugin_name = $plugin_name;
        $this->plugin_version = $plugin_version;
        $this->api_url = $api_url;

        $this->setup();
    }

    private function setup() {
        // 1. Init API Client
        $this->client = new Client($this->api_url, $this->plugin_slug, $this->plugin_name, $this->plugin_version);

        // 2. Init Manager
        $this->manager = new Manager($this->client, $this->plugin_slug);

        // 3. Init Admin if in dashboard
        if (is_admin()) {
            $this->admin_page = new LicensePage($this->manager, $this->plugin_name, $this->plugin_slug);
        }
    }

    /**
     * Helper method for the host plugin to block features.
     * returns true if license is active and valid.
     */
    public function is_active() {
        return $this->manager->is_active();
    }

    /**
     * Get the license manager instance
     */
    public function get_manager() {
        return $this->manager;
    }
}
