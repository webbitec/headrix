<?php
namespace Headrix;

use Headrix\Core\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Bootstrap {

    public static function init() {
        // ابتدا مطمئن شو init اجرا شده یا می‌شود
        if ( ! did_action( 'init' ) && current_action() !== 'init' ) {
            // منتظر init بمان
            add_action( 'init', [ __CLASS__, 'setup' ], 0 );
        } else {
            // مستقیم راه‌اندازی کن
            self::setup();
        }
    }
    
    public static function setup() {
        // بارگذاری ترجمه در init
        add_action( 'init', [ __CLASS__, 'load_textdomain' ], 1 );
        
        // سایر تنظیمات
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_front_assets' ] );
        
        // ماژول‌ها
        add_action( 'init', [ __CLASS__, 'register_modules' ], 2 );
        
        // اولویت بالا برای Override
        add_action( 'init', [ __CLASS__, 'init_override' ], 3 );

        // تنظیمات پنل
        add_action( 'init', function() {
            require_once HEADRIX_PLUGIN_DIR . 'modules/settings/panel.php';
            \Headrix\Settings\Panel::init();
        }, 4 );
    }
    
    /**
     * راه‌اندازی سیستم Override
     */
    public static function init_override() {
        require_once HEADRIX_PLUGIN_DIR . 'core/override.php';
        \Headrix\Core\Override::init();
    }

    public static function load_textdomain() {
        // فقط یک بار بارگذاری کن
        static $loaded = false;
        
        if ( ! $loaded ) {
            $loaded = true;
            
            load_plugin_textdomain(
                'headrix',
                false,
                dirname( plugin_basename( HEADRIX_PLUGIN_DIR . 'headrix.php' ) ) . '/languages/'
            );
        }
    }

    public static function activate() {
        add_option( 'headrix_version', HEADRIX_VERSION );

        // Defaults
        add_option( 'headrix_sticky', 1 );
        add_option( 'headrix_logo_position', 'left' );
        add_option( 'headrix_bg_color', '#ffffff' );

        // Advanced defaults
        add_option( 'headrix_enable_search', 0 );
        add_option( 'headrix_enable_social', 0 );
        add_option( 'headrix_menu_override', 1 ); // پیش‌فرض فعال
        add_option( 'headrix_target_menu', 0 ); // term_id of selected WP menu

        add_option( 'headrix_breakpoint', 768 );
        add_option( 'headrix_logo_size_desktop', 50 ); // px
        add_option( 'headrix_logo_size_mobile', 40 );  // px
        add_option( 'headrix_hamburger_size', 22 );    // px
        add_option( 'headrix_mobile_toggle_position', 'right' ); // left|right
        
        // ذخیره موقعیت‌های منو فعلی
        $current_locations = get_nav_menu_locations();
        if ( ! empty( $current_locations ) ) {
            update_option( 'headrix_original_menu_locations', $current_locations );
        }
    }

    public static function deactivate() {
        // بازگرداندن موقعیت‌های منو در صورت نیاز
        $original_locations = get_option( 'headrix_original_menu_locations', [] );
        if ( ! empty( $original_locations ) ) {
            set_theme_mod( 'nav_menu_locations', $original_locations );
        }
        
        // حذف هدرها و استایل‌های اضافه شده
        remove_filter( 'body_class', [ 'Headrix\Core\Override', 'add_override_class' ] );
    }

    public static function enqueue_admin_assets( $hook ) {
        // فقط در صفحات Headrix
        if ( strpos( $hook, 'headrix' ) !== false ) {
            wp_enqueue_style( 'headrix-admin', HEADRIX_PLUGIN_URL . 'assets/css/admin.css', [], HEADRIX_VERSION );
            wp_enqueue_script( 'headrix-admin', HEADRIX_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery', 'wp-color-picker' ], HEADRIX_VERSION, true );
            
            // Localize override status
            wp_localize_script( 'headrix-admin', 'HDRX_ADMIN', [
                'override_active' => get_option( 'headrix_menu_override', 1 ),
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'headrix_admin_nonce' ),
            ] );
        }
    }

    public static function enqueue_front_assets() {
        // فقط اگر Headrix فعال است
        if ( \Headrix\Core\Override::is_headrix_active() ) {
            wp_enqueue_style( 'headrix-frontend', HEADRIX_PLUGIN_URL . 'assets/css/frontend.css', [], HEADRIX_VERSION );
            wp_enqueue_script( 'headrix-frontend', HEADRIX_PLUGIN_URL . 'assets/js/frontend.js', [ 'jquery' ], HEADRIX_VERSION, true );

            // Localize responsive and toggle settings to JS
            wp_localize_script( 'headrix-frontend', 'HDRX_SETTINGS', [
                'breakpoint'          => (int) get_option( 'headrix_breakpoint', 768 ),
                'logoDesktop'         => (int) get_option( 'headrix_logo_size_desktop', 50 ),
                'logoMobile'          => (int) get_option( 'headrix_logo_size_mobile', 40 ),
                'hamburgerSize'       => (int) get_option( 'headrix_hamburger_size', 22 ),
                'togglePosition'      => sanitize_text_field( get_option( 'headrix_mobile_toggle_position', 'right' ) ),
                'stickyEnabled'       => (int) get_option( 'headrix_sticky', 1 ),
                'enableSearch'        => (int) get_option( 'headrix_enable_search', 0 ),
                'enableSocial'        => (int) get_option( 'headrix_enable_social', 0 ),
                'menuOverride'        => (int) get_option( 'headrix_menu_override', 1 ),
                'targetMenu'          => (int) get_option( 'headrix_target_menu', 0 ),
                'isOverrideActive'    => 1,
                'ajax_url'            => admin_url( 'admin-ajax.php' ),
                'nonce'              => wp_create_nonce( 'headrix_frontend_nonce' ),
            ] );
        }
    }

    public static function register_modules() {
        require_once HEADRIX_PLUGIN_DIR . 'core/helpers.php';
        require_once HEADRIX_PLUGIN_DIR . 'core/security.php';
        require_once HEADRIX_PLUGIN_DIR . 'core/api.php';
        
        // راه‌اندازی API
        \Headrix\Core\API::init();

        require_once HEADRIX_PLUGIN_DIR . 'modules/styling/presets.php';
        require_once HEADRIX_PLUGIN_DIR . 'modules/styling/customizer.php';
        \Headrix\Styling\Customizer::init();

        require_once HEADRIX_PLUGIN_DIR . 'modules/header/builder.php';
        require_once HEADRIX_PLUGIN_DIR . 'modules/header/sticky.php';
        \Headrix\Header\Builder::init();
        \Headrix\Header\Sticky::init();

        require_once HEADRIX_PLUGIN_DIR . 'modules/menu/megamenu.php';
        require_once HEADRIX_PLUGIN_DIR . 'modules/menu/widgets.php';
        require_once HEADRIX_PLUGIN_DIR . 'modules/menu/conditions.php';
        \Headrix\Menu\MegaMenu::init();
        \Headrix\Menu\Widgets::init();
        \Headrix\Menu\Conditions::init();

        require_once HEADRIX_PLUGIN_DIR . 'modules/compatibility/elementor.php';
        require_once HEADRIX_PLUGIN_DIR . 'modules/compatibility/visualcomposer.php';
        require_once HEADRIX_PLUGIN_DIR . 'modules/compatibility/gutenberg.php';
        require_once HEADRIX_PLUGIN_DIR . 'modules/compatibility/woocommerce.php';
        require_once HEADRIX_PLUGIN_DIR . 'modules/compatibility/fallback.php';
        \Headrix\Compatibility\Fallback::init();

        require_once HEADRIX_PLUGIN_DIR . 'modules/settings/livepreview.php';
        require_once HEADRIX_PLUGIN_DIR . 'modules/settings/importexport.php';
        require_once HEADRIX_PLUGIN_DIR . 'modules/settings/onboarding.php';
        \Headrix\Settings\LivePreview::init();
        \Headrix\Settings\ImportExport::init();
        \Headrix\Settings\Onboarding::init();

        require_once HEADRIX_PLUGIN_DIR . 'modules/performance/cache.php';
        require_once HEADRIX_PLUGIN_DIR . 'modules/performance/lazyload.php';
        require_once HEADRIX_PLUGIN_DIR . 'modules/performance/accessibility.php';
        \Headrix\Performance\LazyLoad::init();
        \Headrix\Performance\Accessibility::init();
    }
}