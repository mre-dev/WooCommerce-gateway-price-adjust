<?php
/*
|--------------------------------------------------------------------------
| ماژول سیستم هوش مصنوعی پیشنهاد درگاه
|--------------------------------------------------------------------------
*/

if (!defined('ABSPATH')) exit;

add_action('admin_init', function() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'gateway-price-adjust-settings') return;
    if (!isset($_GET['tab']) || $_GET['tab'] !== 'ai_suggestions') return;
    
    error_log('=== GPA AI Tab Loading ===');
    error_log('WooCommerce loaded: ' . (class_exists('WooCommerce') ? 'Yes' : 'No'));
    
    if (class_exists('WooCommerce')) {
        $gateways = WC()->payment_gateways->get_available_payment_gateways();
        error_log('Available gateways: ' . count($gateways));
        
        foreach ($gateways as $id => $gateway) {
            error_log("Gateway: {$id} - " . $gateway->get_title());
        }
    }
});

/*
|--------------------------------------------------------------------------
| اضافه کردن تب هوش مصنوعی
|--------------------------------------------------------------------------
*/
add_filter('gpa_additional_tabs', function($tabs) {
    $tabs['ai_suggestions'] = 'پیشنهاد هوش مصنوعی';
    return $tabs;
});

/*
|--------------------------------------------------------------------------
| محتوای تب هوش مصنوعی
|--------------------------------------------------------------------------
*/
add_action('gpa_settings_tab_content', function($current_tab) {
    if ($current_tab !== 'ai_suggestions') return;
    
    // بررسی فعال بودن ووکامرس
    if (!class_exists('WooCommerce')) {
        echo '<div class="notice notice-error"><p>ووکامرس فعال نیست!</p></div>';
        return;
    }
    
    $ai_settings = get_option('gpa_ai_settings', [
        'enabled' => false,
        'min_orders' => 10,
        'learning_rate' => 0.1,
        'suggestion_method' => 'conversion_rate'
    ]);
    
    ?>
    
    <div class="wrap" style="padding: 10px;">
        <h2>سیستم پیشنهاد هوش مصنوعی درگاه</h2>
        
        <form method="post" action="options.php">
            <?php settings_fields('gateway_price_adjust_group'); ?>
            
            <table class="form-table">
                <tr>
                    <th>فعال‌سازی سیستم هوش مصنوعی</th>
                    <td>
                        <label>
                            <input type="checkbox" name="gpa_ai_settings[enabled]" value="1" 
       <?php checked(isset($ai_settings['enabled']) && $ai_settings['enabled']); ?>>
                            استفاده از هوش مصنوعی برای پیشنهاد بهترین درگاه
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th>حداقل سفارش برای تحلیل</th>
                    <td>
                        <input type="number" name="gpa_ai_settings[min_orders]" 
       value="<?php echo esc_attr($ai_settings['min_orders'] ?? 10); ?>" min="5" max="1000">
                        <span class="description">حداقل تعداد سفارش مورد نیاز برای تحلیل و پیشنهاد</span>
                    </td>
                </tr>
                
                <tr>
                    <th>متد پیشنهاد</th>
                    <td>
                        <?php
                            $current_method = $ai_settings['suggestion_method'] ?? 'conversion_rate';
                            ?>
                            
                            <select name="gpa_ai_settings[suggestion_method]">
                                <option value="conversion_rate" <?php selected($current_method, 'conversion_rate'); ?>>نرخ تبدیل</option>
                                <option value="revenue" <?php selected($current_method, 'revenue'); ?>>درآمد کل</option>
                                <option value="avg_order_value" <?php selected($current_method, 'avg_order_value'); ?>>میانگین ارزش سفارش</option>
                                <option value="composite" <?php selected($current_method, 'composite'); ?>>امتیاز ترکیبی</option>
                            </select>
                    </td>
                </tr>
                
                <tr>
                    <th>وزن‌های امتیازدهی (برای حالت ترکیبی)</th>
                    <td>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; max-width: 300px;">
                            <label>
                                نرخ تبدیل:
                                <input type="number" name="gpa_ai_settings[weight_conversion]" 
                                       value="<?php echo esc_attr($ai_settings['weight_conversion'] ?? 40); ?>" 
                                       min="0" max="100" step="5">%
                            </label>
                            <label>
                                درآمد:
                                <input type="number" name="gpa_ai_settings[weight_revenue]" 
                                       value="<?php echo esc_attr($ai_settings['weight_revenue'] ?? 30); ?>" 
                                       min="0" max="100" step="5">%
                            </label>
                            <label>
                                ارزش سفارش:
                                <input type="number" name="gpa_ai_settings[weight_avg_order]" 
                                       value="<?php echo esc_attr($ai_settings['weight_avg_order'] ?? 20); ?>" 
                                       min="0" max="100" step="5">%
                            </label>
                            <label>
                                کارمزد:
                                <input type="number" name="gpa_ai_settings[weight_fee]" 
                                       value="<?php echo esc_attr($ai_settings['weight_fee'] ?? 10); ?>" 
                                       min="0" max="100" step="5">%
                            </label>
                        </div>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('ذخیره تنظیمات هوش مصنوعی'); ?>
        </form>
        
        <!-- تحلیل و گزارش -->
        <div style="margin-top: 40px;">
            <h3>تحلیل عملکرد درگاه‌ها</h3>
            
            <?php
            $analysis = gpa_analyze_gateway_performance();
            $suggested_gateway = gpa_suggest_best_gateway();
            
            if (is_wp_error($analysis)) {
                echo '<div class="notice notice-warning"><p>' . $analysis->get_error_message() . '</p></div>';
                return;
            }
            ?>
            
            <div class="gpa-ai-analysis">
                <div class="gpa-suggestion-card" style="background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h4>💡 پیشنهاد سیستم هوش مصنوعی</h4>
                    <p style="font-size: 18px; margin: 10px 0;">
                        <strong>بهترین درگاه: 
                            <?php 
                            if ($suggested_gateway && !is_wp_error($suggested_gateway)) {
                                $gateways = WC()->payment_gateways->get_available_payment_gateways();
                                $gateway = $gateways[$suggested_gateway['gateway_id']] ?? null;
                                echo $gateway ? esc_html($gateway->get_title()) : esc_html($suggested_gateway['gateway_id']);
                                echo ' (امتیاز: ' . round($suggested_gateway['score'], 2) . ')';
                            } else {
                                echo 'داده کافی نیست';
                            }
                            ?>
                        </strong>
                    </p>
                </div>
                
                <?php if (!empty($analysis)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>درگاه</th>
                            <th>تعداد سفارش</th>
                            <th>نرخ تبدیل</th>
                            <th>درآمد کل</th>
                            <th>میانگین سفارش</th>
                            <th>کارمزد تخمینی</th>
                            <th>امتیاز</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($analysis as $gateway_id => $data): 
                            $gateways = WC()->payment_gateways->get_available_payment_gateways();
                            $gateway = $gateways[$gateway_id] ?? null;
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo $gateway ? esc_html($gateway->get_title()) : esc_html($gateway_id); ?></strong>
                                    <?php if ($suggested_gateway && !is_wp_error($suggested_gateway) && $suggested_gateway['gateway_id'] === $gateway_id): ?>
                                        <span style="color: #46b450;">★ پیشنهاد شده</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($data['order_count']); ?></td>
                                <td><?php echo round($data['conversion_rate'], 1); ?>%</td>
                                <td><?php echo wc_price($data['total_revenue']); ?></td>
                                <td><?php echo wc_price($data['avg_order_value']); ?></td>
                                <td><?php echo wc_price($data['estimated_fee']); ?></td>
                                <td>
                                    <strong><?php echo round($data['score'], 2); ?></strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="notice notice-info">
                        <p>داده‌ای برای تحلیل وجود ندارد. پس از ثبت چند سفارش، این بخش فعال خواهد شد.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
});

// ذخیره تنظیمات هوش مصنوعی - با تاخیر تا لود کامل ووکامرس
add_action('admin_init', function() {
    register_setting('gateway_price_adjust_group', 'gpa_ai_settings');
});

// تابع تحلیل عملکرد درگاه‌ها با مدیریت خطا
function gpa_analyze_gateway_performance() {
    // بررسی فعال بودن ووکامرس
    if (!class_exists('WooCommerce')) {
        return new WP_Error('woocommerce_not_active', 'ووکامرس فعال نیست.');
    }
    
    global $wpdb;
    
    $gateways = WC()->payment_gateways->get_available_payment_gateways();
    
    // بررسی وجود درگاه
    if (empty($gateways)) {
        return new WP_Error('no_gateways', 'هیچ درگاه پرداختی یافت نشد.');
    }
    
    $analysis = [];
    
    foreach ($gateways as $gateway_id => $gateway) {
        try {
            // آمار سفارشات
            $orders = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'shop_order'
                AND p.post_status IN ('wc-completed', 'wc-processing')
                AND pm.meta_key = '_payment_method'
                AND pm.meta_value = %s
            ", $gateway_id));
            
            // درآمد کل
            $revenue = $wpdb->get_var($wpdb->prepare("
                SELECT SUM(meta2.meta_value) FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                INNER JOIN {$wpdb->postmeta} meta2 ON p.ID = meta2.post_id
                WHERE p.post_type = 'shop_order'
                AND p.post_status IN ('wc-completed', 'wc-processing')
                AND pm.meta_key = '_payment_method'
                AND pm.meta_value = %s
                AND meta2.meta_key = '_order_total'
            ", $gateway_id));
            
            $orders = intval($orders);
            $revenue = floatval($revenue) ?: 0;
            
            // میانگین ارزش سفارش
            $avg_order_value = $orders > 0 ? $revenue / $orders : 0;
            
            // نرخ تبدیل (تخمینی)
            $total_products = $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->posts} 
                WHERE post_type = 'product' 
                AND post_status = 'publish'
            ");
            
            // تخمین بازدید بر اساس تعداد محصولات
            $total_views = $total_products * 100;
            $conversion_rate = $total_views > 0 ? ($orders / $total_views) * 100 : 0;
            
            // کارمزد تخمینی (2.5% برای درگاه‌های آنلاین)
            $estimated_fee = $revenue * 0.025;
            
            $analysis[$gateway_id] = [
                'order_count' => $orders,
                'total_revenue' => $revenue,
                'avg_order_value' => $avg_order_value,
                'conversion_rate' => $conversion_rate,
                'estimated_fee' => $estimated_fee,
                'score' => 0
            ];
            
        } catch (Exception $e) {
            // ثبت خطا و ادامه حلقه
            error_log('GPA AI Analysis Error for gateway ' . $gateway_id . ': ' . $e->getMessage());
            continue;
        }
    }
    
    return $analysis;
}

// تابع پیشنهاد بهترین درگاه با مدیریت خطا
function gpa_suggest_best_gateway() {
    $ai_settings = get_option('gpa_ai_settings', []);
    if (empty($ai_settings['enabled'])) {
        return new WP_Error('ai_disabled', 'سیستم هوش مصنوعی غیرفعال است.');
    }
    
    // بررسی فعال بودن ووکامرس
    if (!class_exists('WooCommerce')) {
        return new WP_Error('woocommerce_not_active', 'ووکامرس فعال نیست.');
    }
    
    $analysis = gpa_analyze_gateway_performance();
    
    if (is_wp_error($analysis)) {
        return $analysis;
    }
    
    if (empty($analysis)) {
        return new WP_Error('no_data', 'داده‌ای برای تحلیل وجود ندارد.');
    }
    
    $min_orders = $ai_settings['min_orders'] ?? 10;
    
    // فیلتر درگاه‌هایی که داده کافی دارند
    $valid_gateways = array_filter($analysis, function($data) use ($min_orders) {
        return $data['order_count'] >= $min_orders;
    });
    
    if (empty($valid_gateways)) {
        return new WP_Error('insufficient_data', 'داده کافی برای تحلیل وجود ندارد. حداقل سفارش: ' . $min_orders);
    }
    
    // محاسبه امتیاز بر اساس متد انتخاب شده
    $method = $ai_settings['suggestion_method'] ?? 'conversion_rate';
    
    foreach ($valid_gateways as $gateway_id => &$data) {
        if ($method === 'composite') {
            // امتیاز ترکیبی
            $weights = [
                'conversion' => $ai_settings['weight_conversion'] ?? 40,
                'revenue' => $ai_settings['weight_revenue'] ?? 30,
                'avg_order' => $ai_settings['weight_avg_order'] ?? 20,
                'fee' => $ai_settings['weight_fee'] ?? 10
            ];
            
            // نرمالایز کردن مقادیر
            $max_conversion = max(array_column($valid_gateways, 'conversion_rate'));
            $max_revenue = max(array_column($valid_gateways, 'total_revenue'));
            $max_avg_order = max(array_column($valid_gateways, 'avg_order_value'));
            $min_fee = min(array_column($valid_gateways, 'estimated_fee'));
            
            $conversion_score = $max_conversion > 0 ? ($data['conversion_rate'] / $max_conversion) * 100 : 0;
            $revenue_score = $max_revenue > 0 ? ($data['total_revenue'] / $max_revenue) * 100 : 0;
            $avg_order_score = $max_avg_order > 0 ? ($data['avg_order_value'] / $max_avg_order) * 100 : 0;
            $fee_score = $min_fee > 0 ? (1 - ($data['estimated_fee'] / $max_revenue)) * 100 : 100;
            
            $data['score'] = (
                $conversion_score * $weights['conversion'] / 100 +
                $revenue_score * $weights['revenue'] / 100 +
                $avg_order_score * $weights['avg_order'] / 100 +
                $fee_score * $weights['fee'] / 100
            );
        } else {
            // امتیاز بر اساس یک معیار
            switch ($method) {
                case 'conversion_rate':
                    $data['score'] = $data['conversion_rate'];
                    break;
                case 'revenue':
                    $data['score'] = $data['total_revenue'];
                    break;
                case 'avg_order_value':
                    $data['score'] = $data['avg_order_value'];
                    break;
                default:
                    $data['score'] = $data['conversion_rate'];
            }
        }
    }
    
    // انتخاب درگاه با بالاترین امتیاز
    $best_gateway = null;
    $best_score = -1;
    
    foreach ($valid_gateways as $gateway_id => $data) {
        if ($data['score'] > $best_score) {
            $best_score = $data['score'];
            $best_gateway = [
                'gateway_id' => $gateway_id,
                'score' => $data['score']
            ];
        }
    }
    
    // ثبت در لاگ
    if ($best_gateway) {
        gpa_log_action('ai_gateway_suggestion', [
            'suggested_gateway' => $best_gateway['gateway_id'],
            'score' => $best_gateway['score'],
            'method' => $method
        ]);
    }
    
    return $best_gateway;
}

// نمایش پیشنهاد در صفحه checkout با مدیریت خطا
add_action('woocommerce_review_order_before_payment', function() {
    $ai_settings = get_option('gpa_ai_settings', []);
    if (empty($ai_settings['enabled'])) return;
    
    // بررسی فعال بودن ووکامرس
    if (!class_exists('WooCommerce')) return;
    
    $suggested_gateway = gpa_suggest_best_gateway();
    
    if (is_wp_error($suggested_gateway) || !$suggested_gateway) return;
    
    $gateways = WC()->payment_gateways->get_available_payment_gateways();
    $gateway = $gateways[$suggested_gateway['gateway_id']] ?? null;
    
    if (!$gateway) return;
    
    ?>
    <div class="gpa-ai-suggestion" style="
        background: #f0f9ff;
        border: 2px solid #0ea5e9;
        border-radius: 8px;
        padding: 15px;
        margin: 15px 0;
        text-align: center;
    ">
        <h4 style="margin: 0 0 10px 0; color: #0369a1;">
            💡 پیشنهاد هوش مصنوعی
        </h4>
        <p style="margin: 0; font-size: 16px;">
            سیستم ما پیشنهاد می‌کند از 
            <strong><?php echo esc_html($gateway->get_title()); ?></strong> 
            استفاده کنید
        </p>
        <p style="margin: 5px 0 0 0; font-size: 12px; color: #64748b;">
            بر اساس تحلیل عملکرد و نرخ تبدیل
        </p>
    </div>
    <?php
});