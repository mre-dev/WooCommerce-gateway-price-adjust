<?php
/**
 * Plugin Name: تغییر قیمت محصولات بر اساس درگاه ووکامرس
 * Plugin URI: https://github.com/mre-dev/WooCommerce-gateway-price-adjust
 * Description: تغییر قیمت محصولات ووکامرس بر اساس درگاه پرداخت انتخابی با تنظیمات عمومی، اختصاصی محصول، قوانین پیشرفته، تب‌بندی فارسی، افزایش/کاهش (درصدی/مبلغ ثابت)، Export/Import و گزارش‌ها.
 * Version: 4.0
 * Author: Mohammad Reza Ebrahimi
 * Author URI: https://mre01.ir
 * License: GPLv2 or later
 * Text Domain: gateway-price-adjust
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

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
| بارگذاری فایل اصلی توابع
|--------------------------------------------------------------------------
*/
require_once GPA_PLUGIN_DIR . 'includes/core-functions.php';

/*
|--------------------------------------------------------------------------
| بررسی وابستگی‌ها
|--------------------------------------------------------------------------
*/
add_action('plugins_loaded', 'gpa_check_dependencies');

function gpa_check_dependencies() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'gpa_woocommerce_missing_notice');
        return false;
    }
    
    // بارگذاری فایل‌های اصلی
    gpa_load_core_files();
    return true;
}

function gpa_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p>پلاگین <strong>"تغییر قیمت محصولات بر اساس درگاه"</strong> نیاز به ووکامرس دارد. لطفاً ووکامرس را نصب و فعال کنید.</p>
    </div>
    <?php
}

function gpa_load_core_files() {
    $core_files = [
        'includes/admin-settings.php',
        'includes/product-metabox.php',
        'includes/cart-handler.php',
        'includes/ajax-handlers.php',
        'includes/rules-enforcement.php'
    ];
    
    foreach ($core_files as $file) {
        if (file_exists(GPA_PLUGIN_DIR . $file)) {
            require_once GPA_PLUGIN_DIR . $file;
        }
    }
    
    // بارگذاری ماژول‌های اختیاری
    gpa_load_optional_modules();
}

function gpa_load_optional_modules() {
    $modules = [
        'modules/tiered-discounts.php',
        'modules/inventory-management.php',
        'modules/audit-logs.php',
        'modules/ai-suggestions.php',
        'modules/telegram-notifications.php',
        'modules/competitor-analysis.php'
    ];
    
    foreach ($modules as $module) {
        if (file_exists(GPA_PLUGIN_DIR . $module)) {
            require_once GPA_PLUGIN_DIR . $module;
        }
    }
}

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
        'gpa_settings_page_handler'
    );
});

function gpa_settings_page_handler() {
    // بررسی اینکه فایل تنظیمات بارگذاری شده باشد
    if (function_exists('gateway_price_adjust_settings_page')) {
        gateway_price_adjust_settings_page();
    } else {
        echo '<div class="wrap"><div class="notice notice-error"><p>خطا در بارگذاری صفحه تنظیمات. لطفاً پلاگین را دوباره نصب کنید.</p></div></div>';
    }
}

/*
|--------------------------------------------------------------------------
| اسکریپت‌ها و استایل‌های مدیریتی
|--------------------------------------------------------------------------
*/
add_action('admin_enqueue_scripts', function($hook) {
    if ('woocommerce_page_gateway-price-adjust-settings' !== $hook) {
        return;
    }
    
    // jQuery به طور پیش‌فرض در وردپرس وجود دارد
    wp_enqueue_script('jquery');
    
    // Chart.js برای تب گزارش‌ها
    if (isset($_GET['tab']) && $_GET['tab'] === 'reports') {
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', [], '3.9.1', true);
    }
    
    // اسکریپت اختصاصی تب‌ها
    $tabs_script_path = GPA_PLUGIN_DIR . 'assets/gpa-tabs.js';
    if (file_exists($tabs_script_path)) {
        wp_enqueue_script('gpa-tabs', GPA_PLUGIN_URL . 'assets/gpa-tabs.js', ['jquery'], GPA_VERSION, true);
    }
    
    // استایل اختصاصی
    wp_enqueue_style('gpa-admin-style', GPA_PLUGIN_URL . 'assets/admin-style.css', [], GPA_VERSION);
});

/*
|--------------------------------------------------------------------------
| فعال‌سازی پلاگین
|--------------------------------------------------------------------------
*/
register_activation_hook(__FILE__, 'gpa_plugin_activation');

function gpa_plugin_activation() {
    // ایجاد جداول مورد نیاز - اگر تابع وجود دارد
    if (function_exists('gpa_create_log_table')) {
        gpa_create_log_table();
    }
    
    // تنظیم کرون جاب‌ها
    if (!wp_next_scheduled('gpa_scheduled_competitor_analysis')) {
        wp_schedule_event(time(), 'weekly', 'gpa_scheduled_competitor_analysis');
    }
    
    // تنظیمات پیش‌فرض
    $default_settings = [
        'gateway_price_adjust_global' => [],
        'gateway_price_adjust_options' => [
            'enable_product_level' => 'yes',
            'enable_cart_discount' => 'yes',
            'show_gateway_notice' => 'yes'
        ]
    ];
    
    foreach ($default_settings as $key => $value) {
        if (get_option($key) === false) {
            add_option($key, $value);
        }
    }
}

/*
|--------------------------------------------------------------------------
| غیرفعال‌سازی پلاگین
|--------------------------------------------------------------------------
*/
register_deactivation_hook(__FILE__, 'gpa_plugin_deactivation');

function gpa_plugin_deactivation() {
    // پاک کردن کرون جاب‌ها
    wp_clear_scheduled_hook('gpa_scheduled_competitor_analysis');
}
