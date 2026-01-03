<?php
namespace Headrix\Header\Elements;

use Headrix\Core\API;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Custom {
    public static function register() {
        API::register_header_element( 'custom', [ __CLASS__, 'render' ] );
    }

    public static function render() {
        echo apply_filters( 'headrix/header/custom_html', '' );
    }
}
Custom::register();
