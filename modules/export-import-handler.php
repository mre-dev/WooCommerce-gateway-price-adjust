<?php
/*
|--------------------------------------------------------------------------
| مدیریت Export و Import تنظیمات
|--------------------------------------------------------------------------
*/

if (!defined('ABSPATH')) exit;

/*
|--------------------------------------------------------------------------
| هندلر Export تنظیمات
|--------------------------------------------------------------------------
*/
add_action('admin_init', function() {
    // فقط اگر در صفحه تنظیمات پلاگین هستیم
    if (!isset($_GET['page']) || $_GET['page'] !== 'gateway-price-adjust-settings') {
        return;
    }
    
    if (!isset($_GET['gpa_action']) || $_GET['gpa_action'] !== 'export' || !isset($_GET['_wpnonce'])) {
        return;
    }
    
    if (!wp_verify_nonce($_GET['_wpnonce'], 'gpa_export')) {
        wp_die('Security check failed');
    }
    
    if (!current_user_can('manage_woocommerce')) {
        wp_die('Access denied');
    }
    
    // جمع‌آوری تمام تنظیمات پلاگین
    $export_data = [
        'version' => GPA_VERSION,
        'export_date' => date('Y-m-d H:i:s'),
        'site_url' => get_site_url(),
        'global_settings' => get_option('gateway_price_adjust_global', []),
        'options' => get_option('gateway_price_adjust_options', []),
        'ai_settings' => get_option('gpa_ai_settings', []),
        'telegram_settings' => get_option('gpa_telegram_settings', []),
        'sms_settings' => get_option('gpa_sms_settings', []),
        'tiered_discounts' => get_option('gpa_tiered_discounts', []),
        'inventory_rules' => get_option('gpa_inventory_rules', []),
        'coupon_settings' => get_option('gpa_coupon_settings', [])
    ];
    
    // تنظیمات هدر برای دانلود
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="gpa-settings-' . date('Y-m-d-H-i') . '.json"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
});


/*
|--------------------------------------------------------------------------
| هندلر Import تنظیمات
|--------------------------------------------------------------------------
*/
add_action('admin_init', function() {
    if (!isset($_POST['gpa_import']) || empty($_FILES['gpa_import_file'])) {
        return;
    }
    
    if (!wp_verify_nonce($_POST['_wpnonce'], 'gateway_price_adjust_group-options')) {
        wp_die('Security check failed');
    }
    
    if (!current_user_can('manage_woocommerce')) {
        wp_die('Access denied');
    }
    
    $file = $_FILES['gpa_import_file'];
    
    // بررسی خطاهای آپلود
    if ($file['error'] !== UPLOAD_ERR_OK) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible"><p>خطا در آپلود فایل!</p></div>';
        });
        return;
    }
    
    // بررسی نوع فایل
    $file_type = wp_check_filetype($file['name']);
    if ($file_type['ext'] !== 'json') {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible"><p>فایل باید از نوع JSON باشد!</p></div>';
        });
        return;
    }
    
    // خواندن محتوای فایل
    $json_content = file_get_contents($file['tmp_name']);
    $import_data = json_decode($json_content, true);
    
    // بررسی صحت JSON
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($import_data)) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible"><p>فایل JSON معتبر نیست!</p></div>';
        });
        return;
    }
    
    // بررسی نسخه سازگاری
    if (version_compare($import_data['version'] ?? '1.0', '2.0', '<')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-warning is-dismissible"><p>این فایل از نسخه قدیمی پلاگین export شده است. ممکن است برخی تنظیمات سازگار نباشند.</p></div>';
        });
    }
    
    // ذخیره تنظیمات
    $imported = 0;
    $settings_map = [
        'global_settings' => 'gateway_price_adjust_global',
        'options' => 'gateway_price_adjust_options',
        'ai_settings' => 'gpa_ai_settings',
        'telegram_settings' => 'gpa_telegram_settings',
        'sms_settings' => 'gpa_sms_settings',
        'tiered_discounts' => 'gpa_tiered_discounts',
        'inventory_rules' => 'gpa_inventory_rules',
        'coupon_settings' => 'gpa_coupon_settings'
    ];
    
    foreach ($settings_map as $import_key => $option_name) {
        if (isset($import_data[$import_key])) {
            update_option($option_name, $import_data[$import_key]);
            $imported++;
        }
    }
    
    // ثبت در لاگ
    gpa_log_action('settings_imported', [
        'imported_settings' => $imported,
        'export_date' => $import_data['export_date'] ?? 'unknown',
        'source_site' => $import_data['site_url'] ?? 'unknown'
    ]);
    
    // نمایش پیام موفقیت
    add_action('admin_notices', function() use ($imported) {
        echo '<div class="notice notice-success is-dismissible"><p>تنظیمات با موفقیت import شدند. ' . $imported . ' گروه تنظیمات بارگذاری شد.</p></div>';
    });
});

