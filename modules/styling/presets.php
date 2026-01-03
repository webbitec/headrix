<?php
namespace Headrix\Styling;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Presets {
    public static function get() {
        return [
            'classic' => [
                'bg'      => '#ffffff',
                'text'    => '#222222',
                'primary' => '#16a2b8',
            ],
            'minimal' => [
                'bg'      => '#ffffff',
                'text'    => '#111111',
                'primary' => '#0d6efd',
            ],
            'shop' => [
                'bg'      => '#f8f9fa',
                'text'    => '#212529',
                'primary' => '#198754',
            ],
        ];
    }
}
