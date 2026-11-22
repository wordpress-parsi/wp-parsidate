# Pods Integration for WP-Parsidate

## Overview

ParsiDate integration for Pods date and datetime fields. This addon is registered in `inc/App/Integration/Integration.php` and implemented by `Pods`.

## Activation

- **Group:** Customizations
- **Addon ID:** `pods`
- **Detection:** Requires `pods/init.php`, class `Pods` or constant `PODS_VERSION`.
- **Activation mode:** Requires internal addon opt-in.

## Settings

`jalali_date_fields=true`.

## Behavior

- Jalali pickers for Pods date/datetime; accepts Jalali or Gregorian input; normalizes to Gregorian `Y-m-d` / `Y-m-d H:i:s` before Pods pre-save; `toJalaliDisplay()` for display.

## Files and assets

- Implementation: `inc/App/Integration/Pods.php`
- Assets: `assets/js/pods-jalali-datepicker{,.min}.js`, `assets/js-admin/pods-jalali-datepicker{,.min}.js`, shared datepicker/CSS.
- Tests: `tests/unit/PodsTest.php`.
- Documentation: This document.

## Notes

- Stored values remain Gregorian.

## See also

- [Integration index](../INTEGRATIONS.md)

## License

GPL v2 or later.
