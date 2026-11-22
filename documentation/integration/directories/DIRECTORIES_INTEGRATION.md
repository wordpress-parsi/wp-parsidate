# Directories Integration for WP-Parsidate

## Overview

ParsiDate integration for Directories / Directories Pro. This addon is registered in `inc/App/Integration/Integration.php` and implemented by `Directories`.

## Activation

- **Group:** Customizations
- **Addon ID:** `directories`
- **Detection:** Requires `directories/directories.php` and function `drts`.
- **Activation mode:** Requires internal addon opt-in.

## Settings

`datepicker=true`.

## Behavior

- Replaces Vanilla Calendar date/date-time/range fields with Jalali picker in admin and frontend, including AJAX forms; visible Jalali, hidden Gregorian; `-` separator admin, `/` frontend.

## Files and assets

- Implementation: `inc/App/Integration/Directories.php`
- Assets: `assets/js/directories-jalali-datepicker{,.min}.js`, `assets/js-admin/directories-jalali-datepicker{,.min}.js`, shared datepicker/CSS.
- Tests: No dedicated tests.
- Documentation: This document.

## Notes

- Uses `drts_form_scripts` plus footer fallback for dynamic forms.

## See also

- [Integration index](../INTEGRATIONS.md)

## License

GPL v2 or later.
