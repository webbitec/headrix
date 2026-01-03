<?php
namespace Headrix\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class API {

    private static $header_elements = [];
    
    public static function init() {
        // AJAX handlers برای پنل ادمین
        add_action( 'wp_ajax_headrix_save_settings', [ __CLASS__, 'ajax_save_settings' ] );
        add_action( 'wp_ajax_headrix_reset_section', [ __CLASS__, 'ajax_reset_section' ] );
        add_action( 'wp_ajax_headrix_export_settings', [ __CLASS__, 'ajax_export_settings' ] );
        add_action( 'wp_ajax_headrix_import_settings', [ __CLASS__, 'ajax_import_settings' ] );
        add_action( 'wp_ajax_headrix_clear_cache', [ __CLASS__, 'ajax_clear_cache' ] );
        add_action( 'wp_ajax_headrix_get_debug_info', [ __CLASS__, 'ajax_get_debug_info' ] );
        
        // رجیستر کردن المنت‌های پیش‌فرض
        add_action( 'init', [ __CLASS__, 'register_default_elements' ], 5 );
    }
    
    /**
     * رجیستر کردن المنت‌های هدر
     */
    public static function register_header_element( $key, $callback ) {
        if ( is_callable( $callback ) ) {
            self::$header_elements[$key] = $callback;
        }
    }
    
    /**
     * گرفتن المنت‌های هدر
     */
    public static function get_header_elements() {
        return apply_filters( 'headrix/header/elements', self::$header_elements );
    }
    
    /**
     * رجیستر المنت‌های پیش‌فرض
     */
    public static function register_default_elements() {
        // المنت‌های پیش‌فرض
        $default_elements = [
            'logo'   => 'Headrix\Header\Elements\Logo::render',
            'menu'   => 'Headrix\Header\Elements\Menu::render',
            'search' => 'Headrix\Header\Elements\Search::render',
            'cart'   => 'Headrix\Header\Elements\Cart::render',
            'button' => 'Headrix\Header\Elements\Button::render',
            'social' => 'Headrix\Header\Elements\Social::render',
            'custom' => 'Headrix\Header\Elements\Custom::render',
        ];
        
        foreach ( $default_elements as $key => $callback ) {
            if ( is_callable( $callback ) ) {
                self::register_header_element( $key, $callback );
            }
        }
    }
    
    /**
     * AJAX handlers
     */
    
