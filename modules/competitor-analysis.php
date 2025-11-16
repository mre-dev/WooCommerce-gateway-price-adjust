<?php
/*
|--------------------------------------------------------------------------
| ماژول آنالیز رقبا
|--------------------------------------------------------------------------
*/

if (!defined('ABSPATH')) exit;

/*
|--------------------------------------------------------------------------
| اضافه کردن تب آنالیز رقبا
|--------------------------------------------------------------------------
*/
add_filter('gpa_additional_tabs', function($tabs) {
    $tabs['competitor_analysis'] = 'آنالیز رقبا';
    return $tabs;
});

/*
|--------------------------------------------------------------------------
| محتوای تب آنالیز رقبا
|--------------------------------------------------------------------------
*/
add_action('gpa_settings_tab_content', function($current_tab) {
    if ($current_tab !== 'competitor_analysis') return;
    
    $competitor_settings = get_option('gpa_competitor_settings', [
        'enabled' => false,
        'competitors' => [],
        'update_frequency' => 'weekly'
    ]);
    
    $analysis_data = get_transient('gpa_competitor_analysis');
    
    // ایجاد nonce برای امنیت
    $analysis_nonce = wp_create_nonce('gpa_run_analysis');
    ?>
    
    <div class="wrap" style="padding: 10px;">
        <h2>آنالیز رقبا و مقایسه کارمزد</h2>
        
        <form method="post" action="options.php">
            <?php settings_fields('gateway_price_adjust_group'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">فعال‌سازی آنالیز رقبا</th>
                    <td>
                        <label>
                            <input type="checkbox" name="gpa_competitor_settings[enabled]" value="1" 
                                   <?php checked($competitor_settings['enabled'] ?? false); ?>>
                            جمع‌آوری و تحلیل داده‌های رقبا
                        </label>
                        <p class="description">با فعال کردن این گزینه، سیستم به صورت دوره‌ای داده‌های رقبا را تحلیل می‌کند</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">فرکانس بروزرسانی</th>
                    <td>
                        <select name="gpa_competitor_settings[update_frequency]">
                            <option value="daily" <?php selected($competitor_settings['update_frequency'] ?? 'weekly', 'daily'); ?>>روزانه</option>
                            <option value="weekly" <?php selected($competitor_settings['update_frequency'] ?? 'weekly', 'weekly'); ?>>هفتگی</option>
                            <option value="monthly" <?php selected($competitor_settings['update_frequency'] ?? 'weekly', 'monthly'); ?>>ماهانه</option>
                        </select>
                        <p class="description">فرکانس به‌روزرسانی خودکار داده‌های رقبا</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">لیست رقبا</th>
                    <td>
                        <div id="gpa-competitors-list">
                            <?php 
                            $competitors = $competitor_settings['competitors'] ?? [];
                            if (!empty($competitors)): 
                                foreach($competitors as $index => $competitor): 
                            ?>
                            <div class="gpa-competitor" style="margin-bottom: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9;">
                                <input type="text" name="gpa_competitor_settings[competitors][<?php echo $index; ?>][name]" 
                                       value="<?php echo esc_attr($competitor['name'] ?? ''); ?>" 
                                       placeholder="نام رقیب" style="margin-bottom: 5px; width: 200px;">
                                <input type="url" name="gpa_competitor_settings[competitors][<?php echo $index; ?>][url]" 
                                       value="<?php echo esc_attr($competitor['url'] ?? ''); ?>" 
                                       placeholder="آدرس وبسایت" style="width: 300px;">
                                <button type="button" class="button button-small gpa-remove-competitor" style="color: #dc3232;">
                                    🗑️ حذف
                                </button>
                            </div>
                            <?php 
                                endforeach;
                            else:
                            ?>
                            <div class="notice notice-info">
                                <p style="color: black;">هنوز رقیبی اضافه نشده است. برای شروع روی دکمه "افزودن رقیب جدید" کلیک کنید.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="button" id="gpa-add-competitor" class="button button-secondary" style="margin-top: 10px;">
                            ➕ افزودن رقیب جدید
                        </button>
                        <p class="description">وبسایت‌های رقبا برای تحلیل و مقایسه کارمزدها</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('💾 ذخیره تنظیمات آنالیز', 'primary', 'submit', true); ?>
        </form>
        
        <hr style="margin: 30px 0;">
        
        <!-- گزارش آنالیز -->
        <div style="margin-top: 40px;">
            <h3>📊 گزارش مقایسه کارمزد و خدمات</h3>
            
            <?php if ($analysis_data && !empty($analysis_data['fee_comparison'])): ?>
                <div class="gpa-comparison-charts">
                    <!-- نمودار مقایسه کارمزد -->
                    <div style="margin: 30px 0; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <h4>📈 مقایسه کارمزد درگاه‌ها</h4>
                        <canvas id="gpa-fee-comparison-chart" width="400" height="200" style="max-width: 100%;"></canvas>
                    </div>
                    
                    <!-- جدول مقایسه -->
                    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <h4>📋 جدول مقایسه کارمزد</h4>
                        <table class="wp-list-table widefat fixed striped" style="margin-top: 15px;">
                            <thead>
                                <tr>
                                    <th>درگاه پرداخت</th>
                                    <th>کارمزد ما</th>
                                    <th>میانگین کارمزد بازار</th>
                                    <th>تفاوت</th>
                                    <th>وضعیت</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($analysis_data['fee_comparison'] as $comparison): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($comparison['gateway']); ?></strong></td>
                                    <td><?php echo number_format($comparison['our_fee'], 2); ?>%</td>
                                    <td><?php echo number_format($comparison['market_avg'], 2); ?>%</td>
                                    <td>
                                        <?php 
                                        $diff = $comparison['difference'] ?? ($comparison['our_fee'] - $comparison['market_avg']);
                                        $color = $diff < 0 ? '#46b450' : ($diff > 0 ? '#dc3232' : '#ffb900');
                                        ?>
                                        <span style="color: <?php echo $color; ?>; font-weight: bold;">
                                            <?php echo $diff > 0 ? '+' : ''; ?><?php echo number_format($diff, 2); ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($diff < -0.1): ?>
                                            <span style="color: #46b450;">✅ بهتر از بازار</span>
                                        <?php elseif ($diff > 0.1): ?>
                                            <span style="color: #dc3232;">❌ بالاتر از بازار</span>
                                        <?php else: ?>
                                            <span style="color: #ffb900;">⚡ هم‌سطح بازار</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- پیشنهادات بهینه‌سازی -->
                    <div style="background: #fff8e5; padding: 20px; border-radius: 8px; margin: 20px 0; border-right: 4px solid #ffb900;">
                        <h4>💡 پیشنهادات بهینه‌سازی</h4>
                        <ul style="list-style-type: disc; margin-right: 20px;">
                            <?php foreach($analysis_data['optimization_suggestions'] as $suggestion): ?>
                                <li style="margin-bottom: 8px;"><?php echo esc_html($suggestion); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <div style="margin-top: 15px; padding: 10px; background: #fff; border-radius: 5px;">
                            <small>🕒 آخرین به‌روزرسانی: <?php echo date_i18n('j F Y H:i', strtotime($analysis_data['last_updated'])); ?></small>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center; margin: 20px 0;">
                    <button type="button" id="gpa-run-analysis" class="button button-primary">
                        🔄 اجرای تحلیل جدید
                    </button>
                </div>
                
            <?php else: ?>
                <div style="background: #f0f9ff; padding: 30px; text-align: center; border-radius: 8px; border: 1px solid #0ea5e9;">
                    <h4 style="color: #0369a1; margin-top: 0;">📊 اولین تحلیل را اجرا کنید</h4>
                    <p style="color: #0c4a6e; margin-bottom: 20px;">برای مشاهده گزارش مقایسه کارمزد و دریافت پیشنهادات بهینه‌سازی، تحلیل رقبا را اجرا کنید.</p>
                    
                    <button type="button" id="gpa-run-analysis" class="button button-primary button-large">
                        🚀 اجرای تحلیل رقبا
                    </button>
                    
                    <div id="gpa-analysis-result" style="margin-top: 20px;"></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script type="text/template" id="gpa-competitor-template">
        <div class="gpa-competitor" style="margin-bottom: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9;">
            <input type="text" name="gpa_competitor_settings[competitors][{{index}}][name]" 
                   placeholder="نام رقیب" style="margin-bottom: 5px; width: 200px;">
            <input type="url" name="gpa_competitor_settings[competitors][{{index}}][url]" 
                   placeholder="آدرس وبسایت" style="width: 300px;">
            <button type="button" class="button button-small gpa-remove-competitor" style="color: #dc3232;">
                🗑️ حذف
            </button>
        </div>
    </script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('GPA Competitor Analysis loaded');
        
        // مقداردهی اولیه
        let competitorIndex = <?php echo count($competitor_settings['competitors'] ?? []); ?>;
        console.log('Initial competitorIndex:', competitorIndex);
        
        // دکمه افزودن رقیب
        const addCompetitorBtn = document.getElementById('gpa-add-competitor');
        if (addCompetitorBtn) {
            addCompetitorBtn.addEventListener('click', function() {
                console.log('Add competitor button clicked');
                
                // مخفی کردن پیام "هیچ رقیبی وجود ندارد"
                const notice = document.querySelector('.notice-info');
                if (notice) {
                    notice.style.display = 'none';
                }
                
                const template = document.getElementById('gpa-competitor-template').innerHTML.replace(/{{index}}/g, competitorIndex);
                const competitorsList = document.getElementById('gpa-competitors-list');
                competitorsList.insertAdjacentHTML('beforeend', template);
                competitorIndex++;
                
                console.log('New competitor added, index:', competitorIndex);
            });
        }
        
        // Event Delegation برای حذف رقیب
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('gpa-remove-competitor')) {
                if (confirm('آیا از حذف این رقیب اطمینان دارید؟')) {
                    const competitorElement = e.target.closest('.gpa-competitor');
                    competitorElement.remove();
                    console.log('Competitor removed');
                    
                    // اگر همه رقبا حذف شدند، پیام را نشان بده
                    const remainingCompetitors = document.querySelectorAll('.gpa-competitor');
                    if (remainingCompetitors.length === 0) {
                        const competitorsList = document.getElementById('gpa-competitors-list');
                        competitorsList.innerHTML = '<div class="notice notice-info"><p>هنوز رقیبی اضافه نشده است. برای شروع روی دکمه "افزودن رقیب جدید" کلیک کنید.</p></div>';
                        competitorIndex = 0;
                    }
                }
            }
        });
        
        // اجرای تحلیل
        const runAnalysisBtn = document.getElementById('gpa-run-analysis');
        if (runAnalysisBtn) {
            runAnalysisBtn.addEventListener('click', function() {
                const button = this;
                const resultDiv = document.getElementById('gpa-analysis-result');
                
                button.disabled = true;
                button.innerHTML = '⏳ در حال تحلیل...';
                
                if (resultDiv) {
                    resultDiv.innerHTML = '<div style="color: #666; text-align: center; padding: 10px;">در حال جمع‌آوری و تحلیل داده‌های رقبا...</div>';
                }
                
                // درخواست AJAX
                const formData = new FormData();
                formData.append('action', 'gpa_run_competitor_analysis');
                formData.append('nonce', '<?php echo $analysis_nonce; ?>');
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    button.disabled = false;
                    button.innerHTML = '🔄 اجرای تحلیل جدید';
                    
                    if (data.success) {
                        if (resultDiv) {
                            resultDiv.innerHTML = '<div style="color: #46b450; text-align: center; padding: 10px;">✅ تحلیل با موفقیت انجام شد. صفحه در حال به‌روزرسانی است...</div>';
                        }
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        if (resultDiv) {
                            resultDiv.innerHTML = '<div style="color: #dc3232; text-align: center; padding: 10px;">❌ خطا: ' + (data.data?.message || 'خطای ناشناخته') + '</div>';
                        }
                    }
                })
                .catch(error => {
                    button.disabled = false;
                    button.innerHTML = '🔄 اجرای تحلیل جدید';
                    if (resultDiv) {
                        resultDiv.innerHTML = '<div style="color: #dc3232; text-align: center; padding: 10px;">❌ خطای شبکه: ' + error.message + '</div>';
                    }
                });
            });
        }
        
        // نمودار مقایسه کارمزد
        <?php if ($analysis_data && !empty($analysis_data['fee_comparison'])): ?>
        const feeChartCanvas = document.getElementById('gpa-fee-comparison-chart');
        if (feeChartCanvas) {
            const feeCtx = feeChartCanvas.getContext('2d');
            
            // داده‌های نمودار
            const labels = <?php echo json_encode(array_column($analysis_data['fee_comparison'], 'gateway')); ?>;
            const ourFees = <?php echo json_encode(array_column($analysis_data['fee_comparison'], 'our_fee')); ?>;
            const marketFees = <?php echo json_encode(array_column($analysis_data['fee_comparison'], 'market_avg')); ?>;
            
            new Chart(feeCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'کارمزد ما',
                            data: ourFees,
                            backgroundColor: 'rgba(54, 162, 235, 0.8)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'میانگین بازار',
                            data: marketFees,
                            backgroundColor: 'rgba(255, 99, 132, 0.8)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'مقایسه کارمزد درگاه‌های پرداخت'
                        },
                        legend: {
                            display: true,
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'کارمزد (%)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'درگاه‌های پرداخت'
                            }
                        }
                    }
                }
            });
            
            console.log('Fee comparison chart initialized');
        }
        <?php endif; ?>
        
        console.log('GPA Competitor Analysis initialized successfully');
    });
    </script>
    
    <style>
    .gpa-competitor {
        transition: all 0.3s ease;
    }
    .gpa-competitor:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    #gpa-run-analysis {
        transition: all 0.3s ease;
    }
    #gpa-run-analysis:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    </style>
    <?php
});

