<?php
/*
|--------------------------------------------------------------------------
| توابع اصلی پلاگین
|--------------------------------------------------------------------------
*/

if (!defined('ABSPATH')) exit;

/*
|--------------------------------------------------------------------------
| گرفتن تعداد استفاده از هر درگاه
|--------------------------------------------------------------------------
*/
function gpa_get_gateway_usage_counts() {
    global $wpdb;
    
    // بررسی وجود جدول ووکامرس
    $gateways = WC()->payment_gateways->get_available_payment_gateways();
    $counts = [];

    if (empty($gateways)) {
        error_log('GPA: No gateways found');
        return $counts;
    }

    foreach($gateways as $gateway_id => $gateway) {
        try {
            // کوئری بهینه‌شده برای ووکامرس
            $sql = $wpdb->prepare("
                SELECT COUNT(DISTINCT p.ID) 
                FROM {$wpdb->posts} AS p
                INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
                WHERE p.post_type = 'shop_order'
                AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                AND pm.meta_key = '_payment_method'
                AND pm.meta_value = %s
            ", $gateway_id);

            $count = $wpdb->get_var($sql);
            $counts[$gateway_id] = intval($count ?: 0);
            
            error_log("GPA: Gateway {$gateway_id} - {$counts[$gateway_id]} orders");

        } catch (Exception $e) {
            error_log("GPA Error counting gateway {$gateway_id}: " . $e->getMessage());
            $counts[$gateway_id] = 0;
        }
    }

    return $counts;
}

/*
|--------------------------------------------------------------------------
| تابع جایگزین برای وقتی که داده‌ای وجود ندارد
|--------------------------------------------------------------------------
*/
function gpa_get_sample_gateway_stats() {
    $gateways = WC()->payment_gateways->get_available_payment_gateways();
    $sample_data = [];
    
    foreach($gateways as $gateway_id => $gateway) {
        // داده‌های نمونه برای نمایش
        $sample_data[$gateway_id] = rand(5, 50);
    }
    
    return $sample_data;
}

/*
|--------------------------------------------------------------------------
| تابع ثبت لاگ
|--------------------------------------------------------------------------
*/
function gpa_log_action($action_type, $details = []) {
    global $wpdb;
    
    $user_id = get_current_user_id();
    $user_ip = $_SERVER['REMOTE_ADDR'];
    
    $wpdb->insert(
        $wpdb->prefix . 'gpa_audit_logs',
        [
            'action_type' => $action_type,
            'user_id' => $user_id ?: null,
            'user_ip' => $user_ip,
            'details' => json_encode($details, JSON_UNESCAPED_UNICODE)
        ],
        ['%s', '%d', '%s', '%s']
    );
    
    return $wpdb->insert_id;
}

/*
|--------------------------------------------------------------------------
| ایجاد جدول لاگ در دیتابیس
|--------------------------------------------------------------------------
*/
function gpa_create_log_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'gpa_audit_logs';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        action_type varchar(100) NOT NULL,
        user_id bigint(20) DEFAULT NULL,
        user_ip varchar(45) DEFAULT NULL,
        details longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY action_type (action_type),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/*
|--------------------------------------------------------------------------
| بررسی فعال بودن ووکامرس
|--------------------------------------------------------------------------
*/
function gpa_is_woocommerce_active() {
    return class_exists('WooCommerce');
}

/*
|--------------------------------------------------------------------------
| گرفتن لیست درگاه‌های فعال
|--------------------------------------------------------------------------
*/
function gpa_get_active_gateways() {
    if (!gpa_is_woocommerce_active()) {
        return [];
    }
    
    $gateways = WC()->payment_gateways->get_available_payment_gateways();
    return !empty($gateways) ? $gateways : [];
}