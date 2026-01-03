<?php
namespace Headrix\Header\Elements;

use Headrix\Core\API;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Menu {
    
    public static function init() {
        self::register();
    }
    
    public static function register() {
        API::register_header_element( 'menu', [ __CLASS__, 'render' ] );
    }
    
    public static function render() {
        $menu_location = 'headrix_primary';
        $menu_id = get_option( 'headrix_target_menu', 0 );
        
        $args = [
            'theme_location' => $menu_location,
            'container'      => 'nav',
            'container_class'=> 'hdrx-nav-container',
            'menu_class'     => 'hdrx-nav',
            'fallback_cb'    => [ __CLASS__, 'fallback_menu' ],
            'depth'          => 3,
        ];
        
        // اگر منوی خاصی انتخاب شده
        if ( $menu_id ) {
            $args['menu'] = $menu_id;
            unset( $args['theme_location'] );
        }
        
        wp_nav_menu( $args );
    }
    
    public static function fallback_menu() {
        echo '<div class="hdrx-fallback-menu">';
        echo '<a href="' . admin_url( 'nav-menus.php' ) . '">' . __( 'Create a Menu', 'headrix' ) . '</a>';
        echo '</div>';
    }
}

// راه‌اندازی خودکار
Menu::init();