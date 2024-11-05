# Parsi Date

![Version](https://img.shields.io/badge/version-5.1.0-blue)
![WordPress Compatible](https://img.shields.io/badge/WordPress-5.3%20to%206.6.1-blue)

**Contributors:** [lord_viper](https://profiles.wordpress.org/lord_viper), [man4toman](https://profiles.wordpress.org/man4toman), [parselearn](https://profiles.wordpress.org/parselearn), [yazdaniwp](https://profiles.wordpress.org/yazdaniwp), [saeedfard](https://profiles.wordpress.org/saeedfard), [iehsanir](https://profiles.wordpress.org/iehsanir)  
**Donate link:** [https://wp-parsi.com/support/](https://wp-parsi.com/support/)  
**Tags:** shamsi, wp-parsi, wpparsi, persian, parsi, farsi, jalali, date, calendar, i18n, l10n, iran, iranian, parsidate, rtl, gutenberg, acf, woocommerce  
**Requires at least:** 5.3  
**Tested up to:** 6.6.1  
**Stable tag:** 5.1.0  

Persian date support for WordPress.

## Description

This package, created by Persian developers, enhances the Persian experience on WordPress by adding Shamsi (Jalali) calendar support, character issue fixes, and RTL compatibility for the WordPress back-end.

### Key Features

- Shamsi (Jalali) day-picker in Block Editor (Gutenberg)
- Shamsi (Jalali) jQuery UI date-picker
- [WP-Planet.ir](https://wp-planet.ir) Widget
- Shamsi (Jalali) dates in posts, comments, pages, archives, search, categories
- Shamsi (Jalali) dates in permalinks
- Shamsi (Jalali) dates in admin sections
- Shamsi (Jalali) calendar and archive widgets
- RTL support and tinymce editor adjustments
- Persian features in WooCommerce and ACF
- Functions for converting Arabic characters and numbers to Persian
- Low resource usage

## Installation

1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. To use the archive widget, go to 'Widgets' and select 'بایگانی تاریخ خورشیدی'.
4. For the calendar widget, go to 'Widgets' and select 'گاه‌شمار تاریخ خورشیدی'.

## Screenshots

1. Main settings page
2. Number conversions settings
3. Compatibility with other plugins
4. Persian datepicker in WooCommerce
5. Persian date type in ACF

## Changelog

### 5.1.0
- Added HPOS compatibility and block-based gateways in WooCommerce
- Added Iranian cities for WooCommerce
- Introduced Persian datepicker in Block Editor
- Fixed conflicts with Jetpack and DATE_W3C format
- Resolved warnings and errors related to ACF, comments feed, and archive pages
- Improved Persian date support in WooCommerce
- Updated settings panel design and added Vazir font to admin area
- Compatibility with WordPress 6.5x and WooCommerce 8.7x

### 4.0.0
- Various fixes, including Jetpack conflict, date issues in comments feed, RevSlider conflict, and timezone problems
- Improved WooCommerce integration for Persian dates and prices
- Updated calendar widgets and attachment filter dropdown
- Added ACF Parsi date support


## Development Log

- Improved handling of `$wp_query` in `posts_where` filter.
- Fixed month dropdown bug in admin edit post date picker.

## License

This project is licensed under the GPLv2 License - see the [LICENSE.md](LICENSE.md) file for details.
