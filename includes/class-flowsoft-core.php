<?php
/**
 * FlowSoft WP — Core Orchestrator
 *
 * Singleton that initializes all modules and registers hooks.
 *
 * @package FlowSoft_WP
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FlowSoft_Core {

    /** @var FlowSoft_Core|null */
    private static $instance = null;

    /** @var array Module instances */
    private $modules = array();

    /** @var FlowSoft_Logger */
    private $logger;

    /** @var FlowSoft_Scheduler */
    private $scheduler;

    private function __construct() {
        $this->logger    = FlowSoft_Logger::get_instance();
        $this->scheduler = FlowSoft_Scheduler::get_instance();
    }

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Boot all modules and register hooks.
     */
    public function run() {
        // Register custom cron intervals
        add_filter( 'cron_schedules', array( $this->scheduler, 'register_intervals' ) );

        // Initialize modules
        $this->load_modules();

        // Register cron handlers
        add_action( 'flowsoft_daily_optimization',    array( $this, 'run_daily_optimizations' ) );
        add_action( 'flowsoft_sixhours_optimization', array( $this, 'run_sixhours_optimizations' ) );
        add_action( 'flowsoft_weekly_optimization',   array( $this, 'run_weekly_optimizations' ) );
        add_action( 'flowsoft_monthly_log_cleanup',   array( $this, 'run_monthly_log_cleanup' ) );

        // Initialize admin if in dashboard
        if ( is_admin() ) {
            FlowSoft_Admin::get_instance()->init();
            FlowSoft_Ajax::get_instance()->init();
        }

        // Apply immediate-effect modules on every load
        $this->apply_immediate_modules();

        // Schedule monthly log cleanup if not already scheduled
        if ( ! wp_next_scheduled( 'flowsoft_monthly_log_cleanup' ) ) {
            wp_schedule_event( time(), 'monthly', 'flowsoft_monthly_log_cleanup' );
        }
    }

    /**
     * Load and initialize all optimization modules.
     */
    private function load_modules() {
        $module_classes = array(
            'database'   => 'FlowSoft_Module_Database',
            'transients' => 'FlowSoft_Module_Transients',
            'heartbeat'  => 'FlowSoft_Module_Heartbeat',
            'revisions'  => 'FlowSoft_Module_Revisions',
            'assets'     => 'FlowSoft_Module_Assets',
            'cron'       => 'FlowSoft_Module_Cron',
            'media'      => 'FlowSoft_Module_Media',
            'cache'      => 'FlowSoft_Module_Cache',
        );

        foreach ( $module_classes as $id => $class ) {
            if ( class_exists( $class ) ) {
                $this->modules[ $id ] = new $class( $this->logger );
            }
        }
    }

    /**
     * Apply modules that take effect immediately (not cron-based).
     */
    private function apply_immediate_modules() {
        $options = get_option( 'flowsoft_modules', array() );

        // Heartbeat Control
        if ( isset( $this->modules['heartbeat'] ) && ! empty( $options['heartbeat']['enabled'] ) ) {
            $this->modules['heartbeat']->apply( $options['heartbeat'] );
        }

        // Asset Optimization
        if ( isset( $this->modules['assets'] ) && ! empty( $options['assets']['enabled'] ) ) {
            $this->modules['assets']->apply( $options['assets'] );
        }

        // Revision limiter
        if ( isset( $this->modules['revisions'] ) && ! empty( $options['revisions']['enabled'] ) ) {
            $this->modules['revisions']->apply( $options['revisions'] );
        }

        // Cache optimization
        if ( isset( $this->modules['cache'] ) && ! empty( $options['cache']['enabled'] ) ) {
            $this->modules['cache']->apply( $options['cache'] );
        }
    }

    /**
     * Run daily optimizations (database, cron health).
     */
    public function run_daily_optimizations() {
        $options = get_option( 'flowsoft_modules', array() );

        if ( isset( $this->modules['database'] ) && ! empty( $options['database']['enabled'] ) ) {
            $this->modules['database']->run();
        }

        if ( isset( $this->modules['cron'] ) && ! empty( $options['cron']['enabled'] ) ) {
            $this->modules['cron']->run();
        }

        // Clear health score cache after optimizations
        delete_transient( 'flowsoft_health_score' );
    }

    /**
     * Run 6-hour optimizations (transients).
     */
    public function run_sixhours_optimizations() {
        $options = get_option( 'flowsoft_modules', array() );

        if ( isset( $this->modules['transients'] ) && ! empty( $options['transients']['enabled'] ) ) {
            $this->modules['transients']->run();
        }

        if ( isset( $this->modules['cache'] ) && ! empty( $options['cache']['enabled'] ) ) {
            $this->modules['cache']->run();
        }

        // Clear health score cache after optimizations
        delete_transient( 'flowsoft_health_score' );
    }

    /**
     * Run weekly optimizations (revisions cleanup, media).
     */
    public function run_weekly_optimizations() {
        $options = get_option( 'flowsoft_modules', array() );

        if ( isset( $this->modules['revisions'] ) && ! empty( $options['revisions']['enabled'] ) ) {
            $this->modules['revisions']->run();
        }

        if ( isset( $this->modules['media'] ) && ! empty( $options['media']['enabled'] ) ) {
            $this->modules['media']->run();
        }

        // Clear health score cache after optimizations
        delete_transient( 'flowsoft_health_score' );
    }

    /**
     * Run monthly log cleanup.
     *
     * @since 1.3.0
     */
    public function run_monthly_log_cleanup() {
        $this->logger->cleanup_old_logs();
    }

    /**
     * Get a specific module instance.
     *
     * @param string $id Module ID.
     * @return object|null
     */
    public function get_module( $id ) {
        return isset( $this->modules[ $id ] ) ? $this->modules[ $id ] : null;
    }

    /**
     * Get all module instances.
     *
     * @return array
     */
    public function get_modules() {
        return $this->modules;
    }

    /**
     * Get the logger instance.
     *
     * @return FlowSoft_Logger
     */
    public function get_logger() {
        return $this->logger;
    }

    /**
     * Get the scheduler instance.
     *
     * @return FlowSoft_Scheduler
     */
    public function get_scheduler() {
        return $this->scheduler;
    }

    /**
     * Run a specific module manually.
     *
     * @param string $module_id Module identifier.
     * @return array Result with 'success' and 'message'.
     */
    public function run_module( $module_id ) {
        if ( ! isset( $this->modules[ $module_id ] ) ) {
            return array( 'success' => false, 'message' => __( 'Módulo no encontrado.', 'flowsoft-wp' ) );
        }

        $options = get_option( 'flowsoft_modules', array() );

        if ( empty( $options[ $module_id ]['enabled'] ) ) {
            return array( 'success' => false, 'message' => __( 'El módulo está desactivado.', 'flowsoft-wp' ) );
        }

        $result = $this->modules[ $module_id ]->run();

        // Clear health score cache after running optimization
        delete_transient( 'flowsoft_health_score' );

        return $result;
    }

    /**
     * Calculate the overall health score (0-100).
     * Uses transient cache to improve performance.
     *
     * @param bool $force_refresh Force recalculation ignoring cache.
     * @return int Health score between 0 and 100.
     */
    public function calculate_health_score( $force_refresh = false ) {
        // Check cache first
        if ( ! $force_refresh ) {
            $cached_score = get_transient( 'flowsoft_health_score' );
            if ( false !== $cached_score ) {
                return (int) $cached_score;
            }
        }

        $score = 100;

        $score -= $this->calculate_module_penalty();
        $score -= $this->check_database_overhead();
        $score -= $this->check_expired_transients();
        $score -= $this->check_post_health();
        $score -= $this->check_comment_health();

        $final_score = max( 0, min( 100, $score ) );

        set_transient( 'flowsoft_health_score', $final_score, FLOWSOFT_HEALTH_CACHE_TTL );

        return $final_score;
    }

    /**
     * Calculate penalty for disabled modules.
     *
     * @return int Penalty points (0-20).
     */
    private function calculate_module_penalty() {
        $options       = get_option( 'flowsoft_modules', array() );
        $enabled_count = 0;

        foreach ( $options as $config ) {
            if ( ! empty( $config['enabled'] ) ) {
                $enabled_count++;
            }
        }

        $total_modules = count( $this->modules );

        return $total_modules > 0 ? (int) round( ( 1 - $enabled_count / $total_modules ) * 20 ) : 0;
    }

    /**
     * Check database overhead and return penalty.
     * Caches information_schema result for 1 hour.
     *
     * @return int Penalty points (0-15).
     */
    private function check_database_overhead() {
        $overhead = get_transient( 'flowsoft_db_overhead' );

        if ( false === $overhead ) {
            global $wpdb;
            $db_name  = $wpdb->dbname;
            $overhead = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT SUM(data_free) FROM information_schema.tables WHERE table_schema = %s",
                $db_name
            ) );
            set_transient( 'flowsoft_db_overhead', $overhead, FLOWSOFT_DB_OVERHEAD_CACHE_TTL );
        }

        $threshold = FLOWSOFT_MAX_OVERHEAD_MB * 1024 * 1024;

        if ( $overhead > $threshold ) {
            return 15;
        } elseif ( $overhead > ( $threshold / 2 ) ) {
            return 8;
        }

        return 0;
    }

    /**
     * Check expired transients and return penalty.
     *
     * @return int Penalty points (0-15).
     */
    private function check_expired_transients() {
        global $wpdb;

        $expired = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
            $wpdb->esc_like( '_transient_timeout_' ) . '%',
            time()
        ) );

        if ( $expired > FLOWSOFT_MAX_EXPIRED_TRANSIENTS ) {
            return 15;
        } elseif ( $expired > 20 ) {
            return 5;
        }

        return 0;
    }

    /**
     * Check post revisions, auto-drafts, and trash. Return penalty.
     *
     * @return int Penalty points (0-20).
     */
    private function check_post_health() {
        global $wpdb;
        $penalty = 0;

        // Revisions
        $revisions = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
            'revision'
        ) );
        if ( $revisions > FLOWSOFT_MAX_REVISIONS_THRESHOLD ) {
            $penalty += 10;
        } elseif ( $revisions > 100 ) {
            $penalty += 5;
        }

        // Auto-drafts and trash
        $trash = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status IN (%s, %s)",
            'auto-draft',
            'trash'
        ) );
        if ( $trash > 50 ) {
            $penalty += 10;
        } elseif ( $trash > 10 ) {
            $penalty += 3;
        }

        return $penalty;
    }

    /**
     * Check spam comments and return penalty.
     *
     * @return int Penalty points (0-10).
     */
    private function check_comment_health() {
        global $wpdb;

        $spam = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = %s",
            'spam'
        ) );

        if ( $spam > 100 ) {
            return 10;
        } elseif ( $spam > 20 ) {
            return 5;
        }

        return 0;
    }
}
