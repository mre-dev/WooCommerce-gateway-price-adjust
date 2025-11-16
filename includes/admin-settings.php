<?php
/*
|--------------------------------------------------------------------------
| صفحه تنظیمات مدیریت
|--------------------------------------------------------------------------
*/

if (!defined('ABSPATH')) exit;

// تعریف متغیرهای global
global $gpa_gateways, $gpa_settings, $gpa_opts;

/*
|--------------------------------------------------------------------------
| فیلتر تب‌های تنظیمات
|--------------------------------------------------------------------------
*/
add_filter('gpa_settings_tabs', function($tabs) {
    $default_tabs = [
        'general' => '🎯 تنظیمات عمومی',
        'rules' => '⚙️ قوانین پیشرفته',
        // 'reports' => '📊 گزارش‌ها',
        'other' => '🔧 تنظیمات دیگر',
        'tiered_discounts' => '💰 تخفیف پلکانی',
    ];
    
    return apply_filters('gpa_additional_tabs', $default_tabs);
});

/*
|--------------------------------------------------------------------------
| صفحه تنظیمات با تب‌ها
|--------------------------------------------------------------------------
*/
function gateway_price_adjust_settings_page() {
    if (!class_exists('WC_Payment_Gateways')) {
        echo '<div class="wrap"><div class="notice notice-error"><p>ووکامرس فعال نیست. لطفاً ابتدا ووکامرس را نصب و فعال کنید.</p></div></div>';
        return;
    }

    // تعریف متغیرهای global
    global $gpa_gateways, $gpa_settings, $gpa_opts;
    
    $gpa_gateways = WC_Payment_Gateways::instance()->get_available_payment_gateways();
    $gpa_settings = get_option('gateway_price_adjust_global', []);
    $gpa_opts = get_option('gateway_price_adjust_options', ['test_mode'=>0,'rules'=>[]]);

    // استفاده از فیلتر برای گرفتن تب‌ها
    $tabs = apply_filters('gpa_settings_tabs', []);
    $current_tab = $_GET['tab'] ?? 'general';

    echo '<div class="wrap">';
    echo '<div class="gpa-header">';
    echo '<h1 class="gpa-title">🎪 تنظیمات قیمت بر اساس درگاه - مخصوص افزونه پی زیتو</h1>';
    echo '<p class="gpa-description">مدیریت هوشمند قیمت‌ها بر اساس درگاه پرداخت انتخابی مشتری</p>';
    echo '</div>';
    
    echo '<div class="gpa-tabs-container">';
    echo '<h2 class="nav-tab-wrapper gpa-nav-tabs">';
    foreach($tabs as $key=>$label){
        $active = $current_tab==$key?'nav-tab-active':'';
        echo '<a class="nav-tab '.$active.'" href="?page=gateway-price-adjust-settings&tab='.$key.'">'.$label.'</a>';
    }
    echo '</h2>';
    echo '</div>';

    echo '<div class="gpa-tab-content">';
    
    // اجرای هوک محتوای تب‌ها
    do_action('gpa_settings_tab_content', $current_tab);

    echo '</div>';
    echo '</div>';

    // Import JSON
    if(!empty($_POST['gpa_import_file']) && !empty($_FILES['gpa_import_file'])){
        if($_FILES['gpa_import_file']['error']===0){
            $json = file_get_contents($_FILES['gpa_import_file']['tmp_name']);
            $data = json_decode($json,true);
            if($data){
                update_option('gateway_price_adjust_global', $data['global'] ?? []);
                update_option('gateway_price_adjust_options', $data['options'] ?? []);
                echo '<div class="notice notice-success is-dismissible"><p>✅ تنظیمات با موفقیت وارد شدند.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>❌ فایل JSON معتبر نیست!</p></div>';
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| محتوای تب‌های اصلی
|--------------------------------------------------------------------------
*/
add_action('gpa_settings_tab_content', function($current_tab) {
    global $gpa_gateways, $gpa_settings, $gpa_opts;
    
    switch($current_tab) {
        case 'general':
            gpa_general_tab_content($gpa_gateways, $gpa_settings);
            break;
        case 'rules':
            gpa_rules_tab_content($gpa_gateways, $gpa_opts);
            break;
        case 'tiered_discounts':
            // این تب توسط ماژول tiered-discounts.php مدیریت می‌شود
            break;
        case 'reports':
            gpa_reports_tab_content($gpa_gateways);
            break;
        case 'other':
            gpa_other_tab_content($gpa_opts);
            break;
    }
});

/*
|--------------------------------------------------------------------------
| محتوای تب تنظیمات عمومی
|--------------------------------------------------------------------------
*/
function gpa_general_tab_content($gateways, $settings) {
    if (empty($gateways)) {
        echo '<div class="notice notice-warning"><p>⚠️ هیچ درگاه پرداختی یافت نشد. لطفاً از فعال بودن درگاه‌های ووکامرس اطمینان حاصل کنید.</p></div>';
        return;
    }
    
    echo '<form method="post" action="options.php" class="gpa-form">';
    settings_fields('gateway_price_adjust_group');
    
    echo '<div class="gpa-card">';
    echo '<div class="gpa-card-header">';
    echo '<h3>🎯 تنظیمات عمومی درگاه‌ها</h3>';
    echo '<p>برای هر درگاه پرداخت، افزایش یا کاهش قیمت را تنظیم کنید</p>';
    echo '</div>';
    
    echo '<div class="gpa-card-body">';
    echo '<div class="gpa-gateways-grid">';
    
    foreach ($gateways as $gateway_id => $gateway) {
        $mode  = $settings[$gateway_id]['mode'] ?? 'increase';
        $kind  = $settings[$gateway_id]['kind'] ?? 'percent';
        $value = $settings[$gateway_id]['value'] ?? '';
        
        echo '<div class="gpa-gateway-card">';
        echo '<div class="gpa-gateway-header">';
        echo '<h4>' . esc_html($gateway->get_title()) . '</h4>';
        echo '<span class="gpa-gateway-badge">' . esc_html($gateway_id) . '</span>';
        echo '</div>';
        
        echo '<div class="gpa-gateway-controls">';
        
        // انتخاب افزایش یا کاهش
        echo '<div class="gpa-control-group">';
        echo '<label>نوع تغییر:</label>';
        echo '<select name="gateway_price_adjust_global[' . esc_attr($gateway_id) . '][mode]" class="gpa-select">';
        echo '<option value="increase" ' . selected($mode, 'increase', false) . '>📈 افزایش قیمت</option>';
        echo '<option value="decrease" ' . selected($mode, 'decrease', false) . '>📉 کاهش قیمت</option>';
        echo '</select>';
        echo '</div>';
        
        // نوع مقدار
        echo '<div class="gpa-control-group">';
        echo '<label>نوع مقدار:</label>';
        echo '<select name="gateway_price_adjust_global[' . esc_attr($gateway_id) . '][kind]" class="gpa-select">';
        echo '<option value="percent" ' . selected($kind, 'percent', false) . '>📊 درصد</option>';
        echo '<option value="fixed" ' . selected($kind, 'fixed', false) . '>💵 مبلغ ثابت</option>';
        echo '</select>';
        echo '</div>';
        
        // مقدار
        echo '<div class="gpa-control-group">';
        echo '<label>مقدار:</label>';
        echo '<input type="number" step="0.01" min="0" name="gateway_price_adjust_global[' . esc_attr($gateway_id) . '][value]" value="' . esc_attr($value) . '" class="gpa-input" placeholder="0">';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>'; // .gpa-gateways-grid
    echo '</div>'; // .gpa-card-body
    
    echo '<div class="gpa-card-footer">';
    submit_button('💾 ذخیره تنظیمات عمومی', 'primary large', 'submit', false);
    echo '</div>';
    echo '</div>'; // .gpa-card
    echo '</form>';
}

/*
|--------------------------------------------------------------------------
| محتوای تب قوانین پیشرفته
|--------------------------------------------------------------------------
*/
function gpa_rules_tab_content($gateways, $opts) {
    if (empty($gateways)) {
        echo '<div class="notice notice-warning"><p>⚠️ هیچ درگاه پرداختی یافت نشد. لطفاً از فعال بودن درگاه‌های ووکامرس اطمینان حاصل کنید.</p></div>';
        return;
    }
    
    echo '<form method="post" action="options.php" class="gpa-form">';
    settings_fields('gateway_price_adjust_group');
    
    echo '<div class="gpa-card">';
    echo '<div class="gpa-card-header">';
    echo '<h3>⚙️ قوانین پیشرفته درگاه‌ها</h3>';
    echo '<p>تنظیمات پیشرفته شامل نقش کاربران، بازه زمانی و پیام‌های دلخواه</p>';
    echo '</div>';
    
    echo '<div class="gpa-card-body">';
    
    foreach ($gateways as $gateway_id => $gateway) {
        $rule = $opts['rules'][$gateway_id] ?? ['roles' => [], 'start' => '', 'end' => '', 'message' => ''];
        $wp_roles = wp_roles()->roles;
        
        echo '<div class="gpa-rule-card">';
        echo '<div class="gpa-rule-header">';
        echo '<h4>📋 ' . esc_html($gateway->get_title()) . '</h4>';
        echo '</div>';
        
        echo '<div class="gpa-rule-content">';
        
        // نقش‌ها
        echo '<div class="gpa-rule-section">';
        echo '<h5>👥 نقش‌های کاربران مجاز</h5>';
        echo '<div class="gpa-roles-grid">';
        foreach ($wp_roles as $role_key => $role_data) {
            $checked = in_array($role_key, $rule['roles'] ?? []) ? 'checked' : '';
            echo '<label class="gpa-role-checkbox">';
            echo '<input type="checkbox" name="gateway_price_adjust_options[rules][' . $gateway_id . '][roles][]" value="' . $role_key . '" ' . $checked . '>';
            echo '<span class="gpa-role-label">' . $role_data['name'] . '</span>';
            echo '</label>';
        }
        echo '</div>';
        echo '</div>';
        
        // بازه زمانی
        echo '<div class="gpa-rule-section">';
        echo '<h5>⏰ بازه زمانی فعال</h5>';
        echo '<div class="gpa-date-range">';
        echo '<div class="gpa-date-input">';
        echo '<label>تاریخ شروع:</label>';
        echo '<input type="date" name="gateway_price_adjust_options[rules][' . $gateway_id . '][start]" value="' . esc_attr($rule['start']) . '" class="gpa-input">';
        echo '</div>';
        echo '<div class="gpa-date-input">';
        echo '<label>تاریخ پایان:</label>';
        echo '<input type="date" name="gateway_price_adjust_options[rules][' . $gateway_id . '][end]" value="' . esc_attr($rule['end']) . '" class="gpa-input">';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // پیام
        echo '<div class="gpa-rule-section">';
        echo '<h5>💬 پیام نمایش در صفحه پرداخت</h5>';
        echo '<textarea name="gateway_price_adjust_options[rules][' . $gateway_id . '][message]" rows="3" class="gpa-textarea" placeholder="پیام دلخواه برای نمایش به کاربر هنگام انتخاب این درگاه">' . esc_textarea($rule['message']) . '</textarea>';
        echo '<p class="gpa-description">این پیام در صفحه تسویه حساب به کاربر نمایش داده می‌شود</p>';
        echo '</div>';
        
        echo '</div>'; // .gpa-rule-content
        echo '</div>'; // .gpa-rule-card
    }
    
    echo '</div>'; // .gpa-card-body
    
    echo '<div class="gpa-card-footer">';
    submit_button('💾 ذخیره قوانین پیشرفته', 'primary large', 'submit', false);
    echo '</div>';
    echo '</div>'; // .gpa-card
    echo '</form>';
}

// Enqueue JS
add_action('admin_enqueue_scripts', function($hook) {
    if ('woocommerce_page_gateway-price-adjust-settings' !== $hook) return;
    
    wp_enqueue_script('gpa-tabs', GPA_PLUGIN_URL . 'assets/gpa-tabs.js', ['jquery'], GPA_VERSION, true);
    
    if (isset($_GET['tab']) && $_GET['tab'] === 'reports') {
    // استفاده از CDN جایگزین
    wp_enqueue_script('chart-js', 
        'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js', 
        [], 
        '3.9.1', 
        true
    );
    
    // اضافه کردن تایمر برای بررسی لود شدن Chart.js
    wp_add_inline_script('chart-js', '
        window.chartJsLoaded = false;
        if (typeof Chart !== "undefined") {
            window.chartJsLoaded = true;
        }
    ');
}

});


/*
|--------------------------------------------------------------------------
| محتوای تب گزارش‌ها
|--------------------------------------------------------------------------
*/
function gpa_reports_tab_content() {
    echo "در حال توسعه";
}

/*
|--------------------------------------------------------------------------
| محتوای تب دیگر
|--------------------------------------------------------------------------
*/
function gpa_other_tab_content($opts) {
    echo '<form method="post" action="options.php" class="gpa-form">';
    settings_fields('gateway_price_adjust_group');
    
    echo '<div class="gpa-card">';
    echo '<div class="gpa-card-header">';
    echo '<h3>🔧 تنظیمات پیشرفته و ابزارها</h3>';
    echo '<p>تنظیمات جانبی و ابزارهای توسعه و مدیریت</p>';
    echo '</div>';
    
    echo '<div class="gpa-card-body">';
    echo '<div class="gpa-settings-grid">';
    
    // حالت تست
    $test_mode = !empty($opts['test_mode']);
    echo '<div class="gpa-setting-card">';
    echo '<div class="gpa-setting-icon">🧪</div>';
    echo '<div class="gpa-setting-content">';
    echo '<h4>حالت تست</h4>';
    echo '<label class="gpa-toggle">';
    echo '<input type="checkbox" name="gateway_price_adjust_options[test_mode]" value="1" ' . checked($test_mode, true, false) . '>';
    echo '<span class="gpa-toggle-slider"></span>';
    echo '</label>';
    echo '<p class="gpa-description">در حالت تست، تغییرات قیمت فقط برای مدیران سایت نمایش داده می‌شود.</p>';
    echo '</div>';
    echo '</div>';
    
    // لاگ دیباگ
    $debug_mode = !empty($opts['debug_mode']);
    echo '<div class="gpa-setting-card">';
    echo '<div class="gpa-setting-icon">📝</div>';
    echo '<div class="gpa-setting-content">';
    echo '<h4>لاگ دیباگ</h4>';
    echo '<label class="gpa-toggle">';
    echo '<input type="checkbox" name="gateway_price_adjust_options[debug_mode]" value="1" ' . checked($debug_mode, true, false) . '>';
    echo '<span class="gpa-toggle-slider"></span>';
    echo '</label>';
    echo '<p class="gpa-description">در صورت فعال بودن، اطلاعات دیباگ در فایل لاگ وردپرس ذخیره می‌شود.</p>';
    echo '</div>';
    echo '</div>';
    
    // بازنشانی تنظیمات
    echo '<div class="gpa-setting-card gpa-danger-card">';
    echo '<div class="gpa-setting-icon">⚠️</div>';
    echo '<div class="gpa-setting-content">';
    echo '<h4>بازنشانی تنظیمات</h4>';
    echo '<button type="button" id="gpa-reset-settings" class="gpa-danger-button">';
    echo 'بازنشانی تمام تنظیمات';
    echo '</button>';
    echo '<p class="gpa-description">تمام تنظیمات پلاگین به حالت پیش‌فرض بازمی‌گردد. این عمل غیرقابل بازگشت است!</p>';
    echo '</div>';
    echo '</div>';
    
    echo '</div>'; // .gpa-settings-grid
    echo '</div>'; // .gpa-card-body
    
    echo '<div class="gpa-card-footer">';
    submit_button('💾 ذخیره تنظیمات', 'primary large', 'submit', false);
    echo '</div>';
    echo '</div>'; // .gpa-card
    echo '</form>';
    
    // اسکریپت بازنشانی
    echo '<script>
        jQuery(function($) {
            $("#gpa-reset-settings").on("click", function() {
                if (confirm("⚠️ آیا از بازنشانی تمام تنظیمات اطمینان دارید؟ این عمل غیرقابل بازگشت است!")) {
                    if (confirm("❌ مطمئن هستید؟ تمام تنظیمات پاک خواهند شد!")) {
                        $.post(ajaxurl, {
                            action: "gpa_reset_settings",
                            nonce: "' . wp_create_nonce('gpa_reset_settings') . '"
                        }, function(response) {
                            if (response.success) {
                                alert("✅ تنظیمات با موفقیت بازنشانی شدند");
                                location.reload();
                            } else {
                                alert("❌ خطا در بازنشانی تنظیمات");
                            }
                        });
                    }
                }
            });
        });
    </script>';
}

// استایل‌های جدید
add_action('admin_head', function() {
    ?>
<style>
/* استایل‌های عمومی */
.gpa-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 30px;
        margin: -20px -20px 30px -20px;
        border-radius: 0 0 10px 10px;
        color: white;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .gpa-title {
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 10px 0;
        color: white;
    }

    .gpa-description {
        font-size: 16px;
        opacity: 0.9;
        margin: 0;
    }

    .gpa-tabs-container {
        background: white;
        border-radius: 10px;
        padding: 0;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .gpa-nav-tabs {
        padding: 0 20px;
        border-bottom: 1px solid #e1e5e9;
    }

    .gpa-nav-tabs .nav-tab {
        border: none;
        background: none;
        padding: 15px 20px;
        font-size: 14px;
        font-weight: 600;
        margin: 0;
        border-radius: 0;
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
    }

    .gpa-nav-tabs .nav-tab:hover {
        background: #f8f9fa;
        border-bottom-color: #667eea;
    }

    .gpa-nav-tabs .nav-tab-active {
        background: white;
        border-bottom-color: #667eea;
        color: #667eea;
    }

    .gpa-tab-content {
        background: white;
        border-radius: 10px;
        padding: 0;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    /* کارت‌ها */
    .gpa-card {
        border: none;
        border-radius: 10px;
        overflow: hidden;
    }

    .gpa-card-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 25px 30px;
        border-bottom: 1px solid #e1e5e9;
    }

    .gpa-card-header h3 {
        margin: 0 0 8px 0;
        font-size: 20px;
        font-weight: 700;
        color: #2d3748;
    }

    .gpa-card-header p {
        margin: 0;
        color: #6b7280;
        font-size: 14px;
    }

    .gpa-card-body {
        padding: 30px;
    }

    .gpa-card-footer {
        background: #f8f9fa;
        padding: 20px 30px;
        border-top: 1px solid #e1e5e9;
        text-align: left;
    }

    /* فرم‌ها */
    .gpa-form .button-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 12px 30px;
        font-size: 16px;
        font-weight: 600;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        transition: all 0.3s ease;
    }

    .gpa-form .button-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    /* تنظیمات عمومی - کارت درگاه‌ها */
    .gpa-gateways-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
    }

    .gpa-gateway-card {
        background: white;
        border: 1px solid #e1e5e9;
        border-radius: 10px;
        padding: 20px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .gpa-gateway-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .gpa-gateway-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #f1f3f4;
    }

    .gpa-gateway-header h4 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #2d3748;
    }

    .gpa-gateway-badge {
        background: #667eea;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }

    .gpa-gateway-controls {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .gpa-control-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .gpa-control-group label {
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        margin-bottom: 2px;
    }

    .gpa-select, .gpa-input {
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .gpa-select:focus, .gpa-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    /* قوانین پیشرفته */
    .gpa-rule-card {
        background: white;
        border: 1px solid #e1e5e9;
        border-radius: 10px;
        margin-bottom: 20px;
        overflow: hidden;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .gpa-rule-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 20px 25px;
        border-bottom: 1px solid #e1e5e9;
    }

    .gpa-rule-header h4 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #2d3748;
    }

    .gpa-rule-content {
        padding: 25px;
    }

    .gpa-rule-section {
        margin-bottom: 25px;
    }

    .gpa-rule-section:last-child {
        margin-bottom: 0;
    }

    .gpa-rule-section h5 {
        margin: 0 0 15px 0;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
    }

    .gpa-roles-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 10px;
    }

    .gpa-role-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        background: #f8f9fa;
        border-radius: 6px;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .gpa-role-checkbox:hover {
        background: #e9ecef;
    }

    .gpa-role-checkbox input[type="checkbox"] {
        margin: 0;
    }

    .gpa-role-label {
        font-size: 13px;
        color: #4b5563;
    }

    .gpa-date-range {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .gpa-date-input {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .gpa-textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        resize: vertical;
        min-height: 80px;
        transition: all 0.3s ease;
    }

    .gpa-textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    /* گزارش‌ها */
    .gpa-chart-container {
        background: white;
        padding: 20px;
        border-radius: 10px;
        border: 1px solid #e1e5e9;
        margin-bottom: 30px;
    }

    .gpa-stats-table {
        background: white;
        border-radius: 10px;
        border: 1px solid #e1e5e9;
        overflow: hidden;
    }

    .gpa-stats-table h4 {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 20px;
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #2d3748;
        border-bottom: 1px solid #e1e5e9;
    }

    .gpa-order-count {
        background: #667eea;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }

    .gpa-progress-container {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .gpa-progress-bar {
        flex: 1;
        background: #f1f3f4;
        height: 8px;
        border-radius: 4px;
        overflow: hidden;
    }

    .gpa-progress-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.3s ease;
    }

    .gpa-percentage {
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        min-width: 35px;
    }

    /* تنظیمات دیگر */
    .gpa-settings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        gap: 20px;
    }

    .gpa-setting-card {
        background: white;
        border: 1px solid #e1e5e9;
        border-radius: 10px;
        padding: 25px;
        display: flex;
        align-items: flex-start;
        gap: 15px;
        transition: all 0.3s ease;
    }

    .gpa-setting-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .gpa-danger-card {
        border-color: #fecaca;
        background: #fef2f2;
    }

    .gpa-setting-icon {
        font-size: 24px;
        flex-shrink: 0;
    }

    .gpa-setting-content {
        flex: 1;
    }

    .gpa-setting-content h4 {
        margin: 0 0 10px 0;
        font-size: 16px;
        font-weight: 600;
        color: #2d3748;
    }

    .gpa-toggle {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
        margin-bottom: 10px;
    }

    .gpa-toggle input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .gpa-toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #d1d5db;
        transition: .4s;
        border-radius: 24px;
    }

    .gpa-toggle-slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked + .gpa-toggle-slider {
        background-color: #667eea;
    }

    input:checked + .gpa-toggle-slider:before {
        transform: translateX(26px);
    }

    .gpa-danger-button {
        background: #dc2626;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .gpa-danger-button:hover {
        background: #b91c1c;
        transform: translateY(-1px);
    }

    /* ریسپانسیو */
    @media (max-width: 768px) {
        .gpa-gateways-grid {
            grid-template-columns: 1fr;
        }
        
        .gpa-settings-grid {
            grid-template-columns: 1fr;
        }
        
        .gpa-date-range {
            grid-template-columns: 1fr;
        }
        
        .gpa-roles-grid {
            grid-template-columns: 1fr;
        }
        
        .gpa-header {
            margin: -10px -10px 20px -10px;
            padding: 20px;
        }
    }
    </style>
    <?php
});


/*
|--------------------------------------------------------------------------
| هندلر بازنشانی تنظیمات
|--------------------------------------------------------------------------
*/
add_action('wp_ajax_gpa_reset_settings', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'gpa_reset_settings')) {
        wp_die('Security check failed');
    }
    
    if (!current_user_can('manage_woocommerce')) {
        wp_die('Access denied');
    }
    
    // حذف تمام تنظیمات
    delete_option('gateway_price_adjust_global');
    delete_option('gateway_price_adjust_options');
    delete_option('gpa_tiered_discounts');
    delete_option('gpa_inventory_rules');
    delete_option('gpa_ai_settings');
    delete_option('gpa_telegram_settings');
    delete_option('gpa_competitor_settings');
    delete_option('gpa_coupon_settings');
    
    gpa_log_action('settings_reset', [
        'user_id' => get_current_user_id(),
        'user_ip' => $_SERVER['REMOTE_ADDR']
    ]);
    
    wp_send_json_success('Settings reset successfully');
});