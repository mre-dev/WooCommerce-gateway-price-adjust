<?php
/*
|--------------------------------------------------------------------------
| اجرا و اعمال قوانین پیشرفته
|--------------------------------------------------------------------------
*/

if (!defined('ABSPATH')) exit;


/*
|--------------------------------------------------------------------------
| بررسی دسترسی کاربر به درگاه‌ها
|--------------------------------------------------------------------------
*/
add_filter('woocommerce_available_payment_gateways', function($available_gateways) {
    if (is_admin()) return $available_gateways;
    
    $options = get_option('gateway_price_adjust_options', []);
    $rules = $options['rules'] ?? [];
    
    if (empty($rules)) return $available_gateways;
    
    $current_user = wp_get_current_user();
    $current_time = current_time('timestamp');
    
    foreach ($available_gateways as $gateway_id => $gateway) {
        $rule = $rules[$gateway_id] ?? [];
        
        // اگر قانونی برای این درگاه تعریف نشده، ادامه بده
        if (empty($rule)) continue;
        
        // بررسی نقش کاربر
        if (!empty($rule['roles'])) {
            $user_has_access = false;
            foreach ($rule['roles'] as $allowed_role) {
                if (in_array($allowed_role, $current_user->roles)) {
                    $user_has_access = true;
                    break;
                }
            }
            
            if (!$user_has_access) {
                unset($available_gateways[$gateway_id]);
                continue;
            }
        }
        
        // بررسی بازه زمانی
        if (!empty($rule['start']) || !empty($rule['end'])) {
            $start_time = !empty($rule['start']) ? strtotime($rule['start']) : 0;
            $end_time = !empty($rule['end']) ? strtotime($rule['end']) : PHP_INT_MAX;
            
            if ($current_time < $start_time || $current_time > $end_time) {
                unset($available_gateways[$gateway_id]);
                continue;
            }
        }
    }
    
    return $available_gateways;
});

/*
|--------------------------------------------------------------------------
| نمایش پیام‌های سفارشی برای درگاه‌ها
|--------------------------------------------------------------------------
*/
add_action('woocommerce_review_order_before_payment', function() {
    $options = get_option('gateway_price_adjust_options', []);
    $rules = $options['rules'] ?? [];
    
    if (empty($rules)) return;
    
    $current_user = wp_get_current_user();
    $current_time = current_time('timestamp');
    
    foreach ($rules as $gateway_id => $rule) {
        if (empty($rule['message'])) continue;
        
        // بررسی نقش کاربر
        $user_has_access = true;
        if (!empty($rule['roles'])) {
            $user_has_access = false;
            foreach ($rule['roles'] as $allowed_role) {
                if (in_array($allowed_role, $current_user->roles)) {
                    $user_has_access = true;
                    break;
                }
            }
        }
        
        // بررسی بازه زمانی
        $time_valid = true;
        if (!empty($rule['start']) || !empty($rule['end'])) {
            $start_time = !empty($rule['start']) ? strtotime($rule['start']) : 0;
            $end_time = !empty($rule['end']) ? strtotime($rule['end']) : PHP_INT_MAX;
            $time_valid = ($current_time >= $start_time && $current_time <= $end_time);
        }
        
        // اگر کاربر دسترسی دارد و زمان معتبر است، پیام را نمایش بده
        if ($user_has_access && $time_valid) {
            echo '<div class="gpa-gateway-message gpa-message-' . esc_attr($gateway_id) . '" style="
                background: #f0f9ff;
                border: 2px solid #0ea5e9;
                border-radius: 8px;
                padding: 15px;
                margin: 10px 0;
                text-align: right;
                display: none;
            ">';
            echo '<h4 style="margin: 0 0 8px 0; color: #0369a1;">💡 پیام ویژه درگاه</h4>';
            echo '<p style="margin: 0; color: #0c4a6e;">' . wp_kses_post($rule['message']) . '</p>';
            echo '</div>';
        }
    }
});


