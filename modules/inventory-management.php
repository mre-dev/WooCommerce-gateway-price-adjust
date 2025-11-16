<?php
/*
|--------------------------------------------------------------------------
| ماژول مدیریت موجودی بر اساس درگاه
|--------------------------------------------------------------------------
*/

if (!defined('ABSPATH')) exit;

/*
|--------------------------------------------------------------------------
| اضافه کردن تب مدیریت موجودی
|--------------------------------------------------------------------------
*/
add_filter('gpa_additional_tabs', function($tabs) {
    $tabs['inventory_management'] = 'مدیریت موجودی';
    return $tabs;
});

/*
|--------------------------------------------------------------------------
| محتوای تب مدیریت موجودی
|--------------------------------------------------------------------------
*/
add_action('gpa_settings_tab_content', function($current_tab) {
    if ($current_tab !== 'inventory_management') return;
    
    $inventory_rules = get_option('gpa_inventory_rules', []);
    $products = wc_get_products(['limit' => 100, 'status' => 'publish']);
    ?>
    
    <div class="wrap" style="padding: 10px;">
        <h2>مدیریت موجودی بر اساس درگاه</h2>
        <p>در این بخش می‌توانید قوانین محدودیت خرید بر اساس درگاه پرداخت تنظیم کنید.</p>
        
        <!-- فرم اصلی -->
        <form method="post" action="options.php" id="gpa-inventory-form">
            <?php settings_fields('gateway_price_adjust_group'); ?>
            
            <div id="gpa-inventory-rules">
                <?php if (empty($inventory_rules)): ?>
                    <div class="notice notice-info">
                        <p style="color: black;">هنوز هیچ قانون مدیریت موجودی تعریف نشده است. برای شروع روی دکمه "افزودن قانون جدید" کلیک کنید.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($inventory_rules as $index => $rule): ?>
                    <div class="gpa-inventory-rule" data-index="<?php echo $index; ?>">
                        <h3>قانون موجودی #<?php echo $index + 1; ?></h3>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">محصول</th>
                                <td>
                                    <select name="gpa_inventory_rules[<?php echo $index; ?>][product_id]" required>
                                        <option value="">انتخاب محصول</option>
                                        <?php foreach($products as $product): ?>
                                            <option value="<?php echo $product->get_id(); ?>" 
                                                    <?php selected($rule['product_id'] ?? '', $product->get_id()); ?>>
                                                <?php echo esc_html($product->get_name()); ?> (<?php echo $product->get_id(); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">محصول مورد نظر برای اعمال محدودیت</p>
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
                                                <input type="checkbox" name="gpa_inventory_rules[<?php echo $index; ?>][gateways][]" 
                                                       value="<?php echo esc_attr($gateway_id); ?>" <?php echo $checked; ?>>
                                                <?php echo esc_html($gateway->get_title()); ?>
                                            </label>
                                        <?php endforeach; ?>
                                        <p class="description">درگاه‌هایی که این محدودیت برای آنها اعمال می‌شود</p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">حداکثر تعداد خرید</th>
                                <td>
                                    <input type="number" name="gpa_inventory_rules[<?php echo $index; ?>][max_quantity]" 
                                           value="<?php echo esc_attr($rule['max_quantity'] ?? ''); ?>" min="1" required placeholder="5">
                                    <p class="description">تعداد مجاز خرید با درگاه‌های انتخاب شده</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">رزرو موجودی (دقیقه)</th>
                                <td>
                                    <input type="number" name="gpa_inventory_rules[<?php echo $index; ?>][reserve_time]" 
                                           value="<?php echo esc_attr($rule['reserve_time'] ?? 30); ?>" min="1" placeholder="30">
                                    <span class="description">دقیقه</span>
                                    <p class="description">مدت زمان رزرو موجودی پس از افزودن به سبد خرید</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">فعال</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="gpa_inventory_rules[<?php echo $index; ?>][enabled]" value="1" 
                                               <?php checked($rule['enabled'] ?? false); ?>>
                                        این قانون فعال باشد
                                    </label>
                                </td>
                            </tr>
                        </table>
                        
                        <button type="button" class="button button-remove-inventory-rule" style="color: #dc3232; margin-bottom: 20px;">
                            🗑️ حذف این قانون
                        </button>
                        <hr style="margin: 20px 0;">
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div style="margin: 20px 0;">
                <button type="button" id="gpa-add-inventory-rule" class="button button-primary">
                    ➕ افزودن قانون جدید
                </button>
                
                <?php submit_button('💾 ذخیره قوانین موجودی', 'primary', 'submit', true); ?>
            </div>
        </form>
    </div>
    
    <style>
    .gpa-inventory-rule {
        background: #f9f9f9;
        padding: 20px;
        margin: 15px 0;
        border-radius: 8px;
        border: 1px solid #ddd;
        transition: all 0.3s ease;
    }
    .gpa-inventory-rule:hover {
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .gpa-inventory-rule h3 {
        margin-top: 0;
        color: #0073aa;
        border-bottom: 2px solid #0073aa;
        padding-bottom: 10px;
    }
    .button-remove-inventory-rule {
        margin-top: 10px;
    }
    </style>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('GPA Inventory Management loaded');
        
        // مقداردهی اولیه
        let inventoryRuleIndex = <?php echo count($inventory_rules); ?>;
        console.log('Initial inventoryRuleIndex:', inventoryRuleIndex);
        
        // دکمه افزودن قانون
        const addButton = document.getElementById('gpa-add-inventory-rule');
        if (addButton) {
            addButton.addEventListener('click', function() {
                console.log('Add inventory rule button clicked');
                addNewInventoryRule(inventoryRuleIndex);
                inventoryRuleIndex++;
            });
        } else {
            console.error('Add inventory rule button not found!');
        }
        
        // تابع افزودن قانون جدید
        function addNewInventoryRule(index) {
            console.log('Adding new inventory rule, index:', index);
            
            // مخفی کردن پیام "هیچ قانونی وجود ندارد"
            const notice = document.querySelector('.notice-info');
            if (notice) {
                notice.style.display = 'none';
            }
            
            // ایجاد HTML جدید برای قانون
            const newRuleHTML = `
                <div class="gpa-inventory-rule" data-index="${index}">
                    <h3>قانون موجودی جدید</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">محصول</th>
                            <td>
                                <select name="gpa_inventory_rules[${index}][product_id]" required>
                                    <option value="">انتخاب محصول</option>
                                    <?php foreach($products as $product): ?>
                                        <option value="<?php echo $product->get_id(); ?>">
                                            <?php echo esc_html($product->get_name()); ?> (<?php echo $product->get_id(); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">محصول مورد نظر برای اعمال محدودیت</p>
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
                                        <input type="checkbox" name="gpa_inventory_rules[${index}][gateways][]" value="<?php echo esc_attr($gateway_id); ?>">
                                        <?php echo esc_html($gateway->get_title()); ?>
                                    </label>
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                    <p class="description">هیچ درگاه پرداختی یافت نشد</p>
                                <?php endif; ?>
                                <p class="description">درگاه‌هایی که این محدودیت برای آنها اعمال می‌شود</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">حداکثر تعداد خرید</th>
                            <td>
                                <input type="number" name="gpa_inventory_rules[${index}][max_quantity]" min="1" required placeholder="5">
                                <p class="description">تعداد مجاز خرید با درگاه‌های انتخاب شده</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">رزرو موجودی (دقیقه)</th>
                            <td>
                                <input type="number" name="gpa_inventory_rules[${index}][reserve_time]" value="30" min="1" placeholder="30">
                                <span class="description">دقیقه</span>
                                <p class="description">مدت زمان رزرو موجودی پس از افزودن به سبد خرید</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">فعال</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="gpa_inventory_rules[${index}][enabled]" value="1" checked>
                                    این قانون فعال باشد
                                </label>
                            </td>
                        </tr>
                    </table>
                    <button type="button" class="button button-remove-inventory-rule" style="color: #dc3232; margin-bottom: 20px;">
                        🗑️ حذف این قانون
                    </button>
                    <hr style="margin: 20px 0;">
                </div>
            `;
            
            // اضافه کردن به صفحه
            const rulesContainer = document.getElementById('gpa-inventory-rules');
            if (rulesContainer) {
                rulesContainer.insertAdjacentHTML('beforeend', newRuleHTML);
                console.log('New inventory rule added successfully');
            } else {
                console.error('Inventory rules container not found!');
            }
        }
        
        // Event Delegation برای حذف قوانین
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('button-remove-inventory-rule')) {
                if (confirm('آیا از حذف این قانون اطمینان دارید؟')) {
                    e.target.closest('.gpa-inventory-rule').remove();
                    console.log('Inventory rule removed');
                    
                    // اگر همه قوانین حذف شدند، پیام را نشان بده
                    const remainingRules = document.querySelectorAll('.gpa-inventory-rule');
                    if (remainingRules.length === 0) {
                        const rulesContainer = document.getElementById('gpa-inventory-rules');
                        const noticeHTML = `
                            <div class="notice notice-info">
                                <p style="color: black;">هنوز هیچ قانون مدیریت موجودی تعریف نشده است. برای شروع روی دکمه "افزودن قانون جدید" کلیک کنید.</p>
                            </div>
                        `;
                        rulesContainer.innerHTML = noticeHTML;
                        inventoryRuleIndex = 0; // ریست کردن ایندکس
                    }
                }
            }
        });
        
        console.log('GPA Inventory Management initialized successfully');
    });
    </script>
    
    <?php
});

