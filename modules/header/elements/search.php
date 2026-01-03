<?php
namespace Headrix\Header\Elements;

use Headrix\Core\API;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Search {
    public static function register() {
        API::register_header_element( 'search', [ __CLASS__, 'render' ] );
    }

    public static function render() {
        echo '<button class="hdrx-search-toggle" aria-label="' . esc_attr__( 'Open search', 'headrix' ) . '">🔍</button>';
        echo '<div class="hdrx-search-box" hidden>';
        get_search_form();
        echo '</div>';
        ?>
        <script>
        (function(){
            var btn = document.querySelector('.hdrx-search-toggle');
            var box = document.querySelector('.hdrx-search-box');
            if (!btn || !box) return;
            btn.addEventListener('click', function(){
                var isHidden = box.hasAttribute('hidden');
                if (isHidden) box.removeAttribute('hidden');
                else box.setAttribute('hidden','hidden');
            });
        })();
        </script>
        <?php
    }
}
Search::register();
