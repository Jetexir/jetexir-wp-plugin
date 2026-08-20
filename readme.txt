=== Jetexir for WooCommerce ===
Contributors: parselearn
Tags: WooCommerce, Product, Cart, Checkout, Order
Requires at least: 6.7
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Jetexir integrates with WooCommerce to help you further enhance your website.

== Description ==

### Jetexir for WooCommerce ###

Jetexir is the enhancement suite for your WooCommerce store, designed to elevate both customer experience and administrative efficiency.

= Key Features: =

* Sale & Price Tools: Customizable sale badges with percentage discounts, sale progress bars, and flexible variable price variations
* Product Enhancements: Product comparison, wish lists, quantity field controls, and call-for-price functionality
* Customer Engagement: Social sharing buttons, FAQ sections, and related products to keep shoppers on your site
* Streamlined Shopping: Fly Cart and Menu Cart options for convenient access to the shopping cart
* Checkout & Order Management: Customizable checkout fields, custom order statuses, and order number customization
* Branding Tools: Announcement bars and custom currency symbols to personalize your store
* Style Customizer: Global color and style controls to match your brand identity
* WordPress Enhancements: Useful tools to enhance your WordPress site beyond WooCommerce

Jetexir transforms your WooCommerce store into a more engaging, user-friendly shopping destination while giving you powerful tools to manage every aspect of the customer journey.

### Why Choose Jetexir for Your WooCommerce Store? ###

1. Boost Conversions: Our product comparison and wishlist features help customers make purchasing decisions faster, increasing your sales potential.

2. Enhance Professionalism: Custom sale badges, price variations, and social sharing tools create a polished shopping experience that builds customer trust.

3. Streamline Operations: The call-for-price feature and custom order statuses simplify complex product management scenarios.

4. Improve Customer Engagement: FAQ sections and related products keep shoppers on your site longer while providing valuable information.

5. Flexible Customization: From checkout fields to currency symbols, Jetexir puts you in complete control of your store's appearance and functionality.

6. Convenient Shopping Experience: Fly Cart and Menu Cart options make it easier for customers to manage their purchases without leaving the product page.

7. Brand Consistency Tools: The announcement bar and customizable elements help maintain a cohesive brand identity across your entire store.

### Develop version:

Jetexir is open source project, you can find source in [GitHub](https://github.com/Jetexir/jetexir-wp-plugin)

== External services ==

This plugin connects to external services only when you enable the add-ons that require them. When enabled, the following services are used:

### WooCommerce Developer Blog feed (Woo Developer Feed add-on)

The **Woo Developer Feed** add-on fetches the latest posts from the official WooCommerce Developer Blog to display them in the add-on feed widget.

When this add-on is enabled, the plugin sends a standard HTTP request to the feed URL ([https://developer.woocommerce.com/feed/](https://developer.woocommerce.com/feed/)) to retrieve the blog posts. No personal data is sent; only the standard request headers that a web browser or server sends with any HTTP request (such as IP address and user agent) are transmitted. The feed is fetched in the background, cached locally on your site for up to one day, and is only requested again after the cached data expires.

This service is provided by WooCommerce (Automattic Inc.):
- Terms of service: [https://woocommerce.com/terms-conditions/](https://woocommerce.com/terms-conditions/)
- Privacy policy: [https://woocommerce.com/privacy-policy/](https://woocommerce.com/privacy-policy/)

### WhatsApp (Product Social Share add-on)

The **Product Social Share** add-on includes a "Share on WhatsApp" button. When a visitor clicks the button, their browser opens a WhatsApp share URL ([https://api.whatsapp.com/send](https://api.whatsapp.com/send)) to share the current product page link. The plugin itself does not send any data to WhatsApp; the share is initiated by the visitor in their own browser.

This service is provided by Meta Platforms, Inc.:
- Terms of service: [https://www.whatsapp.com/legal/terms-of-service](https://www.whatsapp.com/legal/terms-of-service)
- Privacy policy: [https://www.whatsapp.com/legal/privacy-policy](https://www.whatsapp.com/legal/privacy-policy)

== Installation ==

= Minimum Requirements =

Jetexir like WooCommerce has minimum requirements

* PHP 7.4 or greater is required (PHP 8.0 or greater is recommended)
* MySQL 5.5.5 or greater, OR MariaDB version 10.1 or greater, is required
* WordPress 6.7 or greater

= Automatic installation =

Automatic installation is the easiest option — WordPress will handle the file transfer, and you won’t need to leave your web browser. To do an automatic install of Jetexir, log in to your WordPress dashboard, navigate to the Plugins menu, and click “Add New.”

In the search field type “Jetexir,” then click “Search Plugins.” Once you’ve found us, you can view details about it such as the point release, rating, and description. Most importantly of course, you can install it by! Click “Install Now,” and WordPress will take it from there.

= Manual installation =

Manual installation method requires downloading the WooCommerce plugin and uploading it to your web server via your favorite FTP application. The WordPress codex contains [instructions on how to do this here](https://wordpress.org/support/article/managing-plugins/#manual-plugin-installation).

= Updating =

Automatic updates should work smoothly, but we still recommend you back up your site.

== Screenshots ==

1. Dashboard tab
2. Product general settings (Sale Badge, Price Variation)
3. Product Compare
4. Product Quantity
5. Product Wishlist
6. Product Social Share
7. Product FAQ
8. Product Related
9. Product Call for Price
10. Product Sale Progress Bar
20. Fly Cart
21. Menu Cart
30. Checkout Fields
40. Order Custom Statuses
41. Order Number
50. Announcement Bar
51. Currency Symbol
60. WordPress enhance tools
70. Addons page

== Changelog ==
### 1.0.1 - 2026-08-15
- **Fixed:** Output escaping issues in settings fields, admin templates, data table rendering, and the announcement bar.
- **Improved:** Added PHP DocBlocks for hooks, filters, actions, and settings across the codebase.
- **Improved:** Added type casting (array/bool/int/string) to filter return values to prevent display/type errors.
- **Added:** New plugin install/update routine that seeds default settings and tracks the plugin version.
- **Added:** Configurable Product FAQ tab title, FAQ items filter, and FAQ tab priority filter.
- **Added:** "More info" links for all add-ons now point to the Jetexir website (https://jetexir.ir).
- **Added:** Additional SVG attributes (`stroke`, `stroke-width`, `style`) to the media sanitizer.

### 1.0 - 2026-08-10
- **Initial release**
- Release Jetexir V1.0 with 17 add-ons and basic settings.
- Product Add-ons: Sale Badge, Price Variation, Compare, Quantity, WishList, Social Share, FAQ, Products, Call for price
- Cart Add-ons: Fly Cart, Menu Cart
- Checkout Add-ons: Checkout Fields
- Order Add-ons: Order Status, Order Number
- Customizations Add-ons: Announcement Bar, Currency Symbol
- Other Add-ons: Woo Developer Feed