/*
|--------------------------------------------------------------------------
| محتوای تب خروجی و ورودی - نسخه اصلاح شده
|--------------------------------------------------------------------------
*/
function gpa_export_import_tab_content() {
    // نمایش پیام‌های خطا/موفقیت
    if (isset($_GET['import_result'])) {
        $result = sanitize_text_field($_GET['import_result']);
        if ($result === 'success') {
            echo '<div class="notice notice-success is-dismissible"><p>تنظیمات با موفقیت import شدند!</p></div>';
        } elseif ($result === 'error') {
            echo '<div class="notice notice-error is-dismissible"><p>خطا در import تنظیمات!</p></div>';
        }
    }
    ?>
    
    <div class="wrap">
        <h2>خروجی و ورودی تنظیمات</h2>
        <p>از این بخش می‌توانید تنظیمات پلاگین را export کرده یا تنظیمات قبلی را import کنید.</p>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin: 20px 0;">
            
            <!-- بخش Export -->
            <div style="border: 2px dashed #0073aa; padding: 20px; border-radius: 8px; text-align: center;">
                <h3 style="color: #0073aa;">📤 خروجی تنظیمات</h3>
                <p>تنظیمات فعلی را به صورت فایل JSON دریافت کنید</p>
                
                <?php
                $export_url = wp_nonce_url(
                    add_query_arg([
                        'page' => 'gateway-price-adjust-settings',
                        'tab' => 'export_import',
                        'gpa_action' => 'export'
                    ], admin_url('admin.php')),
                    'gpa_export'
                );
                ?>
                
                <a href="<?php echo esc_url($export_url); ?>" class="button button-primary">
                    🗃️ دانلود فایل JSON
                </a>
                
                <div style="margin-top: 15px; font-size: 12px; color: #666;">
                    <p><strong>تنظیمات شامل:</strong></p>
                    <ul style="text-align: right; direction: rtl;">
                        <li>تنظیمات عمومی درگاه‌ها</li>
                        <li>قوانین پیشرفته</li>
                        <li>تنظیمات هوش مصنوعی</li>
                        <li>تنظیمات تلگرام و پیامک</li>
                        <li>تخفیف‌های پلکانی</li>
                    </ul>
                </div>
            </div>
            
            <!-- بخش Import -->
            <div style="border: 2px dashed #46b450; padding: 20px; border-radius: 8px; text-align: center;">
                <h3 style="color: #46b450;">📥 ورودی تنظیمات</h3>
                <p>تنظیمات قبلی را از فایل JSON بارگذاری کنید</p>
                
                <form method="post" action="<?php echo admin_url('options.php'); ?>" enctype="multipart/form-data">
                    <?php settings_fields('gateway_price_adjust_group'); ?>
                    
                    <div style="margin: 15px 0;">
                        <input type="file" name="gpa_import_file" accept=".json,application/json" 
                               style="margin: 10px 0; padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 100%;">
                    </div>
                    
                    <button type="submit" name="gpa_import" class="button button-secondary" 
                            onclick="return confirm('⚠️ با این کار تمام تنظیمات فعلی overwrite خواهند شد. آیا ادامه می‌دهید؟')">
                        📤 بارگذاری و اعمال تنظیمات
                    </button>
                </form>
                
                <div style="margin-top: 15px; font-size: 12px; color: #666;">
                    <p><strong>هشدار:</strong></p>
                    <ul style="text-align: right; direction: rtl;">
                        <li>تنظیمات فعلی پاک خواهند شد</li>
                        <li>فقط فایل‌های export شده از همین پلاگین</li>
                        <li>از تنظیمات فعلی backup بگیرید</li>
                    </ul>
                </div>
            </div>
            
        </div>
        
        <!-- اطلاعات پشتیبان‌گیری -->
        <div style="background: #fff; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3>💾 اطلاعات پشتیبان‌گیری</h3>
            
            <?php
$backup_info = [];

try {
    // هر مقدار را جداگانه محاسبه و بررسی کن
    $global_settings = get_option('gateway_price_adjust_global', []);
    $backup_info['تعداد درگاه‌های تنظیم شده'] = is_array($global_settings) ? count($global_settings) : 0;
    
    $options = get_option('gateway_price_adjust_options', []);
    $rules = isset($options['rules']) && is_array($options['rules']) ? $options['rules'] : [];
    $backup_info['تعداد قوانین پیشرفته'] = count($rules);
    
    $ai_settings = get_option('gpa_ai_settings', []);
    $ai_enabled = isset($ai_settings['enabled']) ? (bool)$ai_settings['enabled'] : false;
    $backup_info['تنظیمات هوش مصنوعی'] = $ai_enabled ? 'فعال' : 'غیرفعال';
    
    $telegram_settings = get_option('gpa_telegram_settings', []);
    $telegram_enabled = isset($telegram_settings['enabled']) ? (bool)$telegram_settings['enabled'] : false;
    $backup_info['اطلاع‌رسانی تلگرام'] = $telegram_enabled ? 'فعال' : 'غیرفعال';
    
    $sms_settings = get_option('gpa_sms_settings', []);
    $sms_enabled = isset($sms_settings['enabled']) ? (bool)$sms_settings['enabled'] : false;
    $backup_info['اطلاع‌رسانی پیامک'] = $sms_enabled ? 'فعال' : 'غیرفعال';
    
    $audit_logs = get_option('gpa_audit_logs', []);
    $backup_info['آخرین تغییرات'] = !empty($audit_logs) ? 'موجود' : 'ندارد';
    
} catch (Exception $e) {
    // اگر خطایی رخ داد، مقادیر پیش‌فرض قرار بده
    $backup_info = [
        'تعداد درگاه‌های تنظیم شده' => 'خطا',
        'تعداد قوانین پیشرفته' => 'خطا',
        'تنظیمات هوش مصنوعی' => 'خطا',
        'اطلاع‌رسانی تلگرام' => 'خطا',
        'اطلاع‌رسانی پیامک' => 'خطا',
        'آخرین تغییرات' => 'خطا'
    ];
    
    error_log('GPA Backup Info Error: ' . $e->getMessage());
}
            ?>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>عنوان</th>
                        <th>وضعیت</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backup_info as $title => $status): ?>
                    <tr>
                        <td><strong><?php echo esc_html($title); ?></strong></td>
                        <td><?php echo esc_html($status); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="notice notice-info">
            <p><strong>🛡️ راهنمای امنیتی:</strong> 
            فایل‌های export شده حاوی اطلاعات حساس هستند. آن‌ها را در مکانی امن نگهداری کرده و به اشتراک نگذارید.</p>
        </div>
    </div>
    
    <style>
    .gpa-export-import-box {
        transition: all 0.3s ease;
    }
    .gpa-export-import-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    </style>
    <?php
}

