# Easy Digital Downloads Integration for WP-Parsidate

## Overview

ParsiDate integration for Easy Digital Downloads. This addon is registered in `inc/App/Integration/Integration.php` and implemented by `EDD`.

## Activation

- **Group:** E-commerce
- **Addon ID:** `edd`
- **Detection:** Requires `easy-digital-downloads/easy-digital-downloads.php` and function `EDD`.
- **Activation mode:** Auto-enabled when required plugin is detected.

## Settings

`fix_prices`, `remove_decimals`, `add_toman_currency`, `fix_currency` — all default `false`.

## Behavior

- Adds Toman currency; converts RIAL/TOMAN labels to Persian words; Persian price digits; optional decimal removal.

## Files and assets

- Implementation: `inc/App/Integration/EDD.php`
- Assets: Inline SVG icon only.
- Tests: No dedicated tests.
- Documentation: This document.

## Notes

- No additional notes.

## See also

- [Integration index](../INTEGRATIONS.md)

## License

GPL v2 or later.
