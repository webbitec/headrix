<?php
namespace Headrix\Menu;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * واکر مگامنو Headrix
 */
class MegaMenuWalker extends DefaultWalker {

    private $mega_menu_active = false;
    private $mega_menu_columns = 4;
    private $current_column = 0;
    private $column_items = [];

    /**
     * شروع لیست
     */
    public function start_lvl( &$output, $depth = 0, $args = null ) {
        $indent = str_repeat( "\t", $depth );
        
        if ( $depth === 0 && $this->mega_menu_active ) {
            // شروع مگامنو
            $output .= "\n$indent<div class=\"hdrx-mega-menu-content\">\n";
            $output .= "$indent\t<div class=\"hdrx-mega-menu-row\">\n";
        } elseif ( $depth === 1 && $this->mega_menu_active ) {
            // شروع ستون مگامنو
            $this->current_column++;
            $output .= "\n$indent<div class=\"hdrx-mega-column hdrx-col-" . $this->mega_menu_columns . "\">\n";
            $output .= "$indent\t<ul class=\"sub-menu\">\n";
        } else {
            // منوی معمولی
            $output .= "\n$indent<ul class=\"sub-menu\">\n";
        }
    }

    /**
     * پایان لیست
     */
    public function end_lvl( &$output, $depth = 0, $args = null ) {
        $indent = str_repeat( "\t", $depth );
        
        if ( $depth === 0 && $this->mega_menu_active ) {
            // پایان مگامنو
            $output .= "$indent\t</div>\n";
            $output .= "$indent</div>\n";
            $this->mega_menu_active = false;
            $this->current_column = 0;
        } elseif ( $depth === 1 && $this->mega_menu_active ) {
            // پایان ستون مگامنو
            $output .= "$indent\t</ul>\n";
            $output .= "$indent</div>\n";
        } else {
            // منوی معمولی
            $output .= "$indent</ul>\n";
        }
    }

    /**
     * شروع آیتم
     */
    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        // بررسی مگامنو برای سطح اول
        if ( $depth === 0 ) {
            $this->mega_menu_active = (bool) get_post_meta( $item->ID, '_headrix_mega_menu', true );
            $this->mega_menu_columns = get_post_meta( $item->ID, '_headrix_mega_columns', true ) ?: 4;
        }
        
        // اگر مگامنو فعال است و سطح دوم است، عنوان ستون را اضافه کن
        if ( $depth === 1 && $this->mega_menu_active && $this->current_column > 0 ) {
            $indent = str_repeat( "\t", $depth );
            
            // اولین آیتم سطح دوم عنوان ستون است
            if ( empty( $this->column_items[ $this->current_column ] ) ) {
                $this->column_items[ $this->current_column ] = true;
                
                $title = apply_filters( 'the_title', $item->title, $item->ID );
                $title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );
                
                $output .= $indent . "\t<li class=\"hdrx-mega-column-title\">";
                $output .= '<span class="hdrx-mega-title">' . $title . '</span>';
                $output .= "</li>\n";
                
                // آیتم اصلی را پردازش نکن
                return;
            }
        }
        
        // ادامه پردازش معمولی
        parent::start_el( $output, $item, $depth, $args, $id );
    }

    /**
     * پایان آیتم
     */
    public function end_el( &$output, $item, $depth = 0, $args = null ) {
        if ( ! ( $depth === 1 && $this->mega_menu_active && empty( $this->column_items[ $this->current_column ] ) ) ) {
            parent::end_el( $output, $item, $depth, $args );
        }
    }

    /**
     * اضافه کردن کلاس‌های ویژه به آیتم‌ها
     */
    public function add_special_classes( $classes, $item, $args, $depth ) {
        if ( $depth === 0 && $this->mega_menu_active ) {
            $classes[] = 'menu-item-has-mega';
            $classes[] = 'hdrx-mega-menu';
        }
        
        if ( $depth === 1 && $this->mega_menu_active ) {
            $classes[] = 'hdrx-mega-item';
        }
        
        return $classes;
    }
}