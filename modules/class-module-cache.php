<?php
/**
 * FlowSoft WP — Cache Optimization Module
 *
 * Manages browser cache headers, page cache headers, DNS prefetch,
 * Object Cache diagnostics, and cache prewarming.
 *
 * Non-intrusive design: operates via HTTP headers and WP Object Cache API
 * without generating disk files or modifying .htaccess.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FlowSoft_Module_Cache implements FlowSoft_Module_Interface {

    private $logger;

    public function __construct( $logger ) {
        $this->logger = $logger;
    }

    public function get_id()          { return 'cache'; }
    public function get_name()        { return __( 'Optimizador de Caché', 'flowsoft-wp' ); }
    public function get_description() { return __( 'Gestiona headers de caché del navegador, caché de página para visitantes, DNS prefetch, y diagnósticos del Object Cache de WordPress.', 'flowsoft-wp' ); }
    public function get_schedule()    { return 'sixhours'; }

    public function get_icon() {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"/><path d="m16.2 7.8 2.9-2.9"/><path d="M18 12h4"/><path d="m16.2 16.2 2.9 2.9"/><path d="M12 18v4"/><path d="m4.9 19.1 2.9-2.9"/><path d="M2 12h4"/><path d="m4.9 4.9 2.9 2.9"/><circle cx="12" cy="12" r="4"/></svg>';
    }

    /**
     * Apply cache optimizations (called on every page load when module is enabled).
     *
     * @param array $settings Module settings.
     */
    public function apply( $settings ) {
        // Browser cache headers for static assets
        if ( ! empty( $settings['browser_cache'] ) ) {
            $ttl = isset( $settings['browser_cache_ttl'] ) ? (int) $settings['browser_cache_ttl'] : 2592000;
            add_action( 'send_headers', function() use ( $ttl ) {
                $this->send_browser_cache_headers( $ttl );
            } );
        }

        // Page cache headers for non-logged-in visitors
        if ( ! empty( $settings['page_cache_headers'] ) && ! is_admin() ) {
            $page_ttl = isset( $settings['page_cache_ttl'] ) ? (int) $settings['page_cache_ttl'] : 3600;
            add_action( 'send_headers', function() use ( $page_ttl ) {
                $this->send_page_cache_headers( $page_ttl );
            } );
        }

        // DNS prefetch and preconnect hints
        if ( ! empty( $settings['dns_prefetch'] ) ) {
            add_action( 'wp_head', array( $this, 'add_dns_prefetch' ), 1 );
            add_filter( 'wp_resource_hints', array( $this, 'add_resource_hints' ), 10, 2 );
        }
    }

    /**
     * Send browser cache headers for static resource requests.
     * Only applies when the request is for a recognized static file type.
     *
     * @param int $ttl Time-to-live in seconds.
     */
    private function send_browser_cache_headers( $ttl ) {
        // Only set headers on frontend, and only if headers haven't been sent
        if ( is_admin() || headers_sent() ) {
            return;
        }

        // Don't cache for logged-in users
        if ( is_user_logged_in() ) {
            return;
        }

        // Check if this is a static resource via query string
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
        $static_extensions = array( '.css', '.js', '.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg', '.woff', '.woff2', '.ttf', '.eot', '.ico' );

        $is_static = false;
        foreach ( $static_extensions as $ext ) {
            if ( false !== strpos( strtolower( $request_uri ), $ext ) ) {
                $is_static = true;
                break;
            }
        }

        if ( $is_static ) {
            header( 'Cache-Control: public, max-age=' . $ttl . ', immutable' );
            header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $ttl ) . ' GMT' );
            header( 'X-FlowSoft-Cache: static-asset' );
        }
    }

    /**
     * Send page cache headers for non-logged-in visitors.
     *
     * @param int $ttl Time-to-live in seconds.
     */
    private function send_page_cache_headers( $ttl ) {
        // Only on frontend, non-logged-in, non-AJAX, non-POST
        if ( is_admin() || headers_sent() || is_user_logged_in() ) {
            return;
        }

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }

        if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
            return;
        }

        // Don't cache POST requests, search results, or cart/checkout pages
        if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
            return;
        }

        // Skip WooCommerce cart/checkout/account pages
        if ( $this->is_woocommerce_dynamic_page() ) {
            header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
            header( 'X-FlowSoft-Cache: bypass-dynamic' );
            return;
        }

        // Skip search results
        if ( is_search() ) {
            header( 'Cache-Control: no-cache, max-age=0' );
            header( 'X-FlowSoft-Cache: bypass-search' );
            return;
        }

        // Apply page cache headers
        header( 'Cache-Control: public, max-age=' . $ttl . ', s-maxage=' . ( $ttl * 2 ) );
        header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $ttl ) . ' GMT' );
        header( 'X-FlowSoft-Cache: page-hit' );
        header( 'Vary: Accept-Encoding, Cookie' );
    }

    /**
     * Check if current page is a WooCommerce dynamic page (cart/checkout/account).
     *
     * @return bool
     */
    private function is_woocommerce_dynamic_page() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return false;
        }

        if ( function_exists( 'is_cart' ) && is_cart() ) {
            return true;
        }
        if ( function_exists( 'is_checkout' ) && is_checkout() ) {
            return true;
        }
        if ( function_exists( 'is_account_page' ) && is_account_page() ) {
            return true;
        }

        return false;
    }

    /**
     * Add DNS prefetch / preconnect hints to <head>.
     */
    public function add_dns_prefetch() {
        $domains = $this->get_external_domains();

        foreach ( $domains as $domain ) {
            printf(
                '<link rel="dns-prefetch" href="%s">' . "\n",
                esc_url( $domain )
            );
        }

        // Preconnect for critical resources
        $preconnect = array(
            '//fonts.googleapis.com',
            '//fonts.gstatic.com',
        );

        foreach ( $preconnect as $domain ) {
            printf(
                '<link rel="preconnect" href="%s" crossorigin>' . "\n",
                esc_url( $domain )
            );
        }
    }

    /**
     * Add resource hints via WordPress filter.
     *
     * @param array  $urls          URLs for hints.
     * @param string $relation_type Hint type.
     * @return array
     */
    public function add_resource_hints( $urls, $relation_type ) {
        if ( 'preconnect' === $relation_type ) {
            $urls[] = array(
                'href'        => 'https://fonts.gstatic.com',
                'crossorigin' => 'anonymous',
            );
        }

        if ( 'dns-prefetch' === $relation_type ) {
            $domains = $this->get_external_domains();
            foreach ( $domains as $domain ) {
                $urls[] = $domain;
            }
        }

        return array_unique( $urls, SORT_REGULAR );
    }



    /**
     * Get common external domains used by WordPress sites.
     *
     * @return array
     */
    private function get_external_domains() {
        $domains = array(
            '//fonts.googleapis.com',
            '//ajax.googleapis.com',
            '//cdnjs.cloudflare.com',
            '//cdn.jsdelivr.net',
            '//www.google-analytics.com',
            '//www.googletagmanager.com',
        );

        $domains = apply_filters( 'flowsoft_cache_prefetch_domains', $domains );

        // Validate all domains
        return array_filter( array_map( 'esc_url', $domains ) );
    }

    /**
     * Run cache optimization tasks (cron-based).
     *
     * @return array
     */
    public function run() {
        $details     = array();
        $total_items = 0;

        try {
            $options  = get_option( 'flowsoft_modules', array() );
            $settings = isset( $options['cache'] ) ? $options['cache'] : array();

            // 1. Object Cache health check
            $cache_info = $this->check_object_cache();
            if ( ! empty( $cache_info['message'] ) ) {
                $details[] = $cache_info['message'];
            }

            // 2. Cache prewarming (pre-load common queries into object cache)
            if ( ! empty( $settings['object_cache_prewarm'] ) ) {
                $prewarmed = $this->prewarm_cache();
                if ( $prewarmed > 0 ) {
                    $total_items += $prewarmed;
                    $details[] = sprintf( __( '%d consultas frecuentes precargadas en caché', 'flowsoft-wp' ), $prewarmed );
                }
            }

            // 3. Flush stale object cache groups
            $flushed = $this->flush_stale_cache();
            if ( $flushed > 0 ) {
                $total_items += $flushed;
                $details[] = sprintf( __( '%d entradas de caché obsoletas limpiadas', 'flowsoft-wp' ), $flushed );
            }

            // 4. Report cache headers status
            $headers_active = ! empty( $settings['browser_cache'] ) || ! empty( $settings['page_cache_headers'] );
            if ( $headers_active ) {
                $details[] = __( 'Headers de caché HTTP activos', 'flowsoft-wp' );
            }

            $message = ! empty( $details ) ? implode( '; ', $details ) : __( 'El sistema de caché está optimizado', 'flowsoft-wp' );
            $this->logger->log( $this->get_id(), 'optimize', $message, $total_items );

            return array( 'success' => true, 'message' => $message, 'items' => $total_items, 'bytes' => 0 );

        } catch ( \Exception $e ) {
            $this->logger->log( $this->get_id(), 'error', sprintf( 'Cache error: %s (L%d)', $e->getMessage(), $e->getLine() ), 0, 0, 'error' );
            return array( 'success' => false, 'message' => __( 'Error durante la optimización. Revisa los registros para más detalles.', 'flowsoft-wp' ) );
        }
    }

    /**
     * Check Object Cache health and type.
     *
     * @return array
     */
    private function check_object_cache() {
        global $wp_object_cache;

        $type       = __( 'Predeterminado (no persistente)', 'flowsoft-wp' );
        $persistent = false;

        // Detect persistent cache backends
        if ( defined( 'WP_REDIS_DISABLED' ) && ! WP_REDIS_DISABLED ) {
            $type       = 'Redis';
            $persistent = true;
        } elseif ( class_exists( 'Redis' ) && isset( $wp_object_cache ) && method_exists( $wp_object_cache, 'redis_instance' ) ) {
            $type       = 'Redis';
            $persistent = true;
        } elseif ( class_exists( 'Memcached' ) && isset( $wp_object_cache->mc ) ) {
            $type       = 'Memcached';
            $persistent = true;
        } elseif ( class_exists( 'Memcache' ) && isset( $wp_object_cache->mc ) ) {
            $type       = 'Memcache';
            $persistent = true;
        } elseif ( function_exists( 'apcu_enabled' ) && apcu_enabled() ) {
            $type       = 'APCu';
            $persistent = true;
        } elseif ( wp_using_ext_object_cache() ) {
            $type       = __( 'Externo (detectado)', 'flowsoft-wp' );
            $persistent = true;
        }

        // Try to get cache hit stats
        $hits   = 0;
        $misses = 0;
        if ( isset( $wp_object_cache ) ) {
            if ( isset( $wp_object_cache->cache_hits ) ) {
                $hits = (int) $wp_object_cache->cache_hits;
            }
            if ( isset( $wp_object_cache->cache_misses ) ) {
                $misses = (int) $wp_object_cache->cache_misses;
            }
        }

        $hit_ratio = ( $hits + $misses ) > 0 ? round( ( $hits / ( $hits + $misses ) ) * 100, 1 ) : 0;

        $message = sprintf(
            __( 'Object Cache: %s | Hit ratio: %s%% (%d hits / %d misses)', 'flowsoft-wp' ),
            $type,
            $hit_ratio,
            $hits,
            $misses
        );

        return array(
            'type'       => $type,
            'persistent' => $persistent,
            'hits'       => $hits,
            'misses'     => $misses,
            'hit_ratio'  => $hit_ratio,
            'message'    => $message,
        );
    }

    /**
     * Pre-warm the object cache with commonly accessed data.
     *
     * @return int Number of items prewarmed.
     */
    private function prewarm_cache() {
        $prewarmed = 0;

        // 1. Pre-warm frequently used options
        $critical_options = array(
            'siteurl', 'home', 'blogname', 'blogdescription',
            'active_plugins', 'template', 'stylesheet',
            'sidebars_widgets', 'widget_text', 'widget_block',
            'permalink_structure', 'rewrite_rules',
            'WPLANG', 'date_format', 'time_format', 'timezone_string',
        );

        foreach ( $critical_options as $option ) {
            // Force a read which populates the Object Cache
            $value = get_option( $option );
            if ( false !== $value ) {
                $prewarmed++;
            }
        }

        // 2. Pre-warm recent posts
        $recent_posts = get_posts( array(
            'numberposts'      => 10,
            'post_status'      => 'publish',
            'suppress_filters' => false,
            'fields'           => 'ids',
        ) );

        if ( ! is_wp_error( $recent_posts ) && ! empty( $recent_posts ) ) {
            // Update post caches in bulk
            _prime_post_caches( $recent_posts, true, true );
            $prewarmed += count( $recent_posts );
        }

        // 3. Pre-warm main navigation menus
        $menu_locations = get_nav_menu_locations();
        foreach ( $menu_locations as $location => $menu_id ) {
            if ( $menu_id > 0 ) {
                wp_get_nav_menu_items( $menu_id );
                $prewarmed++;
            }
        }

        return $prewarmed;
    }

    /**
     * Flush stale entries from the object cache.
     * Targets WordPress-specific cache groups that tend to accumulate stale data.
     *
     * @return int Number of cache groups flushed.
     */
    private function flush_stale_cache() {
        $flushed = 0;

        // Groups that can safely be flushed periodically
        $stale_groups = array(
            'counts',
            'plugins',
            'themes',
            'comment',
            'dashboard_feed',
        );

        foreach ( $stale_groups as $group ) {
            if ( function_exists( 'wp_cache_flush_group' ) ) {
                // WordPress 6.1+ supports group flushing
                wp_cache_flush_group( $group );
                $flushed++;
            } else {
                // Fallback: delete known keys from each group
                $known_keys = $this->get_known_cache_keys( $group );
                foreach ( $known_keys as $key ) {
                    if ( wp_cache_delete( $key, $group ) ) {
                        $flushed++;
                    }
                }
            }
        }

        return $flushed;
    }

    /**
     * Get known cache keys for specific cache groups.
     *
     * @param string $group Cache group name.
     * @return array
     */
    private function get_known_cache_keys( $group ) {
        $keys = array();

        switch ( $group ) {
            case 'counts':
                $keys = array( 'posts-publish', 'posts-draft', 'posts-trash' );
                break;
            case 'plugins':
                $keys = array( 'plugins', 'plugin_updates' );
                break;
            case 'themes':
                $keys = array( 'themes', 'theme_updates' );
                break;
            case 'dashboard_feed':
                $keys = array( 'dashboard_primary', 'dashboard_secondary' );
                break;
        }

        return $keys;
    }

    /**
     * Get cache stats for the module card.
     *
     * @return array
     */
    public function get_stats() {
        $cache_info = $this->check_object_cache();
        $options    = get_option( 'flowsoft_modules', array() );
        $settings   = isset( $options['cache'] ) ? $options['cache'] : array();

        return array(
            'cache_type' => $cache_info['type'],
            'hit_ratio'  => $cache_info['hit_ratio'] . '%',
            'headers'    => ( ! empty( $settings['browser_cache'] ) || ! empty( $settings['page_cache_headers'] ) ),
        );
    }

    /**
     * Get settings fields for the configuration panel.
     *
     * @return array
     */
    public function get_settings_fields() {
        return array(
            array(
                'id'      => 'browser_cache',
                'label'   => __( 'Headers de caché del navegador', 'flowsoft-wp' ),
                'type'    => 'toggle',
                'default' => true,
            ),
            array(
                'id'      => 'browser_cache_ttl',
                'label'   => __( 'TTL de assets estáticos (segundos)', 'flowsoft-wp' ),
                'type'    => 'number',
                'default' => 2592000,
                'min'     => 3600,
                'max'     => 31536000,
            ),
            array(
                'id'      => 'page_cache_headers',
                'label'   => __( 'Headers de caché de página', 'flowsoft-wp' ),
                'type'    => 'toggle',
                'default' => true,
            ),
            array(
                'id'      => 'page_cache_ttl',
                'label'   => __( 'TTL de caché de página (segundos)', 'flowsoft-wp' ),
                'type'    => 'number',
                'default' => 3600,
                'min'     => 300,
                'max'     => 86400,
            ),
            array(
                'id'      => 'object_cache_prewarm',
                'label'   => __( 'Precalentar Object Cache', 'flowsoft-wp' ),
                'type'    => 'toggle',
                'default' => true,
            ),
            array(
                'id'      => 'dns_prefetch',
                'label'   => __( 'DNS Prefetch y Preconnect', 'flowsoft-wp' ),
                'type'    => 'toggle',
                'default' => true,
            ),
        );
    }
}
