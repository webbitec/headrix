<?php
namespace Headrix\Header\Elements;

use Headrix\Core\API;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Button {
    public static function register() {
        API::register_header_element( 'button', [ __CLASS__, 'render' ] );
    }

    public static function render() {
        $enabled = (int) get_option( 'headrix_enable_cta', 0 );
        if ( ! $enabled ) {
            return;
        }
        $label = get_option( 'headrix_cta_label', __( 'Get Started', 'headrix' ) );
        $url   = get_option( 'headrix_cta_url', home_url( '/' ) );

        $label = sanitize_text_field( $label );
        $url   = esc_url( $url );

        echo '<a class="hdrx-cta-btn" href="' . $url . '">' . esc_html( $label ) . '</a>';
    }
}
Button::register();
