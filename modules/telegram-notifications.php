<?php
/*
|--------------------------------------------------------------------------
| ماژول سیستم اطلاع‌رسانی تلگرام
|--------------------------------------------------------------------------
*/

if (!defined('ABSPATH')) exit;

/*
|--------------------------------------------------------------------------
| اضافه کردن تب تلگرام
|--------------------------------------------------------------------------
*/
add_filter('gpa_additional_tabs', function($tabs) {
    $tabs['telegram_notifications'] = 'اطلاع‌رسانی تلگرام';
    return $tabs;
});

/*
|--------------------------------------------------------------------------
| محتوای تب تلگرام
|--------------------------------------------------------------------------
*/
add_action('gpa_settings_tab_content', function($current_tab) {
    if ($current_tab !== 'telegram_notifications') return;
    
    $telegram_settings = get_option('gpa_telegram_settings', [
        'enabled' => false,
        'bot_token' => '',
        'chat_id' => '',
        'notify_new_order' => true,
        'notify_low_stock' => true,
        'notify_gateway_change' => true,
        'notify_ai_suggestion' => true
    ]);
    ?>
    
    <div class="wrap" style="padding: 10px;">
        <h2>اطلاع‌رسانی از طریق تلگرام</h2>
        
        <form method="post" action="options.php">
            <?php settings_fields('gateway_price_adjust_group'); ?>
            
            <table class="form-table">
                <tr>
                    <th>فعال‌سازی اطلاع‌رسانی تلگرام</th>
                    <td>
                        <label>
                            <input type="checkbox" name="gpa_telegram_settings[enabled]" value="1" 
                                   <?php checked(isset($telegram_settings['enabled']) && $telegram_settings['enabled']); ?>
                            ارسال پیام از طریق تلگرام
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th>Bot Token</th>
                    <td>
                        <input type="password" name="gpa_telegram_settings[bot_token]" 
                               value="<?php echo esc_attr($telegram_settings['bot_token'] ?? ''); ?>"
                               class="regular-text">
                        <span class="description">
                            توکن ربات تلگرام (از @BotFather دریافت کنید)
                        </span>
                    </td>
                </tr>
                
                <tr>
                    <th>Chat ID</th>
                    <td>
                        <input type="text" name="gpa_telegram_settings[chat_id]" 
                               value="<?php echo esc_attr($telegram_settings['chat_id'] ?? ''); ?>"
                               class="regular-text">
                        <span class="description">
                            آیدی چت یا کانال (از @userinfobot دریافت کنید)
                        </span>
                    </td>
                </tr>
                
                <tr>
                    <th>رویدادهای اطلاع‌رسانی</th>
                    <td>
                        <label style="display: block; margin: 5px 0;">
                            <input type="checkbox" name="gpa_telegram_settings[notify_new_order]" value="1" 
                                   <?php checked(isset($telegram_settings['notify_new_order']) && $telegram_settings['notify_new_order']); ?>>
                            سفارش جدید
                        </label>
                        
                        <label style="display: block; margin: 5px 0;">
                            <input type="checkbox" name="gpa_telegram_settings[notify_low_stock]" value="1" 
                                   <?php checked(isset($telegram_settings['notify_low_stock']) && $telegram_settings['notify_low_stock']); ?>>
                            اتمام موجودی
                        </label>
                        
                        <label style="display: block; margin: 5px 0;">
                            <input type="checkbox" name="gpa_telegram_settings[notify_gateway_change]" value="1" 
                                   <?php checked(isset($telegram_settings['notify_gateway_change']) && $telegram_settings['notify_gateway_change']); ?>>
                            تغییر تنظیمات درگاه
                        </label>
                        
                        <label style="display: block; margin: 5px 0;">
                            <input type="checkbox" name="gpa_telegram_settings[notify_ai_suggestion]" value="1" 
                                   <?php checked(isset($telegram_settings['notify_ai_suggestion']) && $telegram_settings['notify_ai_suggestion']); ?>>
                            پیشنهاد جدید هوش مصنوعی
                        </label>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('ذخیره تنظیمات تلگرام'); ?>
        </form>
        
        <!-- تست اتصال -->
        <div style="margin-top: 40px;">
            <h3>تست اتصال تلگرام</h3>
            
            <button type="button" id="gpa-test-telegram" class="button button-primary">
                تست ارسال پیام
            </button>
            
            <div id="gpa-telegram-result" style="margin-top: 10px;"></div>
        </div>
    </div>
    
    <script>
    jQuery(function($) {
        $('#gpa-test-telegram').on('click', function() {
            const $button = $(this);
            const $result = $('#gpa-telegram-result');
            
            $button.prop('disabled', true).text('در حال ارسال...');
            $result.html('<p style="color: #666;">در حال ارسال پیام تست...</p>');
            
            $.post(ajaxurl, {
                action: 'gpa_test_telegram',
                nonce: '<?php echo wp_create_nonce('gpa_test_telegram'); ?>'
            }, function(response) {
                $button.prop('disabled', false).text('تست ارسال پیام');
                
                if (response.success) {
                    $result.html('<p style="color: #46b450;">✅ ' + response.data.message + '</p>');
                } else {
                    $result.html('<p style="color: #dc3232;">❌ ' + response.data.message + '</p>');
                }
            });
        });
    });
    </script>
    <?php
});

// ذخیره تنظیمات تلگرام
add_action('admin_init', function() {
    register_setting('gateway_price_adjust_group', 'gpa_telegram_settings');
});

