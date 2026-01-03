<?php
namespace Headrix\Menu;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * کنترلر اصلی مگامنو
 */
class MegaMenu {

    /**
     * راه‌اندازی مگامنو
     */
    public static function init() {
        // بارگذاری وابستگی‌ها
        require_once HEADRIX_PLUGIN_DIR . 'modules/menu/walker.php';
        require_once HEADRIX_PLUGIN_DIR . 'modules/menu/megamenu-walker.php';
        
        // فیلترهای مگامنو
        add_filter( 'nav_menu_css_class', [ __CLASS__, 'add_mega_menu_classes' ], 10, 4 );
        add_filter( 'nav_menu_link_attributes', [ __CLASS__, 'add_mega_menu_attributes' ], 10, 4 );
        
        // استایل‌های مگامنو
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_mega_menu_styles' ] );
    }

    /**
     * اضافه کردن کلاس‌های مگامنو
     */
    public static function add_mega_menu_classes( $classes, $item, $args, $depth ) {
        // فقط اگر مگامنو فعال است
        if ( ! get_option( 'headrix_mega_menu', 0 ) ) {
            return $classes;
        }
        
        // بررسی مگامنو برای آیتم سطح اول
        if ( $depth === 0 ) {
            $is_mega = get_post_meta( $item->ID, '_headrix_mega_menu', true );
            if ( $is_mega ) {
                $classes[] = 'hdrx-has-mega';
                $classes[] = 'hdrx-mega-enabled';
                
                // اضافه کردن کلاس تعداد ستون‌ها
                $columns = get_post_meta( $item->ID, '_headrix_mega_columns', true ) ?: 4;
                $classes[] = 'hdrx-mega-columns-' . $columns;
            }
        }
        
        // کلاس‌های آیتم‌های مگامنو
        if ( $depth === 1 && in_array( 'hdrx-mega-enabled', $classes ) ) {
            $classes[] = 'hdrx-mega-item';
        }
        
        return $classes;
    }

    /**
     * اضافه کردن ویژگی‌های مگامنو
     */
    public static function add_mega_menu_attributes( $atts, $item, $args, $depth ) {
        // فقط اگر مگامنو فعال است
        if ( ! get_option( 'headrix_mega_menu', 0 ) ) {
            return $atts;
        }
        
        if ( $depth === 0 ) {
            $is_mega = get_post_meta( $item->ID, '_headrix_mega_menu', true );
            if ( $is_mega ) {
                $atts['data-mega'] = 'true';
                $atts['data-columns'] = get_post_meta( $item->ID, '_headrix_mega_columns', true ) ?: 4;
            }
        }
        
        return $atts;
    }

    /**
     * استایل‌های مگامنو
     */
    public static function enqueue_mega_menu_styles() {
        if ( get_option( 'headrix_mega_menu', 0 ) ) {
            wp_add_inline_style( 'headrix-frontend', self::get_mega_menu_css() );
        }
    }

    /**
     * تولید CSS مگامنو
     */
    private static function get_mega_menu_css() {
        $submenu_width = get_option( 'headrix_submenu_width', 200 );
        $mega_menu_width = $submenu_width * 4; // 4 ستون
        
        return "
            /* مگامنو */
            .hdrx-nav > li.hdrx-has-mega {
                position: static;
            }
            
            .hdrx-nav > li.hdrx-has-mega > .sub-menu {
                width: {$mega_menu_width}px;
                left: 50%;
                transform: translateX(-50%);
                padding: 20px;
            }
            
            .hdrx-mega-menu-content {
                display: flex;
                flex-wrap: wrap;
            }
            
            .hdrx-mega-column {
                flex: 1;
                min-width: 200px;
                padding: 0 15px;
            }
            
            .hdrx-mega-column-title {
                font-weight: 600;
                margin-bottom: 10px;
                color: #333;
            }
            
            .hdrx-mega-column .sub-menu {
                position: static;
                opacity: 1;
                visibility: visible;
                transform: none;
                box-shadow: none;
                padding: 0;
                background: transparent;
            }
            
            .hdrx-mega-column .sub-menu li {
                margin-bottom: 8px;
            }
            
            .hdrx-mega-column .sub-menu li a {
                padding: 5px 0;
                color: #666;
                transition: color 0.3s ease;
            }
            
            .hdrx-mega-column .sub-menu li a:hover {
                color: #0073aa;
                background: transparent;
            }
            
            /* ریسپانسیو مگامنو */
            @media (max-width: 992px) {
                .hdrx-nav > li.hdrx-has-mega > .sub-menu {
                    width: 90%;
                    max-width: 600px;
                }
                
                .hdrx-mega-menu-content {
                    flex-direction: column;
                }
                
                .hdrx-mega-column {
                    width: 100%;
                    margin-bottom: 20px;
                }
            }
            
            @media (max-width: 768px) {
                .hdrx-nav > li.hdrx-has-mega > .sub-menu {
                    display: none;
                }
                
                .hdrx-mega-enabled .hdrx-submenu-toggle {
                    display: block;
                }
            }
        ";
    }

    /**
     * بررسی فعال بودن مگامنو
     */
    public static function is_mega_menu_active() {
        return (bool) get_option( 'headrix_mega_menu', 0 );
    }

    /**
     * گرفتن تنظیمات مگامنو
     */
    public static function get_settings() {
        return [
            'enabled' => get_option( 'headrix_mega_menu', 0 ),
            'submenu_width' => get_option( 'headrix_submenu_width', 200 ),
            'dropdown_animation' => get_option( 'headrix_dropdown_animation', 'fade' ),
            'hover_effect' => get_option( 'headrix_menu_hover_effect', 'underline' ),
        ];
    }
}