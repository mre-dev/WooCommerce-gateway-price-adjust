<?php
/*
|--------------------------------------------------------------------------
| مدیریت درخواست‌های AJAX
|--------------------------------------------------------------------------
*/

if (!defined('ABSPATH')) exit;

/*
|--------------------------------------------------------------------------
| هندلر Ajax برای آپدیت درگاه
|--------------------------------------------------------------------------
*/
add_action('wp_ajax_gpa_update_payment_method', 'gpa_update_payment_method');
add_action('wp_ajax_nopriv_gpa_update_payment_method', 'gpa_update_payment_method');

function gpa_update_payment_method() {
    // بررسی nonce برای امنیت
    if (!wp_verify_nonce($_POST['nonce'], 'gpa_nonce')) {
        wp_die('Security check failed');
    }
    
    if (!empty($_POST['payment_method'])) {
        WC()->session->set('chosen_payment_method', sanitize_text_field($_POST['payment_method']));
        WC()->session->save_data();
        
        wp_send_json_success('Payment method updated');
    }
    
    wp_send_json_error('No payment method provided');
}

/*
|--------------------------------------------------------------------------
| اسکریپت فرانت‌اند
|--------------------------------------------------------------------------
*/
add_action('wp_enqueue_scripts', function() {
    if (!is_checkout() && !is_cart()) return;
    
    wp_enqueue_script('gateway-price-adjust-frontend', GPA_PLUGIN_URL . 'assets/gpa-frontend.js', ['jquery','wc-checkout'], GPA_VERSION, true);
    
    // انتقال متغیرها به جاوااسکریپت
    wp_localize_script('gateway-price-adjust-frontend', 'gpa_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gpa_nonce')
    ]);
});

/*
|--------------------------------------------------------------------------
| ذخیره در سشن انتخاب درگاه
|--------------------------------------------------------------------------
*/
add_action('woocommerce_checkout_update_order_review', function($post_data) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    
    parse_str($post_data, $data);
    if (!empty($data['payment_method'])) {
        $new_payment_method = sanitize_text_field($data['payment_method']);
        $current_payment_method = WC()->session->get('chosen_payment_method');
        
        // فقط اگر درگاه تغییر کرده باشد، سشن را آپدیت کن
        if ($new_payment_method !== $current_payment_method) {
            WC()->session->set('chosen_payment_method', $new_payment_method);
            WC()->session->save_data();
        }
    }
});

/*
|--------------------------------------------------------------------------
| اضافه کردن هوک اضافی برای ثبت درگاه
|--------------------------------------------------------------------------
*/
add_action('wp_ajax_woocommerce_update_order_review', function() {
    if (!empty($_POST['payment_method'])) {
        WC()->session->set('chosen_payment_method', sanitize_text_field($_POST['payment_method']));
        WC()->session->save_data();
    }
}, 5);

add_action('wp_ajax_nopriv_woocommerce_update_order_review', function() {
    if (!empty($_POST['payment_method'])) {
        WC()->session->set('chosen_payment_method', sanitize_text_field($_POST['payment_method']));
        WC()->session->save_data();
    }
}, 5);