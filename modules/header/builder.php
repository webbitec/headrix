<?php
namespace Headrix\Header;

use Headrix\Core\Helpers;
use Headrix\Core\API;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Builder {

    public static function init() {
        // رندر هدر
        add_action( 'wp_body_open', [ __CLASS__, 'render_header' ], 1 );
        
        // علامت‌گذاری که هدر رندر شده
        add_action( 'headrix_header_rendered', [ __CLASS__, 'mark_as_rendered' ] );
        
        // رنگ پس‌زمینه
        add_action( 'wp_head', function() {
            if ( API::get_option( 'menu_override', 1 ) ) {
                $bg = Helpers::esc_color( get_option( 'headrix_bg_color', '#ffffff' ) );
                echo '<style>:root{--hdrx-bg:' . esc_attr( $bg ) . ';}</style>';
            }
        } );
    }
    
    public static function mark_as_rendered() {
        // هیچ کاری لازم نیست، فقط action اجرا می‌شود
    }

    public static function render_header() {
        // اگر Headrix غیرفعال است، چیزی نشان نده
        if ( ! API::get_option( 'menu_override', 1 ) ) {
            return;
        }
        
        $logo_position = API::get_option( 'logo_position', 'left' );
        $header_layout = API::get_option( 'header_layout', 'standard' );
        $enable_search = API::get_option( 'enable_search', 0 );
        $enable_social = API::get_option( 'enable_social', 0 );
        $enable_cta = API::get_option( 'enable_cta', 0 );
        $enable_cart = API::get_option( 'enable_cart', 0 );

        $elements = API::get_header_elements();

        echo '<header id="headrix-header" class="hdrx-header hdrx-layout-' . esc_attr( $header_layout ) . '" role="banner">';
        echo '  <div class="hdrx-container">';
        
        // لایه‌بندی بر اساس نوع
        switch ( $header_layout ) {
            case 'centered':
                self::render_centered_layout( $elements, $logo_position, $enable_search, $enable_social, $enable_cta, $enable_cart );
                break;
            case 'split':
                self::render_split_layout( $elements, $logo_position, $enable_search, $enable_social, $enable_cta, $enable_cart );
                break;
            case 'stacked':
                self::render_stacked_layout( $elements, $logo_position, $enable_search, $enable_social, $enable_cta, $enable_cart );
                break;
            default: // standard
                self::render_standard_layout( $elements, $logo_position, $enable_search, $enable_social, $enable_cta, $enable_cart );
                break;
        }
        
        // منوی موبایل
        self::render_mobile_menu( $elements );
        
        echo '  </div>';
        echo '</header>';
        
        // علامت‌گذاری که هدر رندر شده
        do_action( 'headrix_header_rendered' );
    }
    
    private static function render_standard_layout( $elements, $logo_position, $enable_search, $enable_social, $enable_cta, $enable_cart ) {
        echo '<div class="hdrx-row hdrx-standard-layout">';
        
        // ستون 1: لوگو
        echo '<div class="hdrx-col hdrx-logo-col">';
        if ( isset( $elements['logo'] ) && is_callable( $elements['logo'] ) ) {
            call_user_func( $elements['logo'] );
        }
        echo '</div>';
        
        // ستون 2: منو (مرکز)
        echo '<div class="hdrx-col hdrx-menu-col">';
        if ( isset( $elements['menu'] ) && is_callable( $elements['menu'] ) ) {
            echo '<div class="hdrx-menu-wrapper">';
            call_user_func( $elements['menu'] );
            echo '</div>';
        }
        echo '</div>';
        
        // ستون 3: اکشن‌ها
        echo '<div class="hdrx-col hdrx-actions-col">';
        echo '<div class="hdrx-actions-wrapper">';
        if ( $enable_search && isset( $elements['search'] ) && is_callable( $elements['search'] ) ) {
            call_user_func( $elements['search'] );
        }
        if ( $enable_cart && isset( $elements['cart'] ) && is_callable( $elements['cart'] ) ) {
            call_user_func( $elements['cart'] );
        }
        if ( $enable_cta && isset( $elements['button'] ) && is_callable( $elements['button'] ) ) {
            call_user_func( $elements['button'] );
        }
        if ( $enable_social && isset( $elements['social'] ) && is_callable( $elements['social'] ) ) {
            call_user_func( $elements['social'] );
        }
        if ( isset( $elements['custom'] ) && is_callable( $elements['custom'] ) ) {
            call_user_func( $elements['custom'] );
        }
        
        // همبرگر موبایل
        echo '<button class="hdrx-mobile-toggle" aria-label="' . esc_attr__( 'Toggle Menu', 'headrix' ) . '">';
        echo '<span></span><span></span><span></span>';
        echo '</button>';
        
        echo '</div>';
        echo '</div>';
        
        echo '</div>'; // .hdrx-row
    }
    
    private static function render_centered_layout( $elements, $logo_position, $enable_search, $enable_social, $enable_cta, $enable_cart ) {
        echo '<div class="hdrx-row hdrx-centered-layout">';
        
        // ستون 1: اکشن‌های چپ
        echo '<div class="hdrx-col hdrx-left-col">';
        echo '<div class="hdrx-left-actions">';
        if ( $enable_search && isset( $elements['search'] ) && is_callable( $elements['search'] ) ) {
            call_user_func( $elements['search'] );
        }
        echo '</div>';
        echo '</div>';
        
        // ستون 2: لوگو و منو (مرکز)
        echo '<div class="hdrx-col hdrx-center-col">';
        if ( isset( $elements['logo'] ) && is_callable( $elements['logo'] ) ) {
            echo '<div class="hdrx-logo-centered">';
            call_user_func( $elements['logo'] );
            echo '</div>';
        }
        
        if ( isset( $elements['menu'] ) && is_callable( $elements['menu'] ) ) {
            echo '<div class="hdrx-menu-centered">';
            call_user_func( $elements['menu'] );
            echo '</div>';
        }
        echo '</div>';
        
        // ستون 3: اکشن‌های راست
        echo '<div class="hdrx-col hdrx-right-col">';
        echo '<div class="hdrx-right-actions">';
        if ( $enable_cart && isset( $elements['cart'] ) && is_callable( $elements['cart'] ) ) {
            call_user_func( $elements['cart'] );
        }
        if ( $enable_cta && isset( $elements['button'] ) && is_callable( $elements['button'] ) ) {
            call_user_func( $elements['button'] );
        }
        if ( $enable_social && isset( $elements['social'] ) && is_callable( $elements['social'] ) ) {
            call_user_func( $elements['social'] );
        }
        if ( isset( $elements['custom'] ) && is_callable( $elements['custom'] ) ) {
            call_user_func( $elements['custom'] );
        }
        
        // همبرگر موبایل
        echo '<button class="hdrx-mobile-toggle" aria-label="' . esc_attr__( 'Toggle Menu', 'headrix' ) . '">';
        echo '<span></span><span></span><span></span>';
        echo '</button>';
        
        echo '</div>';
        echo '</div>';
        
        echo '</div>'; // .hdrx-row
    }
    
    private static function render_split_layout( $elements, $logo_position, $enable_search, $enable_social, $enable_cta, $enable_cart ) {
        echo '<div class="hdrx-row hdrx-split-layout">';
        
        // ستون 1: لوگو و منوی چپ
        echo '<div class="hdrx-col hdrx-left-col">';
        if ( isset( $elements['logo'] ) && is_callable( $elements['logo'] ) ) {
            echo '<div class="hdrx-logo-split">';
            call_user_func( $elements['logo'] );
            echo '</div>';
        }
        
        if ( isset( $elements['menu'] ) && is_callable( $elements['menu'] ) ) {
            echo '<div class="hdrx-menu-left">';
            call_user_func( $elements['menu'] );
            echo '</div>';
        }
        echo '</div>';
        
        // ستون 2: اکشن‌های راست
        echo '<div class="hdrx-col hdrx-right-col">';
        echo '<div class="hdrx-actions-split">';
        if ( $enable_search && isset( $elements['search'] ) && is_callable( $elements['search'] ) ) {
            call_user_func( $elements['search'] );
        }
        if ( $enable_cart && isset( $elements['cart'] ) && is_callable( $elements['cart'] ) ) {
            call_user_func( $elements['cart'] );
        }
        if ( $enable_cta && isset( $elements['button'] ) && is_callable( $elements['button'] ) ) {
            call_user_func( $elements['button'] );
        }
        if ( $enable_social && isset( $elements['social'] ) && is_callable( $elements['social'] ) ) {
            call_user_func( $elements['social'] );
        }
        if ( isset( $elements['custom'] ) && is_callable( $elements['custom'] ) ) {
            call_user_func( $elements['custom'] );
        }
        
        // همبرگر موبایل
        echo '<button class="hdrx-mobile-toggle" aria-label="' . esc_attr__( 'Toggle Menu', 'headrix' ) . '">';
        echo '<span></span><span></span><span></span>';
        echo '</button>';
        
        echo '</div>';
        echo '</div>';
        
        echo '</div>'; // .hdrx-row
    }
    
    private static function render_stacked_layout( $elements, $logo_position, $enable_search, $enable_social, $enable_cta, $enable_cart ) {
        // ردیف 1: لوگو و اکشن‌ها
        echo '<div class="hdrx-row hdrx-top-row">';
        
        echo '<div class="hdrx-col hdrx-top-left">';
        if ( isset( $elements['logo'] ) && is_callable( $elements['logo'] ) ) {
            call_user_func( $elements['logo'] );
        }
        echo '</div>';
        
        echo '<div class="hdrx-col hdrx-top-right">';
        echo '<div class="hdrx-top-actions">';
        if ( $enable_search && isset( $elements['search'] ) && is_callable( $elements['search'] ) ) {
            call_user_func( $elements['search'] );
        }
        if ( $enable_cart && isset( $elements['cart'] ) && is_callable( $elements['cart'] ) ) {
            call_user_func( $elements['cart'] );
        }
        if ( $enable_cta && isset( $elements['button'] ) && is_callable( $elements['button'] ) ) {
            call_user_func( $elements['button'] );
        }
        if ( $enable_social && isset( $elements['social'] ) && is_callable( $elements['social'] ) ) {
            call_user_func( $elements['social'] );
        }
        if ( isset( $elements['custom'] ) && is_callable( $elements['custom'] ) ) {
            call_user_func( $elements['custom'] );
        }
        echo '</div>';
        echo '</div>';
        
        echo '</div>'; // .hdrx-top-row
        
        // ردیف 2: منو
        echo '<div class="hdrx-row hdrx-bottom-row">';
        echo '<div class="hdrx-col hdrx-full-width">';
        if ( isset( $elements['menu'] ) && is_callable( $elements['menu'] ) ) {
            echo '<div class="hdrx-menu-stacked">';
            call_user_func( $elements['menu'] );
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
    }
    
    private static function render_mobile_menu( $elements ) {
        echo '<div class="hdrx-mobile-menu-overlay"></div>';
        echo '<div class="hdrx-mobile-menu">';
        echo '  <div class="hdrx-mobile-menu-header">';
        echo '    <button class="hdrx-mobile-close" aria-label="' . esc_attr__( 'Close Menu', 'headrix' ) . '">&times;</button>';
        echo '  </div>';
        echo '  <div class="hdrx-mobile-menu-content">';
        
        if ( isset( $elements['menu'] ) && is_callable( $elements['menu'] ) ) {
            $menu_args = [
                'container'      => 'nav',
                'container_class'=> 'hdrx-mobile-nav',
                'menu_class'     => 'hdrx-mobile-nav-menu',
                'depth'          => 2,
                'fallback_cb'    => false,
            ];
            
            // استفاده از همان منوی اصلی
            $menu_id = get_option( 'headrix_target_menu', 0 );
            if ( $menu_id ) {
                $menu_args['menu'] = $menu_id;
            } else {
                $menu_args['theme_location'] = 'headrix_primary';
            }
            
            wp_nav_menu( $menu_args );
        }
        
        echo '  </div>';
        echo '</div>';
    }
}