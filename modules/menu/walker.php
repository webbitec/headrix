<?php
namespace Headrix\Menu;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * واکر پیش‌فرض منوهای Headrix
 */
class DefaultWalker extends \Walker_Nav_Menu {

    /**
     * شروع لیست
     */
    public function start_lvl( &$output, $depth = 0, $args = null ) {
        $indent = str_repeat( "\t", $depth );
        $output .= "\n$indent<ul class=\"sub-menu\">\n";
    }

    /**
     * پایان لیست
     */
    public function end_lvl( &$output, $depth = 0, $args = null ) {
        $indent = str_repeat( "\t", $depth );
        $output .= "$indent</ul>\n";
    }

    /**
     * شروع آیتم
     */
    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

        $classes = empty( $item->classes ) ? [] : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;
        
        // اضافه کردن کلاس‌های سفارشی
        $custom_class = get_post_meta( $item->ID, '_headrix_menu_custom_class', true );
        if ( $custom_class ) {
            $classes[] = $custom_class;
        }
        
        // مدیریت visibility
        $visibility = get_post_meta( $item->ID, '_headrix_menu_visibility', true );
        if ( $visibility && $visibility != 'both' ) {
            $classes[] = 'hdrx-' . $visibility . '-only';
        }

        $class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
        $class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

        $id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args, $depth );
        $id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

        $output .= $indent . '<li' . $id . $class_names . '>';

        $atts = [];
        $atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
        $atts['target'] = ! empty( $item->target )     ? $item->target     : '';
        $atts['rel']    = ! empty( $item->xfn )        ? $item->xfn        : '';
        $atts['href']   = ! empty( $item->url )        ? $item->url        : '';
        $atts['class']  = 'hdrx-menu-link';

        $atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

        $attributes = '';
        foreach ( $atts as $attr => $value ) {
            if ( is_scalar( $value ) && '' !== $value && false !== $value ) {
                $value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        $title = apply_filters( 'the_title', $item->title, $item->ID );
        $title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );

        $item_output = $args->before;
        $item_output .= '<a' . $attributes . '>';
        
        // اضافه کردن آیکون
        $icon = get_post_meta( $item->ID, '_headrix_menu_icon', true );
        if ( $icon ) {
            $item_output .= '<span class="hdrx-menu-icon ' . esc_attr( $icon ) . '"></span>';
        }
        
        $item_output .= $args->link_before . $title . $args->link_after;
        
        // اضافه کردن بدج
        $badge = get_post_meta( $item->ID, '_headrix_menu_badge', true );
        if ( $badge ) {
            $badge_color = get_post_meta( $item->ID, '_headrix_menu_badge_color', true );
            $badge_style = $badge_color ? ' style="background-color:' . esc_attr( $badge_color ) . '"' : '';
            $item_output .= '<span class="hdrx-menu-badge"' . $badge_style . '>' . esc_html( $badge ) . '</span>';
        }
        
        // اضافه کردن نشانگر ساب منو
        if ( in_array( 'menu-item-has-children', $classes ) ) {
            $item_output .= '<span class="hdrx-submenu-toggle"></span>';
        }
        
        $item_output .= '</a>';
        $item_output .= $args->after;

        $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
    }

    /**
     * پایان آیتم
     */
    public function end_el( &$output, $item, $depth = 0, $args = null ) {
        $output .= "</li>\n";
    }
}