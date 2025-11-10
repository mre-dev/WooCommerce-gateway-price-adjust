jQuery(function($){
    // وقتی یکی از input های درگاه تغییر کرد، درخواست update_checkout اجرا شود.
    $('form.checkout').on('change', 'input[name="payment_method"]', function(){
        $(document.body).trigger('update_checkout');
    });

    // listener برای لیبل‌ها (برخی تم‌ها)
    $('form.checkout').on('click', '.payment_method_label', function(){
        setTimeout(function(){
            $(document.body).trigger('update_checkout');
        }, 100);
    });

    $(document.body).on('updated_checkout', function(){
        // placeholder برای توسعه بعدی
    });
});
