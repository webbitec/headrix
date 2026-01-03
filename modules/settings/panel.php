<?php
namespace Headrix\Settings;

use Headrix\Core\Helpers;
use Headrix\Core\Security;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Panel {

    private static $current_tab = 'general';
    private static $tabs = [];
    
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_settings_page' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
        
        // تعریف تب‌ها
        self::$tabs = [
            'general'     => __( 'General', 'headrix' ),
            'header'      => __( 'Header Layout', 'headrix' ),
            'menu'        => __( 'Menu Settings', 'headrix' ),
            'mobile'      => __( 'Mobile Menu', 'headrix' ),
            'styling'     => __( 'Styling', 'headrix' ),
            'advanced'    => __( 'Advanced', 'headrix' ),
            'importexport'=> __( 'Import/Export', 'headrix' ),
        ];
    }
    
    public static function enqueue_admin_assets( $hook ) {
        if ( strpos( $hook, 'headrix-settings' ) === false ) {
            return;
        }
        
        // استایل اختصاصی برای پنل
        wp_enqueue_style( 
            'headrix-admin-panel', 
            HEADRIX_PLUGIN_URL . 'assets/css/admin-panel.css', 
            [], 
            HEADRIX_VERSION 
        );
        
        // اسکریپت اختصاصی
        wp_enqueue_script( 
            'headrix-admin-panel', 
            HEADRIX_PLUGIN_URL . 'assets/js/admin-panel.js', 
            [ 'jquery', 'wp-color-picker', 'jquery-ui-tabs' ], 
            HEADRIX_VERSION, 
            true 
        );
        
        // لوکالایز کردن
        wp_localize_script( 'headrix-admin-panel', 'headrixAdmin', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'headrix_admin_nonce' ),
            'strings'  => [
                'saving'   => __( 'Saving...', 'headrix' ),
                'saved'    => __( 'Settings Saved!', 'headrix' ),
                'error'    => __( 'Error saving settings.', 'headrix' ),
                'confirm_reset' => __( 'Are you sure you want to reset all settings?', 'headrix' ),
                'confirm_import' => __( 'This will overwrite all current settings. Continue?', 'headrix' ),
            ]
        ] );
    }
    
    public static function add_settings_page() {
        add_menu_page(
            __( 'Headrix Settings', 'headrix' ),
            __( 'Headrix', 'headrix' ),
            'manage_options',
            'headrix-settings',
            [ __CLASS__, 'render_settings_page' ],
            'dashicons-menu-alt',
            60
        );
        
        // زیرمنو برای مستندات
        add_submenu_page(
            'headrix-settings',
            __( 'Documentation', 'headrix' ),
            __( 'Documentation', 'headrix' ),
            'manage_options',
            'headrix-docs',
            [ __CLASS__, 'render_docs_page' ]
        );
        
        // زیرمنو برای پشتیبانی
        add_submenu_page(
            'headrix-settings',
            __( 'Support', 'headrix' ),
            __( 'Support', 'headrix' ),
            'manage_options',
            'headrix-support',
            [ __CLASS__, 'render_support_page' ]
        );
    }
    
    public static function register_settings() {
        // گرفتن تب جاری
        self::$current_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], self::$tabs ) 
            ? sanitize_key( $_GET['tab'] ) 
            : 'general';
        
        // ثبت تنظیمات هر تب
        switch ( self::$current_tab ) {
            case 'general':
                self::register_general_settings();
                break;
            case 'header':
                self::register_header_settings();
                break;
            case 'menu':
                self::register_menu_settings();
                break;
            case 'mobile':
                self::register_mobile_settings();
                break;
            case 'styling':
                self::register_styling_settings();
                break;
            case 'advanced':
                self::register_advanced_settings();
                break;
            case 'importexport':
                self::register_importexport_settings();
                break;
        }
    }
    
    /**
     * ============================================
     * ثبت تنظیمات هر تب
     * ============================================
     */
    
    private static function register_general_settings() {
        register_setting( 'headrix_general_group', 'headrix_menu_override', [
            'type' => 'boolean',
            'sanitize_callback' => 'absint',
            'default' => 1
        ] );
        
        register_setting( 'headrix_general_group', 'headrix_target_menu', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 0
        ] );
        
        register_setting( 'headrix_general_group', 'headrix_enable_sticky', [
            'type' => 'boolean',
            'sanitize_callback' => 'absint',
            'default' => 1
        ] );
        
        register_setting( 'headrix_general_group', 'headrix_enable_search', [
            'type' => 'boolean',
            'sanitize_callback' => 'absint',
            'default' => 0
        ] );
        
        register_setting( 'headrix_general_group', 'headrix_enable_social', [
            'type' => 'boolean',
            'sanitize_callback' => 'absint',
            'default' => 0
        ] );
        
        register_setting( 'headrix_general_group', 'headrix_enable_cart', [
            'type' => 'boolean',
            'sanitize_callback' => 'absint',
            'default' => 0
        ] );
        
        register_setting( 'headrix_general_group', 'headrix_enable_button', [
            'type' => 'boolean',
            'sanitize_callback' => 'absint',
            'default' => 0
        ] );
        
        register_setting( 'headrix_general_group', 'headrix_button_text', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => __( 'Contact Us', 'headrix' )
        ] );
        
        register_setting( 'headrix_general_group', 'headrix_button_url', [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => '#'
        ] );

        // سکشن عمومی
        add_settings_section(
            'headrix_general_section',
            __( 'General Settings', 'headrix' ),
            [ __CLASS__, 'render_section_description' ],
            'headrix-settings'
        );

        // فیلد انتخاب منو
        add_settings_field(
            'headrix_target_menu',
            __( 'Select Menu', 'headrix' ),
            [ __CLASS__, 'render_menu_select' ],
            'headrix-settings',
            'headrix_general_section',
            [
                'label_for' => 'headrix_target_menu',
                'description' => __( 'Select which WordPress menu to display in Headrix header.', 'headrix' )
            ]
        );

        // فیلد فعال‌سازی منو
        add_settings_field(
            'headrix_menu_override',
            __( 'Enable Headrix', 'headrix' ),
            [ __CLASS__, 'render_toggle' ],
            'headrix-settings',
            'headrix_general_section',
            [
                'label_for' => 'headrix_menu_override',
                'description' => __( 'Enable Headrix to replace theme header and menus.', 'headrix' )
            ]
        );

        // فیلدهای فعال‌سازی ویژگی‌ها
        $features = [
            'headrix_enable_sticky' => __( 'Sticky Header', 'headrix' ),
            'headrix_enable_search' => __( 'Search Box', 'headrix' ),
            'headrix_enable_social' => __( 'Social Icons', 'headrix' ),
            'headrix_enable_cart'   => __( 'Shopping Cart', 'headrix' ),
            'headrix_enable_button' => __( 'Call to Action Button', 'headrix' ),
        ];
        
        foreach ( $features as $key => $label ) {
            add_settings_field(
                $key,
                $label,
                [ __CLASS__, 'render_toggle' ],
                'headrix-settings',
                'headrix_general_section',
                [
                    'label_for' => $key,
                    'description' => ''
                ]
            );
        }
        
        // فیلد متن دکمه
        add_settings_field(
            'headrix_button_text',
            __( 'Button Text', 'headrix' ),
            [ __CLASS__, 'render_text' ],
            'headrix-settings',
            'headrix_general_section',
            [
                'label_for' => 'headrix_button_text',
                'description' => __( 'Text for call to action button.', 'headrix' )
            ]
        );
        
        // فیلد URL دکمه
        add_settings_field(
            'headrix_button_url',
            __( 'Button URL', 'headrix' ),
            [ __CLASS__, 'render_text' ],
            'headrix-settings',
            'headrix_general_section',
            [
                'label_for' => 'headrix_button_url',
                'description' => __( 'URL for call to action button.', 'headrix' )
            ]
        );
    }
    
    private static function register_header_settings() {
        register_setting( 'headrix_header_group', 'headrix_header_layout', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_key',
            'default' => 'standard'
        ] );
        
        register_setting( 'headrix_header_group', 'headrix_logo_position', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_key',
            'default' => 'left'
        ] );
        
        register_setting( 'headrix_header_group', 'headrix_header_height', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 80
        ] );
        
        register_setting( 'headrix_header_group', 'headrix_header_padding', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '20px 0'
        ] );
        
        register_setting( 'headrix_header_group', 'headrix_container_width', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 1200
        ] );
        
        register_setting( 'headrix_header_group', 'headrix_logo_url', [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ] );
        
        register_setting( 'headrix_header_group', 'headrix_logo_width', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 150
        ] );
        
        register_setting( 'headrix_header_group', 'headrix_logo_height', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 50
        ] );

        // سکشن لایه‌بندی
        add_settings_section(
            'headrix_header_section',
            __( 'Header Layout', 'headrix' ),
            [ __CLASS__, 'render_section_description' ],
            'headrix-settings'
        );

        // فیلد نوع لایه‌بندی
        add_settings_field(
            'headrix_header_layout',
            __( 'Header Layout', 'headrix' ),
            [ __CLASS__, 'render_select' ],
            'headrix-settings',
            'headrix_header_section',
            [
                'label_for' => 'headrix_header_layout',
                'options' => [
                    'standard' => __( 'Standard', 'headrix' ),
                    'centered' => __( 'Centered Logo', 'headrix' ),
                    'split'    => __( 'Split Menu', 'headrix' ),
                    'stacked'  => __( 'Stacked', 'headrix' ),
                ],
                'description' => __( 'Choose header layout style.', 'headrix' )
            ]
        );

        // فیلد موقعیت لوگو
        add_settings_field(
            'headrix_logo_position',
            __( 'Logo Position', 'headrix' ),
            [ __CLASS__, 'render_select' ],
            'headrix-settings',
            'headrix_header_section',
            [
                'label_for' => 'headrix_logo_position',
                'options' => [
                    'left'   => __( 'Left', 'headrix' ),
                    'center' => __( 'Center', 'headrix' ),
                    'right'  => __( 'Right', 'headrix' ),
                ],
                'description' => __( 'Position of logo in header.', 'headrix' )
            ]
        );
        
        // فیلد URL لوگو
        add_settings_field(
            'headrix_logo_url',
            __( 'Custom Logo URL', 'headrix' ),
            [ __CLASS__, 'render_media_upload' ],
            'headrix-settings',
            'headrix_header_section',
            [
                'label_for' => 'headrix_logo_url',
                'description' => __( 'Upload or select a custom logo.', 'headrix' )
            ]
        );
        
        // فیلد عرض لوگو
        add_settings_field(
            'headrix_logo_width',
            __( 'Logo Width', 'headrix' ),
            [ __CLASS__, 'render_range' ],
            'headrix-settings',
            'headrix_header_section',
            [
                'label_for' => 'headrix_logo_width',
                'min' => 50,
                'max' => 300,
                'step' => 5,
                'unit' => 'px',
                'description' => __( 'Width of logo in pixels.', 'headrix' )
            ]
        );
        
        // فیلد ارتفاع لوگو
        add_settings_field(
            'headrix_logo_height',
            __( 'Logo Height', 'headrix' ),
            [ __CLASS__, 'render_range' ],
            'headrix-settings',
            'headrix_header_section',
            [
                'label_for' => 'headrix_logo_height',
                'min' => 20,
                'max' => 100,
                'step' => 5,
                'unit' => 'px',
                'description' => __( 'Height of logo in pixels.', 'headrix' )
            ]
        );

        // فیلد ارتفاع هدر
        add_settings_field(
            'headrix_header_height',
            __( 'Header Height', 'headrix' ),
            [ __CLASS__, 'render_range' ],
            'headrix-settings',
            'headrix_header_section',
            [
                'label_for' => 'headrix_header_height',
                'min' => 40,
                'max' => 200,
                'step' => 5,
                'unit' => 'px',
                'description' => __( 'Height of header in pixels.', 'headrix' )
            ]
        );

        // فیلد padding
        add_settings_field(
            'headrix_header_padding',
            __( 'Header Padding', 'headrix' ),
            [ __CLASS__, 'render_spacing' ],
            'headrix-settings',
            'headrix_header_section',
            [
                'label_for' => 'headrix_header_padding',
                'description' => __( 'Padding around header content.', 'headrix' )
            ]
        );

        // فیلد عرض کانتینر
        add_settings_field(
            'headrix_container_width',
            __( 'Container Width', 'headrix' ),
            [ __CLASS__, 'render_range' ],
            'headrix-settings',
            'headrix_header_section',
            [
                'label_for' => 'headrix_container_width',
                'min' => 960,
                'max' => 1920,
                'step' => 10,
                'unit' => 'px',
                'description' => __( 'Maximum width of header container.', 'headrix' )
            ]
        );
    }
    
    private static function register_menu_settings() {
        register_setting( 'headrix_menu_group', 'headrix_menu_animation', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_key',
            'default' => 'fade'
        ] );
        
        register_setting( 'headrix_menu_group', 'headrix_submenu_width', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 200
        ] );
        
        register_setting( 'headrix_menu_group', 'headrix_mega_menu', [
            'type' => 'boolean',
            'sanitize_callback' => 'absint',
            'default' => 0
        ] );
        
        register_setting( 'headrix_menu_group', 'headrix_menu_hover_effect', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_key',
            'default' => 'underline'
        ] );
        
        register_setting( 'headrix_menu_group', 'headrix_dropdown_animation', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_key',
            'default' => 'fade'
        ] );
        
        register_setting( 'headrix_menu_group', 'headrix_menu_alignment', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_key',
            'default' => 'center'
        ] );
        
        register_setting( 'headrix_menu_group', 'headrix_menu_spacing', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 30
        ] );
        
        register_setting( 'headrix_menu_group', 'headrix_submenu_depth', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 3
        ] );

        // سکشن منو
        add_settings_section(
            'headrix_menu_section',
            __( 'Menu Settings', 'headrix' ),
            [ __CLASS__, 'render_section_description' ],
            'headrix-settings'
        );

        // فیلد انیمیشن منو
        add_settings_field(
            'headrix_menu_animation',
            __( 'Menu Animation', 'headrix' ),
            [ __CLASS__, 'render_select' ],
            'headrix-settings',
            'headrix_menu_section',
            [
                'label_for' => 'headrix_menu_animation',
                'options' => [
                    'none'      => __( 'None', 'headrix' ),
                    'fade'      => __( 'Fade', 'headrix' ),
                    'slide'     => __( 'Slide', 'headrix' ),
                    'zoom'      => __( 'Zoom', 'headrix' ),
                    'bounce'    => __( 'Bounce', 'headrix' ),
                ],
                'description' => __( 'Animation effect for menu items.', 'headrix' )
            ]
        );

        // فیلد عرض ساب منو
        add_settings_field(
            'headrix_submenu_width',
            __( 'Submenu Width', 'headrix' ),
            [ __CLASS__, 'render_range' ],
            'headrix-settings',
            'headrix_menu_section',
            [
                'label_for' => 'headrix_submenu_width',
                'min' => 150,
                'max' => 500,
                'step' => 10,
                'unit' => 'px',
                'description' => __( 'Width of dropdown submenus.', 'headrix' )
            ]
        );

        // فیلد مگامنو
        add_settings_field(
            'headrix_mega_menu',
            __( 'Enable Mega Menu', 'headrix' ),
            [ __CLASS__, 'render_toggle' ],
            'headrix-settings',
            'headrix_menu_section',
            [
                'label_for' => 'headrix_mega_menu',
                'description' => __( 'Enable mega menu functionality.', 'headrix' )
            ]
        );

        // فیلد افکت هاور
        add_settings_field(
            'headrix_menu_hover_effect',
            __( 'Hover Effect', 'headrix' ),
            [ __CLASS__, 'render_select' ],
            'headrix-settings',
            'headrix_menu_section',
            [
                'label_for' => 'headrix_menu_hover_effect',
                'options' => [
                    'underline' => __( 'Underline', 'headrix' ),
                    'background' => __( 'Background', 'headrix' ),
                    'color'     => __( 'Color Change', 'headrix' ),
                    'scale'     => __( 'Scale', 'headrix' ),
                ],
                'description' => __( 'Hover effect for menu items.', 'headrix' )
            ]
        );

        // فیلد انیمیشن دراپ‌داون
        add_settings_field(
            'headrix_dropdown_animation',
            __( 'Dropdown Animation', 'headrix' ),
            [ __CLASS__, 'render_select' ],
            'headrix-settings',
            'headrix_menu_section',
            [
                'label_for' => 'headrix_dropdown_animation',
                'options' => [
                    'fade'      => __( 'Fade In', 'headrix' ),
                    'slide'     => __( 'Slide Down', 'headrix' ),
                    'zoom'      => __( 'Zoom In', 'headrix' ),
                    'flip'      => __( 'Flip', 'headrix' ),
                ],
                'description' => __( 'Animation for dropdown menus.', 'headrix' )
            ]
        );
        
        // فیلد تراز منو
        add_settings_field(
            'headrix_menu_alignment',
            __( 'Menu Alignment', 'headrix' ),
            [ __CLASS__, 'render_select' ],
            'headrix-settings',
            'headrix_menu_section',
            [
                'label_for' => 'headrix_menu_alignment',
                'options' => [
                    'left'   => __( 'Left', 'headrix' ),
                    'center' => __( 'Center', 'headrix' ),
                    'right'  => __( 'Right', 'headrix' ),
                ],
                'description' => __( 'Alignment of menu items.', 'headrix' )
            ]
        );
        
        // فیلد فاصله منو
        add_settings_field(
            'headrix_menu_spacing',
            __( 'Menu Item Spacing', 'headrix' ),
            [ __CLASS__, 'render_range' ],
            'headrix-settings',
            'headrix_menu_section',
            [
                'label_for' => 'headrix_menu_spacing',
                'min' => 10,
                'max' => 60,
                'step' => 5,
                'unit' => 'px',
                'description' => __( 'Spacing between menu items.', 'headrix' )
            ]
        );
        
        // فیلد عمق ساب منو
        add_settings_field(
            'headrix_submenu_depth',
            __( 'Submenu Depth', 'headrix' ),
            [ __CLASS__, 'render_range' ],
            'headrix-settings',
            'headrix_menu_section',
            [
                'label_for' => 'headrix_submenu_depth',
                'min' => 1,
                'max' => 5,
                'step' => 1,
                'unit' => 'levels',
                'description' => __( 'Maximum depth of submenus.', 'headrix' )
            ]
        );
    }
    
    private static function register_mobile_settings() {
        register_setting( 'headrix_mobile_group', 'headrix_breakpoint', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 768
        ] );
        
        register_setting( 'headrix_mobile_group', 'headrix_mobile_menu_style', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_key',
            'default' => 'slide'
        ] );
        
        register_setting( 'headrix_mobile_group', 'headrix_hamburger_size', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 22
        ] );
        
        register_setting( 'headrix_mobile_group', 'headrix_mobile_toggle_position', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_key',
            'default' => 'right'
        ] );
        
        register_setting( 'headrix_mobile_group', 'headrix_mobile_logo_size', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 40
        ] );
        
        register_setting( 'headrix_mobile_group', 'headrix_mobile_menu_position', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_key',
            'default' => 'right'
        ] );
        
        register_setting( 'headrix_mobile_group', 'headrix_mobile_menu_width', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 300
        ] );
        
        register_setting( 'headrix_mobile_group', 'headrix_mobile_menu_background', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => '#ffffff'
        ] );

        // سکشن موبایل
        add_settings_section(
            'headrix_mobile_section',
            __( 'Mobile Menu Settings', 'headrix' ),
            [ __CLASS__, 'render_section_description' ],
            'headrix-settings'
        );

        // فیلد breakpoint
        add_settings_field(
            'headrix_breakpoint',
            __( 'Mobile Breakpoint', 'headrix' ),
            [ __CLASS__, 'render_range' ],
            'headrix-settings',
            'headrix_mobile_section',
            [
                'label_for' => 'headrix_breakpoint',
                'min' => 320,
                'max' => 1200,
                'step' => 10,
                'unit' => 'px',
                'description' => __( 'Screen width at which mobile menu activates.', 'headrix' )
            ]
        );

        // فیلد استایل منوی موبایل
        add_settings_field(
            'headrix_mobile_menu_style',
            __( 'Mobile Menu Style', 'headrix' ),
            [ __CLASS__, 'render_select' ],
            'headrix-settings',
            'headrix_mobile_section',
            [
                'label_for' => 'headrix_mobile_menu_style',
                'options' => [
                    'slide'     => __( 'Slide In', 'headrix' ),
                    'fullscreen'=> __( 'Full Screen', 'headrix' ),
                    'dropdown'  => __( 'Dropdown', 'headrix' ),
                    'accordion' => __( 'Accordion', 'headrix' ),
                ],
                'description' => __( 'Style of mobile menu.', 'headrix' )
            ]
        );

        // فیلد اندازه همبرگر
        add_settings_field(
            'headrix_hamburger_size',
            __( 'Hamburger Icon Size', 'headrix' ),
            [ __CLASS__, 'render_range' ],
            'headrix-settings',
            'headrix_mobile_section',
            [
                'label_for' => 'headrix_hamburger_size',
                'min' => 16,
                'max' => 48,
                'step' => 2,
                'unit' => 'px',
                'description' => __( 'Size of hamburger menu icon.', 'headrix' )
            ]
        );

        // فیلد موقعیت همبرگر
        add_settings_field(
            'headrix_mobile_toggle_position',
            __( 'Toggle Position', 'headrix' ),
            [ __CLASS__, 'render_select' ],
            'headrix-settings',
            'headrix_mobile_section',
            [
                'label_for' => 'headrix_mobile_toggle_position',
                'options' => [
                    'left'  => __( 'Left', 'headrix' ),
                    'right' => __( 'Right', 'headrix' ),
                ],
                'description' => __( 'Position of mobile menu toggle.', 'headrix' )
            ]
        );

        // فیلد اندازه لوگوی موبایل
        add_settings_field(
            'headrix_mobile_logo_size',
            __( 'Mobile Logo Size', 'headrix' ),
            [ __CLASS__, 'render_range' ],
            'headrix-settings',
            'headrix_mobile_section',
            [
                'label_for' => 'headrix_mobile_logo_size',
                'min' => 20,
                'max' => 100,
                'step' => 5,
                'unit' => 'px',
                'description' => __( 'Logo size on mobile devices.', 'headrix' )
            ]
        );
        
        // فیلد موقعیت منوی موبایل
        add_settings_field(
            'headrix_mobile_menu_position',
            __( 'Menu Position', 'headrix' ),
            [ __CLASS__, 'render_select' ],
            'headrix-settings',
            'headrix_mobile_section',
            [
                'label_for' => 'headrix_mobile_menu_position',
                'options' => [
                    'left'  => __( 'Left', 'headrix' ),
                    'right' => __( 'Right', 'headrix' ),
                    'top'   => __( 'Top', 'headrix' ),
                    'bottom'=> __( 'Bottom', 'headrix' ),
                ],
                'description' => __( 'Position of mobile menu.', 'headrix' )
            ]
        );
        
        // فیلد عرض منوی موبایل
        add_settings_field(
            'headrix_mobile_menu_width',
            __( 'Menu Width', 'headrix' ),
            [ __CLASS__, 'render_range' ],
            'headrix-settings',
            'headrix_mobile_section',
            [
                'label_for' => 'headrix_mobile_menu_width',
                'min' => 250,
                'max' => 500,
                'step' => 10,
                'unit' => 'px',
                'description' => __( 'Width of mobile menu.', 'headrix' )
            ]
        );
        
        // فیلد رنگ پس‌زمینه منوی موبایل
        add_settings_field(
            'headrix_mobile_menu_background',
            __( 'Menu Background', 'headrix' ),
            [ __CLASS__, 'render_color' ],
            'headrix-settings',
            'headrix_mobile_section',
            [
                'label_for' => 'headrix_mobile_menu_background',
                'description' => __( 'Background color of mobile menu.', 'headrix' )
            ]
        );
    }
    
    private static function register_styling_settings() {
        register_setting( 'headrix_styling_group', 'headrix_bg_color', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => '#ffffff'
        ] );
        
        register_setting( 'headrix_styling_group', 'headrix_text_color', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => '#333333'
        ] );
        
        register_setting( 'headrix_styling_group', 'headrix_link_color', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => '#0073aa'
        ] );
        
        register_setting( 'headrix_styling_group', 'headrix_hover_color', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => '#005a87'
        ] );
        
        register_setting( 'headrix_styling_group', 'headrix_font_family', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'inherit'
        ] );
        
        register_setting( 'headrix_styling_group', 'headrix_font_size', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 16
        ] );
        
        register_setting( 'headrix_styling_group', 'headrix_font_weight', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_key',
            'default' => '500'
        ] );
        
        register_setting( 'headrix_styling_group', 'headrix_border_radius', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 4
        ] );
        
        register_setting( 'headrix_styling_group', 'headrix_box_shadow', [
            'type' => 'boolean',
            'sanitize_callback' => 'absint',
            'default' => 1
        ] );

        // سکشن استایل
        add_settings_section(
            'headrix_styling_section',
            __( 'Styling Settings', 'headrix' ),
            [ __CLASS__, 'render_section_description' ],
            'headrix-settings'
        );

        // فیلد رنگ‌ها
        $color_fields = [
            'headrix_bg_color'    => __( 'Background Color', 'headrix' ),
            'headrix_text_color'  => __( 'Text Color', 'headrix' ),
            'headrix_link_color'  => __( 'Link Color', 'headrix' ),
            'headrix_hover_color' => __( 'Hover Color', 'headrix' ),
        ];
        
        foreach ( $color_fields as $key => $label ) {
            add_settings_field(
                $key,
                $label,
                [ __CLASS__, 'render_color' ],
                'headrix-settings',
                'headrix_styling_section',
                [
                    'label_for' => $key,
                    'description' => ''
                ]
            );
        }

        // فیلد فونت
        add_settings_field(
            'headrix_font_family',
            __( 'Font Family', 'headrix' ),
            [ __CLASS__, 'render_font_select' ],
            'headrix-settings',
            'headrix_styling_section',
            [
                'label_for' => 'headrix_font_family',
                'description' => __( 'Font family for menu items.', 'headrix' )
            ]
        );

        // فیلد سایز فونت
        add_settings_field(
            'headrix_font_size',
            __( 'Font Size', 'headrix' ),
            [ __CLASS__, 'render_range' ],
            'headrix-settings',
            'headrix_styling_section',
            [
                'label_for' => 'headrix_font_size',
                'min' => 12,
                'max' => 24,
                'step' => 1,
                'unit' => 'px',
                'description' => __( 'Font size for menu items.', 'headrix' )
            ]
        );
        
        // فیلد وزن فونت
        add_settings_field(
            'headrix_font_weight',
            __( 'Font Weight', 'headrix' ),
            [ __CLASS__, 'render_select' ],
            'headrix-settings',
            'headrix_styling_section',
            [
                'label_for' => 'headrix_font_weight',
                'options' => [
                    '300' => __( 'Light', 'headrix' ),
                    '400' => __( 'Regular', 'headrix' ),
                    '500' => __( 'Medium', 'headrix' ),
                    '600' => __( 'Semi Bold', 'headrix' ),
                    '700' => __( 'Bold', 'headrix' ),
                ],
                'description' => __( 'Font weight for menu items.', 'headrix' )
            ]
        );
        
        // فیلد border radius
        add_settings_field(
            'headrix_border_radius',
            __( 'Border Radius', 'headrix' ),
            [ __CLASS__, 'render_range' ],
            'headrix-settings',
            'headrix_styling_section',
            [
                'label_for' => 'headrix_border_radius',
                'min' => 0,
                'max' => 20,
                'step' => 1,
                'unit' => 'px',
                'description' => __( 'Border radius for buttons and dropdowns.', 'headrix' )
            ]
        );
        
        // فیلد box shadow
        add_settings_field(
            'headrix_box_shadow',
            __( 'Box Shadow', 'headrix' ),
            [ __CLASS__, 'render_toggle' ],
            'headrix-settings',
            'headrix_styling_section',
            [
                'label_for' => 'headrix_box_shadow',
                'description' => __( 'Enable box shadow for dropdowns.', 'headrix' )
            ]
        );
    }
    
    private static function register_advanced_settings() {
        register_setting( 'headrix_advanced_group', 'headrix_custom_css', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => ''
        ] );
        
        register_setting( 'headrix_advanced_group', 'headrix_custom_js', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => ''
        ] );
        
        register_setting( 'headrix_advanced_group', 'headrix_clear_cache', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_key',
            'default' => ''
        ] );
        
        register_setting( 'headrix_advanced_group', 'headrix_debug_mode', [
            'type' => 'boolean',
            'sanitize_callback' => 'absint',
            'default' => 0
        ] );
        
        register_setting( 'headrix_advanced_group', 'headrix_disable_cache', [
            'type' => 'boolean',
            'sanitize_callback' => 'absint',
            'default' => 0
        ] );

        // سکشن پیشرفته
        add_settings_section(
            'headrix_advanced_section',
            __( 'Advanced Settings', 'headrix' ),
            [ __CLASS__, 'render_section_description' ],
            'headrix-settings'
        );

        // فیلد CSS سفارشی
        add_settings_field(
            'headrix_custom_css',
            __( 'Custom CSS', 'headrix' ),
            [ __CLASS__, 'render_textarea' ],
            'headrix-settings',
            'headrix_advanced_section',
            [
                'label_for' => 'headrix_custom_css',
                'rows' => 10,
                'description' => __( 'Add custom CSS code.', 'headrix' )
            ]
        );

        // فیلد JS سفارشی
        add_settings_field(
            'headrix_custom_js',
            __( 'Custom JavaScript', 'headrix' ),
            [ __CLASS__, 'render_textarea' ],
            'headrix-settings',
            'headrix_advanced_section',
            [
                'label_for' => 'headrix_custom_js',
                'rows' => 10,
                'description' => __( 'Add custom JavaScript code.', 'headrix' )
            ]
        );
        
        // فیلد دیباگ مود
        add_settings_field(
            'headrix_debug_mode',
            __( 'Debug Mode', 'headrix' ),
            [ __CLASS__, 'render_toggle' ],
            'headrix-settings',
            'headrix_advanced_section',
            [
                'label_for' => 'headrix_debug_mode',
                'description' => __( 'Enable debug mode for troubleshooting.', 'headrix' )
            ]
        );
        
        // فیلد غیرفعال کردن کش
        add_settings_field(
            'headrix_disable_cache',
            __( 'Disable Cache', 'headrix' ),
            [ __CLASS__, 'render_toggle' ],
            'headrix-settings',
            'headrix_advanced_section',
            [
                'label_for' => 'headrix_disable_cache',
                'description' => __( 'Disable CSS/JS caching.', 'headrix' )
            ]
        );

        // فیلد پاک کردن کش
        add_settings_field(
            'headrix_clear_cache',
            __( 'Clear Cache', 'headrix' ),
            [ __CLASS__, 'render_button' ],
            'headrix-settings',
            'headrix_advanced_section',
            [
                'label_for' => 'headrix_clear_cache',
                'button_text' => __( 'Clear Cache', 'headrix' ),
                'description' => __( 'Clear all cached styles and scripts.', 'headrix' )
            ]
        );
    }
    
    private static function register_importexport_settings() {
        register_setting( 'headrix_importexport_group', 'headrix_export_settings' );
        register_setting( 'headrix_importexport_group', 'headrix_import_settings' );

        // سکشن ایمپورت/اکسپورت
        add_settings_section(
            'headrix_importexport_section',
            __( 'Import/Export Settings', 'headrix' ),
            [ __CLASS__, 'render_section_description' ],
            'headrix-settings'
        );

        // فیلد اکسپورت
        add_settings_field(
            'headrix_export_settings',
            __( 'Export Settings', 'headrix' ),
            [ __CLASS__, 'render_export' ],
            'headrix-settings',
            'headrix_importexport_section',
            [
                'description' => __( 'Export your Headrix settings as a JSON file.', 'headrix' )
            ]
        );

        // فیلد ایمپورت
        add_settings_field(
            'headrix_import_settings',
            __( 'Import Settings', 'headrix' ),
            [ __CLASS__, 'render_import' ],
            'headrix-settings',
            'headrix_importexport_section',
            [
                'description' => __( 'Import Headrix settings from a JSON file.', 'headrix' )
            ]
        );
    }
    
    /**
     * ============================================
     * رندر تب‌ها و فرم
     * ============================================
     */
    
    public static function render_settings_page() {
        // گرفتن تب جاری
        self::$current_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], self::$tabs ) 
            ? sanitize_key( $_GET['tab'] ) 
            : 'general';
        ?>
        
        <div class="wrap headrix-settings-wrap">
            <!-- هدر -->
            <div class="headrix-settings-header">
                <div class="headrix-header-left">
                    <h1 class="headrix-title">
                        <span class="dashicons dashicons-menu-alt"></span>
                        <?php esc_html_e( 'Headrix Settings', 'headrix' ); ?>
                    </h1>
                    <p class="headrix-description">
                        <?php esc_html_e( 'Professional Header & Mega Menu Builder for WordPress', 'headrix' ); ?>
                    </p>
                </div>
                <div class="headrix-header-right">
                    <span class="headrix-version"><?php echo esc_html( HEADRIX_VERSION ); ?></span>
                    <a href="https://headrix.io/docs" target="_blank" class="button button-secondary">
                        <?php esc_html_e( 'Documentation', 'headrix' ); ?>
                    </a>
                </div>
            </div>
            
            <!-- پیام ذخیره موفق -->
            <?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) : ?>
                <div class="notice notice-success is-dismissible" style="margin: 20px 0;">
                    <p><?php esc_html_e( 'Settings saved successfully!', 'headrix' ); ?></p>
                </div>
            <?php endif; ?>
            
            <!-- تب‌ها -->
            <nav class="nav-tab-wrapper headrix-nav-tabs">
                <?php foreach ( self::$tabs as $tab_id => $tab_name ) : ?>
                    <a href="?page=headrix-settings&tab=<?php echo esc_attr( $tab_id ); ?>" 
                       class="nav-tab <?php echo self::$current_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html( $tab_name ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            
            <!-- فرم -->
            <div class="headrix-settings-content">
                <form method="post" action="options.php" class="headrix-settings-form" id="headrix-settings-form">
                    <?php 
                    // فیلدهای nonce و ...
                    settings_fields( 'headrix_' . self::$current_tab . '_group' );
                    do_settings_sections( 'headrix-settings' );
                    ?>
                    
                    <div class="headrix-form-actions">
                        <?php submit_button( __( 'Save Changes', 'headrix' ), 'primary large', 'submit', false ); ?>
                        <button type="button" class="button button-secondary headrix-reset-section">
                            <?php esc_html_e( 'Reset Section', 'headrix' ); ?>
                        </button>
                        <span class="headrix-save-status"></span>
                    </div>
                </form>
                
                <!-- پیش‌نمایش -->
                <div class="headrix-preview-sidebar">
                    <h3><?php esc_html_e( 'Live Preview', 'headrix' ); ?></h3>
                    <div class="headrix-preview-container">
                        <div class="headrix-preview-device headrix-preview-desktop">
                            <div class="headrix-preview-header">
                                <!-- پیش‌نمایش هدر -->
                                <div class="headrix-preview-logo"></div>
                                <div class="headrix-preview-menu">
                                    <span></span><span></span><span></span>
                                </div>
                                <div class="headrix-preview-actions">
                                    <span class="headrix-preview-search"></span>
                                    <span class="headrix-preview-cart"></span>
                                </div>
                            </div>
                        </div>
                        <div class="headrix-preview-device headrix-preview-mobile">
                            <div class="headrix-preview-header">
                                <div class="headrix-preview-hamburger"></div>
                                <div class="headrix-preview-logo"></div>
                            </div>
                        </div>
                    </div>
                    <p class="description"><?php esc_html_e( 'Changes update in real-time', 'headrix' ); ?></p>
                </div>
            </div>
            
            <!-- فوتر -->
            <div class="headrix-settings-footer">
                <p>
                    <?php esc_html_e( 'Need help?', 'headrix' ); ?>
                    <a href="?page=headrix-support"><?php esc_html_e( 'Contact Support', 'headrix' ); ?></a> |
                    <a href="https://headrix.io/changelog" target="_blank"><?php esc_html_e( 'Changelog', 'headrix' ); ?></a> |
                    <a href="#" class="headrix-debug-info"><?php esc_html_e( 'Debug Info', 'headrix' ); ?></a>
                </p>
            </div>
        </div>
        
        <?php
        // نمایش اطلاعات دیباگ در صورت کلیک
        add_action( 'admin_footer', function() {
            ?>
            <div id="headrix-debug-modal" style="display:none;">
                <pre><?php echo esc_html( print_r( \Headrix\Core\Override::get_status(), true ) ); ?></pre>
            </div>
            <?php
        } );
    }
    
    public static function render_docs_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Headrix Documentation', 'headrix' ); ?></h1>
            <div class="headrix-docs-container">
                <div class="headrix-docs-sidebar">
                    <ul>
                        <li><a href="#getting-started"><?php esc_html_e( 'Getting Started', 'headrix' ); ?></a></li>
                        <li><a href="#menu-setup"><?php esc_html_e( 'Menu Setup', 'headrix' ); ?></a></li>
                        <li><a href="#header-layouts"><?php esc_html_e( 'Header Layouts', 'headrix' ); ?></a></li>
                        <li><a href="#styling"><?php esc_html_e( 'Styling', 'headrix' ); ?></a></li>
                        <li><a href="#mobile-menu"><?php esc_html_e( 'Mobile Menu', 'headrix' ); ?></a></li>
                        <li><a href="#faq"><?php esc_html_e( 'FAQ', 'headrix' ); ?></a></li>
                    </ul>
                </div>
                <div class="headrix-docs-content">
                    <!-- محتوای مستندات -->
                    <h2 id="getting-started"><?php esc_html_e( 'Getting Started', 'headrix' ); ?></h2>
                    <p><?php esc_html_e( 'Headrix is a powerful header and mega menu builder for WordPress. Follow these steps to get started:', 'headrix' ); ?></p>
                    <ol>
                        <li><?php esc_html_e( 'Go to Headrix Settings in your WordPress admin.', 'headrix' ); ?></li>
                        <li><?php esc_html_e( 'Select a menu from your existing WordPress menus.', 'headrix' ); ?></li>
                        <li><?php esc_html_e( 'Enable Headrix to replace your theme header.', 'headrix' ); ?></li>
                        <li><?php esc_html_e( 'Customize the header layout, styling, and mobile menu.', 'headrix' ); ?></li>
                        <li><?php esc_html_e( 'Save your changes and view your site.', 'headrix' ); ?></li>
                    </ol>
                    
                    <h2 id="menu-setup"><?php esc_html_e( 'Menu Setup', 'headrix' ); ?></h2>
                    <p><?php esc_html_e( 'To use Headrix, you need to have at least one menu created in WordPress:', 'headrix' ); ?></p>
                    <ol>
                        <li><?php esc_html_e( 'Go to Appearance → Menus in WordPress admin.', 'headrix' ); ?></li>
                        <li><?php esc_html_e( 'Create a new menu or edit an existing one.', 'headrix' ); ?></li>
                        <li><?php esc_html_e( 'Add pages, posts, custom links, or categories to your menu.', 'headrix' ); ?></li>
                        <li><?php esc_html_e( 'Save the menu and return to Headrix Settings.', 'headrix' ); ?></li>
                        <li><?php esc_html_e( 'Select your menu from the dropdown in General Settings.', 'headrix' ); ?></li>
                    </ol>
                </div>
            </div>
        </div>
        <?php
    }
    
    public static function render_support_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Headrix Support', 'headrix' ); ?></h1>
            <div class="headrix-support-container">
                <div class="headrix-support-box">
                    <h2><span class="dashicons dashicons-sos"></span> <?php esc_html_e( 'Need Help?', 'headrix' ); ?></h2>
                    <p><?php esc_html_e( 'If you need help with Headrix, here are your options:', 'headrix' ); ?></p>
                    
                    <div class="headrix-support-options">
                        <div class="headrix-support-option">
                            <h3><?php esc_html_e( 'Documentation', 'headrix' ); ?></h3>
                            <p><?php esc_html_e( 'Check our comprehensive documentation for answers to common questions.', 'headrix' ); ?></p>
                            <a href="?page=headrix-docs" class="button button-primary"><?php esc_html_e( 'View Documentation', 'headrix' ); ?></a>
                        </div>
                        
                        <div class="headrix-support-option">
                            <h3><?php esc_html_e( 'Debug Information', 'headrix' ); ?></h3>
                            <p><?php esc_html_e( 'If you\'re experiencing issues, provide this information when contacting support.', 'headrix' ); ?></p>
                            <button id="headrix-copy-debug" class="button button-secondary"><?php esc_html_e( 'Copy Debug Info', 'headrix' ); ?></button>
                            <textarea id="headrix-debug-info" style="display:none;"><?php 
                                echo esc_textarea( json_encode( \Headrix\Core\Override::get_status(), JSON_PRETTY_PRINT ) );
                            ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * ============================================
     * رندر فیلدها
     * ============================================
     */
    
    public static function render_section_description( $args ) {
        // می‌تواند توضیحات اضافی نمایش دهد
    }
    
    public static function render_menu_select( $args ) {
        $selected = get_option( $args['label_for'], 0 );
        $menus = wp_get_nav_menus();
        ?>
        <select name="<?php echo esc_attr( $args['label_for'] ); ?>" id="<?php echo esc_attr( $args['label_for'] ); ?>" class="headrix-select">
            <option value="0"><?php esc_html_e( '— Select a Menu —', 'headrix' ); ?></option>
            <?php foreach ( $menus as $menu ) : ?>
                <option value="<?php echo esc_attr( $menu->term_id ); ?>" <?php selected( $selected, $menu->term_id ); ?>>
                    <?php echo esc_html( $menu->name ); ?> (<?php echo esc_html( $menu->count ); ?> <?php esc_html_e( 'items', 'headrix' ); ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ( ! empty( $args['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
        <?php endif; ?>
        
        <!-- دکمه مدیریت منوها -->
        <p>
            <a href="<?php echo admin_url( 'nav-menus.php' ); ?>" class="button button-small">
                <span class="dashicons dashicons-menu"></span>
                <?php esc_html_e( 'Manage Menus', 'headrix' ); ?>
            </a>
            <a href="<?php echo admin_url( 'nav-menus.php?action=edit&menu=0' ); ?>" class="button button-small">
                <span class="dashicons dashicons-plus"></span>
                <?php esc_html_e( 'Create New Menu', 'headrix' ); ?>
            </a>
        </p>
        <?php
    }
    
    public static function render_toggle( $args ) {
        $value = get_option( $args['label_for'], 0 );
        ?>
        <label class="headrix-toggle">
            <input type="checkbox" 
                   name="<?php echo esc_attr( $args['label_for'] ); ?>" 
                   value="1" 
                   <?php checked( 1, $value ); ?> 
                   class="headrix-toggle-checkbox">
            <span class="headrix-toggle-slider"></span>
        </label>
        <?php if ( ! empty( $args['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
        <?php endif; ?>
        <?php
    }
    
    public static function render_select( $args ) {
        $value = get_option( $args['label_for'], '' );
        ?>
        <select name="<?php echo esc_attr( $args['label_for'] ); ?>" id="<?php echo esc_attr( $args['label_for'] ); ?>" class="headrix-select">
            <?php foreach ( $args['options'] as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ( ! empty( $args['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
        <?php endif; ?>
        <?php
    }
    
    public static function render_text( $args ) {
        $value = get_option( $args['label_for'], '' );
        ?>
        <input type="text" 
               name="<?php echo esc_attr( $args['label_for'] ); ?>" 
               id="<?php echo esc_attr( $args['label_for'] ); ?>"
               value="<?php echo esc_attr( $value ); ?>"
               class="headrix-text regular-text">
        <?php if ( ! empty( $args['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
        <?php endif; ?>
        <?php
    }
    
    public static function render_range( $args ) {
        $value = get_option( $args['label_for'], isset( $args['default'] ) ? $args['default'] : $args['min'] );
        ?>
        <div class="headrix-range-container">
            <input type="range" 
                   name="<?php echo esc_attr( $args['label_for'] ); ?>" 
                   id="<?php echo esc_attr( $args['label_for'] ); ?>" 
                   min="<?php echo esc_attr( $args['min'] ); ?>" 
                   max="<?php echo esc_attr( $args['max'] ); ?>" 
                   step="<?php echo esc_attr( $args['step'] ); ?>" 
                   value="<?php echo esc_attr( $value ); ?>"
                   class="headrix-range">
            <span class="headrix-range-value">
                <input type="number" 
                       class="headrix-range-input"
                       min="<?php echo esc_attr( $args['min'] ); ?>" 
                       max="<?php echo esc_attr( $args['max'] ); ?>" 
                       step="<?php echo esc_attr( $args['step'] ); ?>" 
                       value="<?php echo esc_attr( $value ); ?>">
                <span class="headrix-range-unit"><?php echo esc_html( $args['unit'] ); ?></span>
            </span>
        </div>
        <?php if ( ! empty( $args['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
        <?php endif; ?>
        <?php
    }
    
    public static function render_spacing( $args ) {
        $value = get_option( $args['label_for'], '20px 0' );
        $values = explode( ' ', $value );
        $sides = [ 'Top', 'Right', 'Bottom', 'Left' ];
        ?>
        <div class="headrix-spacing-container">
            <?php for ( $i = 0; $i < 4; $i++ ) : ?>
                <div class="headrix-spacing-input">
                    <label><?php echo esc_html( $sides[$i] ); ?></label>
                    <input type="number" 
                           value="<?php echo esc_attr( isset( $values[$i] ) ? intval( $values[$i] ) : 0 ); ?>"
                           min="0"
                           max="100"
                           class="headrix-spacing-value">
                    <span>px</span>
                </div>
            <?php endfor; ?>
            <input type="hidden" 
                   name="<?php echo esc_attr( $args['label_for'] ); ?>" 
                   id="<?php echo esc_attr( $args['label_for'] ); ?>"
                   value="<?php echo esc_attr( $value ); ?>"
                   class="headrix-spacing-hidden">
        </div>
        <?php if ( ! empty( $args['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
        <?php endif; ?>
        <?php
    }
    
    public static function render_color( $args ) {
        $value = get_option( $args['label_for'], '#ffffff' );
        ?>
        <input type="text" 
               name="<?php echo esc_attr( $args['label_for'] ); ?>" 
               id="<?php echo esc_attr( $args['label_for'] ); ?>"
               value="<?php echo esc_attr( $value ); ?>"
               class="headrix-color-picker">
        <?php if ( ! empty( $args['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
        <?php endif; ?>
        <?php
    }
    
    public static function render_media_upload( $args ) {
        $value = get_option( $args['label_for'], '' );
        ?>
        <div class="headrix-media-upload">
            <input type="text" 
                   name="<?php echo esc_attr( $args['label_for'] ); ?>" 
                   id="<?php echo esc_attr( $args['label_for'] ); ?>"
                   value="<?php echo esc_attr( $value ); ?>"
                   class="headrix-media-url regular-text">
            <button type="button" class="button headrix-media-upload-btn">
                <?php esc_html_e( 'Upload', 'headrix' ); ?>
            </button>
            <button type="button" class="button headrix-media-remove-btn" style="<?php echo empty( $value ) ? 'display:none;' : ''; ?>">
                <?php esc_html_e( 'Remove', 'headrix' ); ?>
            </button>
            <?php if ( ! empty( $value ) ) : ?>
                <div class="headrix-media-preview">
                    <img src="<?php echo esc_url( $value ); ?>" style="max-width: 100px; margin-top: 10px;">
                </div>
            <?php endif; ?>
        </div>
        <?php if ( ! empty( $args['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
        <?php endif; ?>
        <?php
    }
    
    public static function render_font_select( $args ) {
        $value = get_option( $args['label_for'], 'inherit' );
        $fonts = [
            'inherit' => __( 'Theme Default', 'headrix' ),
            'Arial, sans-serif' => 'Arial',
            'Helvetica, Arial, sans-serif' => 'Helvetica',
            "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif" => 'Segoe UI',
            "'Open Sans', sans-serif" => 'Open Sans',
            "'Roboto', sans-serif" => 'Roboto',
            "'Montserrat', sans-serif" => 'Montserrat',
            "'Poppins', sans-serif" => 'Poppins',
            "'Lato', sans-serif" => 'Lato',
            "'Playfair Display', serif" => 'Playfair Display',
        ];
        ?>
        <select name="<?php echo esc_attr( $args['label_for'] ); ?>" id="<?php echo esc_attr( $args['label_for'] ); ?>" class="headrix-select headrix-font-select">
            <?php foreach ( $fonts as $font_value => $font_name ) : ?>
                <option value="<?php echo esc_attr( $font_value ); ?>" <?php selected( $value, $font_value ); ?> style="font-family: <?php echo esc_attr( $font_value ); ?>;">
                    <?php echo esc_html( $font_name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ( ! empty( $args['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
        <?php endif; ?>
        <?php
    }
    
    public static function render_textarea( $args ) {
        $value = get_option( $args['label_for'], '' );
        $rows = isset( $args['rows'] ) ? $args['rows'] : 5;
        ?>
        <textarea name="<?php echo esc_attr( $args['label_for'] ); ?>" 
                  id="<?php echo esc_attr( $args['label_for'] ); ?>"
                  rows="<?php echo esc_attr( $rows ); ?>"
                  class="headrix-textarea code"><?php echo esc_textarea( $value ); ?></textarea>
        <?php if ( ! empty( $args['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
        <?php endif; ?>
        <?php
    }
    
    public static function render_button( $args ) {
        ?>
        <button type="button" 
                id="<?php echo esc_attr( $args['label_for'] ); ?>"
                class="button button-secondary headrix-action-button">
            <?php echo esc_html( $args['button_text'] ); ?>
        </button>
        <?php if ( ! empty( $args['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
        <?php endif; ?>
        <?php
    }
    
    public static function render_export( $args ) {
        ?>
        <button type="button" 
                id="headrix-export-settings"
                class="button button-secondary">
            <?php esc_html_e( 'Export Settings', 'headrix' ); ?>
        </button>
        <?php if ( ! empty( $args['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
        <?php endif; ?>
        <?php
    }
    
    public static function render_import( $args ) {
        ?>
        <div class="headrix-import-container">
            <input type="file" 
                   id="headrix-import-file"
                   accept=".json"
                   class="headrix-import-file">
            <button type="button" 
                    id="headrix-import-settings"
                    class="button button-secondary"
                    disabled>
                <?php esc_html_e( 'Import Settings', 'headrix' ); ?>
            </button>
        </div>
        <?php if ( ! empty( $args['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
        <?php endif; ?>
        <?php
    }
}