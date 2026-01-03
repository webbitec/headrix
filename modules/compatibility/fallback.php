<?php
namespace Headrix\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Fallback {
    public static function init() {
        // Ensure wp_body_open exists for older themes
        if ( ! function_exists( 'wp_body_open' ) ) {
            add_action( 'wp_footer', [ __CLASS__, 'body_open_fallback' ] );
        }
    }

    public static function body_open_fallback() {
        do_action( 'wp_body_open' );
    }
}
