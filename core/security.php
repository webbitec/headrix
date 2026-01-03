<?php
namespace Headrix\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Security {

    const NONCE_ACTION = 'headrix_action';
    const NONCE_NAME   = 'headrix_nonce';

    public static function verify_nonce() {
        if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
            return false;
        }
        return wp_verify_nonce( sanitize_text_field( $_POST[ self::NONCE_NAME ] ), self::NONCE_ACTION );
    }

    public static function print_nonce_field() {
        wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
    }

    public static function sanitize_array( $arr ) {
        if ( ! is_array( $arr ) ) {
            return [];
        }
        return array_map( function( $val ) {
            return is_string( $val ) ? sanitize_text_field( $val ) : $val;
        }, $arr );
    }
}
