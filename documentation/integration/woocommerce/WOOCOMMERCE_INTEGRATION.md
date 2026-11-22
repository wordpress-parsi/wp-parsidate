# WooCommerce Integration for WP-Parsidate

## Overview

ParsiDate integration for WooCommerce. This addon is registered in `inc/App/Integration/Integration.php` and implemented by `WooCommerce`.

## Activation

- **Group:** E-commerce
- **Addon ID:** `woocommerce`
- **Detection:** Requires `woocommerce/woocommerce.php`, class `WooCommerce`, and commonly `is_woocommerce()` / `fa_IR` checks.
- **Activation mode:** Auto-enabled when required plugin is detected.

## Settings

`fix_prices`, `fix_persian_postcode`, `fix_persian_phone`, `dropdown_cities`, `validate_postcode`, `validate_phone`, `analytics_shamsi_date`, `fix_email_content_numbers` — all default `false`. Settings via `addTabSettings()`.

## Behavior

- Persian/English digits in prices, checkout, postcode and phone; Iranian phone/postcode validation; city dropdown (classic + block checkout); Jalali admin dates/datepickers; analytics overlay; email number handling; legacy gateway support and ParsiGate migration via `WcGateways`.

## Files and assets

- Implementation: `inc/App/Integration/WooCommerce.php`
- Assets: `assets/js/woocommerce-checkout-block-city{,.min}.js`, `assets/js/city-select.js`, `assets/js-admin/woocommerce{,.min}.js`, `assets/css-admin/woocommerce{,.min}.css`, `assets/js-admin/woocommerce-analytics{,.min}.js`, `assets/js-admin/wc-pg-blocks/wpp-wc-*-pg{,.min}.js`.
- Tests: No dedicated integration test.
- Documentation: This document.

## Notes

- Declares HPOS/product caching compatibility.
- Gateway code lives under `inc/App/Integration/WooCommerce/`.

## See also

- [Integration index](../INTEGRATIONS.md)

## License

GPL v2 or later.
