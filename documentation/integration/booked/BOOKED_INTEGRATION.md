# Booked Integration for WP-Parsidate

## Overview

ParsiDate integration for Booked. This addon is registered in `inc/App/Integration/Integration.php` and implemented by `Booked`.

## Activation

- **Group:** Tools
- **Addon ID:** `booked`
- **Detection:** Requires `booked/booked.php`, class `booked_plugin` or constant `BOOKED_VERSION`.
- **Activation mode:** Auto-enabled when required plugin is detected.

## Settings

No settings.

## Behavior

- Adds `HookDeactivator` entries for `date_i18n` in calendar widget, AJAX appointment handlers, reminders, availability, and appointment-list functions.

## Files and assets

- Implementation: `inc/App/Integration/Booked.php`
- Assets: None.
- Tests: No dedicated tests.
- Documentation: This document.

## Notes

- Premium plugin; compatibility/suppression only.

## See also

- [Integration index](../INTEGRATIONS.md)

## License

GPL v2 or later.
