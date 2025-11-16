<?php
/*
|--------------------------------------------------------------------------
| ماژول تخفیف پلکانی بر اساس درگاه - نسخه نهایی
|--------------------------------------------------------------------------
*/

if (!defined('ABSPATH')) exit;

/*
|--------------------------------------------------------------------------
| اضافه کردن تب تخفیف پلکانی
|--------------------------------------------------------------------------
*/
add_filter('gpa_additional_tabs', function($tabs) {
    $tabs['tiered_discounts'] = 'تخفیف‌های پلکانی';
    return $tabs;
});

/*
|--------------------------------------------------------------------------
| محتوای تب تخفیف پلکانی
|--------------------------------------------------------------------------
*/
add_action('gpa_settings_tab_content', function($current_tab) {
    if ($current_tab !== 'tiered_discounts') return;
    
    $tiered_discounts = get_option('gpa_tiered_discounts', []);
    ?>
    
    <div class="wrap" style="padding: 10px;">
        <h2>تخفیف‌های پلکانی بر اساس درگاه</h2>
        <p>در این بخش می‌توانید قوانین تخفیف پلکانی بر اساس مبلغ سبد خرید و درگاه پرداخت تنظیم کنید.</p>
        
        <!-- فرم اصلی -->
        <form method="post" action="options.php" id="gpa-tiered-form">
            <?php settings_fields('gateway_price_adjust_group'); ?>
            
            <div id="gpa-tiered-rules">
                <?php if (empty($tiered_discounts)): ?>
                    <div class="notice notice-info">
                        <p style="color: black;">هنوز هیچ قانون تخفیف پلکانی تعریف نشده است. برای شروع روی دکمه "افزودن قانون جدید" کلیک کنید.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($tiered_discounts as $index => $rule): ?>
                    <div class="gpa-tier-rule" data-index="<?php echo $index; ?>">
                        <h3>قانون پلکانی #<?php echo $index + 1; ?></h3>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">نام قانون</th>
                                <td>
                                    <input type="text" name="gpa_tiered_discounts[<?php echo $index; ?>][name]" 
                                           value="<?php echo esc_attr($rule['name'] ?? ''); ?>" class="regular-text" placeholder="مثال: تخفیف ویژه خریدهای بالای 500 هزار تومان">
                                    <p class="description">نام توصیفی برای این قانون تخفیف</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">حداقل مبلغ سبد</th>
                                <td>
                                    <input type="number" name="gpa_tiered_discounts[<?php echo $index; ?>][min_amount]" 
                                           value="<?php echo esc_attr($rule['min_amount'] ?? ''); ?>" step="1000" min="0" placeholder="100000">
                                    <span class="description">تومان</span>
                                    <p class="description">حداقل مبلغ سبد خرید برای اعمال این تخفیف</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">درگاه‌های هدف</th>
                                <td>
                                    <?php 
                                    $gateways = WC()->payment_gateways->get_available_payment_gateways();
                                    if (empty($gateways)): 
                                    ?>
                                        <p class="description">هیچ درگاه پرداختی یافت نشد</p>
                                    <?php else: ?>
                                        <?php foreach($gateways as $gateway_id => $gateway): 
                                            $checked = (isset($rule['gateways']) && in_array($gateway_id, $rule['gateways'])) ? 'checked' : '';
                                        ?>
                                            <label style="display: block; margin: 5px 0;">
                                                <input type="checkbox" name="gpa_tiered_discounts[<?php echo $index; ?>][gateways][]" 
                                                       value="<?php echo esc_attr($gateway_id); ?>" <?php echo $checked; ?>>
                                                <?php echo esc_html($gateway->get_title()); ?>
                                            </label>
                                        <?php endforeach; ?>
                                        <p class="description">درگاه‌هایی که این تخفیف برای آنها اعمال می‌شود</p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">نوع تخفیف</th>
                                <td>
                                    <select name="gpa_tiered_discounts[<?php echo $index; ?>][type]" class="gpa-discount-type">
                                        <option value="percent" <?php selected($rule['type'] ?? 'percent', 'percent'); ?>>درصدی</option>
                                        <option value="fixed" <?php selected($rule['type'] ?? 'percent', 'fixed'); ?>>مبلغ ثابت</option>
                                    </select>
                                    <p class="description">نوع تخفیف را انتخاب کنید</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">مقدار تخفیف</th>
                                <td>
                                    <input type="number" name="gpa_tiered_discounts[<?php echo $index; ?>][value]" 
                                           value="<?php echo esc_attr($rule['value'] ?? ''); ?>" step="0.01" min="0" placeholder="10">
                                    <span class="description gpa-discount-unit">
                                        <?php echo (($rule['type'] ?? 'percent') === 'percent') ? '%' : 'تومان'; ?>
                                    </span>
                                    <p class="description">مقدار تخفیف را وارد کنید</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">فعال</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="gpa_tiered_discounts[<?php echo $index; ?>][enabled]" value="1" 
                                               <?php checked($rule['enabled'] ?? false); ?>>
                                        این قانون فعال باشد
                                    </label>
                                </td>
                            </tr>
                        </table>
                        
                        <button type="button" class="button button-remove-rule" style="color: #dc3232; margin-bottom: 20px;">
                            🗑️ حذف این قانون
                        </button>
                        <hr style="margin: 20px 0;">
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div style="margin: 20px 0;">
                <button type="button" id="gpa-add-tier-rule" class="button button-primary">
                    ➕ افزودن قانون جدید
                </button>
                
                <?php submit_button('💾 ذخیره قوانین تخفیف', 'primary', 'submit', true); ?>
            </div>
        </form>
    </div>
    
    <style>
    .gpa-tier-rule {
        background: #f9f9f9;
        padding: 20px;
        margin: 15px 0;
        border-radius: 8px;
        border: 1px solid #ddd;
        transition: all 0.3s ease;
    }
    .gpa-tier-rule:hover {
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .gpa-tier-rule h3 {
        margin-top: 0;
        color: #0073aa;
        border-bottom: 2px solid #0073aa;
        padding-bottom: 10px;
    }
    .button-remove-rule {
        margin-top: 10px;
    }
    .gpa-discount-unit {
        font-weight: bold;
        color: #0073aa;
    }
    </style>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('GPA Tiered Discounts loaded');
        
        // مقداردهی اولیه
        let ruleIndex = <?php echo count($tiered_discounts); ?>;
        console.log('Initial ruleIndex:', ruleIndex);
        
        // دکمه افزودن قانون
        const addButton = document.getElementById('gpa-add-tier-rule');
        if (addButton) {
            addButton.addEventListener('click', function() {
                console.log('Add button clicked');
                addNewRule(ruleIndex);
                ruleIndex++;
            });
        } else {
            console.error('Add button not found!');
        }
        
        // تابع افزودن قانون جدید
        function addNewRule(index) {
            console.log('Adding new rule, index:', index);
            
            // مخفی کردن پیام "هیچ قانونی وجود ندارد"
            const notice = document.querySelector('.notice-info');
            if (notice) {
                notice.style.display = 'none';
            }
            
            // ایجاد HTML جدید برای قانون
            const newRuleHTML = `
                <div class="gpa-tier-rule" data-index="${index}">
                    <h3>قانون پلکانی جدید</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">نام قانون</th>
                            <td>
                                <input type="text" name="gpa_tiered_discounts[${index}][name]" class="regular-text" placeholder="نام قانون تخفیف">
                                <p class="description">نام توصیفی برای این قانون تخفیف</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">حداقل مبلغ سبد</th>
                            <td>
                                <input type="number" name="gpa_tiered_discounts[${index}][min_amount]" step="1000" min="0" placeholder="100000">
                                <span class="description">تومان</span>
                                <p class="description">حداقل مبلغ سبد خرید برای اعمال این تخفیف</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">درگاه‌های هدف</th>
                            <td>
                                <?php 
                                $gateways = WC()->payment_gateways->get_available_payment_gateways();
                                if (!empty($gateways)): 
                                    foreach($gateways as $gateway_id => $gateway): 
                                ?>
                                    <label style="display: block; margin: 5px 0;">
                                        <input type="checkbox" name="gpa_tiered_discounts[${index}][gateways][]" value="<?php echo esc_attr($gateway_id); ?>">
                                        <?php echo esc_html($gateway->get_title()); ?>
                                    </label>
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                    <p class="description">هیچ درگاه پرداختی یافت نشد</p>
                                <?php endif; ?>
                                <p class="description">درگاه‌هایی که این تخفیف برای آنها اعمال می‌شود</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">نوع تخفیف</th>
                            <td>
                                <select name="gpa_tiered_discounts[${index}][type]" class="gpa-discount-type">
                                    <option value="percent">درصدی</option>
                                    <option value="fixed">مبلغ ثابت</option>
                                </select>
                                <p class="description">نوع تخفیف را انتخاب کنید</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">مقدار تخفیف</th>
                            <td>
                                <input type="number" name="gpa_tiered_discounts[${index}][value]" step="0.01" min="0" placeholder="10">
                                <span class="description gpa-discount-unit">%</span>
                                <p class="description">مقدار تخفیف را وارد کنید</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">فعال</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="gpa_tiered_discounts[${index}][enabled]" value="1" checked>
                                    این قانون فعال باشد
                                </label>
                            </td>
                        </tr>
                    </table>
                    <button type="button" class="button button-remove-rule" style="color: #dc3232; margin-bottom: 20px;">
                        🗑️ حذف این قانون
                    </button>
                    <hr style="margin: 20px 0;">
                </div>
            `;
            
            // اضافه کردن به صفحه
            const rulesContainer = document.getElementById('gpa-tiered-rules');
            if (rulesContainer) {
                rulesContainer.insertAdjacentHTML('beforeend', newRuleHTML);
                console.log('New rule added successfully');
            } else {
                console.error('Rules container not found!');
            }
        }
        
        // Event Delegation برای حذف قوانین
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('button-remove-rule')) {
                if (confirm('آیا از حذف این قانون اطمینان دارید؟')) {
                    e.target.closest('.gpa-tier-rule').remove();
                    console.log('Rule removed');
                }
            }
        });
        
        // Event Delegation برای تغییر نوع تخفیف
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('gpa-discount-type')) {
                const row = e.target.closest('tr');
                const nextRow = row.nextElementSibling;
                if (nextRow) {
                    const unitSpan = nextRow.querySelector('.gpa-discount-unit');
                    if (unitSpan) {
                        unitSpan.textContent = e.target.value === 'percent' ? '%' : 'تومان';
                        console.log('Discount unit updated to:', unitSpan.textContent);
                    }
                }
            }
        });
        
        console.log('GPA Tiered Discounts initialized successfully');
    });
    </script>
    
    <?php
});