// ذخیره قوانین موجودی
add_action('admin_init', function() {
    register_setting('gateway_price_adjust_group', 'gpa_inventory_rules', [
        'sanitize_callback' => 'gpa_sanitize_inventory_rules'
    ]);
});

/*
|--------------------------------------------------------------------------
| تابع سانیتیز قوانین موجودی
|--------------------------------------------------------------------------
*/
function gpa_sanitize_inventory_rules($input) {
    if (!is_array($input)) {
        return [];
    }
    
    $sanitized = [];
    
    foreach ($input as $index => $rule) {
        // سانیتیز کردن محصول
        $product_id = intval($rule['product_id'] ?? 0);
        if ($product_id <= 0) {
            continue; // اگر محصول معتبر نیست، این قانون را نادیده بگیر
        }
        
        // سانیتیز کردن درگاه‌ها
        $gateways = [];
        if (isset($rule['gateways']) && is_array($rule['gateways'])) {
            $gateways = array_map('sanitize_text_field', $rule['gateways']);
        }
        
        $sanitized[$index] = [
            'product_id' => $product_id,
            'gateways' => $gateways,
            'max_quantity' => max(1, intval($rule['max_quantity'] ?? 1)),
            'reserve_time' => max(1, intval($rule['reserve_time'] ?? 30)),
            'enabled' => !empty($rule['enabled'])
        ];
    }
    
    // بازسازی ایندکس‌ها به صورت متوالی
    $sanitized = array_values($sanitized);
    
    // ثبت در لاگ
    gpa_log_action('inventory_rules_updated', [
        'rules_count' => count($sanitized),
        'user_id' => get_current_user_id()
    ]);
    
    return $sanitized;
}

