<?php
/*
|--------------------------------------------------------------------------
| ماژول سیستم اطلاع‌رسانی پیامک
|--------------------------------------------------------------------------
*/

if (!defined('ABSPATH')) exit;

/*
|--------------------------------------------------------------------------
| اضافه کردن تب پیامک
|--------------------------------------------------------------------------
*/
add_filter('gpa_additional_tabs', function($tabs) {
    $tabs['sms_notifications'] = 'اطلاع‌رسانی پیامک';
    return $tabs;
});

/*
|--------------------------------------------------------------------------
| محتوای تب پیامک
|--------------------------------------------------------------------------
*/
add_action('gpa_settings_tab_content', function($current_tab) {
    if ($current_tab !== 'sms_notifications') return;
    
    $sms_settings = get_option('gpa_sms_settings', [
        'enabled' => false,
        'provider' => 'custom',
        'custom_url' => '',
        'api_key' => '',
        'line_number' => '',
        'admin_mobile' => '',
        'notify_new_order' => true,
        'notify_low_stock' => true,
        'notify_gateway_change' => true,
        'notify_ai_suggestion' => true
    ]);
    ?>
    
    <div class="wrap">
        <h2>اطلاع‌رسانی از طریق پیامک</h2>
        
        <form method="post" action="options.php">
            <?php settings_fields('gateway_price_adjust_group'); ?>
            
            <table class="form-table">
                <tr>
                    <th>فعال‌سازی اطلاع‌رسانی پیامک</th>
                    <td>
                        <label>
                            <input type="checkbox" name="gpa_sms_settings[enabled]" value="1" 
                                   <?php checked(isset($sms_settings['enabled']) && $sms_settings['enabled']); ?>>
                            ارسال پیامک اطلاع‌رسانی
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th>سرویس دهنده پیامک</th>
                    <td>
                        <select name="gpa_sms_settings[provider]" id="gpa_sms_provider">
                            <option value="custom" <?php selected($sms_settings['provider'] ?? 'custom', 'custom'); ?>>
                                سرویس شخصی (Webservice)
                            </option>
                            <option value="kavenegar" <?php selected($sms_settings['provider'] ?? 'custom', 'kavenegar'); ?>>
                                کاوه‌نگار
                            </option>
                            <option value="melipayamak" <?php selected($sms_settings['provider'] ?? 'custom', 'melipayamak'); ?>>
                                ملی پیامک
                            </option>
                            <option value="smsir" <?php selected($sms_settings['provider'] ?? 'custom', 'smsir'); ?>>
                                SMS.ir
                            </option>
                            <option value="farapayamak" <?php selected($sms_settings['provider'] ?? 'custom', 'farapayamak'); ?>>
                                فراپیامک
                            </option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th>شماره موبایل ادمین</th>
                    <td>
                        <input type="text" name="gpa_sms_settings[admin_mobile]" 
                               value="<?php echo esc_attr($sms_settings['admin_mobile'] ?? ''); ?>" 
                               class="regular-text" placeholder="09123456789">
                        <span class="description">
                            شماره موبایل دریافت کننده پیامک‌ها
                        </span>
                    </td>
                </tr>
                
                <!-- تنظیمات سرویس شخصی -->
                <tr class="gpa-sms-custom" style="display: none;">
                    <th>آدرس وب‌سرویس شخصی</th>
                    <td>
                        <input type="url" name="gpa_sms_settings[custom_url]" 
                               value="<?php echo esc_attr($sms_settings['custom_url'] ?? ''); ?>" 
                               class="regular-text" placeholder="https://example.com/send-sms">
                        <span class="description">
                            آدرس کامل وب‌سرویس پیامک شما (GET/POST)
                        </span>
                    </td>
                </tr>
                
                <!-- تنظیمات برای سرویس‌های ایرانی -->
                <tr class="gpa-sms-api" style="display: none;">
                    <th>API Key</th>
                    <td>
                        <input type="password" name="gpa_sms_settings[api_key]" 
                               value="<?php echo esc_attr($sms_settings['api_key'] ?? ''); ?>" 
                               class="regular-text">
                        <span class="description">
                            کلید API سرویس پیامک
                        </span>
                    </td>
                </tr>
                
                <tr class="gpa-sms-api" style="display: none;">
                    <th>شماره خط</th>
                    <td>
                        <input type="text" name="gpa_sms_settings[line_number]" 
                               value="<?php echo esc_attr($sms_settings['line_number'] ?? ''); ?>" 
                               class="regular-text" placeholder="3000xxxx">
                        <span class="description">
                            شماره خط ارسال پیامک
                        </span>
                    </td>
                </tr>
                
                <tr>
                    <th>رویدادهای اطلاع‌رسانی</th>
                    <td>
                        <label style="display: block; margin: 5px 0;">
                            <input type="checkbox" name="gpa_sms_settings[notify_new_order]" value="1" 
                                   <?php checked(isset($sms_settings['notify_new_order']) && $sms_settings['notify_new_order']); ?>>
                            سفارش جدید
                        </label>
                        
                        <label style="display: block; margin: 5px 0;">
                            <input type="checkbox" name="gpa_sms_settings[notify_low_stock]" value="1" 
                                   <?php checked(isset($sms_settings['notify_low_stock']) && $sms_settings['notify_low_stock']); ?>>
                            اتمام موجودی
                        </label>
                        
                        <label style="display: block; margin: 5px 0;">
                            <input type="checkbox" name="gpa_sms_settings[notify_gateway_change]" value="1" 
                                   <?php checked(isset($sms_settings['notify_gateway_change']) && $sms_settings['notify_gateway_change']); ?>>
                            تغییر تنظیمات درگاه
                        </label>
                        
                        <label style="display: block; margin: 5px 0;">
                            <input type="checkbox" name="gpa_sms_settings[notify_ai_suggestion]" value="1" 
                                   <?php checked(isset($sms_settings['notify_ai_suggestion']) && $sms_settings['notify_ai_suggestion']); ?>>
                            پیشنهاد جدید هوش مصنوعی
                        </label>
                    </td>
                </tr>
            </table>
            
            <!-- راهنمای وب‌سرویس شخصی -->
            <div id="gpa_sms_custom_guide" style="display: none; background: #f0f9ff; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <h3>📋 راهنمای وب‌سرویس شخصی</h3>
                <p>وب‌سرویس شما باید پارامترهای زیر را دریافت کند:</p>
                <ul>
                    <li><strong>to</strong>: شماره موبایل مقصد</li>
                    <li><strong>message</strong>: متن پیامک</li>
                    <li><strong>from</strong>: شماره خط ارسال (اختیاری)</li>
                </ul>
                <p>مثال آدرس وب‌سرویس:</p>
                <pre style="background: #fff; padding: 10px; border-radius: 3px;">
https://your-sms-provider.com/send?to={MOBILE}&message={MESSAGE}&from={LINE}</pre>
            </div>
            
            <?php submit_button('ذخیره تنظیمات پیامک'); ?>
        </form>
        
        <!-- تست ارسال پیامک -->
        <div style="margin-top: 40px;">
            <h3>تست ارسال پیامک</h3>
            
            <button type="button" id="gpa-test-sms" class="button button-primary">
                تست ارسال پیامک
            </button>
            
            <div id="gpa-sms-result" style="margin-top: 10px;"></div>
        </div>
    </div>
    
    <script>
    jQuery(function($) {
        // نمایش/پنهان کردن فیلدهای مربوط به سرویس پیامک
        function toggleSMSFields() {
            const provider = $('#gpa_sms_provider').val();
            const isCustom = provider === 'custom';
            const isAPI = !isCustom;
            
            $('.gpa-sms-custom').toggle(isCustom);
            $('.gpa-sms-api').toggle(isAPI);
            $('#gpa_sms_custom_guide').toggle(isCustom);
        }
        
        $('#gpa_sms_provider').on('change', toggleSMSFields);
        toggleSMSFields(); // اجرای اولیه
        
        // تست پیامک
        $('#gpa-test-sms').on('click', function() {
            const $button = $(this);
            const $result = $('#gpa-sms-result');
            
            $button.prop('disabled', true).text('در حال ارسال...');
            $result.html('<p style="color: #666;">در حال ارسال پیامک تست...</p>');
            
            $.post(ajaxurl, {
                action: 'gpa_test_sms',
                nonce: '<?php echo wp_create_nonce('gpa_test_sms'); ?>'
            }, function(response) {
                $button.prop('disabled', false).text('تست ارسال پیامک');
                
                if (response.success) {
                    $result.html('<p style="color: #46b450;">✅ ' + response.data.message + '</p>');
                } else {
                    $result.html('<p style="color: #dc3232;">❌ ' + response.data.message + '</p>');
                }
            }).fail(function() {
                $button.prop('disabled', false).text('تست ارسال پیامک');
                $result.html('<p style="color: #dc3232;">❌ خطا در ارتباط با سرور</p>');
            });
        });
    });
    </script>
    <?php
});

