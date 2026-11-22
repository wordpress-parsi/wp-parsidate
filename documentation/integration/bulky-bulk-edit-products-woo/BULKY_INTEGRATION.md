# Bulky Integration for WP-Parsidate

## Overview

ParsiDate integration for Bulky (Bulk Edit Products for WooCommerce). This addon is registered in `inc/App/Integration/Integration.php` and implemented by `BulkyBulkEditProductsWooCommerce`.

## Activation

- **Group:** E-commerce
- **Addon ID:** `bulky_bulk_edit_products_woo`
- **Detection:** Requires `bulky-bulk-edit-products-for-woo/bulky-bulk-edit-products-for-woo.php` and class `BULKY\Admin\Admin`.
- **Activation mode:** Auto-enabled when required plugin is detected.

## Settings

No settings.

## Behavior

- Adds `HookDeactivator` entries to suppress global `wp_date()` / `date_i18n()` conversion inside `BULKY\Admin\Handle_Product::get_product_data`.

## Files and assets

- Implementation: `inc/App/Integration/BulkyBulkEditProductsWooCommerce.php`
- Assets: None.
- Tests: No dedicated tests.
- Documentation: This document.

## Notes

- Compatibility/suppression integration, not an active date converter.

## See also

- [Integration index](../INTEGRATIONS.md)

## License

GPL v2 or later.
