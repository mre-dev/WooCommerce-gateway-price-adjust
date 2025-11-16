jQuery(function($){
    // مدیریت تب‌ها - برای آینده قابل توسعه است
    console.log('GPA Tabs loaded successfully');
    
    // اضافه کردن استایل‌های لازم
    $('head').append(`
        <style>
            .gpa-rule-card, .gpa-tier-rule, .gpa-inventory-rule, .gpa-competitor {
                transition: all 0.3s ease;
            }
            .gpa-rule-card:hover, .gpa-tier-rule:hover, .gpa-inventory-rule:hover, .gpa-competitor:hover {
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            }
            .button-remove-rule, .button-remove-inventory-rule, .gpa-remove-competitor {
                cursor: pointer;
            }
        </style>
    `);
});