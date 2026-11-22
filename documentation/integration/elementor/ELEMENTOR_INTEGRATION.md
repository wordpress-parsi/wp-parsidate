# Elementor Integration for WP-Parsidate

## Overview

ParsiDate integration for Elementor. This addon is registered in `inc/App/Integration/Integration.php` and implemented by `Elementor`.

## Activation

- **Group:** Page Builder
- **Addon ID:** `elementor`
- **Detection:** Requires `elementor/elementor.php` and constant `ELEMENTOR_PLUGIN_BASE`.
- **Activation mode:** Requires internal addon opt-in.

## Settings

`fix_editor_date_time=false`, `fix_front_date_fields=false`, `fix_submissions_dates=false`.

## Behavior

- Jalali editor DATE_TIME controls (e.g. Countdown), hidden Gregorian mirror for Pro Form Date fields, Jalali Form Submissions dates, Persian font CSS in editor contexts.

## Files and assets

- Implementation: `inc/App/Integration/Elementor.php`
- Assets: `assets/js/elementor-jalali-date{,.min}.js`, `assets/js-admin/elementor-jalali-editor{,.min}.js`, `assets/js-admin/elementor-jalali-date{,.min}.js`, shared datepicker/CSS.
- Tests: No dedicated tests.
- Documentation: This document.

## Notes

- Sources live in `assets/js-src` and `assets/js-admin-src`.

## See also

- [Integration index](../INTEGRATIONS.md)

## License

GPL v2 or later.
