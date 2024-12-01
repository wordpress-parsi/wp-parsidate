jQuery(function ($) {
  // Initialize sortable
  $('.wpp-checkout-fields-list').sortable({
    handle: '.field-header',
    placeholder: 'wpp-checkout-field ui-sortable-placeholder',
    axis: 'y',
    update: function (event, ui) {
      // Update priorities after sorting
      $(this).find('.wpp-checkout-field').each(function (index) {
        $(this).find('.priority-input').val((index + 1) * 10);
      });
    }
  });

  // Toggle field settings
  $(document).on('click', '.wpp-checkout-field .field-header', function (e) {
    if (!$(e.target).hasClass('dashicons-menu')) {
      var $field = $(this).closest('.wpp-checkout-field');
      $field.toggleClass('active');
      $field.find('.dashicons-arrow-down-alt2').toggleClass('dashicons-arrow-up-alt2');
    }
  });

  // Toggle position select based on width
  $(document).on('change', '.field-width select', function () {
    const $positionField = $(this).closest('.field-settings').find('.field-position');

    if ($(this).val() === 'half') {
      $positionField.show();
    } else {
      $positionField.hide();
    }
  });

  // Initialize position fields visibility
  $('.field-width select').each(function () {
    const $positionField = $(this).closest('.field-settings').find('.field-position');

    if ($(this).val() === 'half') {
      $positionField.show();
    } else {
      $positionField.hide();
    }
  });
});