/*
|--------------------------------------------------------------------------
| اسکریپت مدیریت پیام‌ها - لود در فوتر
|--------------------------------------------------------------------------
*/
add_action('wp_footer', function() {
    if (!is_checkout()) return;
    
    $options = get_option('gateway_price_adjust_options', []);
    $rules = $options['rules'] ?? [];
    
    if (empty($rules)) return;
    ?>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        console.log('GPA Gateway Messages loaded');
        
        // تابع برای نمایش پیام مربوط به درگاه انتخاب شده
        function showGatewayMessage(gatewayId) {
            console.log('Showing message for gateway:', gatewayId);
            $('.gpa-gateway-message').hide();
            $('.gpa-message-' + gatewayId).fadeIn(300);
        }
        
        // تابع برای پنهان کردن تمام پیام‌ها
        function hideAllMessages() {
            $('.gpa-gateway-message').hide();
        }
        
        // بررسی اولیه - نمایش پیام برای درگاه انتخاب شده
        function initGatewayMessages() {
            var selectedGateway = $('input[name="payment_method"]:checked').val();
            console.log('Initial selected gateway:', selectedGateway);
            
            if (selectedGateway) {
                showGatewayMessage(selectedGateway);
            } else {
                hideAllMessages();
            }
        }
        
        // اجرای اولیه
        initGatewayMessages();
        
        // رویداد تغییر درگاه - نسخه بهینه شده
        $(document).on('change', 'input[name="payment_method"]', function() {
            var selectedGateway = $(this).val();
            console.log('Gateway changed to:', selectedGateway);
            
            if (selectedGateway) {
                showGatewayMessage(selectedGateway);
            } else {
                hideAllMessages();
            }
        });
        
        // همچنین هنگام آپدیت صفحه پرداخت (برای مواقعی که AJAX رفرش می‌کند)
        $(document).on('updated_checkout', function() {
            console.log('Checkout updated - reinitializing messages');
            setTimeout(initGatewayMessages, 100);
        });
        
        // رویداد کلیک روی لیبل درگاه‌ها (برای اطمینان بیشتر)
        $(document).on('click', '.wc_payment_method label', function() {
            setTimeout(function() {
                var selectedGateway = $('input[name="payment_method"]:checked').val();
                console.log('Label clicked - selected gateway:', selectedGateway);
                if (selectedGateway) {
                    showGatewayMessage(selectedGateway);
                }
            }, 50);
        });
        
        // رویدادهای تاچ برای موبایل
        $(document).on('touchstart', '.wc_payment_method', function() {
            setTimeout(function() {
                var selectedGateway = $('input[name="payment_method"]:checked').val();
                console.log('Touch event - selected gateway:', selectedGateway);
                if (selectedGateway) {
                    showGatewayMessage(selectedGateway);
                }
            }, 100);
        });
    });
    </script>
    
    <style>
    .gpa-gateway-message {
        animation: fadeIn 0.5s ease-in-out;
        transition: all 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* هایلایت کردن درگاه انتخاب شده */
    .wc_payment_method input:checked + label {
        background: #f0f9ff !important;
        border-color: #0ea5e9 !important;
    }
    </style>
    <?php
});

/*
|--------------------------------------------------------------------------
| اعمال محدودیت‌ها در سشن و پرداخت
|--------------------------------------------------------------------------
*/
add_action('woocommerce_checkout_process', function() {
    $chosen_gateway = WC()->session->get('chosen_payment_method');
    if (!$chosen_gateway) return;
    
    $options = get_option('gateway_price_adjust_options', []);
    $rules = $options['rules'] ?? [];
    $rule = $rules[$chosen_gateway] ?? [];
    
    if (empty($rule)) return;
    
    $current_user = wp_get_current_user();
    $current_time = current_time('timestamp');
    
    $errors = [];
    
    // بررسی نقش کاربر
    if (!empty($rule['roles'])) {
        $user_has_access = false;
        foreach ($rule['roles'] as $allowed_role) {
            if (in_array($allowed_role, $current_user->roles)) {
                $user_has_access = true;
                break;
            }
        }
        
        if (!$user_has_access) {
            $gateway_title = WC()->payment_gateways->payment_gateways()[$chosen_gateway]->get_title() ?? $chosen_gateway;
            $errors[] = "شما دسترسی استفاده از درگاه {$gateway_title} را ندارید.";
        }
    }
    
    // بررسی بازه زمانی
    if (!empty($rule['start']) || !empty($rule['end'])) {
        $start_time = !empty($rule['start']) ? strtotime($rule['start']) : 0;
        $end_time = !empty($rule['end']) ? strtotime($rule['end']) : PHP_INT_MAX;
        
        if ($current_time < $start_time) {
            $start_date = date('Y/m/d H:i', $start_time);
            $errors[] = "این درگاه از تاریخ {$start_date} فعال خواهد شد.";
        } elseif ($current_time > $end_time) {
            $end_date = date('Y/m/d H:i', $end_time);
            $errors[] = "مهلت استفاده از این درگاه تا تاریخ {$end_date} بوده است.";
        }
    }
    
    // نمایش خطاها
    foreach ($errors as $error) {
        wc_add_notice($error, 'error');
    }
});

