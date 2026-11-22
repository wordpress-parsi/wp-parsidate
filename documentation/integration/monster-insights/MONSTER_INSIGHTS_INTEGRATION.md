# Monster Insights Integration for WP-Parsidate

## Overview

ParsiDate integration for Monster Insights. This addon is registered in `inc/App/Integration/Integration.php` and implemented by `MonsterInsights`.

## Activation

- **Group:** SEO
- **Addon ID:** `monster_insights`
- **Detection:** Requires `google-analytics-for-wordpress/googleanalytics.php`, class/function `MonsterInsights` or `MONSTERINSIGHTS_VERSION`.
- **Activation mode:** Auto-enabled when required plugin is detected.

## Settings

No settings.

## Behavior

- Adds `HookDeactivator` entry for `wp_date` in `monsterinsights_yearinreview_dates`.

## Files and assets

- Implementation: `inc/App/Integration/MonsterInsights.php`
- Assets: None.
- Tests: No dedicated tests.
- Documentation: This document.

## Notes

- Compatibility/suppression only.

## See also

- [Integration index](../INTEGRATIONS.md)

## License

GPL v2 or later.