// ذخیره تنظیمات پیامک
add_action('admin_init', function() {
    register_setting('gateway_price_adjust_group', 'gpa_sms_settings');
});

/*
|--------------------------------------------------------------------------
| تابع اصلی ارسال پیامک
|--------------------------------------------------------------------------
*/
function gpa_send_sms($message, $to = null) {
    $settings = get_option('gpa_sms_settings', []);
    
    if (empty($settings['enabled'])) {
        return false;
    }
    
    // اگر شماره مقصد مشخص نشده، از شماره ادمین استفاده کن
    if (empty($to)) {
        $to = $settings['admin_mobile'] ?? '';
    }
    
    if (empty($to)) {
        gpa_log_action('sms_error', ['error' => 'No mobile number provided']);
        return false;
    }
    
    // پاکسازی شماره موبایل
    $to = gpa_clean_mobile_number($to);
    
    $provider = $settings['provider'] ?? 'custom';
    
    switch ($provider) {
        case 'kavenegar':
            return gpa_send_sms_kavenegar($to, $message, $settings);
            
        case 'melipayamak':
            return gpa_send_sms_melipayamak($to, $message, $settings);
            
        case 'smsir':
            return gpa_send_sms_smsir($to, $message, $settings);
            
        case 'farapayamak':
            return gpa_send_sms_farapayamak($to, $message, $settings);
            
        case 'custom':
        default:
            return gpa_send_sms_custom($to, $message, $settings);
    }
}

