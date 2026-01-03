<?php
namespace Headrix\Header\Elements;

use Headrix\Core\API;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Social {
    public static function register() {
        API::register_header_element( 'social', [ __CLASS__, 'render' ] );
    }

    public static function render() {
        $links = apply_filters( 'headrix/header/social_links', [
            'twitter'  => '#',
            'facebook' => '#',
            'instagram'=> '#',
        ] );
        echo '<div class="hdrx-social">';
        foreach ( $links as $network => $url ) {
            echo '<a class="hdrx-social-link hdrx-' . esc_attr( $network ) . '" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( ucfirst( $network ) ) . '</a>';
        }
        echo '</div>';
    }
}
Social::register();
