# Bookly Integration for WP-Parsidate

## Overview

This integration adds Jalali (Shamsi) date selection and Jalali date presentation to **Bookly Free** and the installed **Bookly Pro** add-on. It follows the WP-Parsidate integration contract:

- Bookly keeps receiving and storing **Gregorian** dates.
- WP-Parsidate localizes the **visible** date UI and date text.
- Bookly Free is sufficient; Pro surfaces are optional and activated only when the matching Bookly Pro script is present.
- Pro is never required by the add-on metadata.

The implementation targets issue #326 and the installed versions checked during research: Bookly Free 28.8 and Bookly Pro 10.5.

## Architecture

### Presentation Layer

JavaScript adapters attach to Bookly-rendered calendars and date text. They use the shared WP-Parsidate engine:

- `window.WpPdJalaliDate.toJalaali()`
- `window.WpPdJalaliDate.toGregorian()`
- `window.jalaliDatepicker`

When a user selects a Jalali date, the adapter converts it back to Gregorian and passes it into Bookly's native handlers. Bookly still owns validation, availability, AJAX payloads, and database writes.

### Svelte-Safe Text Updates

Several modern Bookly and AI surfaces are Svelte-rendered. Svelte keeps references to its own text nodes, so replacing `textContent` detaches those nodes and later updates disappear. The adapters therefore mutate existing text nodes in place (`node.nodeValue` / `setTextContent`) wherever Svelte output is involved.

### PHP Date Filters

Some Bookly output already uses WordPress `date_i18n()` / Bookly `DateTime::formatDate()`. WP-Parsidate's global `FixDates` filter converts those values when Persian date settings are enabled, so not every localized date needs a Bookly-specific adapter.

## File Structure

```text
inc/App/Integration/Bookly.php
assets/js-src/bookly-jalali-calendar.js
assets/js-src/bookly-admin-jalali-calendar.js
assets/js-src/bookly-admin-jalali-daterange.js
assets/js-src/bookly-pro-calendar.js
assets/js-src/bookly-pro-services-form.js
assets/js-src/bookly-ai-assistant.js
assets/js-src/bookly-appearance-preview.js
tests/js/bookly-jalali-calendar.test.js
tests/js/bookly-admin-jalali-calendar.test.js
tests/js/bookly-admin-jalali-daterange.test.js
tests/js/bookly-pro-calendar.test.js
tests/js/bookly-pro-services-form.test.js
tests/js/bookly-ai-assistant.test.js
tests/js/bookly-appearance-preview.test.js
```

Compiled assets are generated in `assets/js/`.

## Features and Coverage

### Bookly Free

| Surface | Status | Date contract |
|---|---|---|
| `[bookly-form]` service-step date picker | Implemented | Jalali picker selection is converted to Gregorian and applied through Bookly's native calendar, preserving validation and AJAX behavior. |
| `[bookly-form]` time-step calendar | Implemented | Bookly remains responsible for selected dates, availability, and requests. |
| `[bookly-form]` dropdown date mode | Implemented | `prepareServiceDate()` falls back to the dropdown control and hides it behind the Jalali picker. Bookly still receives Gregorian values. |
| `[bookly-ai-assistant-form]` chat dates | Implemented | Chat tool-result dates are localized in place. `BooklyAiAssistant.showForm` is patched before widget mount so the payment card uses ISO `YYYY-MM-DD`, then visible ISO text is localized across the whole widget. |

### Bookly Admin

| Surface | Status | Date contract |
|---|---|---|
| Admin calendar datepicker | Implemented | Jalali selection is sent to Bookly as Gregorian. |
| Calendar week-header day numbers | Implemented | Rendered from machine-readable Gregorian dates into Jalali display. |
| Appointment detail date | Implemented | Bookly's ISO value remains unchanged; only visible date text is localized. |
| Admin date-range filters | Implemented | `daterangepicker` instances are wrapped and displayed through a Jalali picker; Gregorian values are pushed back through Bookly APIs. |
| Appointment create/edit dialog | Covered by date-range adapter | The dialog's `singleDatePicker` instances are decorated by the same adapter. A regression test verifies single-date Gregorian push-back. |
| Appearance step-by-step preview | Implemented | Static mockup day numbers are converted from reconstructed Gregorian dates to Jalali. Month/weekday labels and the service date input already use `date_i18n()`. |

### Bookly Pro

