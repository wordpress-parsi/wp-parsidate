# Limit Login Attempts Security Integration for WP-Parsidate

## Overview

ParsiDate integration for Limit Login Attempts Security. This addon is registered in `inc/App/Integration/Integration.php` and implemented by `LimitLoginAttempts`.

## Activation

- **Group:** Security
- **Addon ID:** `limit_login_attempts`
- **Detection:** Requires `limit-login-attempts-reloaded/limit-login-attempts-reloaded.php`, class `LLAR\Core\LimitLoginAttempts` or `LLA_PLUGIN_URL`.
- **Activation mode:** Auto-enabled when required plugin is detected.

## Settings

No settings.

## Behavior

- Adds `HookDeactivator` entries for `date_i18n` / `wp_date` in dashboard widget and options page output.

## Files and assets

- Implementation: `inc/App/Integration/LimitLoginAttempts.php`
- Assets: None.
- Tests: No dedicated tests.
- Documentation: This document.

## Notes

- Compatibility/suppression only.

## See also

- [Integration index](../INTEGRATIONS.md)

## License

GPL v2 or later.
