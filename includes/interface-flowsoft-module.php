<?php
/**
 * FlowSoft WP — Module Interface
 *
 * Defines the contract that all optimization modules must implement.
 * Ensures consistent API across all modules.
 *
 * @package FlowSoft_WP
 * @since   1.3.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface FlowSoft_Module_Interface {

    /**
     * Get unique module identifier.
     *
     * @return string
     */
    public function get_id();

    /**
     * Get human-readable module name.
     *
     * @return string
     */
    public function get_name();

    /**
     * Get module description.
     *
     * @return string
     */
    public function get_description();

    /**
     * Get the cron schedule type for this module.
     *
     * @return string One of 'daily', 'sixhours', 'weekly', 'immediate'.
     */
    public function get_schedule();

    /**
     * Get the SVG icon markup for the module card.
     *
     * @return string SVG HTML string.
     */
    public function get_icon();

    /**
     * Run the module's optimization task.
     *
     * @return array {
     *     @type bool   $success Whether the operation succeeded.
     *     @type string $message Human-readable result message.
     *     @type int    $items   Number of items processed.
     *     @type int    $bytes   Bytes freed (if applicable).
     * }
     */
    public function run();

    /**
     * Get current stats for the dashboard module card.
     *
     * @return array Associative array of stats.
     */
    public function get_stats();

    /**
     * Get settings fields for the configuration panel.
     *
     * @return array Array of field definitions.
     */
    public function get_settings_fields();
}
