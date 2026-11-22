# Rank Math Integration for WP-Parsidate

## Overview

ParsiDate integration for Rank Math. This addon is registered in `inc/App/Integration/Integration.php` and implemented by `RankMath`.

## Activation

- **Group:** SEO
- **Addon ID:** `rank_math`
- **Detection:** Requires `seo-by-rank-math/rank-math.php` and class `RankMath`.
- **Activation mode:** Requires internal addon opt-in.

## Settings

No settings UI.

## Behavior

- Normalizes Persian digits in Facebook metadata; converts JSON-LD `uploadDate`, `datePublished`, `dateModified` to ISO Gregorian; converts Jalali `priceValidUntil` including ProductGroup variants; IRT → IRR and price ×10.

## Files and assets

- Implementation: `inc/App/Integration/RankMath.php`
- Assets: Inline SVG icon only.
- Tests: No dedicated tests.
- Documentation: This document.

## Notes

- Declares `settings_key` but does not register section settings.

## See also

- [Integration index](../INTEGRATIONS.md)

## License

GPL v2 or later.