// ذخیره تنظیمات تخفیف پلکانی
add_action('admin_init', function() {
    register_setting('gateway_price_adjust_group', 'gpa_tiered_discounts', [
        'sanitize_callback' => 'gpa_sanitize_tiered_discounts'
    ]);
});

/*
|--------------------------------------------------------------------------
| تابع سانیتیز تنظیمات تخفیف پلکانی
|--------------------------------------------------------------------------
*/
function gpa_sanitize_tiered_discounts($input) {
    if (!is_array($input)) {
        return [];
    }
    
    $sanitized = [];
    
    foreach ($input as $index => $rule) {
        // سانیتیز کردن نام
        $name = sanitize_text_field($rule['name'] ?? '');
        if (empty($name)) {
            $name = 'تخفیف پلکانی ' . ($index + 1);
        }
        
        // سانیتیز کردن درگاه‌ها
        $gateways = [];
        if (isset($rule['gateways']) && is_array($rule['gateways'])) {
            $gateways = array_map('sanitize_text_field', $rule['gateways']);
        }
        
        $sanitized[$index] = [
            'name' => $name,
            'min_amount' => max(0, floatval($rule['min_amount'] ?? 0)),
            'gateways' => $gateways,
            'type' => in_array($rule['type'] ?? 'percent', ['percent', 'fixed']) ? $rule['type'] : 'percent',
            'value' => max(0, floatval($rule['value'] ?? 0)),
            'enabled' => !empty($rule['enabled'])
        ];
    }
    
    // بازسازی ایندکس‌ها به صورت متوالی
    $sanitized = array_values($sanitized);
    
    // ثبت در لاگ
    gpa_log_action('tiered_discounts_updated', [
        'rules_count' => count($sanitized),
        'user_id' => get_current_user_id()
    ]);
    
    return $sanitized;
}