// ذخیره تنظیمات آنالیز رقبا
add_action('admin_init', function() {
    register_setting('gateway_price_adjust_group', 'gpa_competitor_settings', [
        'sanitize_callback' => 'gpa_sanitize_competitor_settings'
    ]);
});

/*
|--------------------------------------------------------------------------
| تابع سانیتیز تنظیمات آنالیز رقبا
|--------------------------------------------------------------------------
*/
function gpa_sanitize_competitor_settings($input) {
    $sanitized = [
        'enabled' => !empty($input['enabled']),
        'update_frequency' => sanitize_text_field($input['update_frequency'] ?? 'weekly'),
        'competitors' => []
    ];
    
    // سانیتیز کردن لیست رقبا
    if (!empty($input['competitors']) && is_array($input['competitors'])) {
        foreach ($input['competitors'] as $index => $competitor) {
            if (!empty($competitor['name']) && !empty($competitor['url'])) {
                $sanitized['competitors'][] = [
                    'name' => sanitize_text_field($competitor['name']),
                    'url' => esc_url_raw($competitor['url'])
                ];
            }
        }
    }
    
    // ثبت در لاگ
    gpa_log_action('competitor_settings_updated', [
        'enabled' => $sanitized['enabled'],
        'competitors_count' => count($sanitized['competitors']),
        'user_id' => get_current_user_id()
    ]);
    
    return $sanitized;
}

