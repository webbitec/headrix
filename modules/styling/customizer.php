<?php
namespace Headrix\Styling;

use Headrix\Core\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Customizer {

    public static function init() {
        add_action( 'customize_register', [ __CLASS__, 'register' ] );
        add_action( 'customize_preview_init', [ __CLASS__, 'preview_assets' ] );
    }

    public static function register( $wp_customize ) {
        $wp_customize->add_section( 'headrix_header', [
            'title'    => __( 'Headrix Header', 'headrix' ),
            'priority' => 30,
        ] );

        $wp_customize->add_setting( 'headrix_bg_color', [
            'default'           => '#ffffff',
            'sanitize_callback' => [ __CLASS__, 'sanitize_color' ],
            'transport'         => 'postMessage',
        ] );

        $wp_customize->add_control( new \WP_Customize_Color_Control(
            $wp_customize,
            'headrix_bg_color',
            [
                'label'   => __( 'Header Background', 'headrix' ),
                'section' => 'headrix_header',
            ]
        ) );
    }

    public static function preview_assets() {
        wp_enqueue_script( 'headrix-customizer', Helpers::asset_url( 'assets/js/frontend.js' ), [ 'customize-preview' ], \HEADRIX_VERSION, true );
    }

    public static function sanitize_color( $value ) {
        return Helpers::esc_color( $value, '#ffffff' );
    }
}
