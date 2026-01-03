<?php
namespace Headrix\Header;

use Headrix\Core\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Sticky {

    public static function init() {
        if ( Helpers::get_bool_option( 'headrix_sticky', 1 ) ) {
            add_action( 'wp_head', [ __CLASS__, 'print_sticky_style' ] );
            add_action( 'wp_footer', [ __CLASS__, 'print_sticky_script' ] );
        }
    }

    public static function print_sticky_style() {
        $desktop_logo = (int) get_option( 'headrix_logo_size_desktop', 50 );
        $mobile_logo  = (int) get_option( 'headrix_logo_size_mobile', 40 );
        $breakpoint   = (int) get_option( 'headrix_breakpoint', 768 );

        echo '<style>
            .hdrx-header.sticky{position:fixed;top:0;left:0;right:0;box-shadow:0 2px 6px rgba(0,0,0,.1);background:var(--hdrx-bg)}
            body.hdrx-has-sticky{padding-top:' . max( $desktop_logo + 20, 70 ) . 'px}
            .hdrx-logo img{max-height:' . $desktop_logo . 'px;transition:max-height .3s ease}
            @media (max-width:' . $breakpoint . 'px){
              body.hdrx-has-sticky{padding-top:' . max( $mobile_logo + 20, 60 ) . 'px}
              .hdrx-logo img{max-height:' . $mobile_logo . 'px}
            }
        </style>';
    }

    public static function print_sticky_script() {
        ?>
        <script>
        (function(){
            var header = document.getElementById('headrix-header');
            if (!header) return;
            var offset = header.offsetTop || 0;
            window.addEventListener('scroll', function(){
                if (window.scrollY > offset) {
                    header.classList.add('sticky');
                    document.body.classList.add('hdrx-has-sticky');
                } else {
                    header.classList.remove('sticky');
                    document.body.classList.remove('hdrx-has-sticky');
                }
            }, {passive:true});
        })();
        </script>
        <?php
    }
}