/*
|--------------------------------------------------------------------------
| تابع تحلیل رقبا
|--------------------------------------------------------------------------
*/
function gpa_run_competitor_analysis() {
    // بررسی وجود ووکامرس
    if (!class_exists('WC_Payment_Gateways')) {
        throw new Exception('ووکامرس فعال نیست');
    }
    
    $gateways = WC()->payment_gateways->get_available_payment_gateways();
    $analysis_data = [
        'fee_comparison' => [],
        'optimization_suggestions' => [],
        'last_updated' => current_time('mysql')
    ];
    
    // کارمزد استاندارد بازار (داده‌های نمونه - در حالت واقعی از API استفاده شود)
    $market_fees = [
        'zarinpal' => 2.5,
        'parsian' => 3.0,
        'saman' => 2.8,
        'mellat' => 2.9,
        'sadad' => 2.7,
        'payir' => 2.0,
        'idpay' => 1.5,
        'nextpay' => 2.2,
        'sepordeh' => 2.6
    ];
    
    // مقایسه کارمزد
    foreach ($gateways as $gateway_id => $gateway) {
        $our_fee = 2.5; // کارمزد پیش‌فرض ما - در حالت واقعی از تنظیمات خوانده شود
        $market_avg = $market_fees[$gateway_id] ?? 2.5;
        $difference = $our_fee - $market_avg;
        
        $analysis_data['fee_comparison'][] = [
            'gateway' => $gateway->get_title(),
            'gateway_id' => $gateway_id,
            'our_fee' => $our_fee,
            'market_avg' => $market_avg,
            'difference' => $difference
        ];
    }
    
    // تولید پیشنهادات بهینه‌سازی
    foreach ($analysis_data['fee_comparison'] as $comparison) {
        if ($comparison['difference'] > 0.5) {
            $analysis_data['optimization_suggestions'][] = 
                "کارمزد {$comparison['gateway']} ({$comparison['our_fee']}%) بالاتر از میانگین بازار ({$comparison['market_avg']}%) است. کاهش کارمزد را بررسی کنید.";
        } elseif ($comparison['difference'] < -0.5) {
            $analysis_data['optimization_suggestions'][] = 
                "کارمزد {$comparison['gateway']} ({$comparison['our_fee']}%) پایین‌تر از بازار ({$comparison['market_avg']}%) است. این یک مزیت رقابتی خوبی است.";
        } else {
            $analysis_data['optimization_suggestions'][] = 
                "کارمزد {$comparison['gateway']} در سطح بازار است. وضعیت مطلوبی دارید.";
        }
    }
    
    // پیشنهادات عمومی
    $analysis_data['optimization_suggestions'][] = 
        "افزایش تبلیغات برای درگاه‌هایی که کارمزد پایین‌تری دارند";
    $analysis_data['optimization_suggestions'][] = 
        "بررسی امکان مذاکره برای کاهش کارمزد درگاه‌های پراستفاده";
    $analysis_data['optimization_suggestions'][] = 
        "آنالیز رقبا به صورت ماهانه برای حفظ مزیت رقابتی";
    
    // ذخیره نتایج
    set_transient('gpa_competitor_analysis', $analysis_data, WEEK_IN_SECONDS);
    
    // ثبت در لاگ
    gpa_log_action('competitor_analysis_completed', [
        'gateways_analyzed' => count($gateways),
        'suggestions_generated' => count($analysis_data['optimization_suggestions']),
        'timestamp' => current_time('mysql')
    ]);
    
    return $analysis_data;
}

