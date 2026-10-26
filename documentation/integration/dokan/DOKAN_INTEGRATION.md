# Dokan Integration for WP-Parsidate

## Overview

This integration provides comprehensive Jalali (Shamsi) date support for both **Dokan Lite** and **Dokan Pro** plugins while maintaining complete database compatibility. All dates are displayed in Shamsi/Jalali format for Persian users, but stored in Gregorian format in the database.

## Architecture

### Three-Layer Approach

1. **Display Layer (PHP Filters)**
   - Hooks into `dokan_format_date()` and `dokan_format_datetime()`
   - Converts displayed dates to Shamsi format
   - Applied via `addDateHooksToDeactivator()` filter

2. **Input Layer (JavaScript)**
   - Replaces native jQuery UI datepickers with Jalali datepickers
   - Uses dual-input pattern: visible Jalali field + hidden Gregorian field
   - Handles AJAX-loaded forms via MutationObserver

3. **Database Layer**
   - Never modified
   - All conversions happen at read/write boundaries
   - Zero conflict with WooCommerce/Dokan validation

## Features

### Dokan Lite Support

✅ **Product Date Fields**
- Sale price start date (`_sale_price_dates_from`)
- Sale price end date (`_sale_price_dates_to`)
- Jalali datepicker in vendor product edit forms

✅ **Order Management**
- Order date displays in Shamsi format
- Order date range filters with Jalali picker
- Export date filters

✅ **Withdrawal System**
- Persian number support in withdrawal amounts
- Withdrawal request date displays
- Date filtering in withdrawal history

✅ **Vendor Dashboard**
- Dashboard widget dates
- Store hours display (optional)
- Activity dates

### Dokan Pro Support

✅ **All Lite Features** plus:

✅ **Booking Module**
- Booking date displays
- Check-in/Check-out dates
- Calendar views with Shamsi dates

✅ **Delivery Time**
- Delivery date selection
- Time slot displays

✅ **Vendor Analytics**
- Report date ranges
- Chart date labels
- Custom date range selection

✅ **Subscription Dates**
- Subscription start/end dates
- Renewal date displays

## Implementation Details

### File Structure

```
inc/App/Integration/Dokan.php              # Main integration class
assets/js-src/dokan-jalali-date.js          # Source JavaScript
assets/js-admin/dokan-jalali-date.js        # Admin version
assets/js-admin/dokan-jalali-date.min.js    # Admin minified
assets/js/dokan-jalali-date.js              # Frontend version
assets/js/dokan-jalali-date.min.js          # Frontend minified
```

### PHP Class: `../../../inc/App/Integration/Dokan.php`

**Key Methods:**

- `addDateHooksToDeactivator()` — Registers date formatting hooks
- `enqueueFrontAssets()` — Loads assets on vendor dashboard
- `enqueueAdminAssets()` — Loads assets in admin area
- `convertProductDatesToGregorian()` — Converts Jalali dates before saving
- `convertPersianNumbersInPost()` — Normalizes Persian digits
- `fixWithdrawAmountDisplay()` — Formats withdrawal amounts

**Settings Available:**

| Setting | Default | Description |
|---------|---------|-------------|
| `fix_dates_display` | `true` | Display dates in Shamsi format |
| `fix_datepicker` | `true` | Replace datepickers with Jalali |
| `fix_product_dates` | `true` | Jalali picker for product dates |
| `fix_withdraw_numbers` | `false` | Persian number support |
| `fix_store_hours` | `false` | Shamsi store hours display (Dokan Pro only) |

### JavaScript: `dokan-jalali-date.js`

**Key Components:**

1. **DokanJalaliDate Class**
   - Main orchestrator
   - Handles field detection and setup
   - Manages conversion logic

2. **Dual-Input Pattern**
   ```html
   <!-- Original (hidden) -->
   <input type="hidden" name="_sale_price_dates_from" value="2024-10-23">

   <!-- Visible Jalali -->
   <input type="text" class="wpp-dokan-jalali-input" value="1403/08/02">
   ```

3. **Conversion Functions**
   - `gregorianToJalali()` — Y-m-d → jY/jM/jD
   - `jalaliToGregorian()` — jY/jM/jD → Y-m-d
   - `gregorianRangeToJalali()` — Range conversion
   - `jalaliRangeToGregorian()` — Range parsing

4. **Field Detection**
   - `.datepicker` — Generic date fields
   - `.dokan-start-date`, `.dokan-end-date` — Product dates
   - `.dokan-daterangepicker` — Order filters
   - `input[name="_sale_price_dates_from"]` — Sale start
   - `input[name="_sale_price_dates_to"]` — Sale end