/*
|--------------------------------------------------------------------------
| لاگ کردن فعالیت‌های قوانین
|--------------------------------------------------------------------------
*/
add_action('gpa_rule_validation', function($gateway_id, $user_id, $result, $reason = '') {
    gpa_log_action('rule_validation', [
        'gateway_id' => $gateway_id,
        'user_id' => $user_id,
        'result' => $result ? 'allowed' : 'denied',
        'reason' => $reason,
        'user_ip' => $_SERVER['REMOTE_ADDR']
    ]);
}, 10, 4);

/*
|--------------------------------------------------------------------------
| نمایش وضعیت قوانین در پنل مدیریت
|--------------------------------------------------------------------------
*/
add_action('gpa_rules_status_display', function() {
    $options = get_option('gateway_price_adjust_options', []);
    $rules = $options['rules'] ?? [];
    
    if (empty($rules)) return;
    
    echo '<div class="gpa-rules-status">';
    echo '<h4>📋 وضعیت قوانین فعال</h4>';
    echo '<div class="gpa-rules-grid">';
    
    foreach ($rules as $gateway_id => $rule) {
        $gateway = WC()->payment_gateways->payment_gateways()[$gateway_id] ?? null;
        if (!$gateway) continue;
        
        $status = 'فعال';
        $status_class = 'success';
        $reasons = [];
        
        // بررسی نقش‌ها
        if (!empty($rule['roles'])) {
            $roles_text = implode(', ', array_map(function($role) {
                $wp_roles = wp_roles()->roles;
                return $wp_roles[$role]['name'] ?? $role;
            }, $rule['roles']));
            $reasons[] = "نقش‌ها: " . $roles_text;
        }
        
        // بررسی زمان
        if (!empty($rule['start']) || !empty($rule['end'])) {
            $time_text = '';
            if (!empty($rule['start'])) {
                $time_text .= 'از ' . $rule['start'];
            }
            if (!empty($rule['end'])) {
                $time_text .= ($time_text ? ' تا ' : 'تا ') . $rule['end'];
            }
            $reasons[] = "زمان: " . $time_text;
        }
        
        echo '<div class="gpa-rule-status-card">';
        echo '<div class="gpa-rule-header">';
        echo '<h5>' . esc_html($gateway->get_title()) . '</h5>';
        echo '<span class="gpa-status-badge gpa-status-' . $status_class . '">' . $status . '</span>';
        echo '</div>';
        
        if (!empty($reasons)) {
            echo '<div class="gpa-rule-reasons">';
            foreach ($reasons as $reason) {
                echo '<span class="gpa-reason">' . esc_html($reason) . '</span>';
            }
            echo '</div>';
        }
        
        if (!empty($rule['message'])) {
            echo '<div class="gpa-rule-message">';
            echo '<strong>پیام:</strong> ' . esc_html($rule['message']);
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
    
    // استایل‌ها
    ?>
    <style>
    .gpa-rules-status {
        background: white;
        border: 1px solid #e1e5e9;
        border-radius: 10px;
        padding: 20px;
        margin: 20px 0;
    }
    
    .gpa-rules-status h4 {
        margin: 0 0 15px 0;
        color: #2d3748;
    }
    
    .gpa-rules-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 15px;
    }
    
    .gpa-rule-status-card {
        background: #f8f9fa;
        border: 1px solid #e1e5e9;
        border-radius: 8px;
        padding: 15px;
    }
    
    .gpa-rule-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .gpa-rule-header h5 {
        margin: 0;
        font-size: 14px;
        color: #2d3748;
    }
    
    .gpa-status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .gpa-status-success {
        background: #d1fae5;
        color: #065f46;
    }
    
    .gpa-rule-reasons {
        display: flex;
        flex-direction: column;
        gap: 5px;
        margin-bottom: 10px;
    }
    
    .gpa-reason {
        font-size: 12px;
        color: #6b7280;
        background: white;
        padding: 4px 8px;
        border-radius: 4px;
        border: 1px solid #e5e7eb;
    }
    
    .gpa-rule-message {
        font-size: 12px;
        color: #374151;
        background: #fef3c7;
        padding: 8px;
        border-radius: 4px;
        border-right: 3px solid #f59e0b;
    }
    </style>
    <?php
});