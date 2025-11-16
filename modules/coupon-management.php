<?php
/*
|--------------------------------------------------------------------------
| ماژول مدیریت کوپن ترکیبی با درگاه
|--------------------------------------------------------------------------
*/

if (!defined('ABSPATH')) exit;

/*
|--------------------------------------------------------------------------
| اضافه کردن تب مدیریت کوپن
|--------------------------------------------------------------------------
*/
add_filter('gpa_additional_tabs', function($tabs) {
    $tabs['coupon_management'] = 'مدیریت کوپن';
    return $tabs;
});

/*
|--------------------------------------------------------------------------
| محتوای تب مدیریت کوپن
|--------------------------------------------------------------------------
*/
add_action('gpa_settings_tab_content', function($current_tab) {
    if ($current_tab !== 'coupon_management') return;
    
    $coupon_settings = get_option('gpa_coupon_settings', []);
    ?>
    <div class="wrap">
        <h2>مدیریت کوپن ترکیبی با درگاه</h2>
        
        <p>در این بخش می‌توانید تنظیمات کلی مربوط به کوپن‌ها را مدیریت کنید.</p>
        
        <table class="form-table">
            <tr>
                <th>فعال‌سازی کوپن ترکیبی</th>
                <td>
                    <label>
                        <input type="checkbox" name="gpa_coupon_settings[enabled]" value="1" 
                               <?php checked($coupon_settings['enabled'] ?? false); ?>>
                        فعال‌سازی سیستم کوپن ترکیبی با درگاه
                    </label>
                </td>
            </tr>
            <tr>
                <th>نمایش پیام خطا</th>
                <td>
                    <label>
                        <input type="checkbox" name="gpa_coupon_settings[show_error_message]" value="1" 
                               <?php checked($coupon_settings['show_error_message'] ?? true); ?>>
                        نمایش پیام خطا هنگام استفاده از کوپن با درگاه نامعتبر
                    </label>
                </td>
            </tr>
        </table>
        
        <h3>راهنما</h3>
        <div class="notice notice-info">
            <p>برای تنظیم کوپن‌های اختصاصی، به بخش <strong>کوپن‌ها → افزودن کوپن جدید</strong> مراجعه کرده و در تب "تنظیمات درگاه پرداخت" تنظیمات مورد نظر را اعمال کنید.</p>
        </div>
    </div>
    <?php
});

// ذخیره تنظیمات کوپن
add_action('admin_init', function() {
    register_setting('gateway_price_adjust_group', 'gpa_coupon_settings');
});

/*
|--------------------------------------------------------------------------
| اضافه کردن فیلد به کوپن‌ها
|--------------------------------------------------------------------------
*/
add_action('woocommerce_coupon_options', function($coupon_id, $coupon) {
    $coupon_settings = get_option('gpa_coupon_settings', []);
    if (empty($coupon_settings['enabled'])) return;
    
    ?>
    <div class="options_group">
        <h3>تنظیمات درگاه پرداخت</h3>
        
        <?php
        $restricted_gateways = get_post_meta($coupon_id, '_gpa_restricted_gateways', true) ?: [];
        $gateway_discounts = get_post_meta($coupon_id, '_gpa_gateway_discounts', true) ?: [];
        ?>
        
        <p class="form-field">
            <label>درگاه‌های مجاز</label>
            <?php
            $gateways = WC()->payment_gateways->get_available_payment_gateways();
            foreach($gateways as $gateway_id => $gateway):
                $checked = in_array($gateway_id, $restricted_gateways) ? 'checked' : '';
            ?>
                <label style="display: block; margin: 5px 0;">
                    <input type="checkbox" name="gpa_restricted_gateways[]" 
                           value="<?php echo $gateway_id; ?>" <?php echo $checked; ?>>
                    <?php echo esc_html($gateway->get_title()); ?>
                </label>
            <?php endforeach; ?>
            <span class="description">در صورت انتخاب، کوپن فقط با این درگاه‌ها قابل استفاده است</span>
        </p>
        
        <h4>تخفیف اضافی بر اساس درگاه</h4>
        <?php foreach($gateways as $gateway_id => $gateway): 
            $discount = $gateway_discounts[$gateway_id] ?? ['type' => 'percent', 'value' => 0];
        ?>
            <p class="form-field">
                <label><?php echo esc_html($gateway->get_title()); ?></label>
                <select name="gpa_gateway_discounts[<?php echo $gateway_id; ?>][type]" style="width: 100px;">
                    <option value="percent" <?php selected($discount['type'], 'percent'); ?>>درصد</option>
                    <option value="fixed" <?php selected($discount['type'], 'fixed'); ?>>مبلغ ثابت</option>
                </select>
                <input type="number" name="gpa_gateway_discounts[<?php echo $gateway_id; ?>][value]" 
                       value="<?php echo esc_attr($discount['value']); ?>" step="0.01" min="0" 
                       placeholder="مقدار" style="width: 120px;">
            </p>
        <?php endforeach; ?>
    </div>
    <?php
}, 10, 2);