// تابع ارسال پیام تلگرام
function gpa_send_telegram_message($message) {
    $settings = get_option('gpa_telegram_settings', []);
    
    if (empty($settings['enabled']) || empty($settings['bot_token']) || empty($settings['chat_id'])) {
        return false;
    }
    
    $bot_token = $settings['bot_token'];
    $chat_id = $settings['chat_id'];
    
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    
    $response = wp_remote_post($url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode([
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'HTML'
        ]),
        'timeout' => 30
    ]);
    
    if (is_wp_error($response)) {
        gpa_log_action('telegram_error', [
            'error' => $response->get_error_message()
        ]);
        return false;
    }
    
    $response_body = wp_remote_retrieve_body($response);
    $body = $response_body ? json_decode($response_body, true) : [];
    
    if ($body['ok']) {
        gpa_log_action('telegram_message_sent', [
            'message' => $message
        ]);
        return true;
    } else {
        gpa_log_action('telegram_error', [
            'error' => $body['description'] ?? 'Unknown error'
        ]);
        return false;
    }
}

// هندلر تست تلگرام
add_action('wp_ajax_gpa_test_telegram', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'gpa_test_telegram')) {
        wp_die('Security check failed');
    }
    
    $message = "✅ تست اتصال تلگرام\n\n";
    $message .= "این پیام تست از افزونه قیمت بر اساس درگاه ارسال شده است.\n";
    $message .= "زمان: " . date('Y/m/d H:i:s') . "\n";
    $message .= "وبسایت: " . get_bloginfo('name');
    
    $result = gpa_send_telegram_message($message);
    
    if ($result) {
        wp_send_json_success(['message' => 'پیام تست با موفقیت ارسال شد']);
    } else {
        wp_send_json_error(['message' => 'خطا در ارسال پیام. لطفاً تنظیمات را بررسی کنید.']);
    }
});

// اطلاع‌رسانی سفارش جدید
add_action('woocommerce_new_order', function($order_id) {
    $settings = get_option('gpa_telegram_settings', []);
    
    if (empty($settings['notify_new_order'])) return;
    
    $order = wc_get_order($order_id);
    if (!$order) return;
    
    $gateway_id = $order->get_payment_method();
    $gateway = WC()->payment_gateways->payment_gateways()[$gateway_id] ?? null;
    
    $message = "🛒 <b>سفارش جدید</b>\n\n";
    $message .= "📋 شماره سفارش: #{$order_id}\n";
    $message .= "💳 درگاه پرداخت: " . ($gateway ? $gateway->get_title() : $gateway_id) . "\n";
    $message .= "💰 مبلغ: " . wc_price($order->get_total()) . "\n";
    $message .= "👤 مشتری: " . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . "\n";
    $message .= "📧 ایمیل: " . $order->get_billing_email() . "\n";
    $message .= "📞 تلفن: " . $order->get_billing_phone() . "\n\n";
    $message .= "🕒 زمان: " . date('Y/m/d H:i:s');
    
    gpa_send_telegram_message($message);
});

// اطلاع‌رسانی تغییر تنظیمات درگاه
add_action('update_option_gateway_price_adjust_global', function($old_value, $new_value) {
    $settings = get_option('gpa_telegram_settings', []);
    
    if (empty($settings['notify_gateway_change'])) return;
    
    $changes = [];
    $gateways = WC()->payment_gateways->get_available_payment_gateways();
    
    foreach ($new_value as $gateway_id => $new_settings) {
        $old_settings = $old_value[$gateway_id] ?? [];
        
        if ($new_settings != $old_settings) {
            $gateway_name = isset($gateways[$gateway_id]) ? $gateways[$gateway_id]->get_title() : $gateway_id;
            $changes[] = "{$gateway_name}: " . 
                        ($new_settings['mode'] ?? 'increase') . ' ' .
                        ($new_settings['value'] ?? 0) . 
                        (($new_settings['kind'] ?? 'percent') === 'percent' ? '%' : 'تومان');
        }
    }
    
    if (!empty($changes)) {
        $message = "⚙️ <b>تغییر تنظیمات درگاه</b>\n\n";
        $message .= "تغییرات اعمال شده:\n";
        $message .= implode("\n", $changes) . "\n\n";
        $message .= "👤 کاربر: " . wp_get_current_user()->display_name . "\n";
        $message .= "🕒 زمان: " . date('Y/m/d H:i:s');
        
        gpa_send_telegram_message($message);
    }
}, 10, 2);

// اطلاع‌رسانی پیشنهاد هوش مصنوعی
add_action('gpa_ai_suggestion_updated', function($suggestion) {
    $settings = get_option('gpa_telegram_settings', []);
    
    if (empty($settings['notify_ai_suggestion'])) return;
    
    $gateway = WC()->payment_gateways->payment_gateways()[$suggestion['gateway_id']] ?? null;
    
    $message = "🤖 <b>پیشنهاد جدید هوش مصنوعی</b>\n\n";
    $message .= "درگاه پیشنهادی: <b>" . ($gateway ? $gateway->get_title() : $suggestion['gateway_id']) . "</b>\n";
    $message .= "امتیاز: " . round($suggestion['score'], 2) . "\n";
    $message .= "متد تحلیل: " . ($suggestion['method'] ?? 'composite') . "\n\n";
    $message .= "🕒 زمان: " . date('Y/m/d H:i:s');
    
    gpa_send_telegram_message($message);
});