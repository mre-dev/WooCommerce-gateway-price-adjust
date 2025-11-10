<?php
/*
Plugin Name: Gateway Price Adjust for WooCommerce
Plugin URI: https://github.com/mre-dev/WooCommerce-gateway-price-adjust
Description: تغییر قیمت محصولات ووکامرس بر اساس درگاه پرداخت انتخابی (تنظیمات عمومی و اختصاصی هر محصول) با پشتیبانی از افزایش یا کاهش مبلغ به‌صورت درصدی یا ثابت. شامل بروزرسانی زنده هنگام تغییر درگاه در صفحه تسویه‌حساب.
Version: 1.3
Author: Mohammad Reza Ebrahimi
Author URI: https://mre01.ir
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: gateway-price-adjust
Domain Path: /languages
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
WC requires at least: 5.0
WC tested up to: 8.5
Tags: woocommerce, payment gateway, dynamic pricing, discount, fee, increase, decrease
*/


if (!defined('ABSPATH')) exit;

// Load translations
add_action('plugins_loaded', function() {
    load_plugin_textdomain(
        'gateway-price-adjust',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
});

// Add custom action links under plugin name
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $custom_links = [
        '<a href="' . admin_url('admin.php?page=gateway-price-adjust-settings') . '">تنظیمات عمومی</a>',
        '<a href="https://t.me/mre01">پشتیبانی تلگرام</a>'
    ];

    // Links appear first
    return array_merge($custom_links, $links);
});

