<?php
namespace Headrix\Menu;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * مدیریت ویجت‌ها در مگامنو
 */
class Widgets {

    /**
     * راه‌اندازی
     */
    public static function init() {
        // ویجت‌های سفارشی
        add_action( 'widgets_init', [ __CLASS__, 'register_widget_areas' ] );
        
        // شورت‌کد برای نمایش ویجت در منو
        add_shortcode( 'headrix_widget', [ __CLASS__, 'widget_shortcode' ] );
        
        // Ajax برای مدیریت ویجت‌ها
        add_action( 'wp_ajax_headrix_get_widgets', [ __CLASS__, 'ajax_get_widgets' ] );
    }

    /**
     * ثبت ناحیه‌های ویجت
     */
    public static function register_widget_areas() {
        // ناحیه‌های ویجت برای مگامنو
        for ( $i = 1; $i <= 6; $i++ ) {
            register_sidebar([
                'name'          => sprintf( __( 'Headrix Mega Menu Widget %d', 'headrix' ), $i ),
                'id'            => 'headrix-mega-widget-' . $i,
                'description'   => __( 'Widgets in this area will be displayed in mega menus.', 'headrix' ),
                'before_widget' => '<div class="hdrx-mega-widget %2$s">',
                'after_widget'  => '</div>',
                'before_title'  => '<h4 class="hdrx-widget-title">',
                'after_title'   => '</h4>',
            ]);
        }
        
        // ناحیه‌های ویجت برای فوتر
        register_sidebar([
            'name'          => __( 'Headrix Footer Widgets', 'headrix' ),
            'id'            => 'headrix-footer-widgets',
            'description'   => __( 'Widgets in this area will be displayed in the footer menu.', 'headrix' ),
            'before_widget' => '<div class="hdrx-footer-widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h4 class="hdrx-footer-widget-title">',
            'after_title'   => '</h4>',
        ]);
    }

    /**
     * شورت‌کد ویجت
     */
    public static function widget_shortcode( $atts ) {
        $atts = shortcode_atts([
            'id' => '',
            'title' => '',
        ], $atts, 'headrix_widget' );
        
        if ( empty( $atts['id'] ) ) {
            return '';
        }
        
        ob_start();
        dynamic_sidebar( $atts['id'] );
        return ob_get_clean();
    }

    /**
     * Ajax گرفتن ویجت‌ها
     */
    public static function ajax_get_widgets() {
        check_ajax_referer( 'headrix_admin_nonce', 'nonce' );
        
        $widgets = [];
        global $wp_registered_sidebars;
        
        foreach ( $wp_registered_sidebars as $sidebar ) {
            if ( strpos( $sidebar['id'], 'headrix-' ) === 0 ) {
                $widgets[] = [
                    'id' => $sidebar['id'],
                    'name' => $sidebar['name'],
                    'description' => $sidebar['description'],
                ];
            }
        }
        
        wp_send_json_success( $widgets );
    }

    /**
     * نمایش ویجت در منو
     */
    public static function display_widget_in_menu( $widget_area, $menu_item_id ) {
        if ( ! is_active_sidebar( $widget_area ) ) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="hdrx-menu-widget" data-menu-item="<?php echo esc_attr( $menu_item_id ); ?>">
            <?php dynamic_sidebar( $widget_area ); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * گرفتن لیست ویجت‌ها
     */
    public static function get_widgets_list() {
        global $wp_registered_sidebars;
        $list = [];
        
        foreach ( $wp_registered_sidebars as $sidebar ) {
            $list[ $sidebar['id'] ] = $sidebar['name'];
        }
        
        return $list;
    }
}