| Surface | Status | Date contract |
|---|---|---|
| `[bookly-calendar]` frontend event calendar | Implemented, optional | Loaded only when Pro is active and the calendar script is enqueued. Jalali picker selection is passed to Pro as Gregorian. |
| `[bookly-services-form]` | Implemented, optional | Modern calendar title and day numbers are Jalali; cells retain Gregorian dates for Bookly selection and availability. |
| `[bookly-search-form]` | Implemented, optional | Same modern-form adapter; appointment summaries are localized including portalled modal header/body containers. |
| `[bookly-staff-form]` and `[bookly-tags-form]` | Implemented, optional | Same modern-form adapter; calendar titles and summaries localized. |
| `[bookly-appointments-list]` | Covered by global date filters | Date cells use `DateTime::formatDate()` → `date_i18n()`, including AJAX rows. Live check showed Jalali output; no dedicated adapter required. |
| `[bookly-checkout-form]` | No adapter required | Standalone checkout form has no date display; its date formatting helpers are unused. |
| `[bookly-cancellation-confirmation]` | No adapter required | Template contains confirm/cancel controls and error text only. |

## Implementation Details

### Main PHP Class: `../../../inc/App/Integration/Bookly.php`

**Key responsibilities:**

- Detects Bookly script handles in `wp_footer` and `admin_footer`.
- Enqueues the correct adapter for each surface.
- Loads the shared Jalali engine only when needed.
- Localizes month names, placeholders, today, and display format values.
- Gates Pro assets by `Bookly\Lib\Config::proActive()` and shortcode-specific script handles.
- Resolves each surface through its own toggle, grouped in the settings UI under **Frontend** and **Admin**.

**Settings:**

Activating the add-on is the global switch. `Addon::registerWpFooterAction()` and `Addon::registerAdminEnqueueScriptsAction()` both require `isActivated()`, so deactivating Bookly in the add-ons list leaves every Bookly surface on Gregorian dates without any extra configuration. Each surface is then controlled independently:

| Setting | Group | Default | Description |
|---|---|---|---|
| `jalali_booking_form` | Frontend | `true` | Bookly Free `[bookly-form]` service and time steps, including dropdown date mode. |
| `jalali_pro_calendar` | Frontend | `true` | Pro `[bookly-calendar]`. Requires Bookly Pro. |
| `jalali_pro_forms` | Frontend | `true` | Pro `[bookly-services-form]`, `[bookly-search-form]`, `[bookly-staff-form]`, `[bookly-tags-form]`. Requires Bookly Pro. |
| `jalali_ai_assistant` | Frontend | `true` | Bookly AI assistant chat and payment card dates. Requires the Bookly Cloud AI assistant. |
| `jalali_admin_calendar` | Admin | `true` | Admin calendar headers and appointment card dates. |
| `jalali_admin_daterange` | Admin | `true` | Admin date-range filters and appointment create/edit dialog date fields. |
| `jalali_appearance_preview` | Admin | `true` | Appearance step-by-step preview calendar day numbers. |

Gate resolution is implemented once in `isSurfaceEnabled()`:

```php
private function isSurfaceEnabled( string $key ): bool {
  return (bool) $this->getSetting( $key, true );
}
```

The pre-split `jalali_booking_calendar` key is no longer read or registered; a value saved by an earlier version stays in the option row and is ignored.

### JavaScript Adapters

#### `bookly-jalali-calendar.js`

- Targets `.bookly-form`.
- Handles `.bookly-js-date-from` in inline-calendar or dropdown mode.
- Handles `.bookly-js-slot-calendar`.
- Keeps Bookly's `date_from` and AJAX behavior Gregorian.

#### `bookly-admin-jalali-calendar.js`

- Targets `.bookly-js-calendar .ec-title`, `.ec-col-head time`, and appointment-card date text.
- Overrides `BooklyL10nAppointmentCard.moment_format_date` to `YYYY-MM-DD` so ISO dates are visible and localizable.

#### `bookly-admin-jalali-daterange.js`

- Wraps `jQuery.fn.daterangepicker`.
- Covers admin filters, dashboard/payment/SMS pages, booking wizard, customer dialog, and appointment dialog date fields.
- Overlays a Jalali field and pushes selected values back through `setStartDate`, `setEndDate`, picker callbacks, and `apply.daterangepicker`.
- Handles both range and `singleDatePicker` modes.

#### `bookly-pro-calendar.js`

- Targets `.bookly-frontend-calendar`.
- Localizes calendar titles and day headers.
- Passes Jalali selections back as Gregorian dates.

#### `bookly-pro-services-form.js`

- Targets services, search, staff, and tags modern form containers.
- Localizes calendar titles, day numbers, and appointment summaries.
- Sets `BooklyL10nModernBookingForm.moment_format_date = 'YYYY-MM-DD'`.
- Mutates Svelte text nodes in place to keep re-renders working.

#### `bookly-ai-assistant.js`

