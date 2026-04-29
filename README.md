# WP Parsidate - افزونه پارسی دیت

![Version](https://img.shields.io/badge/version-5.1.8-blue)
![WordPress Compatible](https://img.shields.io/badge/WordPress-5.3%2B-green)
![License](https://img.shields.io/badge/license-GPLv2-blue)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/wp-parsidate.svg)](https://wordpress.org/plugins/wp-parsidate/)
![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple)

**Contributors:** [lord_viper](https://profiles.wordpress.org/lord_viper), [man4toman](https://profiles.wordpress.org/man4toman), [parselearn](https://profiles.wordpress.org/parselearn), [yazdaniwp](https://profiles.wordpress.org/yazdaniwp)  
**Donate link:** [https://wp-parsi.com/support/](https://wp-parsi.com/support/)  
**Telegram Community:** [@parsidate](https://t.me/parsidate)  
**Tags:** shamsi, jalali, persian, parsi, farsi, date, calendar, widget, picker, plugin, wordpress, rtl, gutenberg, acf, woocommerce, i18n, l10n  
**Requires:** WordPress 5.3+  
**Tested up to:** 6.7.1  
**Stable tag:** 5.1.8  
**License:** GPLv2 or later  

A comprehensive WordPress plugin that integrates the Solar Hijri (Persian/Shamsi/Jalali) calendar into your WordPress site with full RTL support.

---

## Description

WP Parsidate is a feature-rich WordPress plugin designed by Persian developers to enhance the experience of Persian-speaking users. It seamlessly integrates the Shamsi (Jalali) calendar throughout WordPress while providing character and number localization, RTL optimization, and compatibility with popular plugins.

Whether you're running a Persian blog, e-commerce store, or any WordPress site, WP Parsidate brings authentic Persian date and calendar functionality to improve user experience and engagement.

---

## ✨ Key Features

### Date & Calendar Functionality
- 🗓️ **Shamsi (Jalali) Calendar** - Complete Solar Hijri calendar support
- 📅 **Date Conversion** - Automatic conversion throughout WordPress
- 📍 **Archive Widgets** - Shamsi date-based post archives
- 📆 **Calendar Widget** - Interactive Persian calendar widget
- 🔗 **Permalink Support** - Shamsi dates in custom permalinks

### Editor & Admin Integration
- ✏️ **Gutenberg Support** - Shamsi date picker in Block Editor
- 🎨 **ACF Integration** - Persian date field type for Advanced Custom Fields
- ⚙️ **Admin Dates** - Shamsi dates throughout WordPress admin dashboard
- 📝 **Content Dates** - Automatic Persian dates in posts, pages, comments, and archives

### Commerce & E-commerce
- 🛒 **WooCommerce Ready** - Full WooCommerce compatibility with Persian dates
- 📦 **Product Support** - Persian dates for orders, products, and customer data

### Text & Character Processing
- 🔤 **Arabic to Persian** - Automatic character conversion (ي/ك to ی/ک)
- 🔢 **Number Localization** - Convert Eastern Arabic and Latin numerals
- 📖 **Full RTL Support** - Complete right-to-left text direction optimization
- ✏️ **TinyMCE Adjustments** - RTL-optimized WordPress editor

### Performance
- ⚡ **Lightweight** - Minimal resource usage and fast performance
- 🎯 **Efficient** - Optimized code for production environments
- 📡 **WP-Planet Widget** - Integration with [WP-Planet.ir](https://wp-planet.ir)

---

## 🚀 Installation

### From WordPress Plugin Directory (Recommended)
1. Go to **Plugins** → **Add New** in your WordPress admin
2. Search for **WP Parsidate**
3. Click **Install Now**
4. Activate the plugin

### Manual Installation
1. Download the plugin from [WordPress.org](https://wordpress.org/plugins/wp-parsidate/) or GitHub
2. Upload the plugin folder to `/wp-content/plugins/` directory
3. Activate the plugin through the **Plugins** menu in WordPress

### Initial Setup
1. Navigate to **Settings** → **Parsidate** to configure the plugin
2. Choose your preferred options (date format, conversion settings, etc.)
3. (Optional) Go to **Widgets** to add:
   - **گاه‌شمار تاریخ خورشیدی** (Persian Calendar Widget)
   - **بایگانی تاریخ خورشیدی** (Persian Archive Widget)

---

## 📖 Usage

### For Site Administrators
- **Settings Page**: Configure date formats, conversion options, and compatibility features
- **Widgets**: Add Persian calendar or archive widgets to your sidebar
- **Automatic Conversion**: The plugin automatically converts dates across your site once activated

### For Developers
The plugin provides useful functions for developers:

```php
// Convert Gregorian date to Shamsi
$shamsi_date = jd2jalali('Y/m/d', strtotime('2026-04-29'));

// Convert numbers to Persian
$persian_number = wp_parsidate_number_convert('1234');

// Convert Arabic characters to Persian
$persian_text = wp_parsidate_arabic_to_persian($text);