/*
|--------------------------------------------------------------------------
| اعمال تخفیف پلکانی در سبد خرید
|--------------------------------------------------------------------------
*/
add_action('woocommerce_cart_calculate_fees', function() {
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (!WC()->cart || WC()->cart->is_empty()) return;
    
    $chosen_gateway = WC()->session->get('chosen_payment_method');
    if (!$chosen_gateway) return;
    
    $cart_total = WC()->cart->get_subtotal();
    $tiered_discounts = get_option('gpa_tiered_discounts', []);
    
    // پیدا کردن مناسب‌ترین تخفیف
    $best_discount = null;
    $best_discount_amount = 0;
    
    foreach ($tiered_discounts as $rule) {
        if (empty($rule['enabled']) || empty($rule['gateways'])) continue;
        
        // بررسی حداقل مبلغ
        if ($cart_total < floatval($rule['min_amount'])) continue;
        
        // بررسی درگاه
        if (!in_array($chosen_gateway, $rule['gateways'])) continue;
        
        // محاسبه تخفیف
        $discount_value = floatval($rule['value']);
        $discount_amount = 0;
        
        if ($rule['type'] === 'percent') {
            $discount_amount = ($cart_total * $discount_value) / 100;
        } else {
            $discount_amount = $discount_value;
        }
        
        // انتخاب تخفیف با بیشترین مقدار
        if ($discount_amount > $best_discount_amount) {
            $best_discount_amount = $discount_amount;
            $best_discount = $rule;
        }
    }
    
    // اعمال تخفیف
    if ($best_discount_amount > 0 && $best_discount) {
        $discount_label = sprintf(
            'تخفیف پلکانی %s (%s)',
            $best_discount['name'],
            $best_discount['type'] === 'percent' ? 
                $best_discount['value'] . '%' : 
                wc_price($best_discount['value'])
        );
        
        WC()->cart->add_fee($discount_label, -$best_discount_amount, false);
        
        // ثبت در لاگ
        gpa_log_action('tiered_discount_applied', [
            'rule_name' => $best_discount['name'],
            'gateway' => $chosen_gateway,
            'cart_total' => $cart_total,
            'discount_amount' => $best_discount_amount,
            'discount_type' => $best_discount['type']
        ]);
    }
});

