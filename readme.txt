=== Gateway Price Adjust for WooCommerce ===
Contributors: mohammadrezaebrahimi
Donate link: https://mre01.ir
Tags: woocommerce, payment gateway, dynamic pricing, discount, fee
Requires at least: 5.8
Tested up to: 6.6
Stable tag: 1.3
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily adjust WooCommerce product prices based on selected payment gateways — increase or decrease price by percentage or fixed amount.

== Description ==

**Gateway Price Adjust** lets you set custom price changes for each payment gateway in WooCommerce.  
You can define adjustments **per product** or **globally**, either as a **fixed amount** or a **percentage** — and choose whether to **increase** or **decrease** prices.

**Main Features:**
- Adjust product price based on selected payment gateway  
- Set **increase or decrease** type (fixed or percentage)  
- Works with **all active gateways** automatically  
- Live price updates on checkout (AJAX)  
- Simple UI inside each product edit page  
- Compatible with WooCommerce 5+ and WordPress 6+

== Installation ==

1. Upload `gateway-price-adjust.zip` to the `/wp-content/plugins/` directory  
2. Activate the plugin through the 'Plugins' menu in WordPress  
3. Go to WooCommerce → Settings → Payment Gateway Adjustments  
4. Set your preferred adjustment type and amount per gateway  
5. Optionally, override per-product settings from the product edit page  

== Frequently Asked Questions ==

= Can I both increase and decrease prices? =
Yes! You can select either increase or decrease for each gateway, and choose between percentage or fixed amount.

= Does it support all payment gateways? =
It automatically detects all **active** gateways registered in WooCommerce.

= Is the adjustment applied before or after taxes? =
By default, adjustments apply to the subtotal (before tax). You can modify this in the source if needed.

== Screenshots ==

1. Settings page in WooCommerce admin  
2. Product edit page with gateway adjustment options  
3. Live price change on checkout page  

== Changelog ==

= 1.3 =
* Added support for both price increase and decrease
* Added fixed and percentage modes
* Improved admin UI and settings handling
* Minor code optimization and bug fixes

= 1.2 =
* Added per-product gateway settings
* Added global settings page

= 1.0 =
* Initial release – basic percentage adjustment by gateway

== Upgrade Notice ==
Upgrade to v1.3 for full control over increasing or decreasing prices per gateway.
