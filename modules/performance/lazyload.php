<?php
namespace Headrix\Performance;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LazyLoad {

    public static function init() {
        add_filter( 'script_loader_tag', [ __CLASS__, 'add_defer' ], 10, 3 );
    }

    public static function add_defer( $tag, $handle, $src ) {
        if ( 'headrix-frontend' === $handle ) {
            $tag = '<script src="' . esc_url( $src ) . '" defer></script>';
        }
        return $tag;
    }
}
