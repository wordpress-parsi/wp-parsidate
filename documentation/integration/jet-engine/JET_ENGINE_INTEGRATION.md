# JetEngine Integration for WP-Parsidate

## Overview

ParsiDate integration for JetEngine. This addon is registered in `inc/App/Integration/Integration.php` and implemented by `JetEngine`.

## Activation

- **Group:** Customizations
- **Addon ID:** `jet_engine`
- **Detection:** Requires `jet-engine/jet-engine.php`, class `Jet_Engine` or function `jet_engine`.
- **Activation mode:** Requires internal addon opt-in.

## Settings

`datepicker=true`, `date_to_shamsi=false`, `date_format='j F Y'`, `datetime_to_shamsi=false`, `datetime_format='j F Y, g:i a'`.

## Behavior

- Jalali datepicker in meta boxes; Jalali display for post/term/user meta, Options Pages, Dynamic Fields, Table Builder; restores Gregorian before save; adds `jet-engine` to post-type ignore list.

## Files and assets

- Implementation: `inc/App/Integration/JetEngine.php`
- Assets: `assets/js-admin/jet-engine{,.min}.js`, source `assets/js-admin-src/jet-engine.js`, shared datepicker.
- Tests: No dedicated tests.
- Documentation: This document.

## Notes

- Admin behavior requires `persian_date` and `fa_IR`.

## See also

- [Integration index](../INTEGRATIONS.md)

## License

GPL v2 or later.
