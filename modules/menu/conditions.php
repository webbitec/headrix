<?php
namespace Headrix\Menu;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * سیستم شرایط نمایش منو
 */
class Conditions {

    /**
     * راه‌اندازی
     */
    public static function init() {
        // فیلتر آیتم‌های منو
        add_filter( 'wp_nav_menu_objects', [ __CLASS__, 'filter_menu_items' ], 10, 2 );
        
        // شورت‌کدهای شرطی
        add_shortcode( 'headrix_condition', [ __CLASS__, 'condition_shortcode' ] );
    }

    /**
     * فیلتر آیتم‌های منو بر اساس شرایط
     */
    public static function filter_menu_items( $items, $args ) {
        $filtered_items = [];
        
        foreach ( $items as $item ) {
            if ( self::should_display_item( $item ) ) {
                $filtered_items[] = $item;
            }
        }
        
        return $filtered_items;
    }

    /**
     * بررسی نمایش آیتم
     */
    private static function should_display_item( $item ) {
        // بررسی visibility
        $visibility = get_post_meta( $item->ID, '_headrix_menu_visibility', true );
        if ( $visibility ) {
            if ( $visibility === 'desktop' && wp_is_mobile() ) {
                return false;
            }
            if ( $visibility === 'mobile' && ! wp_is_mobile() ) {
                return false;
            }
        }
        
        // بررسی شرایط سفارشی
        $conditions = get_post_meta( $item->ID, '_headrix_menu_conditions', true );
        if ( $conditions ) {
            $conditions = json_decode( $conditions, true );
            if ( $conditions && ! self::check_conditions( $conditions ) ) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * بررسی شرایط
     */
    private static function check_conditions( $conditions ) {
        foreach ( $conditions as $condition ) {
            if ( ! self::check_single_condition( $condition ) ) {
                return false;
            }
        }
        return true;
    }

    /**
     * بررسی یک شرط
     */
    private static function check_single_condition( $condition ) {
        if ( empty( $condition['type'] ) ) {
            return true;
        }
        
        switch ( $condition['type'] ) {
            case 'user_role':
                return self::check_user_role( $condition );
                
            case 'logged_in':
                return is_user_logged_in() === ( $condition['value'] === 'true' );
                
            case 'page':
                return is_page( $condition['value'] );
                
            case 'post_type':
                return get_post_type() === $condition['value'];
                
            case 'category':
                return in_category( $condition['value'] );
                
            case 'date':
                return self::check_date_condition( $condition );
                
            case 'time':
                return self::check_time_condition( $condition );
                
            case 'device':
                return self::check_device_condition( $condition );
                
            default:
                return true;
        }
    }

    /**
     * بررسی نقش کاربر
     */
    private static function check_user_role( $condition ) {
        if ( ! is_user_logged_in() ) {
            return false;
        }
        
        $user = wp_get_current_user();
        $required_role = $condition['value'];
        $operator = $condition['operator'] ?? 'equals';
        
        if ( $operator === 'equals' ) {
            return in_array( $required_role, $user->roles );
        } elseif ( $operator === 'not_equals' ) {
            return ! in_array( $required_role, $user->roles );
        }
        
        return false;
    }

    /**
     * بررسی شرط تاریخ
     */
    private static function check_date_condition( $condition ) {
        $current_date = current_time( 'timestamp' );
        $target_date = strtotime( $condition['value'] );
        $operator = $condition['operator'] ?? 'equals';
        
        switch ( $operator ) {
            case 'before':
                return $current_date < $target_date;
            case 'after':
                return $current_date > $target_date;
            case 'equals':
                return date( 'Y-m-d', $current_date ) === date( 'Y-m-d', $target_date );
            default:
                return true;
        }
    }

    /**
     * بررسی شرط زمان
     */
    private static function check_time_condition( $condition ) {
        $current_time = current_time( 'H:i' );
        $target_time = $condition['value'];
        $operator = $condition['operator'] ?? 'equals';
        
        switch ( $operator ) {
            case 'before':
                return $current_time < $target_time;
            case 'after':
                return $current_time > $target_time;
            case 'equals':
                return $current_time === $target_time;
            default:
                return true;
        }
    }

    /**
     * بررسی شرط دستگاه
     */
    private static function check_device_condition( $condition ) {
        $device_type = $condition['value'];
        
        if ( $device_type === 'mobile' ) {
            return wp_is_mobile();
        } elseif ( $device_type === 'desktop' ) {
            return ! wp_is_mobile();
        } elseif ( $device_type === 'tablet' ) {
            // تشخیص تبلت (ساده‌سازی)
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $tablet_patterns = [
                'ipad',
                'android.*mobile',
                'tablet',
                'kindle',
                'silk'
            ];
            
            foreach ( $tablet_patterns as $pattern ) {
                if ( preg_match( "/$pattern/i", $user_agent ) ) {
                    return true;
                }
            }
            return false;
        }
        
        return true;
    }

    /**
     * شورت‌کد شرطی
     */
    public static function condition_shortcode( $atts, $content = null ) {
        $atts = shortcode_atts([
            'type' => '',
            'value' => '',
            'operator' => 'equals',
        ], $atts, 'headrix_condition' );
        
        if ( empty( $atts['type'] ) ) {
            return $content;
        }
        
        $condition = [
            'type' => $atts['type'],
            'value' => $atts['value'],
            'operator' => $atts['operator'],
        ];
        
        if ( self::check_single_condition( $condition ) ) {
            return do_shortcode( $content );
        }
        
        return '';
    }

    /**
     * گرفتن لیست شرایط
     */
    public static function get_conditions_list() {
        return [
            'user_role' => __( 'User Role', 'headrix' ),
            'logged_in' => __( 'Logged In/Out', 'headrix' ),
            'page' => __( 'Page', 'headrix' ),
            'post_type' => __( 'Post Type', 'headrix' ),
            'category' => __( 'Category', 'headrix' ),
            'date' => __( 'Date', 'headrix' ),
            'time' => __( 'Time', 'headrix' ),
            'device' => __( 'Device', 'headrix' ),
        ];
    }

    /**
     * اضافه کردن رابط کاربری شرایط
     */
    public static function render_conditions_ui( $item_id ) {
        $conditions = get_post_meta( $item_id, '_headrix_menu_conditions', true );
        $conditions = $conditions ? json_decode( $conditions, true ) : [];
        ?>
        
        <div class="headrix-conditions-container">
            <h4><?php esc_html_e( 'Display Conditions', 'headrix' ); ?></h4>
            
            <div class="headrix-conditions-list">
                <?php if ( empty( $conditions ) ) : ?>
                    <p class="description"><?php esc_html_e( 'No conditions set. Item will always display.', 'headrix' ); ?></p>
                <?php else : ?>
                    <?php foreach ( $conditions as $index => $condition ) : ?>
                        <div class="headrix-condition-item">
                            <input type="hidden" 
                                   name="headrix_menu_conditions[<?php echo esc_attr( $item_id ); ?>][<?php echo esc_attr( $index ); ?>][type]" 
                                   value="<?php echo esc_attr( $condition['type'] ); ?>">
                            <input type="hidden" 
                                   name="headrix_menu_conditions[<?php echo esc_attr( $item_id ); ?>][<?php echo esc_attr( $index ); ?>][value]" 
                                   value="<?php echo esc_attr( $condition['value'] ); ?>">
                            <input type="hidden" 
                                   name="headrix_menu_conditions[<?php echo esc_attr( $item_id ); ?>][<?php echo esc_attr( $index ); ?>][operator]" 
                                   value="<?php echo esc_attr( $condition['operator'] ?? 'equals' ); ?>">
                            
                            <span class="headrix-condition-text">
                                <?php echo self::get_condition_text( $condition ); ?>
                            </span>
                            <button type="button" class="button-link headrix-remove-condition">
                                <?php esc_html_e( 'Remove', 'headrix' ); ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <button type="button" class="button button-small headrix-add-condition">
                <?php esc_html_e( 'Add Condition', 'headrix' ); ?>
            </button>
        </div>
        
        <?php
    }

    /**
     * گرفتن متن قابل خواندن شرایط
     */
    private static function get_condition_text( $condition ) {
        $types = self::get_conditions_list();
        $type_label = $types[ $condition['type'] ] ?? $condition['type'];
        
        switch ( $condition['type'] ) {
            case 'user_role':
                $operator = $condition['operator'] === 'not_equals' ? 'is not' : 'is';
                return sprintf( __( 'User role %s %s', 'headrix' ), $operator, $condition['value'] );
                
            case 'logged_in':
                $state = $condition['value'] === 'true' ? 'logged in' : 'logged out';
                return sprintf( __( 'User is %s', 'headrix' ), $state );
                
            case 'page':
                $page = get_the_title( $condition['value'] );
                return sprintf( __( 'On page: %s', 'headrix' ), $page );
                
            case 'device':
                return sprintf( __( 'Device is %s', 'headrix' ), $condition['value'] );
                
            default:
                return sprintf( '%s: %s', $type_label, $condition['value'] );
        }
    }
}