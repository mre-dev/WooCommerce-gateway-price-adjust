<?php
/*
|--------------------------------------------------------------------------
| ماژول سیستم لاگ و Audit Trail
|--------------------------------------------------------------------------
*/

if (!defined('ABSPATH')) exit;

/*
|--------------------------------------------------------------------------
| اضافه کردن تب لاگ‌ها
|--------------------------------------------------------------------------
*/
add_filter('gpa_additional_tabs', function($tabs) {
    $tabs['audit_logs'] = 'لاگ و گزارش‌ها';
    return $tabs;
});

/*
|--------------------------------------------------------------------------
| محتوای تب لاگ‌ها
|--------------------------------------------------------------------------
*/
add_action('gpa_settings_tab_content', function($current_tab) {
    if ($current_tab !== 'audit_logs') return;
    
    global $wpdb;
    
    // pagination
    $per_page = 20;
    $current_page = max(1, $_GET['paged'] ?? 1);
    $offset = ($current_page - 1) * $per_page;
    
    // فیلترها
    $action_type = $_GET['action_type'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    
    $where = '1=1';
    $prepare_args = [];
    
    if ($action_type) {
        $where .= " AND action_type = %s";
        $prepare_args[] = $action_type;
    }
    
    if ($date_from) {
        $where .= " AND created_at >= %s";
        $prepare_args[] = $date_from . ' 00:00:00';
    }
    
    if ($date_to) {
        $where .= " AND created_at <= %s";
        $prepare_args[] = $date_to . ' 23:59:59';
    }
    
    // گرفتن لاگ‌ها
    $table_name = $wpdb->prefix . 'gpa_audit_logs';
    
    $sql = "SELECT * FROM $table_name WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
    $prepare_args[] = $per_page;
    $prepare_args[] = $offset;
    
    $logs = $wpdb->get_results($wpdb->prepare($sql, $prepare_args));
    
    // تعداد کل
    $count_sql = "SELECT COUNT(*) FROM $table_name WHERE $where";
    $total_logs = $wpdb->get_var($prepare_args ? $wpdb->prepare($count_sql, array_slice($prepare_args, 0, -2)) : $count_sql);
    $total_pages = ceil($total_logs / $per_page);
    
    // انواع action
    $action_types = $wpdb->get_col("SELECT DISTINCT action_type FROM $table_name ORDER BY action_type");
    ?>
    
    <div class="wrap" style="padding: 10px;">
        <h2>لاگ و گزارش‌های سیستم</h2>
        
        <!-- فیلترها -->
        <div class="gpa-log-filters" style="background: #f9f9f9; padding: 15px; margin: 20px 0; border: 1px solid #ddd;">
            <form method="get">
                <input type="hidden" name="page" value="gateway-price-adjust-settings">
                <input type="hidden" name="tab" value="audit_logs">
                
                <table class="form-table">
                    <tr>
                        <th>نوع action</th>
                        <td>
                            <select name="action_type">
                                <option value="">همه</option>
                                <?php foreach($action_types as $type): ?>
                                    <option value="<?php echo esc_attr($type); ?>" 
                                            <?php selected($action_type, $type); ?>>
                                        <?php echo esc_html($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>از تاریخ</th>
                        <td><input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>"></td>
                    </tr>
                    <tr>
                        <th>تا تاریخ</th>
                        <td><input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>"></td>
                    </tr>
                </table>
                
                <button type="submit" class="button button-primary">اعمال فیلتر</button>
                <a href="?page=gateway-price-adjust-settings&tab=audit_logs" class="button">پاک کردن فیلترها</a>
            </form>
        </div>
        
        <!-- جدول لاگ‌ها -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>نوع Action</th>
                    <th>کاربر</th>
                    <th>IP</th>
                    <th>جزئیات</th>
                    <th>تاریخ</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($logs): ?>
                    <?php foreach($logs as $log): 
                        $details = json_decode($log->details, true);
                        $user = $log->user_id ? get_user_by('id', $log->user_id) : null;
                    ?>
                        <tr>
                            <td><?php echo $log->id; ?></td>
                            <td>
                                <strong><?php echo esc_html($log->action_type); ?></strong>
                            </td>
                            <td>
                                <?php if ($user): ?>
                                    <?php echo esc_html($user->display_name); ?>
                                <?php else: ?>
                                    <em>سیستم</em>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($log->user_ip); ?></td>
                            <td>
                                <?php if ($details): ?>
                                    <button type="button" class="button button-small gpa-view-details" 
                                            data-details='<?php echo esc_attr(json_encode($details, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?>'>
                                        مشاهده جزئیات
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('Y/m/d H:i:s', strtotime($log->created_at)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">هیچ لاگی یافت نشد</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $current_page
                    ]);
                    ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- دکمه خروجی -->
        <div style="margin-top: 20px;">
            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=gpa_export_logs'), 'gpa_export_logs'); ?>" 
               class="button button-primary">
                خروجی Excel
            </a>
            
            <button type="button" id="gpa-clear-logs" class="button button-danger" 
                    style="color: #dc3232; border-color: #dc3232;">
                پاک کردن لاگ‌های قدیمی
            </button>
        </div>
    </div>
    
    <!-- مودال جزئیات -->
    <div id="gpa-details-modal" style="display: none;">
        <div class="gpa-modal-content">
            <pre id="gpa-details-content" style="background: #f6f6f6; padding: 15px; border-radius: 5px; max-height: 400px; overflow: auto;"></pre>
        </div>
    </div>
    
    <style>
    .gpa-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }
    .gpa-modal-content {
        background: white;
        padding: 20px;
        border-radius: 5px;
        max-width: 600px;
        max-height: 80vh;
        overflow: auto;
    }
    </style>
    
    <script>
    jQuery(function($) {
        $('.gpa-view-details').on('click', function() {
            const details = $(this).data('details');
            $('#gpa-details-content').text(details);
            
            $('body').append(
                '<div class="gpa-modal-overlay">' +
                    $('#gpa-details-modal').html() +
                '</div>'
            );
            
            $('.gpa-modal-overlay').on('click', function(e) {
                if (e.target === this) {
                    $(this).remove();
                }
            });
        });
        
        $('#gpa-clear-logs').on('click', function() {
            if (confirm('آیا از پاک کردن لاگ‌های قدیمی تر از 30 روز اطمینان دارید؟')) {
                $.post(ajaxurl, {
                    action: 'gpa_clear_old_logs',
                    nonce: '<?php echo wp_create_nonce('gpa_clear_logs'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('خطا در پاک کردن لاگ‌ها');
                    }
                });
            }
        });
    });
    </script>
    <?php
});

