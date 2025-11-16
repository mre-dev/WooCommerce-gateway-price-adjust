<?php
/*
Plugin Name: تغییر قیمت محصولات بر اساس درگاه ووکامرس
Plugin URI: https://github.com/mre-dev/WooCommerce-gateway-price-adjust
Description: تغییر قیمت محصولات ووکامرس بر اساس درگاه پرداخت انتخابی با تنظیمات عمومی، اختصاصی محصول، قوانین پیشرفته، تب‌بندی فارسی، افزایش/کاهش (درصدی/مبلغ ثابت)، Export/Import و گزارش‌ها.
Version: 4.0
Author: Mohammad Reza Ebrahimi
Author URI: https://mre01.ir
License: GPLv2 or later
Text Domain: gateway-price-adjust
Domain Path: /languages
*/

if (!defined('ABSPATH')) exit;

// در فایل gateway-price-adjust.php
add_action('admin_enqueue_scripts', function($hook) {
    if ('woocommerce_page_gateway-price-adjust-settings' !== $hook) return;
    
    wp_enqueue_script('gpa-tabs', GPA_PLUGIN_URL . 'assets/gpa-tabs.js', ['jquery'], GPA_VERSION, true);
    
    // اضافه کردن Chart.js برای تب گزارش‌ها
    if (isset($_GET['tab']) && $_GET['tab'] === 'reports') {
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', [], '3.9.1', true);
    }
});

/*
|--------------------------------------------------------------------------
| تعریف ثابت‌ها
|--------------------------------------------------------------------------
*/
define('GPA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GPA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GPA_VERSION', '4.0');

/*
|--------------------------------------------------------------------------
| بارگذاری فایل‌های مورد نیاز
|--------------------------------------------------------------------------
*/
add_action('plugins_loaded', function() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>پلاگین "تغییر قیمت محصولات بر اساس درگاه" نیاز به ووکامرس دارد. لطفاً ووکامرس را نصب و فعال کنید.</p></div>';
        });
        return;
    }
        require_once GPA_PLUGIN_DIR . 'includes/core-functions.php';
        require_once GPA_PLUGIN_DIR . 'includes/admin-settings.php';
        require_once GPA_PLUGIN_DIR . 'includes/product-metabox.php';
        require_once GPA_PLUGIN_DIR . 'includes/cart-handler.php';
        require_once GPA_PLUGIN_DIR . 'includes/ajax-handlers.php';
        
        // بارگذاری ماژول‌های اختیاری
        // require_once GPA_PLUGIN_DIR . 'modules/export-import-handler.php';
        require_once GPA_PLUGIN_DIR . 'modules/tiered-discounts.php';
        // require_once GPA_PLUGIN_DIR . 'modules/coupon-management.php';
        require_once GPA_PLUGIN_DIR . 'modules/inventory-management.php';
        require_once GPA_PLUGIN_DIR . 'modules/audit-logs.php';
        require_once GPA_PLUGIN_DIR . 'modules/ai-suggestions.php';
        require_once GPA_PLUGIN_DIR . 'modules/telegram-notifications.php';
        // require_once GPA_PLUGIN_DIR . 'modules/sms-notifications.php';
        require_once GPA_PLUGIN_DIR . 'modules/competitor-analysis.php';
        require_once GPA_PLUGIN_DIR . 'includes/rules-enforcement.php'; 
});

/*
|--------------------------------------------------------------------------
| بارگذاری ترجمه‌ها
|--------------------------------------------------------------------------
*/
add_action('plugins_loaded', function() {
    load_plugin_textdomain('gateway-price-adjust', false, dirname(plugin_basename(__FILE__)) . '/languages/');
});

/*
|--------------------------------------------------------------------------
| ثبت تنظیمات
|--------------------------------------------------------------------------
*/
add_action('admin_init', function() {
    register_setting('gateway_price_adjust_group', 'gateway_price_adjust_global');
    register_setting('gateway_price_adjust_group', 'gateway_price_adjust_options');
});

/*
|--------------------------------------------------------------------------
| لینک‌های سریع در صفحه افزونه
|--------------------------------------------------------------------------
*/
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $custom_links = [
        '<a href="' . admin_url('admin.php?page=gateway-price-adjust-settings') . '">تنظیمات عمومی</a>',
        '<a href="https://t.me/mre01">پشتیبانی تلگرام</a>',
        '<a href="https://t.me/payzitoFAGR">پشتیبانی پی زیتو</a>'
    ];
    return array_merge($custom_links, $links);
});

/*
|--------------------------------------------------------------------------
| منوی تنظیمات
|--------------------------------------------------------------------------
*/
add_action('admin_menu', function() {
    add_submenu_page(
        'woocommerce',
        'تنظیمات قیمت بر اساس درگاه',
        'قیمت بر اساس درگاه',
        'manage_woocommerce',
        'gateway-price-adjust-settings',
        'gateway_price_adjust_settings_page'
    );
});

/*
|--------------------------------------------------------------------------
| فعال‌سازی پلاگین
|--------------------------------------------------------------------------
*/
register_activation_hook(__FILE__, 'gpa_plugin_activation');
function gpa_plugin_activation() {
    // ایجاد جداول مورد نیاز
    gpa_create_log_table();
    
    // تنظیم کرون جاب‌ها
    if (!wp_next_scheduled('gpa_scheduled_competitor_analysis')) {
        wp_schedule_event(time(), 'weekly', 'gpa_scheduled_competitor_analysis');
    }
}

/*
|--------------------------------------------------------------------------
| غیرفعال‌سازی پلاگین
|--------------------------------------------------------------------------
*/
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('gpa_scheduled_competitor_analysis');
});