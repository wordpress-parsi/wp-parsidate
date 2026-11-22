# Ultimate Member Integration for WP-Parsidate

## Overview

ParsiDate integration for Ultimate Member. This addon is registered in `inc/App/Integration/Integration.php` and implemented by `UltimateMember`.

## Activation

- **Group:** Customizations
- **Addon ID:** `ultimate_member`
- **Detection:** Requires `ultimate-member/ultimate-member.php`, class `UM` or `UM_PLUGIN_NAME`.
- **Activation mode:** Auto-enabled when required plugin is detected.

## Settings

No settings.

## Behavior

- Adds `HookDeactivator` entries for UM cron schedules and `um_profile_field_filter_hook__date`.

## Files and assets

- Implementation: `inc/App/Integration/UltimateMember.php`
- Assets: None.
- Tests: No dedicated tests.
- Documentation: This document.

## Notes

- Compatibility/suppression integration only.

## See also

- [Integration index](../INTEGRATIONS.md)

## License

GPL v2 or later.