/*
|--------------------------------------------------------------------------
| بررسی موجودی هنگام افزودن به سبد خرید
|--------------------------------------------------------------------------
*/
add_filter('woocommerce_add_to_cart_validation', function($passed, $product_id, $quantity) {
    if (!$passed) return $passed;
    
    // فقط در صورتی که کاربر لاگین کرده باشد یا سشن ووکامرس موجود باشد
    if (!WC()->session) return $passed;
    
    $chosen_gateway = WC()->session->get('chosen_payment_method');
    if (!$chosen_gateway) return $passed;
    
    $inventory_rules = get_option('gpa_inventory_rules', []);
    
    foreach ($inventory_rules as $rule) {
        if (empty($rule['enabled']) || $rule['product_id'] != $product_id) continue;
        
        if (in_array($chosen_gateway, $rule['gateways'])) {
            $max_quantity = intval($rule['max_quantity']);
            
            if ($quantity > $max_quantity) {
                wc_add_notice(
                    sprintf('با درگاه پرداخت انتخاب شده، حداکثر می‌توانید %d عدد از این محصول را خریداری کنید.', $max_quantity),
                    'error'
                );
                return false;
            }
            
            // رزرو موجودی
            if (isset($rule['reserve_time']) && $rule['reserve_time'] > 0) {
                gpa_reserve_inventory($product_id, $quantity, $rule['reserve_time']);
            }
        }
    }
    
    return $passed;
}, 10, 3);

