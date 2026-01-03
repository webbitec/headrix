<?php
namespace Headrix\Performance;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Accessibility {

    public static function init() {
        add_filter( 'nav_menu_link_attributes', [ __CLASS__, 'add_aria' ], 10, 3 );
    }

    public static function add_aria( $atts, $item, $args ) {
        if ( isset( $atts['href'] ) && '#' === $atts['href'] ) {
            $atts['role'] = 'button';
        }
        return $atts;
    }
}
