<?php
namespace Headrix\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Override {

    private static $header_rendered = false;
    private static $theme_headers_disabled = false;
    private static $current_theme = '';
    private static $theme_version = '';
    
    public static function init() {
        // ذخیره اطلاعات قالب فعلی
        self::$current_theme = get_template();
        $theme = wp_get_theme( self::$current_theme );
        self::$theme_version = $theme->get( 'Version' );
        
        // اولویت بالا برای جلوگیری از رندر قالب
        add_action( 'wp', [ __CLASS__, 'disable_theme_features' ], 1 );
        
        // بازنویسی منوها
        add_action( 'after_setup_theme', [ __CLASS__, 'setup_headrix_menus' ], 20 );
        
        // حذف استایل‌های منو و هدر قالب
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'remove_theme_menu_assets' ], 999 );
        
        // فیلترهای اضافی
        add_filter( 'wp_nav_menu_args', [ __CLASS__, 'force_headrix_args' ], 9999 );
        add_filter( 'body_class', [ __CLASS__, 'add_override_class' ] );
        
        // جلوگیری از رندر دوگانه
        add_filter( 'wp_nav_menu', [ __CLASS__, 'prevent_duplicate_menu' ], 10, 2 );
        add_action( 'headrix_header_rendered', function() {
            self::$header_rendered = true;
        } );
        
        // CSS برای پنهان کردن هدر قالب
        add_action( 'wp_head', [ __CLASS__, 'add_hide_css' ], 999 );
        
        // AJAX handler برای بررسی وضعیت
        add_action( 'wp_ajax_headrix_get_override_status', [ __CLASS__, 'ajax_get_status' ] );
    }
    
    /**
     * اضافه کردن CSS برای پنهان کردن هدر قالب
     */
    public static function add_hide_css() {
        if ( self::is_headrix_active() ) {
            $theme = self::$current_theme;
            $css = '';
            
            // CSS عمومی برای همه قالب‌ها
            $css .= '
                /* Headrix - پنهان کردن هدر و منوهای قالب */
                #masthead,
                .site-header,
                .main-header,
                header.site-header,
                header[role="banner"]:not(#headrix-header),
                .main-navigation,
                .primary-navigation,
                .site-navigation,
                nav.site-navigation:not(.hdrx-nav-container),
                nav.main-navigation:not(.hdrx-nav-container),
                .navbar:not(.hdrx-header),
                .header-wrapper:not(.hdrx-header),
                .header-main:not(.hdrx-header) {
                    display: none !important;
                    visibility: hidden !important;
                    height: 0 !important;
                    overflow: hidden !important;
                    opacity: 0 !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    max-height: 0 !important;
                    min-height: 0 !important;
                }
                
                /* نمایش هدر Headrix */
                #headrix-header {
                    display: block !important;
                    visibility: visible !important;
                    opacity: 1 !important;
                }
            ';
            
            // CSS مخصوص قالب‌های خاص
            switch ( $theme ) {
                case 'astra':
                    $css .= '
                        /* Astra specific */
                        .ast-header-wrapper,
                        .ast-main-header,
                        .main-header-bar-navigation,
                        .ast-header-break-point .main-header-bar-navigation,
                        .main-header-bar,
                        .site-primary-header-wrap,
                        .site-branding,
                        .ast-site-identity,
                        .ast-mobile-menu-buttons,
                        .ast-header-woo-cart,
                        .ast-header-edd-cart,
                        .ast-above-header,
                        .ast-below-header,
                        .ast-desktop .main-header-menu,
                        .ast-header-sections-navigation {
                            display: none !important;
                            visibility: hidden !important;
                            height: 0 !important;
                            overflow: hidden !important;
                            opacity: 0 !important;
                        }
                    ';
                    break;
                    
                case 'oceanwp':
                    $css .= '
                        /* OceanWP specific */
                        .oceanwp-header,
                        #site-header,
                        .top-bar-wrap,
                        #site-navigation,
                        .dropdown-menu,
                        #mobile-menu {
                            display: none !important;
                            visibility: hidden !important;
                            height: 0 !important;
                            overflow: hidden !important;
                            opacity: 0 !important;
                        }
                    ';
                    break;
                    
                case 'generatepress':
                    $css .= '
                        /* GeneratePress specific */
                        .main-header,
                        .site-header,
                        #site-navigation,
                        .main-navigation,
                        .menu-toggle {
                            display: none !important;
                            visibility: hidden !important;
                            height: 0 !important;
                            overflow: hidden !important;
                            opacity: 0 !important;
                        }
                    ';
                    break;
                    
                case 'Divi':
                    $css .= '
                        /* Divi specific */
                        #main-header,
                        #et-top-navigation,
                        .et_header_style_left #main-header,
                        #top-header,
                        #et_mobile_nav_menu {
                            display: none !important;
                            visibility: hidden !important;
                            height: 0 !important;
                            overflow: hidden !important;
                            opacity: 0 !important;
                        }
                    ';
                    break;
            }
            
            // تنظیمات admin bar
            $css .= '
                /* تنظیم مجدد padding body اگر هدر استیکی دارد */
                body.admin-bar #headrix-header.sticky {
                    top: 32px;
                }
                
                @media screen and (max-width: 782px) {
                    body.admin-bar #headrix-header.sticky {
                        top: 46px;
                    }
                }
                
                /* جلوگیری از تداخل با محتوای سایت */
                .headrix-override-active .site-content,
                .headrix-override-active .content-area,
                .headrix-override-active #content,
                .headrix-override-active .entry-content {
                    margin-top: 0 !important;
                    padding-top: 0 !important;
                }
            ';
            
            echo '<style id="headrix-hide-theme-header">' . $css . '</style>';
        }
    }

    /**
     * غیرفعال کردن ویژگی‌های قالب
     */
    public static function disable_theme_features() {
        // فقط یک بار اجرا شود
        if ( self::$theme_headers_disabled ) {
            return;
        }
        
        // غیرفعال کردن بر اساس قالب
        self::disable_specific_theme( self::$current_theme );
        
        // غیرفعال کردن عمومی
        self::disable_general_features();
        
        self::$theme_headers_disabled = true;
    }

    /**
     * غیرفعال کردن قالب خاص
     */
    private static function disable_specific_theme( $theme ) {
        $handlers = [
            'astra' => function() {
                // Astra - فقط هدر و منو را غیرفعال کن
                if ( ! is_admin() && ! wp_doing_ajax() ) {
                    // حذف اکشن‌های هدر Astra
                    remove_action( 'astra_header', 'astra_header_markup' );
                    remove_action( 'astra_masthead', 'astra_masthead_primary_template' );
                    remove_action( 'astra_masthead_content', 'astra_primary_navigation_markup', 10 );
                    
                    // غیرفعال کردن منوی موبایل
                    remove_action( 'astra_masthead_content', 'astra_toggle_buttons_markup', 9 );
                    
                    // فیلتر برای جلوگیری از لود استایل‌های منو
                    add_filter( 'astra_dynamic_theme_css', function( $css ) {
                        // حذف CSS مربوط به هدر و منو
                        $patterns = [
                            '/\.ast-header[-_].*?\{.*?\}/s',
                            '/\.main-header.*?\{.*?\}/s',
                            '/\.site-header.*?\{.*?\}/s',
                            '/\.menu-toggle.*?\{.*?\}/s',
                            '/\.main-navigation.*?\{.*?\}/s',
                        ];
                        
                        foreach ( $patterns as $pattern ) {
                            $css = preg_replace( $pattern, '', $css );
                        }
                        
                        return $css;
                    }, 999 );
                }
            },
            
            'oceanwp' => function() {
                // OceanWP
                if ( ! is_admin() && ! wp_doing_ajax() ) {
                    remove_action( 'ocean_top_bar', 'oceanwp_top_bar_template' );
                    remove_action( 'ocean_header', 'oceanwp_header_template' );
                    remove_action( 'ocean_before_header', 'oceanwp_before_header' );
                    remove_action( 'ocean_after_header', 'oceanwp_after_header' );
                }
            },
            
            'generatepress' => function() {
                // GeneratePress
                if ( ! is_admin() && ! wp_doing_ajax() ) {
                    remove_action( 'generate_header', 'generate_construct_header' );
                    remove_action( 'generate_after_header', 'generate_add_navigation_after_header' );
                    remove_action( 'generate_before_header', 'generate_before_header' );
                    remove_action( 'generate_after_header', 'generate_after_header' );
                }
            },
            
            'Divi' => function() {
                // Divi
                if ( ! is_admin() && ! wp_doing_ajax() ) {
                    remove_action( 'et_header_top', 'et_add_mobile_navigation' );
                    remove_action( 'et_html_logo_container', 'et_add_logo_container' );
                }
            },
            
            'Avada' => function() {
                // Avada
                if ( ! is_admin() && ! wp_doing_ajax() ) {
                    remove_action( 'avada_header', 'avada_header_template' );
                    remove_action( 'avada_before_header_wrapper', 'avada_header_wrapper' );
                    remove_action( 'avada_after_header_wrapper', 'avada_after_header_wrapper' );
                }
            },
            
            'flatsome' => function() {
                // Flatsome
                if ( ! is_admin() && ! wp_doing_ajax() ) {
                    remove_action( 'flatsome_header', 'flatsome_header' );
                    remove_action( 'flatsome_before_header', 'flatsome_before_header' );
                    remove_action( 'flatsome_after_header', 'flatsome_after_header' );
                }
            }
        ];
        
        if ( isset( $handlers[$theme] ) && is_callable( $handlers[$theme] ) ) {
            call_user_func( $handlers[$theme] );
        } else {
            // برای قالب‌های ناشناخته - غیرفعال کردن عمومی
            self::disable_unknown_theme();
        }
    }

    /**
     * غیرفعال کردن قالب ناشناخته
     */
    private static function disable_unknown_theme() {
        if ( ! is_admin() && ! wp_doing_ajax() ) {
            // حذف اکشن‌های رایج هدر
            $common_actions = [
                'wp_body_open' => [
                    'header',
                    'masthead',
                    'site_header',
                    'main_header'
                ],
                'get_header' => [
                    'header',
                    'head'
                ]
            ];
            
            foreach ( $common_actions as $hook => $functions ) {
                if ( isset( $GLOBALS['wp_filter'][$hook] ) ) {
                    $priorities = $GLOBALS['wp_filter'][$hook]->callbacks;
                    foreach ( $priorities as $priority => $callbacks ) {
                        foreach ( $callbacks as $callback_key => $callback ) {
                            $function_name = self::get_function_name( $callback['function'] );
                            foreach ( $functions as $func ) {
                                if ( stripos( $function_name, $func ) !== false ) {
                                    remove_action( $hook, $callback_key, $priority );
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * غیرفعال کردن ویژگی‌های عمومی
     */
    private static function disable_general_features() {
        // حذف پشتیبانی منو
        add_action( 'after_setup_theme', function() {
            remove_theme_support( 'menus' );
        }, 999 );
        
        // غیرفعال کردن هدر سفارشی
        add_filter( 'theme_mod_header_image', '__return_empty_string' );
        add_filter( 'has_header_image', '__return_false' );
        
        // جلوگیری از لود header.php قالب
        add_filter( 'template_include', function( $template ) {
            if ( self::is_headrix_active() && basename( $template ) === 'header.php' ) {
                // اجازه نده header.php قالب لود شود
                return '';
            }
            return $template;
        }, 999 );
    }

    /**
     * حذف استایل‌های منو قالب
     */
    public static function remove_theme_menu_assets() {
        if ( ! self::is_headrix_active() ) {
            return;
        }
        
        global $wp_styles, $wp_scripts;
        
        // لیست handleهای منو و هدر برای حذف
        $menu_handles = [];
        
        // شناسایی استایل‌های منو بر اساس قالب
        $theme = self::$current_theme;
        $theme_patterns = [
            'menu', 'nav', 'navigation', 'header', 'masthead', 'top-bar'
        ];
        
        foreach ( $wp_styles->registered as $handle => $style ) {
            $src = $style->src ?? '';
            $is_menu_style = false;
            
            // بررسی بر اساس handle
            foreach ( $theme_patterns as $pattern ) {
                if ( stripos( $handle, $pattern ) !== false ) {
                    $is_menu_style = true;
                    break;
                }
            }
            
            // بررسی بر اساس src
            if ( ! $is_menu_style && $src ) {
                foreach ( $theme_patterns as $pattern ) {
                    if ( stripos( $src, $pattern ) !== false ) {
                        $is_menu_style = true;
                        break;
                    }
                }
            }
            
            // بررسی قالب خاص
            if ( ! $is_menu_style && stripos( $handle, $theme ) !== false ) {
                $is_menu_style = true;
            }
            
            // استثنا: استایل‌های ضروری و Headrix را حذف نکن
            $exceptions = [
                'headrix', 'dashicons', 'admin-bar', 'wp-block',
                'astra-theme-css', 'oceanwp-style', 'generate-style',
                'twentytwenty-style', 'twentytwentyone-style'
            ];
            
            $is_exception = false;
            foreach ( $exceptions as $exception ) {
                if ( stripos( $handle, $exception ) !== false ) {
                    $is_exception = true;
                    break;
                }
            }
            
            if ( $is_menu_style && ! $is_exception ) {
                $menu_handles['styles'][] = $handle;
            }
        }
        
        // شناسایی اسکریپت‌های منو
        foreach ( $wp_scripts->registered as $handle => $script ) {
            $src = $script->src ?? '';
            $is_menu_script = false;
            
            foreach ( $theme_patterns as $pattern ) {
                if ( stripos( $handle, $pattern ) !== false ) {
                    $is_menu_script = true;
                    break;
                }
            }
            
            // استثنا: اسکریپت‌های ضروری
            $script_exceptions = [
                'headrix', 'jquery', 'wp-', 'underscore', 'backbone',
                'astra-theme-js', 'oceanwp-main', 'generate-navigation'
            ];
            
            $is_script_exception = false;
            foreach ( $script_exceptions as $exception ) {
                if ( stripos( $handle, $exception ) !== false ) {
                    $is_script_exception = true;
                    break;
                }
            }
            
            if ( $is_menu_script && ! $is_script_exception ) {
                $menu_handles['scripts'][] = $handle;
            }
        }
        
        // حذف استایل‌ها
        if ( ! empty( $menu_handles['styles'] ) ) {
            foreach ( $menu_handles['styles'] as $handle ) {
                wp_dequeue_style( $handle );
                wp_deregister_style( $handle );
            }
        }
        
        // حذف اسکریپت‌ها
        if ( ! empty( $menu_handles['scripts'] ) ) {
            foreach ( $menu_handles['scripts'] as $handle ) {
                wp_dequeue_script( $handle );
                wp_deregister_script( $handle );
            }
        }
    }

    /**
     * تنظیم منوهای Headrix
     */
    public static function setup_headrix_menus() {
        // اگر منوی فعال Headrix داریم
        $menu_override = get_option( 'headrix_menu_override', 1 );
        $target_menu = get_option( 'headrix_target_menu', 0 );
        
        if ( $menu_override && $target_menu ) {
            // منوی انتخابی کاربر را به موقعیت Headrix متصل کن
            $locations = get_theme_mod( 'nav_menu_locations', [] );
            $locations['headrix_primary'] = $target_menu;
            set_theme_mod( 'nav_menu_locations', $locations );
            
            // ذخیره موقعیت اصلی برای بازیابی
            if ( ! get_option( 'headrix_original_menu_locations' ) ) {
                update_option( 'headrix_original_menu_locations', $locations );
            }
        }
        
        // رجیستر موقعیت‌های Headrix
        register_nav_menus([
            'headrix_primary'   => __( 'Headrix Primary Menu', 'headrix' ),
            'headrix_mobile'    => __( 'Headrix Mobile Menu', 'headrix' ),
            'headrix_footer'    => __( 'Headrix Footer Menu', 'headrix' ),
            'headrix_secondary' => __( 'Headrix Secondary Menu', 'headrix' ),
            'headrix_topbar'    => __( 'Headrix Top Bar Menu', 'headrix' ),
        ]);
    }

    /**
     * مجبور کردن آرگومان‌های منو به Headrix
     */
    public static function force_headrix_args( $args ) {
        // تبدیل شیء به آرایه اگر لازم باشد
        $args_array = is_object( $args ) ? (array) $args : $args;
        
        // اگر این منو مربوط به قالب است
        if ( isset( $args_array['theme_location'] ) ) {
            $theme_location = $args_array['theme_location'];
            
            // موقعیت‌های قالب که باید به Headrix تبدیل شوند
            $theme_to_headrix = [
                'primary' => 'headrix_primary',
                'main' => 'headrix_primary',
                'header' => 'headrix_primary',
                'main-menu' => 'headrix_primary',
                'primary-menu' => 'headrix_primary',
                'menu-1' => 'headrix_primary',
                'menu-primary' => 'headrix_primary',
                'primary-nav' => 'headrix_primary',
                'main-nav' => 'headrix_primary',
                'top-menu' => 'headrix_topbar',
                'footer-menu' => 'headrix_footer',
                'mobile' => 'headrix_mobile',
                'mobile-menu' => 'headrix_mobile',
                'responsive' => 'headrix_mobile',
                'menu-mobile' => 'headrix_mobile',
                'mobile-nav' => 'headrix_mobile',
            ];
            
            if ( isset( $theme_to_headrix[$theme_location] ) ) {
                $args_array['theme_location'] = $theme_to_headrix[$theme_location];
                $args_array['container_class'] = isset( $args_array['container_class'] ) ? 
                    $args_array['container_class'] . ' hdrx-menu-container' : 'hdrx-menu-container';
                $args_array['menu_class'] = isset( $args_array['menu_class'] ) ? 
                    $args_array['menu_class'] . ' hdrx-menu' : 'hdrx-menu';
                $args_array['fallback_cb'] = false;
            }
        }
        
        // اگر container_class یا menu_class ندارند، اضافه کن
        if ( ! isset( $args_array['container_class'] ) || strpos( $args_array['container_class'], 'hdrx' ) === false ) {
            $args_array['container_class'] = ( isset( $args_array['container_class'] ) ? $args_array['container_class'] . ' ' : '' ) . 'hdrx-menu-container';
        }
        
        if ( ! isset( $args_array['menu_class'] ) || strpos( $args_array['menu_class'], 'hdrx' ) === false ) {
            $args_array['menu_class'] = ( isset( $args_array['menu_class'] ) ? $args_array['menu_class'] . ' ' : '' ) . 'hdrx-menu';
        }
        
        // تبدیل مجدد به شیء اگر لازم باشد
        return is_object( $args ) ? (object) $args_array : $args_array;
    }

    /**
     * جلوگیری از نمایش منوی دوگانه
     */
    public static function prevent_duplicate_menu( $nav_menu, $args ) {
        // اگر Headrix فعال نیست، ادامه بده
        if ( ! self::is_headrix_active() ) {
            return $nav_menu;
        }
        
        // تبدیل آرگومان‌ها به آرایه
        $args_array = is_object( $args ) ? (array) $args : $args;
        
        // بررسی اینکه آیا این منوی Headrix است
        $is_headrix_menu = false;
        
        // بررسی از طریق container_class و menu_class
        $container_class = $args_array['container_class'] ?? '';
        $menu_class = $args_array['menu_class'] ?? '';
        
        if ( strpos( $container_class, 'hdrx' ) !== false || 
             strpos( $menu_class, 'hdrx' ) !== false ) {
            $is_headrix_menu = true;
        }
        
        // بررسی از طریق theme_location
        $theme_location = $args_array['theme_location'] ?? '';
        if ( strpos( $theme_location, 'headrix_' ) === 0 ) {
            $is_headrix_menu = true;
        }
        
        // اگر منوی Headrix نیست و مربوط به قالب است، حذفش کن
        if ( ! $is_headrix_menu && self::is_theme_menu( $args ) ) {
            return '';
        }
        
        return $nav_menu;
    }
    
    /**
     * بررسی اینکه آیا این منو مربوط به قالب است
     */
    private static function is_theme_menu( $args ) {
        // تبدیل شیء به آرایه اگر لازم باشد
        $args_array = is_object( $args ) ? (array) $args : $args;
        
        // بررسی theme_location
        $theme_location = $args_array['theme_location'] ?? '';
        
        // موقعیت‌های معروف قالب‌ها
        $theme_locations = [
            'primary', 'main', 'header', 'main-menu', 'primary-menu',
            'menu-1', 'menu-primary', 'primary-nav', 'main-nav',
            'top-menu', 'footer-menu', 'mobile', 'mobile-menu',
            'responsive', 'menu-mobile', 'mobile-nav'
        ];
        
        if ( in_array( $theme_location, $theme_locations ) ) {
            return true;
        }
        
        // بررسی کلاس‌ها
        $container_class = $args_array['container_class'] ?? '';
        $menu_class = $args_array['menu_class'] ?? '';
        
        // کلاس‌های Headrix
        $headrix_classes = [ 'hdrx', 'headrix' ];
        foreach ( $headrix_classes as $class ) {
            if ( strpos( $container_class, $class ) !== false || 
                 strpos( $menu_class, $class ) !== false ) {
                return false;
            }
        }
        
        // کلاس‌های مخصوص قالب‌ها
        $theme_classes = [ 
            'main-navigation', 'primary-menu', 'site-navigation',
            'main-menu', 'primary-nav', 'menu-primary',
            'ast-main-header', 'main-header-menu',
            'oceanwp-header', 'generate-header',
            strtolower( self::$current_theme )
        ];
        
        foreach ( $theme_classes as $theme_class ) {
            if ( strpos( $container_class, $theme_class ) !== false || 
                 strpos( $menu_class, $theme_class ) !== false ) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * اضافه کردن کلاس‌های override به body
     */
    public static function add_override_class( $classes ) {
        if ( self::is_headrix_active() ) {
            $classes[] = 'headrix-override-active';
            $classes[] = 'headrix-theme-disabled';
            
            // اضافه کردن کلاس برای قالب خاص
            $classes[] = 'headrix-theme-' . sanitize_html_class( self::$current_theme );
            
            // اضافه کردن کلاس برای حالت RTL
            if ( is_rtl() ) {
                $classes[] = 'headrix-rtl';
            }
        }
        
        return $classes;
    }

    /**
     * بررسی فعال بودن Headrix
     */
    public static function is_headrix_active() {
        // بررسی گزینه override
        $menu_override = get_option( 'headrix_menu_override', 1 );
        
        // اگر override غیرفعال است
        if ( ! $menu_override ) {
            return false;
        }
        
        // اگر در admin هستیم
        if ( is_admin() ) {
            return false;
        }
        
        // بررسی استثناها
        $exceptions = [
            'wp-login.php',
            'wp-register.php',
            'wp-signup.php',
            'wp-cron.php',
            'xmlrpc.php',
            'admin-ajax.php'
        ];
        
        global $pagenow;
        if ( isset( $pagenow ) && in_array( $pagenow, $exceptions ) ) {
            return false;
        }
        
        // اگر در حالت customize یا preview هستیم
        if ( is_customize_preview() || isset( $_GET['preview'] ) ) {
            return false;
        }
        
        // اگر در حال ویرایش در صفحه‌سازها هستیم
        $page_builders = [
            'elementor-preview', 'elementor', 'vc_editable', 'vc_action',
            'fl_builder', 'et_fb', 'brizy', 'oxygen'
        ];
        
        foreach ( $page_builders as $builder ) {
            if ( isset( $_GET[$builder] ) ) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * گرفتن وضعیت Override
     */
    public static function get_status() {
        $locations = get_nav_menu_locations();
        
        $headrix_locations = 0;
        $theme_locations = 0;
        
        foreach ( $locations as $location => $menu_id ) {
            if ( strpos( $location, 'headrix_' ) === 0 ) {
                $headrix_locations++;
            } else {
                $theme_locations++;
            }
        }
        
        return [
            'active' => self::is_headrix_active(),
            'theme' => [
                'name' => self::$current_theme,
                'version' => self::$theme_version,
            ],
            'settings' => [
                'menu_override' => get_option( 'headrix_menu_override', 1 ),
                'target_menu' => get_option( 'headrix_target_menu', 0 ),
                'has_target_menu' => (bool) get_option( 'headrix_target_menu', 0 ),
            ],
            'locations' => [
                'headrix' => $headrix_locations,
                'theme' => $theme_locations,
                'total' => count( $locations ),
            ],
            'state' => [
                'theme_headers_disabled' => self::$theme_headers_disabled,
                'header_rendered' => self::$header_rendered,
            ],
            'compatibility' => [
                'elementor' => defined( 'ELEMENTOR_VERSION' ),
                'wpbakery' => defined( 'WPB_VC_VERSION' ),
                'beaver' => defined( 'FL_BUILDER_VERSION' ),
                'divi' => defined( 'ET_BUILDER_VERSION' ),
            ]
        ];
    }
    
    /**
     * AJAX handler برای گرفتن وضعیت
     */
    public static function ajax_get_status() {
        check_ajax_referer( 'headrix_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( -1, 403 );
        }
        
        wp_send_json_success( self::get_status() );
    }
    
    /**
     * گرفتن نام تابع از callback
     */
    private static function get_function_name( $callback ) {
        if ( is_string( $callback ) ) {
            return $callback;
        }
        
        if ( is_array( $callback ) ) {
            if ( is_object( $callback[0] ) ) {
                return get_class( $callback[0] ) . '::' . $callback[1];
            }
            return $callback[0] . '::' . $callback[1];
        }
        
        if ( $callback instanceof \Closure ) {
            return 'Closure';
        }
        
        return 'unknown';
    }
    
    /**
     * فعال‌سازی مجدد قالب (برای deactivation)
     */
    public static function restore_theme_features() {
        // بازگرداندن موقعیت‌های منو
        $original_locations = get_option( 'headrix_original_menu_locations', [] );
        if ( ! empty( $original_locations ) ) {
            set_theme_mod( 'nav_menu_locations', $original_locations );
        }
        
        // حذف فیلترها
        remove_filter( 'wp_nav_menu_args', [ __CLASS__, 'force_headrix_args' ], 9999 );
        remove_filter( 'body_class', [ __CLASS__, 'add_override_class' ] );
        remove_filter( 'wp_nav_menu', [ __CLASS__, 'prevent_duplicate_menu' ], 10 );
        
        // حذف اکشن‌ها
        remove_action( 'wp', [ __CLASS__, 'disable_theme_features' ], 1 );
        remove_action( 'wp_head', [ __CLASS__, 'add_hide_css' ], 999 );
        remove_action( 'wp_enqueue_scripts', [ __CLASS__, 'remove_theme_menu_assets' ], 999 );
        
        // پاک کردن کش
        delete_option( 'headrix_original_menu_locations' );
    }
}