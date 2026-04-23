<?php
/**
 * FlowSoft WP — Asset Optimizer Module
 *
 * Optimizes scripts and styles: removes emojis, embeds, query strings, defers JS.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FlowSoft_Module_Assets implements FlowSoft_Module_Interface {

    private $logger;

    public function __construct( $logger ) {
        $this->logger = $logger;
    }

    public function get_id()          { return 'assets'; }
    public function get_name()        { return __( 'Optimizador de Assets', 'flowsoft-wp' ); }
    public function get_description() { return __( 'Elimina scripts de emojis, embeds, query strings de los assets y opcionalmente difiere la carga de JavaScript.', 'flowsoft-wp' ); }
    public function get_schedule()    { return 'immediate'; }

    public function get_icon() {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 16 4-4-4-4"/><path d="m6 8-4 4 4 4"/><path d="m14.5 4-5 16"/></svg>';
    }

    /**
     * Apply asset optimization (called on every page load).
     *
     * @param array $settings Module settings.
     */
    public function apply( $settings ) {
        // Disable WordPress emojis
        if ( ! empty( $settings['disable_emojis'] ) ) {
            add_action( 'init', array( $this, 'disable_emojis' ) );
        }

        // Disable oEmbed
        if ( ! empty( $settings['disable_embeds'] ) ) {
            add_action( 'init', array( $this, 'disable_embeds' ), 9999 );
        }

        // Remove query strings from static resources
        if ( ! empty( $settings['remove_query_strings'] ) ) {
            add_filter( 'script_loader_src', array( $this, 'remove_query_strings' ), 15 );
            add_filter( 'style_loader_src', array( $this, 'remove_query_strings' ), 15 );
        }

        // Defer JavaScript
        if ( ! empty( $settings['defer_js'] ) ) {
            add_filter( 'script_loader_tag', array( $this, 'defer_scripts' ), 10, 3 );
        }
    }

    /**
     * Disable WordPress emoji functionality.
     */
    public function disable_emojis() {
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        remove_action( 'admin_print_styles', 'print_emoji_styles' );
        remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
        remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
        remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

        add_filter( 'tiny_mce_plugins', function( $plugins ) {
            return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
        } );

        add_filter( 'wp_resource_hints', function( $urls, $relation_type ) {
            if ( 'dns-prefetch' === $relation_type ) {
                $emoji_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/' );
                $urls = array_filter( $urls, function( $url ) use ( $emoji_url ) {
                    return false === strpos( $url, $emoji_url );
                } );
            }
            return $urls;
        }, 10, 2 );
    }

    /**
     * Disable WordPress oEmbed.
     */
    public function disable_embeds() {
        // Remove oEmbed discovery links
        remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
        remove_action( 'wp_head', 'wp_oembed_add_host_js' );

        // Remove oEmbed REST API endpoint
        remove_action( 'rest_api_init', 'wp_oembed_register_route' );

        // Remove oEmbed-specific JavaScript
        add_filter( 'embed_oembed_discover', '__return_false' );

        // Remove filter that converts oEmbed URLs
        remove_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result', 10 );

        // Deregister wp-embed script
        add_action( 'wp_footer', function() {
            wp_deregister_script( 'wp-embed' );
        } );
    }

    /**
     * Remove version query strings from static resources.
     *
     * @param string $src Script/style URL.
     * @return string
     */
    public function remove_query_strings( $src ) {
        if ( strpos( $src, '?ver=' ) !== false || strpos( $src, '&ver=' ) !== false ) {
            $src = remove_query_arg( 'ver', $src );
        }
        return $src;
    }

    /**
     * Add defer attribute to non-critical scripts.
     *
     * @param string $tag    The script tag HTML.
     * @param string $handle The script handle.
     * @param string $src    The script source URL.
     * @return string
     */
    public function defer_scripts( $tag, $handle, $src ) {
        // Don't defer admin scripts or jQuery
        $excluded = array( 'jquery', 'jquery-core', 'jquery-migrate', 'wp-includes', 'admin-bar', 'flowsoft' );
        foreach ( $excluded as $exclude ) {
            if ( strpos( $handle, $exclude ) !== false ) {
                return $tag;
            }
        }

        // Don't defer if already has async or defer
        if ( strpos( $tag, 'defer' ) !== false || strpos( $tag, 'async' ) !== false ) {
            return $tag;
        }

        return str_replace( ' src=', ' defer src=', $tag );
    }

    /**
     * Run is not applicable for immediate modules.
     */
    public function run() {
        return array(
            'success' => true,
            'message' => __( 'Las optimizaciones de assets se aplican en tiempo real en cada carga de página.', 'flowsoft-wp' ),
            'items'   => 0,
            'bytes'   => 0,
        );
    }

    /**
     * Get asset stats.
     */
    public function get_stats() {
        $options  = get_option( 'flowsoft_modules', array() );
        $settings = isset( $options['assets'] ) ? $options['assets'] : array();

        return array(
            'emojis_disabled'  => ! empty( $settings['disable_emojis'] ),
            'embeds_disabled'  => ! empty( $settings['disable_embeds'] ),
            'query_strings'    => ! empty( $settings['remove_query_strings'] ),
            'js_deferred'      => ! empty( $settings['defer_js'] ),
        );
    }

    public function get_settings_fields() {
        return array(
            array(
                'id'          => 'disable_emojis',
                'label'       => __( 'Desactivar emojis de WordPress', 'flowsoft-wp' ),
                'description' => __( 'Elimina scripts y estilos de emojis que rara vez se necesitan. Mejora velocidad de carga.', 'flowsoft-wp' ),
                'type'        => 'toggle',
                'default'     => true,
            ),
            array(
                'id'          => 'disable_embeds',
                'label'       => __( 'Desactivar oEmbed', 'flowsoft-wp' ),
                'description' => __( 'Desactiva la funcionalidad de embeds automáticos de WordPress (YouTube, Twitter, etc.).', 'flowsoft-wp' ),
                'type'        => 'toggle',
                'default'     => true,
            ),
            array(
                'id'          => 'remove_query_strings',
                'label'       => __( 'Eliminar query strings de los assets', 'flowsoft-wp' ),
                'description' => __( 'Remueve ?ver=X.X de archivos CSS/JS para mejorar el caching en CDNs y proxies.', 'flowsoft-wp' ),
                'type'        => 'toggle',
                'default'     => true,
            ),
            array(
                'id'          => 'defer_js',
                'label'       => __( 'Diferir carga de JavaScript', 'flowsoft-wp' ),
                'description' => __( 'Añade defer a scripts para cargarlos después del HTML. Puede romper algunos plugins.', 'flowsoft-wp' ),
                'type'        => 'toggle',
                'default'     => false,
            ),
        );
    }
}
