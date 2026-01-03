<?php
/**
 * Plugin Name: Headrix
 * Plugin URI:  https://headrix.io
 * Description: Professional Header & Mega Menu Builder for WordPress.
 * Version:     1.0.0
 * Author:      Milad
 * Author URI:  https://headrix.io
 * License:     GPLv2 or later
 * Text Domain: headrix
 * Domain Path: /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Constants
define( 'HEADRIX_VERSION', '1.0.0' );
define( 'HEADRIX_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HEADRIX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// PSR-4-like autoloader (for core/ namespaces)
spl_autoload_register( function ( $class ) {
    $prefix   = 'Headrix\\';
    $len      = strlen( $prefix );

    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }
    
    $relative_class = substr( $class, $len );
    
    // اول در core جستجو کن
    $file = HEADRIX_PLUGIN_DIR . 'core/' . str_replace( '\\', '/', $relative_class ) . '.php';
    if ( file_exists( $file ) ) {
        require $file;
        return;
    }
    
    // سپس در modules جستجو کن
    $file = HEADRIX_PLUGIN_DIR . 'modules/' . str_replace( '\\', '/', $relative_class ) . '.php';
    if ( file_exists( $file ) ) {
        require $file;
        return;
    }
    
    // سپس در مسیر مستقیم
    $file = HEADRIX_PLUGIN_DIR . str_replace( '\\', '/', $relative_class ) . '.php';
    if ( file_exists( $file ) ) {
        require $file;
    }
} );

// Activation / Deactivation
register_activation_hook( __FILE__, [ 'Headrix\\Bootstrap', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'Headrix\\Bootstrap', 'deactivate' ] );

// Bootstrap - با plugins_loaded بارگذاری کن
add_action( 'plugins_loaded', function() {
    require_once HEADRIX_PLUGIN_DIR . 'core/bootstrap.php';
    Headrix\Bootstrap::init();
}, 0 );