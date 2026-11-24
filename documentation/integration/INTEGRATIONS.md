# WP-Parsidate Integrations

## Overview

This document is the index of functional addons currently registered in `inc/App/Integration/Integration.php`. Demo/recommendation-only addons are intentionally excluded.

Each integration is implemented as an `Addon` subclass under `inc/App/Integration/`. Integrations may:

- convert displayed dates/numbers to Persian or Jalali,
- provide Jalali datepickers while preserving Gregorian stored values,
- normalize Persian digits and dates before third-party plugins save data,
- suppress WP-Parsidate's global date filters at known third-party call sites via `HookDeactivator`,
- provide settings and integration notices in WP-Parsidate's admin screens.

Detailed guides exist for the larger integrations:

- [Bookly](bookly/BOOKLY_INTEGRATION.md)
- [Dokan](dokan/DOKAN_INTEGRATION.md)
- [Dokan implementation summary](dokan/DOKAN_IMPLEMENTATION_SUMMARY.md)

## Activation model

Integration activation is based on two layers:

1. **Plugin detection** — each addon declares required or optional plugins/classes/functions in `info()`.
2. **Internal addon state** — most integrations require the internal addon toggle to be enabled. Some integrations use `force_enable`, which activates them automatically when the required plugin is detected.

Demo/recommendation-only addons are intentionally excluded from this document.

## Integration groups

The functional groups below follow the comments in `inc/App/Integration/Integration.php`.

### E-commerce

| Addon | ID | Settings | Behavior |
|---|---|---|---|
| [WooCommerce](woocommerce/WOOCOMMERCE_INTEGRATION.md) | `woocommerce` | `fix_prices`, `fix_persian_postcode`, `fix_persian_phone`, `dropdown_cities`, `validate_postcode`, `validate_phone`, `analytics_shamsi_date`, `fix_email_content_numbers` — all default `false` | Persian digits in prices/checkout/postcode/phone, Iranian phone/postcode validation, city dropdown for classic and block checkout, Jalali admin dates/datepickers, analytics date overlay, email number handling, and legacy Iranian gateway support with ParsiGate migration. |
| [Easy Digital Downloads](edd/EDD_INTEGRATION.md) | `edd` | `fix_prices`, `remove_decimals`, `add_toman_currency`, `fix_currency` — all default `false` | Toman currency support, Persian currency labels, Persian price digits, optional decimal removal. |
| [Dokan / Dokan Pro](dokan/DOKAN_INTEGRATION.md) | `dokan` | `fix_dates_display=true`, `fix_datepicker=true`, `fix_product_dates=true`, `fix_withdraw_numbers=false`, `fix_store_hours=false` when Pro is active | Jalali vendor/admin dates, Jalali datepickers with hidden Gregorian values, Persian withdraw digits, product/month filters, Pro store hours. See [Dokan guide](dokan/DOKAN_INTEGRATION.md). |
| [Bulky Bulk Edit Products](bulky-bulk-edit-products-woo/BULKY_INTEGRATION.md) | `bulky_bulk_edit_products_woo` | — | Suppresses global `date_i18n()` / `wp_date()` conversion inside Bulky product data handling. |

### Customizations

| Addon | ID | Settings | Behavior |
|---|---|---|---|
| [Advanced Custom Fields](acf/ACF_INTEGRATION.md) | `acf` | `fix_date=false`, `save_persian_date=false` | Registers a `jalali_datepicker` ACF field for v4/v5, converts Jalali/Gregorian values, stores Gregorian values, mirrors visible/hidden inputs. |
| [JetEngine](jet-engine/JET_ENGINE_INTEGRATION.md) | `jet_engine` | `datepicker=true`, `date_to_shamsi=false`, `date_format='j F Y'`, `datetime_to_shamsi=false`, `datetime_format='j F Y, g:i a'` | Jalali datepicker in meta boxes, Jalali display for meta/options/dynamic/table fields, Gregorian values restored before save. |
| [Directories](directories/DIRECTORIES_INTEGRATION.md) | `directories` | `datepicker=true` | Replaces date/date-time/range fields with Jalali picker in admin and frontend, including AJAX-rendered forms. |
| [Pods](pods/PODS_INTEGRATION.md) | `pods` | `jalali_date_fields=true` | Jalali pickers for Pods date/datetime fields, accepts Jalali or Gregorian input, stores Gregorian `Y-m-d` / `Y-m-d H:i:s`. Covered by `tests/unit/PodsTest.php`. |
| [Ultimate Member](ultimate-member/ULTIMATE_MEMBER_INTEGRATION.md) | `ultimate_member` | — | Suppresses global date conversion for UM cron events and profile date field filter. |