5. **Dynamic Content Support**
   - MutationObserver watches for AJAX content
   - Handles Dokan's modal popups
   - Listens to `dokan_product_editor_loaded` event

## Usage

### Activation

1. Install and activate **Dokan Lite** (required)
2. Dokan Pro is **optional**: when it is active, Pro-only modules and their settings (store hours) become available
3. Configure settings in **WP-Parsidate → Integration → Dokan**

### Settings Configuration

Navigate to: **WordPress Admin → WP-Parsidate → Integration → Dokan**

**Recommended Settings:**
- ✅ Shamsi date display: **Enabled**
- ✅ Jalali datepicker: **Enabled**
- ✅ Product date fields: **Enabled**
- ⚪ Withdrawal amounts: **As needed**
- ⚪ Store hours display: **As needed**

### For Developers

**Disable specific datepicker fields:**
```javascript
// Add this class to exclude a field from conversion
<input type="text" class="datepicker wpp-dokan-skip" />
```

**Hook into conversion events:**
```php
// After date conversion
add_action( 'wp_parsidate_dokan_date_converted', function( $gregorian, $jalali ) {
    // Your code here
}, 10, 2 );
```

**Filter Jalali picker options:**
```php
add_filter( 'wp_parsidate_dokan_picker_options', function( $options ) {
    $options['zIndex'] = 9999999;
    return $options;
});
```

## Compatibility

### Tested With

- **Dokan Lite:** 3.7.x, 3.8.x
- **Dokan Pro:** 3.7.x, 3.8.x
- **WordPress:** 6.0+
- **WooCommerce:** 7.0+, 8.0+
- **PHP:** 7.4+, 8.0+, 8.1+

### Known Conflicts

**None** — This integration is designed to be zero-conflict.

If you experience issues:
1. Disable other date-related plugins temporarily
2. Clear browser cache
3. Check JavaScript console for errors
4. Report to WP-Parsidate GitHub issues

## Technical Details

### Date Validation

The integration automatically detects whether a date is Jalali or Gregorian:

- **Year 1200-1599**: Treated as Jalali → Converted to Gregorian
- **Year 1900+**: Treated as Gregorian → Used as-is
- **Invalid dates**: Silently ignored, original value retained

### Performance

- **Zero Database Impact**: No queries added
- **Lazy Loading**: Assets only load on Dokan pages
- **Minimal Overhead**: ~15KB JavaScript (minified)
- **Cached Conversions**: Duplicate conversions avoided

### Security

- ✅ All inputs sanitized via `Number::toEnglish()`
- ✅ Date validation before conversion
- ✅ SQL injection safe (no direct queries)
- ✅ XSS protection via proper escaping
- ✅ CSRF protection inherited from Dokan

## Troubleshooting

### Datepicker Not Showing

1. Check if Jalali datepicker setting is enabled
2. Ensure JavaScript console has no errors
3. Verify `WpPdJalaliDate` is loaded (check browser console)
4. Clear browser cache and WordPress cache

### Dates Not Converting

1. Verify `fix_dates_display` setting is enabled
2. Check WordPress locale is `fa_IR`
3. Ensure Persian date setting in WP-Parsidate is enabled
4. Check if the date format matches expected pattern

### Wrong Dates Saved

1. Verify hidden input contains Gregorian date
2. Check browser console for conversion errors
3. Ensure form submission isn't intercepted by other plugins
4. Test with JavaScript disabled to see raw values

### AJAX Forms Not Working

1. Check if MutationObserver is supported in browser
2. Verify `dokan_product_editor_loaded` event fires
3. Clear browser cache
4. Check for JavaScript errors in console

## Changelog

### Version 1.0.0 (2024-10-23)
- Initial release
- Full Dokan Lite support
- Full Dokan Pro support
- Dual-input pattern implementation
- Date range filter support
- Persian number conversion
- MutationObserver for AJAX forms

## Credits

Developed as part of the [WP-Parsidate](https://github.com/wordpress-parsi/wp-parsidate) project.

## License

GPL v2 or later

## Support

- **Documentation**: [WP-Parsidate Wiki](https://github.com/wordpress-parsi/wp-parsidate/wiki)
- **Issues**: [GitHub Issues](https://github.com/wordpress-parsi/wp-parsidate/issues)
- **Community**: [WordPress.org Forums](https://wordpress.org/support/plugin/wp-parsidate/)
