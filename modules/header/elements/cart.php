<?php
namespace Headrix\Header\Elements;

use Headrix\Core\API;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cart {
    public static function register() {
        API::register_header_element( 'cart', [ __CLASS__, 'render' ] );
    }

    public static function render() {
        if ( function_exists( 'WC' ) ) {
            $count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
            echo '<a class="hdrx-cart-icon" href="' . esc_url( wc_get_cart_url() ) . '" aria-label="' . esc_attr__( 'Cart', 'headrix' ) . '">🛒<span class="hdrx-cart-count">' . intval( $count ) . '</span></a>';
        }
    }
}
Cart::register();
