<?php
/**
 * Plugin Name:       FlowSoft WP
 * Plugin URI:        https://gridbase.com.do/flowsoft-wp
 * Description:       Agente de Optimización Continua y Silenciosa para WordPress. Monitorea, limpia y optimiza tu sitio 24/7 en segundo plano.
 * Version:           1.3.0
 * Author:            Gridbase Digital Solutions
 * Author URI:        https://gridbase.com.do
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       flowsoft-wp
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*--------------------------------------------------------------
 * Plugin Constants
 *-------------------------------------------------------------*/
define( 'FLOWSOFT_VERSION', '1.3.0' );
define( 'FLOWSOFT_PLUGIN_FILE', __FILE__ );
define( 'FLOWSOFT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FLOWSOFT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FLOWSOFT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/* Thresholds & Limits */
define( 'FLOWSOFT_MAX_OVERHEAD_MB', 10 );
define( 'FLOWSOFT_MAX_EXPIRED_TRANSIENTS', 100 );
define( 'FLOWSOFT_MAX_REVISIONS_THRESHOLD', 500 );
define( 'FLOWSOFT_HEALTH_CACHE_TTL', 5 * MINUTE_IN_SECONDS );
define( 'FLOWSOFT_DB_OVERHEAD_CACHE_TTL', HOUR_IN_SECONDS );
define( 'FLOWSOFT_MAX_LOG_AGE_DAYS', 30 );
define( 'FLOWSOFT_MAX_LOG_ENTRIES', 10000 );
define( 'FLOWSOFT_RATE_LIMIT_MAX', 10 );
define( 'FLOWSOFT_RATE_LIMIT_WINDOW', 60 );

/* Setting Ranges */
define( 'FLOWSOFT_HEARTBEAT_MIN', 15 );
define( 'FLOWSOFT_HEARTBEAT_MAX', 300 );
define( 'FLOWSOFT_CACHE_TTL_MIN', 300 );
define( 'FLOWSOFT_CACHE_TTL_MAX', 31536000 );
define( 'FLOWSOFT_MAX_REVISIONS_MIN', 1 );
define( 'FLOWSOFT_MAX_REVISIONS_MAX', 100 );
define( 'FLOWSOFT_MAX_IMAGE_SIZE_MIN', 256 );
define( 'FLOWSOFT_MAX_IMAGE_SIZE_MAX', 8192 );

/*--------------------------------------------------------------
 * Activation / Deactivation hooks
 *-------------------------------------------------------------*/
require_once FLOWSOFT_PLUGIN_DIR . 'includes/class-flowsoft-activator.php';
require_once FLOWSOFT_PLUGIN_DIR . 'includes/class-flowsoft-deactivator.php';

register_activation_hook( __FILE__, array( 'FlowSoft_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'FlowSoft_Deactivator', 'deactivate' ) );

/*--------------------------------------------------------------
 * Core dependencies
 *-------------------------------------------------------------*/
require_once FLOWSOFT_PLUGIN_DIR . 'includes/class-flowsoft-logger.php';
require_once FLOWSOFT_PLUGIN_DIR . 'includes/class-flowsoft-scheduler.php';
require_once FLOWSOFT_PLUGIN_DIR . 'includes/interface-flowsoft-module.php';
require_once FLOWSOFT_PLUGIN_DIR . 'includes/class-flowsoft-core.php';

/*--------------------------------------------------------------
 * Modules
 *-------------------------------------------------------------*/
require_once FLOWSOFT_PLUGIN_DIR . 'modules/class-module-database.php';
require_once FLOWSOFT_PLUGIN_DIR . 'modules/class-module-transients.php';
require_once FLOWSOFT_PLUGIN_DIR . 'modules/class-module-heartbeat.php';
require_once FLOWSOFT_PLUGIN_DIR . 'modules/class-module-revisions.php';
require_once FLOWSOFT_PLUGIN_DIR . 'modules/class-module-assets.php';
require_once FLOWSOFT_PLUGIN_DIR . 'modules/class-module-cron.php';
require_once FLOWSOFT_PLUGIN_DIR . 'modules/class-module-media.php';
require_once FLOWSOFT_PLUGIN_DIR . 'modules/class-module-cache.php';

/*--------------------------------------------------------------
 * Admin
 *-------------------------------------------------------------*/
if ( is_admin() ) {
    require_once FLOWSOFT_PLUGIN_DIR . 'admin/class-flowsoft-admin.php';
    require_once FLOWSOFT_PLUGIN_DIR . 'admin/class-flowsoft-ajax.php';
}

/*--------------------------------------------------------------
 * Initialize the plugin
 *-------------------------------------------------------------*/
function flowsoft_wp_init() {
    load_plugin_textdomain( 'flowsoft-wp', false, dirname( plugin_basename( FLOWSOFT_PLUGIN_FILE ) ) . '/languages' );
    
    // Gridbase Auth Verificator Integration
    require_once FLOWSOFT_PLUGIN_DIR . 'includes/gridbase-auth/autoload.php';
    $auth = \Gridbase\Auth\GridbaseAuth::init('flowsoft-wp', 'FlowSoft WP', FLOWSOFT_VERSION);

    if ( ! $auth->is_active() ) {
        // Stop execution of the core plugin features when unlicensed
        return;
    }

    // Proceed with normal plugin booting
    $core = FlowSoft_Core::get_instance();
    $core->run();
}
add_action( 'plugins_loaded', 'flowsoft_wp_init' );