/*
|--------------------------------------------------------------------------
| افزودن قابلیت Backup خودکار
|--------------------------------------------------------------------------
*/
add_action('gpa_daily_backup', function() {
    $backup_settings = get_option('gpa_backup_settings', []);
    
    if (empty($backup_settings['auto_backup'])) {
        return;
    }
    
    // ایجاد backup
    $backup_data = [
        'version' => GPA_VERSION,
        'backup_date' => date('Y-m-d H:i:s'),
        'global_settings' => get_option('gateway_price_adjust_global', []),
        'options' => get_option('gateway_price_adjust_options', [])
    ];
    
    $backup_dir = WP_CONTENT_DIR . '/gpa-backups/';
    if (!file_exists($backup_dir)) {
        wp_mkdir_p($backup_dir);
    }
    
    $filename = $backup_dir . 'backup-' . date('Y-m-d') . '.json';
    file_put_contents($filename, json_encode($backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // حذف backup های قدیمی (بیش از 7 روز)
    $files = glob($backup_dir . 'backup-*.json');
    $keep_days = $backup_settings['keep_days'] ?? 7;
    
    foreach ($files as $file) {
        if (filemtime($file) < strtotime("-{$keep_days} days")) {
            unlink($file);
        }
    }
});

// زمان‌بندی backup روزانه
add_action('init', function() {
    if (!wp_next_scheduled('gpa_daily_backup')) {
        wp_schedule_event(time(), 'daily', 'gpa_daily_backup');
    }
});