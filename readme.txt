=== Parsi Date ===
Contributors: lord_viper, man4toman, parselearn, alirdn, saeedfard, iehsanir
Donate link: https://wp-parsi.com/support/
Tags: shamsi, wp-parsi, wpparsi, persian, parsi, farsi, jalali, date, calendar, i18n, l10n, Iran, Iranian, parsidate, rtl, gutenberg
Requires at least: 5.3
Tested up to: 5.3
Stable tag: 3.0.3

Persian date support for WordPress

== Description ==

This package is made by Persian developers to bring so much better experience of Persian WordPress. It includes Shamsi (Jalali) calendar, character issues fixes and Right-To-Left fix for WordPress back-end environment.

List of some features:

* Shamsi (Jalali) day-picker in Block Editor (Gutenberg)
* Shamsi (Jalali) jQuery UI date-picker
* [WP-Planet.ir](http://wp-planet.ir) Widget
* Shamsi (Jalali) date in Posts, comments, pages, archives, search, categories
* Shamsi (Jalali) date in Permalinks
* Shamsi (Jalali) date in admin sections such as posts lists, comments lists, pages lists
* Shamsi (Jalali) date in post quick edit, comment quick edit, page quick edit of admin panel
* Shamsi (Jalali) calender widget
* Shamsi (Jalali) archive widget
* RTL and fixed tinymce editor
* Powerful and fast function for fixing Arabic (ي , ك) to Persian (ی , ک)
* Powerful and fast function for Persian numbers
* Low resources usage

== Installation ==

1. Upload plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. To use the archives widget, go to 'Widgets' and choose 'بایگانی تاریخ خورشیدی'
4. To use the calender widget, go to 'Widgets' and choose 'گاه‌شمار تاریخ خورشیدی'

== Screenshots ==

1. Plugin configuration page
2. 'Jalali Date Archives' Widget
3. 'Jalali Date Calender' Widget
4. 'Jalali Date Calender' in action

== Changelog ==

= 3.0.3 =
* Fix widgets fatal errors
* Fix get_post_modified_time output
* Replace @ with persian character in edit posts
* Fix notice in disable section

= 3.0.2 =
* Now we support WordPress 5.3!
* Replace/add wp_date instead old function
* Small fix in Woocommerce dates
* Fix date in media files
* All core codes cleaned and beautified

= 3.0.1 =
* The Woocommerce hook has been temporarily disabled

= 3.0.0 =
* Added Shamsi (Jalali) day-picker in Block Editor (Gutenberg)
* Added Shamsi (Jalali) jQuery UI date-picker

= 2.4 =
* Fix for admin_posts_where
* Added new version of planet on dashboard
* New fixes for dates
* Fix month in permalink
* New fix for hook disabler

= 2.3.4 =
* Fix diagnosis leap year function ( bn_parsidate::IsLeapYear() )

= 2.3.3 =
* Fix WooCommerce Sale Price Dates (From/To)

= 2.3.2 =
* Change date based on WordPress language (Persian/Farsi)
* Compatible with WP Multilingual plugin (WPML)

= Development Log
* Fix Wrong usage of $wp_query in posts_where filter that create "old posts" issue in WP_Query!

= 2.3.1 =
* Fix date picker's month dropdown bug in admin edit post. [#issue](https://github.com/wordpress-parsi/wp-parsidate/issues/5)

= 2.3.0.2 =
* Fix set editor font css

= 2.3 =
* Add [WP-Planet.ir](http://wp-planet.ir) widget
* Fix some bug

= 2.2.3 =
* Conflict timezone with wordpress default timezone [#issue](https://github.com/wordpress-parsi/wp-parsidate/issues/1)

= 2.2.2 =
* Fix error in PHP 7

= 2.2.1 =
* Compatible with WP 4.7
* Fixed: Notice error in acf group page. [#issue](https://wordpress.org/support/topic/need-a-conditional-for-posts-in-wpp_fix_post_date-function/)
* Fixed: Undefined variable `$predate` error in admin lists-fix.php [#issue](https://wordpress.org/support/topic/undefined-variable-predate-in-admin-lists-fix-php/)
* Fixed by: [Mostafa Soufi](https://profiles.wordpress.org/mostafas1990/)

= 2.2 =
* Fixed: Widgets bug causes Deprecated notices in WordPress >= 4.3
* Fixed: the_modified_date() is now in Shamsi. [Reported by Amirhossein Habibi]
* New: Added EDD support to convert prices digits in Persian digits.

= 2.1.7 =
* Fixed timezone bug [Reported by HANNANStd]
* Paragraph style returned to its previous style [Reported by WP-Parsi community]

= 2.1.6 =
* Fixed assets folder issue with community.

= 2.1.5 =
* Added "Droid Sans" & "Roboto" font family to back-end environment & editor by default, also an option for returning that
* Added an option for moving menu item to submenu
* Fixed timezone bug that was set to "Asia/Tehran" by default
* Cleaned codes and documentation

= 2.1.2 =
* Admin menu problem fixed

= 2.1.2 =
* Fix Broken Plugins Update Link (Farsi Locale)

= 2.1.1 =
* Fix Post permalink with custom structure (%category%/%postname%/)

= 2.1 =
* Post Permalink Fixed
* WordPress SEO OpenGraph Dates fixed
* WooCommerce order detail date fixed
* New option for set locale in plugin page settings
* LTR post editor text mode

= 2.0.0-alpha =
* Fully recorded!
* WordPress languages (Persian) files removed
* Persian calendar widget added
* Performance enhanced
* Woocommerce prices problem fixed

= 1.3.5 =
* Wordpress 4.0 ready
* languages updated

= 1.3.4 =
* unix timstamp problems fixed
* languages fixed
* core functions improved

= 1.3.3 =
* editor problems fixed

= 1.3.2 =
* update language files

= 1.3.1 =
* tested on wordpress 3.9 
* added new language files

= 1.3 =
* core function enhanced
* some date function problem fixed

= 1.2 =
* fix memory error
* fix post_where hook

= 1.1 =
* Fix TinyMce text direction
* Fix sitemaps date problems
* New features on plugin settings
* Add persian numbers on the_excerpt function
* Some bugfixs on core functions

= 1.0 =
* Hello world...
