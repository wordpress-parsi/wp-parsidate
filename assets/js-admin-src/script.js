jQuery(document).ready(function ($) {
  let wpColorPickerPalettes = ['#333', '#5de0f0', '#608bf7', '#7fff3f', '#00b700', '#fff200', '#ffae63', '#e64f6f', '#ef32e3', '#d1c1ff', '#873eff'],
    wpColorPickerOptions = {
      defaultColor: false, change: function (event, ui) {
        wppdActiveSettingsForm();
      }, clear: function () {
        wppdActiveSettingsForm();
      }, hide: true, palettes: wpColorPickerPalettes
    },
    settingsSubmitActive = false,
    wpMediaFrames = {};

  const wppdBody = $('body'),
    wppdContentWrap = $('#wppd-content-wrap'),
    wppdSettingsHeader = $('#wppd-settings-header'),
    wppdSettingsSidebar = $('#wppd-sidebar'),
    wppdSettingsDisplaySidebar = $('#wppd-display-sidebar'),
    wppdSettingsHideSidebar = $('#wppd-hide-sidebar'),
    wppdSettingsSectionLinks = $('.wppd-section-links ul'),
    settingsForm = document.getElementById('wppd-settings-form'),
    settingsFooter = document.getElementById('wppd-settings-footer'),
    settingsResetButton = document.getElementById("wppd-settings-reset-button");

  let wppdContentWrapPrevScrollPos = wppdContentWrap.scrollTop(),
    wppdContentWrapCurrentScrollPos = wppdContentWrapPrevScrollPos,
    wppdPageRefreshedAfter = parseInt(WpParsiDate.pageRefreshedAfter);

  /**
   * Page refresh
   * */
  if (wppdPageRefreshedAfter > 0) {
    setTimeout(function () {
      if (WpParsiDate.pageRefreshUrl !== null)
        window.location.href = WpParsiDate.pageRefreshUrl;
      else
        window.location.reload(true);
    }, wppdPageRefreshedAfter);
  }

  /**
   * Hide header on scroll down and sticky on scroll to top
   * */
  wppdContentWrap.scroll(function () {
    wppdContentWrapCurrentScrollPos = $(this).scrollTop();
    if (wppdContentWrapPrevScrollPos < wppdContentWrapCurrentScrollPos && wppdContentWrapCurrentScrollPos > wppdSettingsHeader.outerHeight())
      wppdSettingsHeader.addClass('hide-header');
    else
      wppdSettingsHeader.removeClass('hide-header');

    wppdContentWrapPrevScrollPos = wppdContentWrapCurrentScrollPos;
  });

  /**
   * Sidebar menu
   * */
  wppdSettingsDisplaySidebar.on('click', function (e) {
    e.preventDefault();
    wppdSettingsSidebar.addClass('wppd-mobile-sidebar');
    wppdBody.addClass('wppd-mobile-sidebar-active');
  });
  wppdSettingsHideSidebar.on('click', function (e) {
    e.preventDefault();
    wppdSettingsSidebar.removeClass('wppd-mobile-sidebar');
    wppdBody.removeClass('wppd-mobile-sidebar-active');
  });

  /**
   * Auto scroll to active section link
   * */
  if (wppdSettingsSectionLinks.length) {
    let wppdSectionActiveLink = wppdSettingsSectionLinks.find('.wppd-section-link-current'),
      wppdSectionOutsideActiveLink = wppdSettingsSectionLinks.outerWidth() - 100 < wppdSectionActiveLink.position().left,
      wppdSectionScrollActiveLink = wppdSectionActiveLink.position().left - wppdSectionActiveLink.outerWidth(true) - (wppdSettingsSectionLinks.outerWidth() / 3);

    if (isRtl) {
      wppdSectionOutsideActiveLink = wppdSectionActiveLink.position().left - 100 < 0;
    }

    if (wppdSectionOutsideActiveLink) {
      wppdSettingsSectionLinks.animate({
        scrollLeft: wppdSectionScrollActiveLink
      }, 500);
    }
  }

  function wppdActiveSettingsForm() {
    if (settingsSubmitActive) return;
    settingsSubmitActive = true;

    if (settingsFooter) settingsFooter.classList.remove('wppd-submit-inactive');

    window.addEventListener("beforeunload", wppdSettingsFormChangeAlert);
  }

  const wppdSettingsFormChangeAlert = (event) => {
    event.preventDefault();
    event.returnValue = true;
  }

  if (settingsForm) {
    if (settingsFooter) settingsFooter.classList.add('wppd-submit-inactive');

    settingsForm.addEventListener('change', function () {
      wppdActiveSettingsForm();
    });

    settingsForm.addEventListener('submit', function () {
      window.removeEventListener("beforeunload", wppdSettingsFormChangeAlert);
    });

    if (settingsResetButton) {
      settingsResetButton.addEventListener("click", () => {
        settingsSubmitActive = false;

        if (settingsFooter) settingsFooter.classList.add('wppd-submit-inactive');

        window.removeEventListener("beforeunload", wppdSettingsFormChangeAlert);
      });
    }
  }

  function wpColorPickerInit() {
    let wpColorPicker = $('.wppd-wp-color-picker,.wppd-color-palette').not('.wppd-gradient-select-color').find('input[type="text"]');

    if (wpColorPicker.length) {
      wpColorPicker.wpColorPicker(wpColorPickerOptions);

      setTimeout(function () {
        $('.wppd-color-palette[data-removable="1"]').each(function () {
          let wppdPickerContainer = $(this).find('.wp-picker-container');

          if (wppdPickerContainer.length > 0) {
            wppdPickerContainer.append('<button type="button" class="wppd-remove-color"><i class="wppd-icon-cross"></i></button>');
          }
        });
      }, 500);
    }
  }

  wpColorPickerInit();

  /** Media methods */
  function wppdMediaInit() {
    $('.wppd-media-image').unbind('click').on('click', function () {
      let $this = $(this),
        mediaSelectID = $this.attr('data-id'),
        mediaWrap = $this.closest('.wppd-media-wrap'),
        mediaInput = mediaWrap.find('input'),
        mediaImageIDs = mediaInput.val().split(',');

      const index = mediaImageIDs.indexOf(mediaSelectID);
      if (index > -1) {
        mediaImageIDs.splice(index, 1);
      }

      if (mediaImageIDs.length === 0) {
        mediaWrap.removeClass('wppd-media-selected');
      }

      mediaInput.val(mediaImageIDs.join(','));
      $this.remove();
      wppdActiveSettingsForm();
    });
  }

  $('.wppd-media-select').on('click', function () {
    let $this = $(this),
      mediaWrap = $this.closest('.wppd-media-wrap'),
      mediaWrapperID = mediaWrap.attr('id'),
      mediaTitle = mediaWrap.data('title'),
      mediaButton = mediaWrap.data('button'),
      mediaType = mediaWrap.data('type'),
      acceptExtensions = mediaWrap.data('accept-extensions'),
      multiSelection = parseInt(mediaWrap.data('multi-selection')) === 1,
      mediaMaxNumber = parseInt(mediaWrap.data('max-number')),
      mediaMultiple = mediaMaxNumber > 1,
      mediaImageContainer = mediaWrap.find('.wppd-media-images'),
      mediaInput = mediaWrap.find('input'),
      mediaSelected = 1;

    /*if (wpMediaFrames.hasOwnProperty(mediaWrapperID)) {
        wpMediaFrames[mediaWrapperID].open();
        return;
    }*/

    // Create a new media frame
    wpMediaFrames[mediaWrapperID] = wp.media({
      title: mediaTitle,
      button: {
        text: mediaButton
      },
      library: {
        type: mediaType
      },
      multiple: mediaMultiple
    });

    wpMediaFrames[mediaWrapperID].once('uploader:ready', function () {
      var uploader = wpMediaFrames[mediaWrapperID].uploader.uploader.uploader; // Upload manager

      //Updating allowed extensions
      uploader.setOption('filters',
        {
          mime_types: [
            {extensions: acceptExtensions}
          ]
        }
      );

      //Trick to reinit field
      uploader.setOption('multi_selection', multiSelection);
    });

    wpMediaFrames[mediaWrapperID].on('open', function () {
      let selection = wpMediaFrames[mediaWrapperID].state().get('selection'),
        mediaIDs = mediaInput.val().split(',');

      if (mediaIDs.length > 0) {
        mediaIDs.forEach(function (id) {
          let attachment = wp.media.attachment(id);
          attachment.fetch();
          selection.add(attachment ? [attachment] : []);
        });
      }
    });

    // When an image is selected in the media frame...
    wpMediaFrames[mediaWrapperID].on('select', function () {
      mediaImageContainer.html('');

      // Get media attachment details from the frame state
      let attachments = wpMediaFrames[mediaWrapperID].state().get('selection'),
        attachmentIDs = attachments.map(function (attachment) {
          if (mediaSelected <= mediaMaxNumber) {
            attachment = attachment.toJSON();
            let attachmentUrl = attachment.url;
            if (attachment.type !== 'image') {
              if (attachment.hasOwnProperty('image') && attachment.image.hasOwnProperty('src') && attachment.image.src) {
                attachmentUrl = attachment.image.src;
              } else {
                attachmentUrl = attachment.icon;
              }
            }
            let imageTitle = attachment.id + ': ' + (attachment.caption.length > 0 ? attachment.caption : attachment.title) + ' (' + attachment.type + ')';
            mediaImageContainer.append('<div class="wppd-media-image" data-id="' + attachment.id + '"><img src="' + attachmentUrl + '" title="' + imageTitle + '"/><span class="wppd-media-image-title">' + imageTitle + '</span></div>');
          }
          mediaSelected++;
          return attachment.id;
        });

      attachmentIDs = attachmentIDs.slice(0, mediaMaxNumber);

      mediaWrap.addClass('wppd-media-selected');

      // Send the attachment id to our hidden input
      mediaInput.val(attachmentIDs.join(','));

      wppdMediaInit();
      wppdActiveSettingsForm();
    });

    // Finally, open the modal on click
    wpMediaFrames[mediaWrapperID].open();
  });

  $('.wppd-media-remove-all').on('click', function () {
    let $this = $(this),
      mediaWrap = $this.closest('.wppd-media-wrap'),
      mediaInput = mediaWrap.find('input');

    mediaWrap.removeClass('wppd-media-selected');
    mediaInput.val('');
    wppdActiveSettingsForm();
  });

  wppdMediaInit();

  /**
   * Copy text
   * */
  function wppdCopyTextInit() {
    let wppdCopyText = $('.wppd-copy-text');
    if (navigator.clipboard) {
      wppdCopyText.each(function () {
        if ($(this).attr('title') === undefined)
          $(this).attr('title', WpParsiDate.copyText);
      })
      wppdCopyText.unbind('click').on('click', function () {
        let wppdCopyTextElm = $(this),
          wppdTextForCopy = wppdCopyTextElm.attr('data-text') !== undefined ? wppdCopyTextElm.attr('data-text') : wppdCopyTextElm.text();
        navigator.clipboard.writeText(wppdTextForCopy);
        wppdCopyTextElm.addClass('wppd-text-copied');

        setTimeout(function () {
          wppdCopyTextElm.removeClass('wppd-text-copied');
        }, 500);
      });
    } else {
      wppdCopyText.removeClass('wppd-copy-text');
    }
  }

  wppdCopyTextInit();
});