### SEO

| Addon | ID | Settings | Behavior |
|---|---|---|---|
| [Rank Math](rank-math/RANK_MATH_INTEGRATION.md) | `rank_math` | — | Normalizes Persian digits in social/JSON-LD dates, converts Jalali `priceValidUntil` to Gregorian, changes IRT to IRR and multiplies prices by 10. |
| [Schema Pro](schema-pro/SCHEMA_PRO_INTEGRATION.md) | `wp_schema_pro` | — | Suppresses global date conversion in Schema Pro post data. |
| [Monster Insights](monster-insights/MONSTER_INSIGHTS_INTEGRATION.md) | `monster_insights` | — | Suppresses global date conversion in year-in-review dates. |

### Page builders

| Addon | ID | Settings | Behavior |
|---|---|---|---|
| [Elementor](elementor/ELEMENTOR_INTEGRATION.md) | `elementor` | `fix_editor_date_time=false`, `fix_front_date_fields=false`, `fix_submissions_dates=false` | Jalali editor DATE_TIME controls, Pro Form date fields, and Form Submissions dates; hidden Gregorian values are preserved. |
| [Brizy](brizy/BRIZY_INTEGRATION.md) | `brizy` | `convert_dates=true`, `convert_numbers=true`, `editor_font=true` | Converts date strings and digits in compiled Brizy content while protecting tags/attributes/scripts/styles; applies Vazir font in editor contexts. |

### Forms and security

| Addon | ID | Settings | Behavior |
|---|---|---|---|
| [Formello](formello/FORMELLO_INTEGRATION.md) | `formello` | — | Suppresses global date conversion in Formello detail processing. |
| [Limit Login Attempts Security](limit-login-attempts/LIMIT_LOGIN_ATTEMPTS_INTEGRATION.md) | `limit_login_attempts` | — | Suppresses global date conversion in LLAR dashboard/options output. |

### Tools

| Addon | ID | Settings | Behavior |
|---|---|---|---|
| [Bookly](bookly/BOOKLY_INTEGRATION.md) | `bookly` | per-surface toggles (`Frontend` / `Admin`) | Jalali selection and presentation across Bookly Free, Pro, admin, AI assistant, and Appearance preview. See [Bookly guide](bookly/BOOKLY_INTEGRATION.md). |
| [Booked](booked/BOOKED_INTEGRATION.md) | `booked` | — | Suppresses global date conversion in Booked calendar, AJAX appointment, reminder, availability, and appointment-list functions. |

## Implementation patterns

### Active conversion integrations

Active integrations transform values or UI directly:

- WooCommerce
- Dokan
- ACF
- JetEngine
- Directories
- Pods
- Rank Math
- Elementor
- Brizy
- Bookly

These addons typically use one or more of:

- PHP filters for display/output conversion,
- request filters for Jalali → Gregorian normalization before third-party saves,
- JavaScript datepicker overlays with hidden Gregorian inputs,
- script-handle detection to load only the required assets.

### HookDeactivator integrations

Some third-party plugins already produce the desired native values or require WP-Parsidate not to transform their date output. These addons append caller signatures to:

```php
wp_parsidate_hook_deactivator_raw_list
```

`WPParsidate\App\Tools\HookDeactivator` uses a backtrace match to skip global `date_i18n()` / `wp_date()` conversion for those specific call sites.

HookDeactivator-only or primarily deactivator-based addons include:

- Bulky
- Ultimate Member
- Schema Pro
- Monster Insights
- Formello
- Limit Login Attempts
- Booked
- parts of Dokan