- Patches `BooklyAiAssistant.showForm` at footer script execution time, before Bookly's body-template `DOMContentLoaded` handler mounts the widget.
- Forces `moment_format_date = 'YYYY-MM-DD'` so the payment card date is matchable.
- Scans the whole `.bookly-ai-assistant-app` widget and localizes visible ISO dates by mutating text nodes.
- Does not alter AI tool arguments, checkout payloads, or stored appointments.

#### `bookly-appearance-preview.js`

- Runs only when `bookly-appearance.js` is enqueued.
- Reconstructs each static preview calendar cell's Gregorian date from PHP's `first day of this month → monday this week` start.
- Converts each day to a Jalali day number and mutates the existing text node.

## Usage

### Activation

1. Install and activate **Bookly Free** (`bookly-responsive-appointment-booking-tool`).
2. Optionally install and activate **Bookly Pro** (`bookly-addon-pro`).
3. Activate the **Bookly** add-on in **WP-Parsidate → Add-ons**. Activation switches the integration on; no separate master setting exists.
4. Configure **WP-Parsidate → Integration → Bookly** and enable only the surfaces you need under the **Frontend** and **Admin** groups.
5. Ensure Persian date settings are enabled for `date_i18n()`-based surfaces.

### Settings

Navigate to **WordPress Admin → WP-Parsidate → Integration → Bookly**.

Recommended settings:

- ✅ Bookly add-on: **Activated**
- ✅ The individual Frontend/Admin surfaces you actually use: **Enabled**
- ✅ Persian date setting: **Enabled**
- ✅ WordPress locale `fa_IR` for sites expecting Persian labels and date formats

### Frontend Shortcodes

The following Bookly shortcodes are covered:

```text
[bookly-form]
[bookly-ai-assistant-form]
[bookly-calendar]              (Pro)
[bookly-services-form]         (Pro)
[bookly-search-form]           (Pro)
[bookly-staff-form]            (Pro)
[bookly-tags-form]             (Pro)
[bookly-appointments-list]     (Pro, via date_i18n)
```

No dedicated behavior is required for:

```text
[bookly-checkout-form]
[bookly-cancellation-confirmation]
```

## Compatibility

### Tested/Audited With

- Bookly Free 28.8
- Bookly Pro 10.5
- WordPress admin and frontend surfaces in the local test workspace
- Node-based JS unit tests for all adapters

### Known Conflicts

No known date-storage conflicts. Bookly continues to store Gregorian dates.

Potential display-only limitations:

- Bookly's own admin strings (`Today`, `Month`, `Week`, `Day`, `Date This month`) remain English.
- `[bookly-appointments-list]` prints no date column unless the shortcode supplies `columns`, for example `columns="date,time"`.

## Testing

Run the JavaScript test suite, which covers these adapters along with the shared Jalali engine and datepicker:

```bash
npm run test:js
```

### Manual Verification Checklist

- [ ] Bookly Free `[bookly-form]` service date selection
- [ ] Bookly Free dropdown date mode
- [ ] Time-step calendar navigation and booking
- [ ] Admin calendar title and week-day numbers
- [ ] Admin date-range filters
- [ ] Admin appointment create/edit dialog
- [ ] Admin Appearance preview calendar
- [ ] Pro `[bookly-calendar]`
- [ ] Pro modern services/search/staff/tags forms
- [ ] Pro `[bookly-appointments-list]` with `columns="date,time"`
- [ ] AI assistant chat and payment-card dates with active Bookly Cloud AI

Items requiring special environments:

- AI assistant live verification requires an active Bookly Cloud `ai` product.
- Admin dialog and Appearance preview live verification require an authenticated wp-admin session.

## Maintenance Notes

Update the relevant adapter when Bookly changes:

1. Shortcode script handles
2. Calendar container classes
3. `daterangepicker` integration
4. Svelte component class names or text node structure
5. `moment_format_date` localization names
6. AI assistant `showForm` signature or mount timing

## Changelog

### 2026-11-24

- Split the single integration switch into seven per-surface toggles grouped under Frontend and Admin in the settings page.
- Removed the `jalali_booking_calendar` setting: activating the Bookly add-on is now the only global switch, and a previously saved value for that key is ignored.

### 2026-11-23

- Added Bookly AI assistant presentation adapter.
- Added admin appointment single-date regression coverage.
- Added admin Appearance preview calendar adapter.
- Fixed missing `setTimeout` mock in Pro services form tests.
- Localized AI payment card dates by forcing ISO output before Svelte mount.
- Patched AI `showForm` before widget mount to handle real script ordering.
- Documented full Bookly Free/Pro shortcode audit.

## License

GPL v2 or later.

## Support

- **Issues**: [GitHub Issues](https://github.com/wordpress-parsi/wp-parsidate/issues)
- **Documentation**: [WP-Parsidate Wiki](https://github.com/wordpress-parsi/wp-parsidate/wiki)
