# Brizy Integration for WP-Parsidate

## Overview

ParsiDate integration for Brizy Page Builder. This addon is registered in `inc/App/Integration/Integration.php` and implemented by `Brizy`.

## Activation

- **Group:** Page Builder
- **Addon ID:** `brizy`
- **Detection:** Requires `brizy/brizy.php`, constant `BRIZY_VERSION` or class `Brizy_Editor`.
- **Activation mode:** Requires internal addon opt-in.

## Settings

`convert_dates=true`, `convert_numbers=true`, `editor_font=true`.

## Behavior

- Converts Gregorian dates and English month-name dates in compiled `brizy_content`; converts digits; protects tags/attributes/scripts/styles/code with `TextProtector`; skips admin/AJAX/cron/REST/WP-CLI/feeds/sitemaps/editor iframe/asset generation; applies Vazir font in editor contexts.

## Files and assets

- Implementation: `inc/App/Integration/Brizy.php`
- Assets: `assets/css-admin/vazir-font{,.min}.css` plus inline `wp-parsidate_brizy_editor` CSS.
- Tests: No dedicated tests.
- Documentation: This document.

## Notes

- Frontend conversion runs at priority 999.

## See also

- [Integration index](../INTEGRATIONS.md)

## License

GPL v2 or later.
