# Formello Integration for WP-Parsidate

## Overview

ParsiDate integration for Formello. This addon is registered in `inc/App/Integration/Integration.php` and implemented by `Formello`.

## Activation

- **Group:** Form
- **Addon ID:** `formello`
- **Detection:** Requires `formello/formello.php` and function `formello_run`.
- **Activation mode:** Auto-enabled when required plugin is detected.

## Settings

No settings.

## Behavior

- Adds `HookDeactivator` entry for `wp_date` in `Formello\Processor\Form::add_details`.

## Files and assets

- Implementation: `inc/App/Integration/Formello.php`
- Assets: None.
- Tests: No dedicated tests.
- Documentation: This document.

## Notes

- Compatibility/suppression only.

## See also

- [Integration index](../INTEGRATIONS.md)

## License

GPL v2 or later.