/*
|--------------------------------------------------------------------------
| تابع رزرو موجودی
|--------------------------------------------------------------------------
*/
function gpa_reserve_inventory($product_id, $quantity, $reserve_time) {
    $reserved = get_transient("gpa_reserved_{$product_id}") ?: 0;
    $reserved += $quantity;
    
    set_transient("gpa_reserved_{$product_id}", $reserved, $reserve_time * 60);
    
    gpa_log_action('inventory_reserved', [
        'product_id' => $product_id,
        'quantity' => $quantity,
        'reserve_time' => $reserve_time,
        'expires_in' => $reserve_time * 60 . ' seconds'
    ]);
}

/*
|--------------------------------------------------------------------------
| بررسی موجودی در صفحه تسویه حساب
|--------------------------------------------------------------------------
*/
add_action('woocommerce_check_cart_items', function() {
    if (!WC()->session) return;
    
    $chosen_gateway = WC()->session->get('chosen_payment_method');
    if (!$chosen_gateway) return;
    
    $inventory_rules = get_option('gpa_inventory_rules', []);
    
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product_id = $cart_item['product_id'];
        $quantity = $cart_item['quantity'];
        
        foreach ($inventory_rules as $rule) {
            if (empty($rule['enabled']) || $rule['product_id'] != $product_id) continue;
            
            if (in_array($chosen_gateway, $rule['gateways'])) {
                $max_quantity = intval($rule['max_quantity']);
                
                if ($quantity > $max_quantity) {
                    wc_add_notice(
                        sprintf('با درگاه پرداخت انتخاب شده، حداکثر می‌توانید %d عدد از محصول "%s" را خریداری کنید.', 
                                $max_quantity, get_the_title($product_id)),
                        'error'
                    );
                }
            }
        }
    }
});

/*
|--------------------------------------------------------------------------
| نمایش پیام محدودیت در صفحه محصول
|--------------------------------------------------------------------------
*/
add_action('woocommerce_single_product_summary', function() {
    global $product;
    
    if (!WC()->session) return;
    
    $chosen_gateway = WC()->session->get('chosen_payment_method');
    if (!$chosen_gateway) return;
    
    $product_id = $product->get_id();
    $inventory_rules = get_option('gpa_inventory_rules', []);
    
    foreach ($inventory_rules as $rule) {
        if (empty($rule['enabled']) || $rule['product_id'] != $product_id) continue;
        
        if (in_array($chosen_gateway, $rule['gateways'])) {
            $max_quantity = intval($rule['max_quantity']);
            
            echo '<div class="woocommerce-message" style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; margin: 10px 0;">';
            echo '<strong>⚠️ توجه:</strong> با درگاه پرداخت انتخاب شده، حداکثر می‌توانید ' . $max_quantity . ' عدد از این محصول را خریداری کنید.';
            echo '</div>';
            break;
        }
    }
}, 25);