// ذخیره تنظیمات کوپن
add_action('woocommerce_coupon_options_save', function($coupon_id) {
    $coupon_settings = get_option('gpa_coupon_settings', []);
    if (empty($coupon_settings['enabled'])) return;
    
    if (isset($_POST['gpa_restricted_gateways'])) {
        update_post_meta($coupon_id, '_gpa_restricted_gateways', $_POST['gpa_restricted_gateways']);
    } else {
        delete_post_meta($coupon_id, '_gpa_restricted_gateways');
    }
    
    if (isset($_POST['gpa_gateway_discounts'])) {
        update_post_meta($coupon_id, '_gpa_gateway_discounts', $_POST['gpa_gateway_discounts']);
    }
});

// اعمال محدودیت درگاه برای کوپن
add_filter('woocommerce_coupon_is_valid', function($valid, $coupon) {
    if (!$valid) return $valid;
    
    $coupon_settings = get_option('gpa_coupon_settings', []);
    if (empty($coupon_settings['enabled'])) return $valid;
    
    $restricted_gateways = get_post_meta($coupon->get_id(), '_gpa_restricted_gateways', true);
    if (empty($restricted_gateways)) return $valid;
    
    $chosen_gateway = WC()->session->get('chosen_payment_method');
    if (!$chosen_gateway || !in_array($chosen_gateway, $restricted_gateways)) {
        if ($coupon_settings['show_error_message'] ?? true) {
            throw new Exception('این کوپن فقط با درگاه‌های مشخص شده قابل استفاده است');
        }
        return false;
    }
    
    return $valid;
}, 10, 2);

// اعمال تخفیف اضافی بر اساس درگاه
add_action('woocommerce_cart_calculate_fees', function() {
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (!WC()->cart) return;
    
    $coupon_settings = get_option('gpa_coupon_settings', []);
    if (empty($coupon_settings['enabled'])) return;
    
    $chosen_gateway = WC()->session->get('chosen_payment_method');
    if (!$chosen_gateway) return;
    
    $applied_coupons = WC()->cart->get_applied_coupons();
    foreach ($applied_coupons as $coupon_code) {
        $coupon = new WC_Coupon($coupon_code);
        $gateway_discounts = get_post_meta($coupon->get_id(), '_gpa_gateway_discounts', true);
        
        if (!empty($gateway_discounts[$chosen_gateway])) {
            $discount = $gateway_discounts[$chosen_gateway];
            $discount_value = floatval($discount['value']);
            
            if ($discount_value > 0) {
                $discount_amount = 0;
                
                if ($discount['type'] === 'percent') {
                    $cart_total = WC()->cart->get_subtotal();
                    $discount_amount = ($cart_total * $discount_value) / 100;
                } else {
                    $discount_amount = $discount_value;
                }
                
                if ($discount_amount > 0) {
                    WC()->cart->add_fee(
                        sprintf('تخفیف اضافی %s برای %s', $coupon_code, $chosen_gateway),
                        -$discount_amount,
                        false
                    );
                    
                    gpa_log_action('gateway_coupon_discount_applied', [
                        'coupon' => $coupon_code,
                        'gateway' => $chosen_gateway,
                        'discount_amount' => $discount_amount
                    ]);
                }
            }
        }
    }
});