    public static function ajax_save_settings() {
        // بررسی nonce
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'headrix_admin_nonce' ) ) {
            wp_die( 'Invalid nonce' );
        }
        
        // پارس کردن داده‌های فرم
        parse_str( $_POST['form_data'] ?? '', $form_data );
        
        // حذف action و nonce
        unset( $form_data['_wpnonce'], $form_data['_wp_http_referer'], $form_data['option_page'] );
        unset( $form_data['action'], $form_data['submit'] );
        
        // ذخیره هر تنظیم
        foreach ( $form_data as $key => $value ) {
            if ( strpos( $key, 'headrix_' ) === 0 ) {
                update_option( $key, $value );
            }
        }
        
        wp_send_json_success( [ 'message' => __( 'Settings saved', 'headrix' ) ] );
    }
    
    public static function ajax_reset_section() {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'headrix_admin_nonce' ) ) {
            wp_die( 'Invalid nonce' );
        }
        
        $section = sanitize_key( $_POST['section'] ?? '' );
        $defaults = self::get_section_defaults( $section );
        
        foreach ( $defaults as $key => $value ) {
            update_option( $key, $value );
        }
        
        wp_send_json_success( [ 'message' => __( 'Section reset', 'headrix' ) ] );
    }
    
    public static function ajax_export_settings() {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'headrix_admin_nonce' ) ) {
            wp_die( 'Invalid nonce' );
        }
        
        $settings = [];
        $all_options = wp_load_alloptions();
        
        foreach ( $all_options as $key => $value ) {
            if ( strpos( $key, 'headrix_' ) === 0 ) {
                $settings[$key] = maybe_unserialize( $value );
            }
        }
        
        wp_send_json_success( $settings );
    }
    
    public static function ajax_import_settings() {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'headrix_admin_nonce' ) ) {
            wp_die( 'Invalid nonce' );
        }
        
        $settings = json_decode( stripslashes( $_POST['settings'] ?? '{}' ), true );
        
        if ( ! is_array( $settings ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid settings format', 'headrix' ) ] );
        }
        
        foreach ( $settings as $key => $value ) {
            if ( strpos( $key, 'headrix_' ) === 0 ) {
                update_option( $key, $value );
            }
        }
        
        wp_send_json_success( [ 'message' => __( 'Settings imported', 'headrix' ) ] );
    }
    
    public static function ajax_clear_cache() {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'headrix_admin_nonce' ) ) {
            wp_die( 'Invalid nonce' );
        }
        
        // پاک کردن کش‌های احتمالی
        wp_cache_flush();
        
        // حذف فایل‌های CSS/JS کش شده
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/headrix-cache/';
        
        if ( file_exists( $cache_dir ) ) {
            array_map( 'unlink', glob( $cache_dir . '*' ) );
            rmdir( $cache_dir );
        }
        
        wp_send_json_success( [ 'message' => __( 'Cache cleared', 'headrix' ) ] );
    }
    
    public static function ajax_get_debug_info() {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'headrix_admin_nonce' ) ) {
            wp_die( 'Invalid nonce' );
        }
        
        $info = [
            'wordpress' => [
                'version' => get_bloginfo( 'version' ),
                'multisite' => is_multisite(),
                'language' => get_locale(),
            ],
            'headrix' => [
                'version' => HEADRIX_VERSION,
                'override_status' => Override::get_status(),
                'active_modules' => self::get_active_modules(),
                'header_elements' => array_keys( self::get_header_elements() ),
            ],
            'theme' => [
                'name' => wp_get_theme()->get( 'Name' ),
                'version' => wp_get_theme()->get( 'Version' ),
                'parent' => wp_get_theme()->parent() ? wp_get_theme()->parent()->get( 'Name' ) : 'None',
            ],
            'php' => [
                'version' => PHP_VERSION,
                'memory_limit' => ini_get( 'memory_limit' ),
                'max_execution_time' => ini_get( 'max_execution_time' ),
            ],
        ];
        
        wp_send_json_success( $info );
    }
    
    private static function get_section_defaults( $section ) {
        $defaults = [
            'general' => [
                'headrix_menu_override' => 1,
                'headrix_target_menu' => 0,
                'headrix_enable_sticky' => 1,
                'headrix_enable_search' => 0,
                'headrix_enable_social' => 0,
                'headrix_enable_cart' => 0,
            ],
            'header' => [
                'headrix_header_layout' => 'standard',
                'headrix_logo_position' => 'left',
                'headrix_header_height' => 80,
                'headrix_header_padding' => '20px 0',
                'headrix_container_width' => 1200,
            ],
            'menu' => [
                'headrix_menu_animation' => 'fade',
                'headrix_submenu_width' => 200,
                'headrix_mega_menu' => 0,
                'headrix_menu_hover_effect' => 'underline',
                'headrix_dropdown_animation' => 'fade',
            ],
            'mobile' => [
                'headrix_breakpoint' => 768,
                'headrix_mobile_menu_style' => 'slide',
                'headrix_hamburger_size' => 22,
                'headrix_mobile_toggle_position' => 'right',
                'headrix_mobile_logo_size' => 40,
            ],
            'styling' => [
                'headrix_bg_color' => '#ffffff',
                'headrix_text_color' => '#333333',
                'headrix_link_color' => '#0073aa',
                'headrix_hover_color' => '#005a87',
                'headrix_font_family' => 'inherit',
                'headrix_font_size' => 16,
            ],
            'advanced' => [
                'headrix_custom_css' => '',
                'headrix_custom_js' => '',
            ],
        ];
        
        return $defaults[$section] ?? [];
    }
    
    private static function get_active_modules() {
        return [
            'override' => class_exists( 'Headrix\Core\Override' ),
            'builder' => class_exists( 'Headrix\Header\Builder' ),
            'sticky' => class_exists( 'Headrix\Header\Sticky' ),
            'megamenu' => class_exists( 'Headrix\Menu\MegaMenu' ),
            'api' => class_exists( __CLASS__ ),
        ];
    }
    
    /**
     * متدهای کمکی
     */
    public static function get_option( $key, $default = '' ) {
        return get_option( 'headrix_' . $key, $default );
    }
    
    public static function update_option( $key, $value ) {
        return update_option( 'headrix_' . $key, $value );
    }
    
    public static function delete_option( $key ) {
        return delete_option( 'headrix_' . $key );
    }
}