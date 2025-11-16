<?php
/*
|--------------------------------------------------------------------------
| مدیریت سبد خرید و اعمال تغییرات قیمت
|--------------------------------------------------------------------------
*/

if (!defined('ABSPATH')) exit;

/*
|--------------------------------------------------------------------------
| اعمال تغییرات قیمت/تخفیف هنگام محاسبه سبد
|--------------------------------------------------------------------------
*/
add_action('woocommerce_cart_calculate_fees', function() {
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (!WC()->cart || WC()->cart->is_empty()) return;

    $chosen = WC()->session->get('chosen_payment_method');
    if (empty($chosen)) return;

    $global_settings = get_option('gateway_price_adjust_global', []);
    $total_adjustment = 0;
    $applied_to_products = [];

    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $product_id = $cart_item['product_id'];
        
        // اول تنظیمات محصول را بررسی کن
        $product_rules = get_post_meta($product_id, '_gpa_product_rules', true);
        
        if (!empty($product_rules[$chosen])) {
            $settings = $product_rules[$chosen];
        } elseif (!empty($global_settings[$chosen])) {
            $settings = $global_settings[$chosen];
        } else {
            continue;
        }

        if (!isset($settings['value']) || !is_numeric($settings['value'])) continue;

        $price = floatval($product->get_price());
        $quantity = intval($cart_item['quantity']);
        $value = floatval($settings['value']);
        
        if ($value === 0) continue;

        if ($settings['kind'] === 'percent') {
            $delta = ($price * $value) / 100;
        } else {
            $delta = $value;
        }

        if ($settings['mode'] === 'decrease') {
            $delta = -1 * $delta;
        }

        $total_adjustment += $delta * $quantity;
        $applied_to_products[] = $product->get_name();
    }

    // حذف feeهای قبلی با همین عنوان
    $fees = WC()->cart->get_fees();
    foreach ($fees as $fee_key => $fee) {
        if (strpos($fee->name, 'درگاه') !== false || 
            strpos($fee->name, 'افزایش') !== false || 
            strpos($fee->name, 'تخفیف') !== false) {
            unset($fees[$fee_key]);
        }
    }

    if ($total_adjustment != 0) {
        $label = $total_adjustment > 0 
            ? __('افزایش قیمت بر اساس درگاه', 'gateway-price-adjust') 
            : __('تخفیف بر اساس درگاه', 'gateway-price-adjust');
        
        WC()->cart->add_fee($label, $total_adjustment, false);
        
        // برای دیباگ
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("GPA: Applied adjustment for gateway {$chosen}: {$total_adjustment}");
        }
    }
}, 20);

/*
|--------------------------------------------------------------------------
| ریست سشن وقتی سبد خالی می‌شود
|--------------------------------------------------------------------------
*/
add_action('woocommerce_cart_emptied', function() {
    WC()->session->__unset('chosen_payment_method');
});

/*
|--------------------------------------------------------------------------
| بررسی و ریست session در صورت نیاز
|--------------------------------------------------------------------------
*/
add_action('wp_head', function() {
    if (is_checkout() && !WC()->cart->is_empty()) {
        $chosen = WC()->session->get('chosen_payment_method');
        if (!$chosen) {
            // اگر session خالی است، درگاه پیش‌فرض را تنظیم کن
            $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
            if (!empty($available_gateways)) {
                $default_gateway = current($available_gateways);
                WC()->session->set('chosen_payment_method', $default_gateway->id);
            }
        }
    }
});