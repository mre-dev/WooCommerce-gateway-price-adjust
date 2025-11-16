<?php
/*
|--------------------------------------------------------------------------
| مدیریت متاباکس محصولات
|--------------------------------------------------------------------------
*/

if (!defined('ABSPATH')) exit;

/*
|--------------------------------------------------------------------------
| اضافه کردن تب جدید در صفحه محصول
|--------------------------------------------------------------------------
*/
add_filter('woocommerce_product_data_tabs', function($tabs){
    $tabs['gateway_price'] = [
        'label' => __('قیمت بر اساس درگاه', 'gateway-price-adjust'),
        'target' => 'gateway_price_options',
        'class' => ['show_if_simple', 'show_if_variable'],
        'priority' => 21,
    ];
    return $tabs;
});

/*
|--------------------------------------------------------------------------
| نمایش پنل
|--------------------------------------------------------------------------
*/
add_action('woocommerce_product_data_panels', function(){
    global $post;
    $gateways = WC_Payment_Gateways::instance()->get_available_payment_gateways();
    $rules = get_post_meta($post->ID, '_gpa_product_rules', true) ?: [];

    echo '<div id="gateway_price_options" class="panel woocommerce_options_panel">';
    echo '<table class="form-table"><tbody>';

    foreach($gateways as $gateway_id => $gateway){
        $mode  = $rules[$gateway_id]['mode'] ?? 'increase';
        $kind  = $rules[$gateway_id]['kind'] ?? 'percent';
        $value = $rules[$gateway_id]['value'] ?? '';

        echo '<tr valign="top">';
        echo '<th style="padding-right:10px;" scope="row">'.esc_html($gateway->get_title()).'</th>';
        echo '<td style="display:flex; gap:10px; align-items:center;">';
        
        // انتخاب افزایش یا کاهش
        echo '<select name="gpa_product_rules['.$gateway_id.'][mode]" style="width:100px; padding:4px;">';
        echo '<option value="increase" '.selected($mode,'increase',false).'>افزایش</option>';
        echo '<option value="decrease" '.selected($mode,'decrease',false).'>کاهش</option>';
        echo '</select>';

        // نوع مقدار
        echo '<select name="gpa_product_rules['.$gateway_id.'][kind]" style="width:100px; padding:4px;">';
        echo '<option value="percent" '.selected($kind,'percent',false).'>درصد</option>';
        echo '<option value="fixed" '.selected($kind,'fixed',false).'>مبلغ ثابت</option>';
        echo '</select>';

        // مقدار
        echo '<input type="number" step="0.01" min="0" name="gpa_product_rules['.$gateway_id.'][value]" value="'.esc_attr($value).'" style="width:120px; padding:4px;" placeholder="مقدار">';

        echo '</td></tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
});

/*
|--------------------------------------------------------------------------
| ذخیره داده‌ها
|--------------------------------------------------------------------------
*/
add_action('woocommerce_process_product_meta', function($post_id){
    $rules = $_POST['gpa_product_rules'] ?? [];
    update_post_meta($post_id, '_gpa_product_rules', $rules);
});