/*
|--------------------------------------------------------------------------
| نمایش پیام تخفیف در صفحه پرداخت
|--------------------------------------------------------------------------
*/
add_action('woocommerce_before_checkout_form', function() {
    if (!WC()->cart || WC()->cart->is_empty()) return;
    
    $chosen_gateway = WC()->session->get('chosen_payment_method');
    if (!$chosen_gateway) return;
    
    $cart_total = WC()->cart->get_subtotal();
    $tiered_discounts = get_option('gpa_tiered_discounts', []);
    $available_discounts = [];
    
    foreach ($tiered_discounts as $rule) {
        if (empty($rule['enabled']) || empty($rule['gateways'])) continue;
        
        if ($cart_total >= floatval($rule['min_amount']) && in_array($chosen_gateway, $rule['gateways'])) {
            $available_discounts[] = $rule;
        }
    }
    
    if (!empty($available_discounts)) {
        echo '<div class="woocommerce-message" style="background: #f0f9ff; border: 1px solid #0ea5e9; padding: 15px; border-radius: 5px; margin: 15px 0;">';
        echo '<strong>🎉 تخفیف‌های فعال برای این درگاه:</strong><br>';
        
        foreach ($available_discounts as $discount) {
            $discount_text = $discount['type'] === 'percent' ? 
                $discount['value'] . '%' : 
                wc_price($discount['value']);
            
            echo '<span style="display: inline-block; background: #0ea5e9; color: white; padding: 5px 10px; border-radius: 3px; margin: 5px; font-size: 12px;">';
            echo $discount['name'] . ': ' . $discount_text;
            echo '</span>';
        }
        
        echo '</div>';
    }
});

/*
|--------------------------------------------------------------------------
| به‌روزرسانی محاسبات هنگام تغییر درگاه
|--------------------------------------------------------------------------
*/
add_action('wp_ajax_gpa_update_payment_method', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'gpa_nonce')) {
        wp_die('Security check failed');
    }
    
    if (!empty($_POST['payment_method'])) {
        WC()->session->set('chosen_payment_method', sanitize_text_field($_POST['payment_method']));
        WC()->session->save_data();
    }
    
    // بازگشت داده‌های سبد خرید برای به‌روزرسانی
    $data = array(
        'fragments' => apply_filters('woocommerce_update_order_review_fragments', array())
    );
    
    wp_send_json($data);
});