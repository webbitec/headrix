<?php
namespace Headrix\Settings;

use Headrix\Core\Security;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ImportExport {

    public static function init() {
        add_action( 'admin_post_headrix_export', [ __CLASS__, 'export' ] );
        add_action( 'admin_post_headrix_import', [ __CLASS__, 'import' ] );
    }

    public static function export() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );
        $options = [
            'headrix_sticky'        => get_option( 'headrix_sticky', 1 ),
            'headrix_logo_position' => get_option( 'headrix_logo_position', 'left' ),
            'headrix_bg_color'      => get_option( 'headrix_bg_color', '#ffffff' ),
        ];
        header( 'Content-Type: application/json' );
        header( 'Content-Disposition: attachment; filename=headrix-settings.json' );
        echo wp_json_encode( $options );
        exit;
    }

    public static function import() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );
        if ( ! Security::verify_nonce() ) wp_die( 'Invalid nonce' );
        if ( ! isset( $_FILES['headrix_import_file'] ) || empty( $_FILES['headrix_import_file']['tmp_name'] ) ) {
            wp_redirect( admin_url( 'admin.php?page=headrix-settings' ) );
            exit;
        }
        $json = file_get_contents( $_FILES['headrix_import_file']['tmp_name'] );
        $data = json_decode( $json, true );
        if ( is_array( $data ) ) {
            foreach ( $data as $key => $val ) {
                update_option( sanitize_text_field( $key ), sanitize_text_field( $val ) );
            }
        }
        wp_redirect( admin_url( 'admin.php?page=headrix-settings' ) );
        exit;
    }
}
