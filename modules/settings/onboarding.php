<?php
namespace Headrix\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Onboarding {

    public static function init() {
        add_action( 'admin_notices', [ __CLASS__, 'welcome_notice' ] );
    }

    public static function welcome_notice() {
        if ( get_option( 'headrix_onboarding_dismissed' ) ) return;
        ?>
        <div class="notice notice-success is-dismissible headrix-onboarding">
            <p><strong><?php esc_html_e( 'Headrix is active!', 'headrix' ); ?></strong> <?php esc_html_e( 'Configure your header from Headrix settings.', 'headrix' ); ?></p>
        </div>
        <script>
        (function($){
            $(document).on('click','.headrix-onboarding .notice-dismiss',function(){
                jQuery.post(ajaxurl, { action: 'headrix_dismiss_onboarding' });
            });
        })(jQuery);
        </script>
        <?php
        add_action( 'wp_ajax_headrix_dismiss_onboarding', function(){
            update_option( 'headrix_onboarding_dismissed', 1 );
            wp_die();
        } );
    }
}
