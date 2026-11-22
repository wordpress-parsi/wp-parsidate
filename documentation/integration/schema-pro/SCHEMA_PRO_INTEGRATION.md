# Schema Pro Integration for WP-Parsidate

## Overview

ParsiDate integration for Schema Pro. This addon is registered in `inc/App/Integration/Integration.php` and implemented by `SchemaPro`.

## Activation

- **Group:** SEO
- **Addon ID:** `wp_schema_pro`
- **Detection:** Requires `wp-schema-pro/wp-schema-pro.php`, class `BSF_AIOSRS_Pro_Schema` or `BSF_AIOSRS_PRO_VER`.
- **Activation mode:** Auto-enabled when required plugin is detected.

## Settings

No settings.

## Behavior

- Adds `HookDeactivator` entries for `get_the_date` and `wp_date` inside `BSF_AIOSRS_Pro_Schema_Template::get_post_data`.

## Files and assets

- Implementation: `inc/App/Integration/SchemaPro.php`
- Assets: None.
- Tests: No dedicated tests.
- Documentation: This document.

## Notes

- Premium plugin; compatibility/suppression only.

## See also

- [Integration index](../INTEGRATIONS.md)

## License

GPL v2 or later.
