=== BML Connect Gateway for WooCommerce ===
Contributors: hussn2
Tags: woocommerce, payment gateway, bml, bank of maldives, mvr
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept online payments through BML Connect (Bank of Maldives) in WooCommerce. Supports classic and block-based checkout, MVR and USD.

== Description ==

This plugin adds BML Connect (Bank of Maldives hosted payment gateway) as a payment
method in WooCommerce. At checkout the customer is redirected to the secure BML
Connect payment page, and once payment is completed the order is verified
server-side and marked paid.

Features:

* Classic (shortcode) checkout and WooCommerce Cart/Checkout Blocks support.
* MVR and USD currencies.
* Sandbox and production environments with separate credentials.
* Server-side transaction verification on both browser return and webhook callback.
* HPOS (High-Performance Order Storage) compatible.
* Optional debug logging via WooCommerce > Status > Logs.

This plugin is an independent integration and is not affiliated with or endorsed by
Bank of Maldives. "BML Connect" is a product of Bank of Maldives.

== Installation ==

1. Upload the `bml-woocommerce-gateway` folder to the `/wp-content/plugins/` directory, or install the zip via Plugins > Add New > Upload.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to WooCommerce > Settings > Payments > BML Connect.
4. Enable the gateway, enter your API key from the BML Merchant Portal, and save.
5. Keep "Sandbox mode" enabled while testing; disable it to take live payments.

== Frequently Asked Questions ==

= Where do I get my API key? =

From the BML Merchant Portal at https://dashboard.merchants.bankofmaldives.com.mv. The
sandbox and production environments use different API keys.

= Which currencies are supported? =

MVR (Maldivian Rufiyaa) and USD (US Dollar). The gateway hides itself if the store
currency is set to anything else.

= Is the payment processed on my site? =

No. The customer is redirected to BML's hosted payment page. No card details are
entered or stored on your site.

== Changelog ==

= 1.0.0 =
* Initial release.
