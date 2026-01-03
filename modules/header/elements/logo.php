<?php
namespace Headrix\Header\Elements;

use Headrix\Core\API;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Logo {
    
    public static function init() {
        self::register();
    }
    
    public static function register() {
        API::register_header_element( 'logo', [ __CLASS__, 'render' ] );
    }
    
    public static function render() {
        $logo_url = get_option( 'headrix_logo_url', '' );
        $logo_alt = get_bloginfo( 'name' );
        $home_url = home_url( '/' );
        
        if ( empty( $logo_url ) ) {
            // اگر لوگوی سفارشی تنظیم نشده، از لوگوی قالب استفاده کن
            $logo_url = get_theme_mod( 'custom_logo' ) 
                ? wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' )
                : '';
            
            // اگر قالب لوگو ندارد، از نام سایت استفاده کن
            if ( empty( $logo_url ) ) {
                echo '<div class="hdrx-site-title">';
                echo '<a href="' . esc_url( $home_url ) . '">' . esc_html( $logo_alt ) . '</a>';
                echo '</div>';
                return;
            }
        }
        
        echo '<div class="hdrx-logo">';
        echo '<a href="' . esc_url( $home_url ) . '" class="hdrx-logo-link">';
        echo '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( $logo_alt ) . '" class="hdrx-logo-image">';
        echo '</a>';
        echo '</div>';
    }
}

// راه‌اندازی خودکار
Logo::init();