jQuery(function($){
    let currentPaymentMethod = '';
    let updateInProgress = false;

    function updatePaymentMethod() {
        if (updateInProgress) return;
        
        const selectedMethod = $('input[name="payment_method"]:checked').val();
        
        if (selectedMethod && selectedMethod !== currentPaymentMethod) {
            currentPaymentMethod = selectedMethod;
            console.log('GPA: Payment method changed to:', selectedMethod);
            
            updateInProgress = true;
            
            // ارسال درخواست AJAX برای آپدیت session
            $.ajax({
                url: gpa_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gpa_update_payment_method',
                    payment_method: selectedMethod,
                    nonce: gpa_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $(document.body).trigger('update_checkout');
                    }
                },
                complete: function() {
                    setTimeout(function() {
                        updateInProgress = false;
                    }, 500);
                }
            });
        }
    }

    // وقتی یکی از input های درگاه تغییر کرد
    $('form.checkout').on('change', 'input[name="payment_method"]', function(){
        updatePaymentMethod();
    });

    // listener برای لیبل‌ها (برخی تم‌ها)
    $('form.checkout').on('click', '.payment_methods li, .payment_method_label', function(){
        setTimeout(updatePaymentMethod, 100);
    });

    // بررسی اولیه وقتی صفحه لود شد
    $(document).ready(function(){
        setTimeout(updatePaymentMethod, 500);
    });

    // وقتی چک‌اوت آپدیت شد، مجدداً وضعیت را بررسی کن
    $(document.body).on('updated_checkout', function(){
        updateInProgress = false;
    });
});