/*
|--------------------------------------------------------------------------
| پاکسازی شماره موبایل
|--------------------------------------------------------------------------
*/
function gpa_clean_mobile_number($mobile) {
    // حذف فاصله و کاراکترهای غیرعددی
    $mobile = preg_replace('/[^0-9]/', '', $mobile);
    
    // اگر با 09 شروع شده
    if (preg_match('/^09[0-9]{9}$/', $mobile)) {
        return $mobile;
    }
    
    // اگر با 9 شروع شده
    if (preg_match('/^9[0-9]{9}$/', $mobile)) {
        return '0' . $mobile;
    }
    
    // اگر با 989 شروع شده (کد ایران)
    if (preg_match('/^989[0-9]{9}$/', $mobile)) {
        return '0' . substr($mobile, 2);
    }
    
    return $mobile;
}

/*
|--------------------------------------------------------------------------
| سرویس شخصی (Webservice)
|--------------------------------------------------------------------------
*/
function gpa_send_sms_custom($to, $message, $settings) {
    $custom_url = $settings['custom_url'] ?? '';
    
    if (empty($custom_url)) {
        gpa_log_action('sms_error', ['error' => 'Custom URL not set']);
        return false;
    }
    
    // جایگزینی پارامترها در URL
    $url = str_replace(
        ['{MOBILE}', '{MESSAGE}', '{LINE}'],
        [urlencode($to), urlencode($message), urlencode($settings['line_number'] ?? '')],
        $custom_url
    );
    
    $response = wp_remote_get($url, [
        'timeout' => 15,
        'sslverify' => false
    ]);
    
    return gpa_handle_sms_response($response, $to, $message, 'custom');
}