### Asset pattern

Editable JavaScript lives in:

```text
assets/js-src/
assets/js-admin-src/
```

Compiled runtime files live in:

```text
assets/js/
assets/js-admin/
```

Production loads `.min` assets unless `WP_PARSI_DEBUG_MODE` is enabled.

Shared building blocks:

```text
assets/js-admin/jalali-date.js
assets/js-admin/jalalidatepicker.min.js
assets/css-admin/jalalidatepicker.css
```

## Detailed documentation coverage

| Integration | Guide |
|---|---|
| WooCommerce | [woocommerce/WOOCOMMERCE_INTEGRATION.md](woocommerce/WOOCOMMERCE_INTEGRATION.md) |
| Easy Digital Downloads | [edd/EDD_INTEGRATION.md](edd/EDD_INTEGRATION.md) |
| Dokan / Dokan Pro | [dokan/DOKAN_INTEGRATION.md](dokan/DOKAN_INTEGRATION.md) |
| Bulky Bulk Edit Products | [bulky-bulk-edit-products-woo/BULKY_INTEGRATION.md](bulky-bulk-edit-products-woo/BULKY_INTEGRATION.md) |
| Advanced Custom Fields | [acf/ACF_INTEGRATION.md](acf/ACF_INTEGRATION.md) |
| JetEngine | [jet-engine/JET_ENGINE_INTEGRATION.md](jet-engine/JET_ENGINE_INTEGRATION.md) |
| Directories | [directories/DIRECTORIES_INTEGRATION.md](directories/DIRECTORIES_INTEGRATION.md) |
| Pods | [pods/PODS_INTEGRATION.md](pods/PODS_INTEGRATION.md) |
| Ultimate Member | [ultimate-member/ULTIMATE_MEMBER_INTEGRATION.md](ultimate-member/ULTIMATE_MEMBER_INTEGRATION.md) |
| Rank Math | [rank-math/RANK_MATH_INTEGRATION.md](rank-math/RANK_MATH_INTEGRATION.md) |
| Schema Pro | [schema-pro/SCHEMA_PRO_INTEGRATION.md](schema-pro/SCHEMA_PRO_INTEGRATION.md) |
| Monster Insights | [monster-insights/MONSTER_INSIGHTS_INTEGRATION.md](monster-insights/MONSTER_INSIGHTS_INTEGRATION.md) |
| Elementor | [elementor/ELEMENTOR_INTEGRATION.md](elementor/ELEMENTOR_INTEGRATION.md) |
| Brizy | [brizy/BRIZY_INTEGRATION.md](brizy/BRIZY_INTEGRATION.md) |
| Formello | [formello/FORMELLO_INTEGRATION.md](formello/FORMELLO_INTEGRATION.md) |
| Limit Login Attempts Security | [limit-login-attempts/LIMIT_LOGIN_ATTEMPTS_INTEGRATION.md](limit-login-attempts/LIMIT_LOGIN_ATTEMPTS_INTEGRATION.md) |
| Bookly | [bookly/BOOKLY_INTEGRATION.md](bookly/BOOKLY_INTEGRATION.md) |
| Booked | [booked/BOOKED_INTEGRATION.md](booked/BOOKED_INTEGRATION.md) |
| Dokan implementation notes | [dokan/DOKAN_IMPLEMENTATION_SUMMARY.md](dokan/DOKAN_IMPLEMENTATION_SUMMARY.md) |

## Maintenance notes

When updating this document:

1. Keep the addon list synchronized with `inc/App/Integration/Integration.php`.
2. Verify IDs, settings, and activation checks against each addon's `info()` / `addSectionSettings()` methods.
3. Add a dedicated `documentation/integration/<addon>/` directory when an integration needs setup, architecture, troubleshooting, and test details.
4. Record known documentation drift. The current Dokan guide still mentions `dokan_format_date()` / `dokan_format_datetime()` hooks that are not present in the current `Dokan.php`; the source now relies on `HookDeactivator`, JavaScript overlays, and PHP request filters.

## License

GPL v2 or later.