/*
|--------------------------------------------------------------------------
| هندلر AJAX برای اجرای تحلیل
|--------------------------------------------------------------------------
*/
add_action('wp_ajax_gpa_run_competitor_analysis', function() {
    // بررسی nonce برای امنیت
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gpa_run_analysis')) {
        wp_send_json_error(['message' => 'خطای امنیتی: Nonce نامعتبر']);
    }
    
    // بررسی اجازه دسترسی
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'شما مجوز اجرای این عملیات را ندارید']);
    }
    
    try {
        $analysis = gpa_run_competitor_analysis();
        wp_send_json_success($analysis);
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
});

/*
|--------------------------------------------------------------------------
| زمان‌بندی تحلیل خودکار
|--------------------------------------------------------------------------
*/
add_action('gpa_scheduled_competitor_analysis', function() {
    $settings = get_option('gpa_competitor_settings', []);
    
    if (!empty($settings['enabled'])) {
        try {
            gpa_run_competitor_analysis();
            gpa_log_action('scheduled_analysis_completed', [
                'timestamp' => current_time('mysql'),
                'frequency' => $settings['update_frequency'] ?? 'weekly'
            ]);
        } catch (Exception $e) {
            gpa_log_action('scheduled_analysis_failed', [
                'error' => $e->getMessage(),
                'timestamp' => current_time('mysql')
            ]);
        }
    }
});

// برنامه‌ریزی کرون وردپرس
add_action('init', function() {
    $settings = get_option('gpa_competitor_settings', []);
    
    if (!empty($settings['enabled'])) {
        $frequency = $settings['update_frequency'] ?? 'weekly';
        
        // حذف هوک‌های قبلی
        wp_clear_scheduled_hook('gpa_scheduled_competitor_analysis');
        
        // برنامه‌ریزی جدید
        if (!wp_next_scheduled('gpa_scheduled_competitor_analysis')) {
            switch ($frequency) {
                case 'daily':
                    wp_schedule_event(time(), 'daily', 'gpa_scheduled_competitor_analysis');
                    break;
                case 'monthly':
                    wp_schedule_event(time(), 'monthly', 'gpa_scheduled_competitor_analysis');
                    break;
                case 'weekly':
                default:
                    wp_schedule_event(time(), 'weekly', 'gpa_scheduled_competitor_analysis');
                    break;
            }
        }
    } else {
        // غیرفعال کردن برنامه‌ریزی اگر آنالیز غیرفعال است
        wp_clear_scheduled_hook('gpa_scheduled_competitor_analysis');
    }
});