/*
|--------------------------------------------------------------------------
| کاوه‌نگار
|--------------------------------------------------------------------------
*/
function gpa_send_sms_kavenegar($to, $message, $settings) {
    $api_key = $settings['api_key'] ?? '';
    $line_number = $settings['line_number'] ?? '';
    
    if (empty($api_key)) {
        return false;
    }
    
    $url = "https://api.kavenegar.com/v1/{$api_key}/sms/send.json";
    
    $response = wp_remote_post($url, [
        'body' => [
            'receptor' => $to,
            'message' => $message,
            'sender' => $line_number
        ],
        'timeout' => 15
    ]);
    
    return gpa_handle_sms_response($response, $to, $message, 'kavenegar');
}

/*
|--------------------------------------------------------------------------
| ملی پیامک
|--------------------------------------------------------------------------
*/
function gpa_send_sms_melipayamak($to, $message, $settings) {
    $api_key = $settings['api_key'] ?? '';
    $line_number = $settings['line_number'] ?? '';
    
    if (empty($api_key)) {
        return false;
    }
    
    $url = "http://api.payamak-panel.com/post/Send.asmx/SendSimpleSMS2";
    
    $response = wp_remote_post($url, [
        'body' => [
            'username' => $api_key, // در ملی پیامک معمولاً username است
            'password' => $api_key, // یا password جداگانه
            'to' => $to,
            'text' => $message,
            'from' => $line_number,
            'isflash' => 'false'
        ],
        'timeout' => 15
    ]);
    
    return gpa_handle_sms_response($response, $to, $message, 'melipayamak');
}

/*
|--------------------------------------------------------------------------
| SMS.ir
|--------------------------------------------------------------------------
*/
function gpa_send_sms_smsir($to, $message, $settings) {
    $api_key = $settings['api_key'] ?? '';
    $line_number = $settings['line_number'] ?? '';
    
    if (empty($api_key)) {
        return false;
    }
    
    // اول باید توکن بگیریم
    $token_url = "https://RestfulSms.com/api/Token";
    $token_response = wp_remote_post($token_url, [
        'headers' => [
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode([
            'UserApiKey' => $api_key,
            'SecretKey' => $api_key // در SMS.ir معمولاً SecretKey جداگانه است
        ])
    ]);
    
    $token_body = wp_remote_retrieve_body($token_response);
    $token_data = json_decode($token_body, true);
    $token = $token_data['TokenKey'] ?? '';
    
    if (empty($token)) {
        return false;
    }
    
    // ارسال پیامک
    $send_url = "https://RestfulSms.com/api/MessageSend";
    $response = wp_remote_post($send_url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'x-sms-ir-secure-token' => $token
        ],
        'body' => json_encode([
            'Messages' => [$message],
            'MobileNumbers' => [$to],
            'LineNumber' => $line_number,
            'SendDateTime' => '',
            'CanContinueInCaseOfError' => false
        ]),
        'timeout' => 15
    ]);
    
    return gpa_handle_sms_response($response, $to, $message, 'smsir');
}

/*
|--------------------------------------------------------------------------
| فراپیامک
|--------------------------------------------------------------------------
*/
function gpa_send_sms_farapayamak($to, $message, $settings) {
    $api_key = $settings['api_key'] ?? '';
    $line_number = $settings['line_number'] ?? '';
    
    if (empty($api_key)) {
        return false;
    }
    
    $url = "http://api.payamak-panel.com/post/Send.asmx/SendSimpleSMS2";
    
    $response = wp_remote_post($url, [
        'body' => [
            'username' => $api_key,
            'password' => $api_key,
            'to' => $to,
            'text' => $message,
            'from' => $line_number,
            'isflash' => 'false'
        ],
        'timeout' => 15
    ]);
    
    return gpa_handle_sms_response($response, $to, $message, 'farapayamak');
}

