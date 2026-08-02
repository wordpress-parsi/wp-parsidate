/******/ (() => { // webpackBootstrap
/*!*******************************************!*\
  !*** ./assets/js-admin-src/jet-engine.js ***!
  \*******************************************/
jQuery(document).ready(function ($) {
  // Returns 'date', 'datetime' or false for a JetEngine meta box field.
  function wppdJetEngineFieldType($hidden, $visible) {
    if (wp_parsidate_jetengine_datepicker !== undefined) {
      for (const field of wp_parsidate_jetengine_datepicker.dateFields) {
        if ($hidden.attr('name') === field.name) {
          return field.input_type === 'datetime-local' ? 'datetime' : field.input_type;
        }
      }
    }

    // Datetime fields keep their settings on the hidden input.
    if ($hidden.data('datetime-settings')) {
      return 'datetime';
    }

    // JetEngine builds time/datetime pickers on the jQuery UI datepicker
    // instance, so time-only fields always carry a timepicker option.
    const inst = $visible.data('datepicker');

    if (inst && $.datepicker._get(inst, 'timepicker')) {
      return false;
    }

    const value = $hidden.val();

    if (!value) {
      return 'date';
    }

    return /^\d{4}[-/]\d{2}[-/]\d{2}$/.test(value) ? 'date' : false;
  }

  // Mark JetEngine date/datetime fields so jalaliDatepicker's selector can
  // match them.
  function wppdJetEngineMarkJalaliFields(scope) {
    scope = scope || $(document);

    scope.find('.cx-control input[type="hidden"]').each(function () {
      const $hidden = $(this);
      const $visible = $hidden.next('input.hasDatepicker');

      if (!$visible.length) {
        return;
      }

      const type = wppdJetEngineFieldType($hidden, $visible);

      if (!type) {
        return;
      }

      // The Jalali picker runs with time enabled (see datepicker.js), so
      // date-only fields must opt out per input.
      if (type === 'datetime') {
        $visible.attr('data-jdp-only-datetime', 'on');
      }

      $visible.addClass('wpp-jalali-date');

      // JetEngine attaches the jQuery UI picker at parse time, before
      // wp-parsidate can guard _showDatepicker, so its focus handler would
      // still open the jQuery UI popup next to the Jalali one. Destroy it;
      // the Jalali picker only needs our own wpp-jalali-date class.
      if (window.jQuery.datepicker && $visible.hasClass('hasDatepicker')) {
        if (type === 'datetime' && $.fn.datetimepicker) {
          $visible.datetimepicker('destroy');
        } else {
          $visible.datepicker('destroy');
        }
      }

      // Force the hidden Y-m-d (date) / Y-m-dTH:i (datetime) value onto the
      // visible input so the Jalali picker reads it. T -> space for datetime.
      if ($hidden.val()) {
        $visible.val(type === 'datetime' ? $hidden.val().replace('T', ' ') : $hidden.val());
      }
    });
  }

  // JetEngine separates the visible datepicker input from the hidden input that
  // actually holds the field value. Sync the visible Jalali value to the hidden
  // input (Y/m/d -> Y-m-d and space -> T for datetime).
  function wppdJetEngineSyncDateHandler() {
    const visible = $(this);
    const hidden = visible.prev('input[type="hidden"]');

    if (!hidden.length) {
      return;
    }

    const value = visible.val().replace(/\//g, '-').replace(' ', 'T');

    if (hidden.val() !== value) {
      hidden.val(value).trigger('change');
    }
  }

  function wppdJetEngineSyncDate(scope) {
    scope = scope || $(document);

    scope.find('.cx-control input.wpp-jalali-date').each(function () {
      $(this).off('change keyup', wppdJetEngineSyncDateHandler).on('change keyup', wppdJetEngineSyncDateHandler);
    });
  }

  setTimeout(function () {
    wppdJetEngineMarkJalaliFields();
    wppdJetEngineSyncDate();

    // JetEngine re-initializes date pickers (repeaters, dynamically added meta boxes)
    $(document).on('cx-control-init', function (event, data) {
      if (data && data.target) {
        setTimeout(function () {
          wppdJetEngineMarkJalaliFields($(data.target));
          wppdJetEngineSyncDate($(data.target));
        }, 300);
      }
    });
  }, 1000);
});

/******/ })()
;