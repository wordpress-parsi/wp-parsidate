# Advanced Custom Fields Integration for WP-Parsidate

## Overview

ParsiDate integration for Advanced Custom Fields. This addon is registered in `inc/App/Integration/Integration.php` and implemented by `ACF`.

## Activation

- **Group:** Customizations
- **Addon ID:** `acf`
- **Detection:** Requires `advanced-custom-fields/acf.php`, class `ACF`; supports ACF v4 and v5 hooks.
- **Activation mode:** Auto-enabled when required plugin is detected.

## Settings

`fix_date=false`, `save_persian_date=false` (field classes read `save_persian_date` via `Settings::get(..., 'acf')`).

## Behavior

- Registers `jalali_datepicker` ACF field; per-field “Convert to Shamsi Date”; converts values on load/update/format; stores Gregorian `Ymd`; mirrors visible/hidden inputs including repeater rows.

## Files and assets

- Implementation: `inc/App/Integration/ACF.php`
- Assets: `inc/App/Integration/ACF/class-wpp-acf-datepicker-v4.php`, `class-wpp-acf-datepicker-v5.php`, `class-wpp-acf-timepicker-v4.php`, `class-wpp-acf-timepicker-v5.php` (timepicker include commented out).
- Tests: No dedicated tests.
- Documentation: This document.

## Notes

- Also suppresses conversion inside ACF date field formatting via `HookDeactivator`.

## See also

- [Integration index](../INTEGRATIONS.md)

## License

GPL v2 or later.