/*
|--------------------------------------------------------------------------
| متاباکس محصول (تنظیمات اختصاصی محصول)
|--------------------------------------------------------------------------
*/
add_action('add_meta_boxes', function() {
    add_meta_box('gateway_price_adjust', 'تنظیم قیمت بر اساس درگاه', 'gateway_price_adjust_metabox', 'product', 'side');
});
function gateway_price_adjust_metabox($post) {
    $values = get_post_meta($post->ID, '_gateway_price_adjust', true) ?: [];
    if (!class_exists('WC_Payment_Gateways')) {
        echo '<p>ووکامرس فعال نیست یا کلاس درگاه‌ها در دسترس نیست.</p>';
        return;
    }
    $gateways = WC_Payment_Gateways::instance()->get_available_payment_gateways();
    echo '<div style="font-size:13px;">';
    echo '<p>برای هر درگاه نوع و مقدار تغییر قیمت را مشخص کنید:</p>';
    echo '<table style="width:100%;border-collapse:collapse;">';
    echo '<tr><th>درگاه</th><th>حالت</th><th>نوع</th><th>مقدار</th></tr>';
    foreach ($gateways as $gateway_id => $gateway) {
        $mode  = $values[$gateway_id]['mode'] ?? 'increase'; // increase | decrease
        $kind  = $values[$gateway_id]['kind'] ?? 'percent'; // percent | fixed
        $value = $values[$gateway_id]['value'] ?? '';
        echo '<tr>';
        echo '<td>' . esc_html($gateway->get_title()) . '</td>';
        echo '<td>
                <select name="gateway_price_adjust[' . esc_attr($gateway_id) . '][mode]">
                    <option value="increase" ' . selected($mode, 'increase', false) . '>افزایش</option>
                    <option value="decrease" ' . selected($mode, 'decrease', false) . '>کاهش</option>
                </select>
              </td>';
        echo '<td>
                <select name="gateway_price_adjust[' . esc_attr($gateway_id) . '][kind]">
                    <option value="percent" ' . selected($kind, 'percent', false) . '>درصد</option>
                    <option value="fixed" ' . selected($kind, 'fixed', false) . '>مبلغ ثابت</option>
                </select>
              </td>';
        echo '<td><input type="number" step="0.01" min="0" name="gateway_price_adjust[' . esc_attr($gateway_id) . '][value]" value="' . esc_attr($value) . '" style="width:80px"></td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</div>';
}
add_action('save_post_product', function($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (isset($_POST['gateway_price_adjust'])) {
        $cleaned = [];
        foreach ((array) $_POST['gateway_price_adjust'] as $g_id => $opt) {
            $g_id = sanitize_text_field($g_id);
            $mode = ($opt['mode'] ?? 'increase') === 'decrease' ? 'decrease' : 'increase';
            $kind = ($opt['kind'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
            $value = isset($opt['value']) ? floatval($opt['value']) : 0;
            $cleaned[$g_id] = ['mode' => $mode, 'kind' => $kind, 'value' => $value];
        }
        update_post_meta($post_id, '_gateway_price_adjust', $cleaned);
    }
});

/*
|--------------------------------------------------------------------------
| صفحه تنظیمات عمومی در منوی ووکامرس
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
add_action('admin_init', function() {
    register_setting('gateway_price_adjust_group', 'gateway_price_adjust_global', [
        'type' => 'array',
        'sanitize_callback' => 'gateway_price_adjust_sanitize_global',
    ]);
});
function gateway_price_adjust_sanitize_global($input) {
    $out = [];
    if (!is_array($input)) return $out;
    foreach ($input as $g_id => $opt) {
        $g_id = sanitize_text_field($g_id);
        $mode = ($opt['mode'] ?? 'increase') === 'decrease' ? 'decrease' : 'increase';
        $kind = ($opt['kind'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
        $value = isset($opt['value']) ? floatval($opt['value']) : 0;
        $out[$g_id] = ['mode' => $mode, 'kind' => $kind, 'value' => $value];
    }
    return $out;
}
function gateway_price_adjust_settings_page() {
    if (!class_exists('WC_Payment_Gateways')) {
        echo '<div class="wrap"><h1>ووکامرس فعال نیست</h1></div>';
        return;
    }
    $gateways = WC_Payment_Gateways::instance()->get_available_payment_gateways();
    $settings = get_option('gateway_price_adjust_global', []);
    echo '<div class="wrap">';
    echo '<h1>تنظیمات عمومی قیمت بر اساس درگاه</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields('gateway_price_adjust_group');
    echo '<table class="widefat" style="max-width:900px;">';
    echo '<thead><tr><th>درگاه</th><th>حالت</th><th>نوع</th><th>مقدار</th></tr></thead><tbody>';
    foreach ($gateways as $gateway_id => $gateway) {
        $mode  = $settings[$gateway_id]['mode'] ?? 'increase';
        $kind  = $settings[$gateway_id]['kind'] ?? 'percent';
        $value = $settings[$gateway_id]['value'] ?? '';
        echo '<tr>';
        echo '<td>' . esc_html($gateway->get_title()) . ' <br><small>(' . esc_html($gateway_id) . ')</small></td>';
        echo '<td>
                <select name="gateway_price_adjust_global[' . esc_attr($gateway_id) . '][mode]">
                    <option value="increase" ' . selected($mode, 'increase', false) . '>افزایش</option>
                    <option value="decrease" ' . selected($mode, 'decrease', false) . '>کاهش</option>
                </select>
              </td>';
        echo '<td>
                <select name="gateway_price_adjust_global[' . esc_attr($gateway_id) . '][kind]">
                    <option value="percent" ' . selected($kind, 'percent', false) . '>درصد</option>
                    <option value="fixed" ' . selected($kind, 'fixed', false) . '>مبلغ ثابت</option>
                </select>
              </td>';
        echo '<td><input type="number" step="0.01" min="0" name="gateway_price_adjust_global[' . esc_attr($gateway_id) . '][value]" value="' . esc_attr($value) . '" style="width:120px"></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    submit_button('ذخیره تنظیمات');
    echo '</form>';
    echo '</div>';
}

/*
|--------------------------------------------------------------------------
| ذخیره در سشن انتخاب درگاه هنگام آپدیت سفارش (AJAX از طریق Checkout)
|--------------------------------------------------------------------------
*/
add_action('woocommerce_checkout_update_order_review', function($post_data) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    parse_str($post_data, $data);
    if (!empty($data['payment_method'])) {
        WC()->session->set('chosen_payment_method', sanitize_text_field($data['payment_method']));
    }
});

/*
|--------------------------------------------------------------------------
| اعمال تغییرات قیمت/تخفیف هنگام محاسبه سبد (کار با تنظیمات محلی محصول یا تنظیمات کلی)
|--------------------------------------------------------------------------
*/
add_action('woocommerce_cart_calculate_fees', function() {
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (!WC()->cart) return;

    $chosen = WC()->session->get('chosen_payment_method');
    if (empty($chosen)) return;

    $global_settings = get_option('gateway_price_adjust_global', []);
    $total_adjustment = 0;

    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $product_id = $cart_item['product_id'];
        $local = get_post_meta($product_id, '_gateway_price_adjust', true);
        $settings = !empty($local[$chosen]) ? $local[$chosen] : ($global_settings[$chosen] ?? null);

        if (empty($settings) || !isset($settings['value'])) continue;

        $mode = $settings['mode'] ?? 'increase';
        $kind = $settings['kind'] ?? 'percent';
        $value = floatval($settings['value']);
        if ($value === 0) continue;

        // قیمت پایه هر واحد محصول (قیمت فعلی محصول در سبد)
        $price = floatval( $product->get_price() );

        if ($kind === 'percent') {
            $delta = ($price * $value) / 100;
        } else {
            $delta = $value;
        }

        // اگر حالت کاهش هست، مقدار منفی می‌شود
        if ($mode === 'decrease') {
            $delta = -1 * $delta;
        }

        // جمع برای تعداد
        $total_adjustment += $delta * intval($cart_item['quantity']);
    }

    if ($total_adjustment != 0) {
        // اگر منفی هست تخفیف، اگر مثبت افزایش
        $label = $total_adjustment > 0 ? __('افزایش قیمت بر اساس درگاه', 'gateway-price-adjust') : __('تخفیف بر اساس درگاه', 'gateway-price-adjust');
        WC()->cart->add_fee($label, $total_adjustment, false);
    }
}, 20);

/*
|--------------------------------------------------------------------------
| اسکریپت فرانت‌اند: بروزرسانی زنده صفحه تسویه‌حساب هنگام تغییر درگاه
|--------------------------------------------------------------------------
*/
add_action('wp_enqueue_scripts', function() {
    if (!is_checkout()) return;
    wp_enqueue_script('gateway-price-adjust-frontend', plugin_dir_url(__FILE__) . 'assets/gpa-frontend.js', ['jquery','wc-checkout'], '1.3', true);
    wp_localize_script('gateway-price-adjust-frontend', 'gpa_params', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);
});