// هندلر پاک کردن لاگ‌های قدیمی
add_action('wp_ajax_gpa_clear_old_logs', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'gpa_clear_logs')) {
        wp_die('Security check failed');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'gpa_audit_logs';
    
    $deleted = $wpdb->query(
        $wpdb->prepare("DELETE FROM $table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)")
    );
    
    gpa_log_action('logs_cleared', ['deleted_count' => $deleted]);
    
    wp_send_json_success([
        'message' => sprintf('%d لاگ قدیمی پاک شد', $deleted)
    ]);
});

// هندلر خروجی Excel
add_action('admin_post_gpa_export_logs', function() {
    if (!wp_verify_nonce($_GET['_wpnonce'], 'gpa_export_logs')) {
        wp_die('Security check failed');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'gpa_audit_logs';
    
    $logs = $wpdb->get_results("
        SELECT * FROM $table_name 
        ORDER BY created_at DESC 
        LIMIT 1000
    ");
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=gpa-logs-' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Action Type', 'User', 'IP', 'Details', 'Date']);
    
    foreach ($logs as $log) {
        $user = $log->user_id ? get_user_by('id', $log->user_id) : null;
        $username = $user ? $user->display_name : 'سیستم';
        
        fputcsv($output, [
            $log->id,
            $log->action_type,
            $username,
            $log->user_ip,
            $log->details,
            $log->created_at
        ]);
    }
    
    fclose($output);
    exit;
});