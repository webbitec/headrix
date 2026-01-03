<?php
namespace Headrix\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Helpers {

    public static function get_option( $key, $default = '' ) {
        $value = get_option( $key, $default );
        return is_string( $value ) ? sanitize_text_field( $value ) : $value;
    }

    public static function get_bool_option( $key, $default = 0 ) {
        $value = get_option( $key, $default );
        return (int) ( ! empty( $value ) );
    }

    public static function esc_color( $hex, $fallback = '#ffffff' ) {
        $hex = trim( $hex );
        if ( preg_match( '/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $hex ) ) {
            return $hex;
        }
        return $fallback;
    }

    public static function asset_url( $path ) {
        return trailingslashit( \HEADRIX_PLUGIN_URL ) . ltrim( $path, '/' );
    }

    public static function plugin_path( $path ) {
        return \HEADRIX_PLUGIN_DIR . ltrim( $path, '/' );
    }
}