/*
|--------------------------------------------------------------------------
| پردازش پاسخ سرویس پیامک
|--------------------------------------------------------------------------
*/
function gpa_handle_sms_response($response, $to, $message, $provider) {
    if (is_wp_error($response)) {
        gpa_log_action('sms_error', [
            'provider' => $provider,
            'to' => $to,
            'error' => $response->get_error_message(),
            'message' => $message
        ]);
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    // برای سرویس‌های مختلف، پاسخ موفق متفاوت است
    $success = false;
    
    switch ($provider) {
        case 'kavenegar':
            $body = json_decode($response_body, true);
            $success = ($body['return']['status'] ?? 0) === 200;
            break;
            
        case 'custom':
            // برای سرویس شخصی، هر پاسخی که کد 200 دارد موفق در نظر گرفته می‌شود
            $success = $response_code === 200;
            break;
            
        default:
            $success = $response_code === 200;
    }
    
    if ($success) {
        gpa_log_action('sms_sent', [
            'provider' => $provider,
            'to' => $to,
            'message' => $message
        ]);
        return true;
    } else {
        gpa_log_action('sms_error', [
            'provider' => $provider,
            'to' => $to,
            'error' => "HTTP {$response_code}",
            'response' => $response_body,
            'message' => $message
        ]);
        return false;
    }
}

/*
|--------------------------------------------------------------------------
| هندلر تست پیامک
|--------------------------------------------------------------------------
*/
add_action('wp_ajax_gpa_test_sms', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'gpa_test_sms')) {
        wp_die('Security check failed');
    }
    
    $message = "تست سرویس پیامک\n";
    $message .= "افزونه قیمت بر اساس درگاه\n";
    $message .= "زمان: " . date('Y/m/d H:i:s') . "\n";
    $message .= "سرویس: " . (get_option('gpa_sms_settings')['provider'] ?? 'custom');
    
    $result = gpa_send_sms($message);
    
    if ($result) {
        wp_send_json_success(['message' => 'پیامک تست با موفقیت ارسال شد']);
    } else {
        wp_send_json_error(['message' => 'خطا در ارسال پیامک. لطفاً تنظیمات را بررسی کنید.']);
    }
});

/*
|--------------------------------------------------------------------------
| اطلاع‌رسانی سفارش جدید
|--------------------------------------------------------------------------
*/
add_action('woocommerce_new_order', function($order_id) {
    $settings = get_option('gpa_sms_settings', []);
    
    if (empty($settings['notify_new_order'])) return;
    
    $order = wc_get_order($order_id);
    if (!$order) return;
    
    $gateway_id = $order->get_payment_method();
    $gateway = WC()->payment_gateways->payment_gateways()[$gateway_id] ?? null;
    
    $message = "سفارش جدید #{$order_id}\n";
    $message .= "مبلغ: " . wc_price($order->get_total()) . "\n";
    $message .= "درگاه: " . ($gateway ? $gateway->get_title() : $gateway_id) . "\n";
    $message .= "مشتری: " . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . "\n";
    $message .= "تلفن: " . $order->get_billing_phone();
    
    gpa_send_sms($message);
});

/*
|--------------------------------------------------------------------------
| اطلاع‌رسانی تغییر تنظیمات درگاه
|--------------------------------------------------------------------------
*/
add_action('update_option_gateway_price_adjust_global', function($old_value, $new_value) {
    $settings = get_option('gpa_sms_settings', []);
    
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
        $message = "تغییر تنظیمات درگاه\n";
        $message .= implode(' - ', $changes) . "\n";
        $message .= "کاربر: " . wp_get_current_user()->display_name . "\n";
        $message .= "زمان: " . date('H:i');
        
        gpa_send_sms($message);
    }
}, 10, 2);

/*
|--------------------------------------------------------------------------
| اطلاع‌رسانی پیشنهاد هوش مصنوعی
|--------------------------------------------------------------------------
*/
add_action('gpa_ai_suggestion_updated', function($suggestion) {
    $settings = get_option('gpa_sms_settings', []);
    
    if (empty($settings['notify_ai_suggestion'])) return;
    
    $gateway = WC()->payment_gateways->payment_gateways()[$suggestion['gateway_id']] ?? null;
    
    $message = "پیشنهاد هوش مصنوعی\n";
    $message .= "درگاه: " . ($gateway ? $gateway->get_title() : $suggestion['gateway_id']) . "\n";
    $message .= "امتیاز: " . round($suggestion['score'], 1) . "\n";
    $message .= "متد: " . ($suggestion['method'] ?? 'composite');
    
    gpa_send_sms($message);
});