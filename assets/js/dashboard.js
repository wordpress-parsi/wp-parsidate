/**
 * @output wp-admin/js/dashboard.js
 */

/* global pagenow, ajaxurl, postboxes, wpActiveEditor:true, ajaxWidgets */
/* global ajaxPopulateWidgets, quickPressLoad,  */
window.wp = window.wp || {};
window.communityEventsData = window.communityEventsData || {};

/**
 * Initializes the dashboard widget functionality.
 *
 * @since 2.7.0
 */
jQuery(function ($) {
  /**
   * These widgets can be populated via ajax.
   *
   * @since 2.7.0
   *
   * @type {string[]}
   *
   * @global
   */
  window.ajaxWidgets = ['wpp_dashboard_primary'];

  /**
   * Triggers widget updates via Ajax.
   *
   * @since 2.7.0
   *
   * @global
   *
   * @param {string} el Optional. Widget to fetch or none to update all.
   *
   * @return {void}
   */
  window.ajaxPopulateWidgets = function (el) {
    /**
     * Fetch the latest representation of the widget via Ajax and show it.
     *
     * @param {number} i Number of half-seconds to use as the timeout.
     * @param {string} id ID of the element which is going to be checked for changes.
     *
     * @return {void}
     */
    function show(i, id) {
      var p, e = $('#' + id + ' div.inside:visible').find('.widget-loading');
      // If the element is found in the dom, queue to load latest representation.
      if (e.length) {
        p = e.parent();
        setTimeout(function () {
          // Request the widget content.
          p.load(ajaxurl + '?action=wpp-dashboard-widgets&widget=' + id + '&pagenow=' + pagenow, '', function () {
            // Hide the parent and slide it out for visual fanciness.
            p.hide().slideDown('normal', function () {
              $(this).css('display', '');
            });
          });
        }, i * 500);
      }
    }

    // If we have received a specific element to fetch, check if it is valid.
    if (el) {
      el = el.toString();
      // If the element is available as Ajax widget, show it.
      if ($.inArray(el, ajaxWidgets) !== -1) {
        // Show element without any delay.
        show(0, el);
      }
    } else {
      // Walk through all ajaxWidgets, loading them after each other.
      $.each(ajaxWidgets, show);
    }
  };

  // Initially populate ajax widgets.
  ajaxPopulateWidgets();

  // Register ajax widgets as postbox toggles.
  
  $(document).on('postbox-toggled', function (e, postbox) {
      if (ajaxWidgets.indexOf(postbox.id) !== -1) {
          if(wppDashboardSlider){
              wppDashboardSlider.update();
          }
          ajaxPopulateWidgets();
      }
  });
});

jQuery(function ($) {
  'use strict';

  var communityEventsData = window.communityEventsData,
    dateI18n = wp.date.dateI18n,
    format = wp.date.format,
    sprintf = wp.i18n.sprintf,
    __ = wp.i18n.__,
    _x = wp.i18n._x,
    app;

  /**
   * Global Community Events namespace.
   *
   * @since 4.8.0
   *
   * @memberOf wp
   * @namespace wp.communityEvents
   */
  app = window.wp.communityEvents = /** @lends wp.communityEvents */{
    initialized: false,
    model: null,

    /**
     * Initializes the wp.communityEvents object.
     *
     * @since 4.8.0
     *
     * @return {void}
     */
    init: function () {
      if (app.initialized) {
        return;
      }

      var $container = $('#community-events');

      /*
       * When JavaScript is disabled, the errors container is shown, so
       * that "This widget requires JavaScript" message can be seen.
       *
       * When JS is enabled, the container is hidden at first, and then
       * revealed during the template rendering, if there actually are
       * errors to show.
       *
       * The display indicator switches from `hide-if-js` to `aria-hidden`
       * here in order to maintain consistency with all the other fields
       * that key off of `aria-hidden` to determine their visibility.
       * `aria-hidden` can't be used initially, because there would be no
       * way to set it to false when JavaScript is disabled, which would
       * prevent people from seeing the "This widget requires JavaScript"
       * message.
       */
      $('.community-events-errors')
        .attr('aria-hidden', 'true')
        .removeClass('hide-if-js');

      $container.on('click', '.community-events-toggle-location, .community-events-cancel', app.toggleLocationForm);

      /**
       * Filters events based on entered location.
       *
       * @return {void}
       */
      $container.on('submit', '.community-events-form', function (event) {
        var location = $('#community-events-location').val().trim();

        event.preventDefault();

        /*
         * Don't trigger a search if the search field is empty or the
         * search term was made of only spaces before being trimmed.
         */
        if (!location) {
          return;
        }

        app.getEvents({
          location: location
        });
      });

      if (communityEventsData && communityEventsData.cache && communityEventsData.cache.location && communityEventsData.cache.events) {
        app.renderEventsTemplate(communityEventsData.cache, 'app');
      } else {
        app.getEvents();
      }

      app.initialized = true;
    },

    /**
     * Toggles the visibility of the Edit Location form.
     *
     * @since 4.8.0
     *
     * @param {event|string} action 'show' or 'hide' to specify a state;
     *                              or an event object to flip between states.
     *
     * @return {void}
     */
    toggleLocationForm: function (action) {
      var $toggleButton = $('.community-events-toggle-location'),
        $cancelButton = $('.community-events-cancel'),
        $form = $('.community-events-form'),
        $target = $();

      if ('object' === typeof action) {
        // The action is the event object: get the clicked element.
        $target = $(action.target);
        /*
         * Strict comparison doesn't work in this case because sometimes
         * we explicitly pass a string as value of aria-expanded and
         * sometimes a boolean as the result of an evaluation.
         */
        action = 'true' === $toggleButton.attr('aria-expanded') ? 'hide' : 'show';
      }

      if ('hide' === action) {
        $toggleButton.attr('aria-expanded', 'false');
        $cancelButton.attr('aria-expanded', 'false');
        $form.attr('aria-hidden', 'true');
        /*
         * If the Cancel button has been clicked, bring the focus back
         * to the toggle button so users relying on screen readers don't
         * lose their place.
         */
        if ($target.hasClass('community-events-cancel')) {
          $toggleButton.trigger('focus');
        }
      } else {
        $toggleButton.attr('aria-expanded', 'true');
        $cancelButton.attr('aria-expanded', 'true');
        $form.attr('aria-hidden', 'false');
      }
    },

    /**
     * Sends REST API requests to fetch events for the widget.
     *
     * @since 4.8.0
     *
     * @param {Object} requestParams REST API Request parameters object.
     *
     * @return {void}
     */
    getEvents: function (requestParams) {
      var initiatedBy,
        app = this,
        $spinner = $('.community-events-form').children('.spinner');

      requestParams = requestParams || {};
      requestParams._wpnonce = communityEventsData.nonce;
      requestParams.timezone = window.Intl ? window.Intl.DateTimeFormat().resolvedOptions().timeZone : '';

      initiatedBy = requestParams.location ? 'user' : 'app';

      $spinner.addClass('is-active');

      wp.ajax.post('get-community-events', requestParams)
        .always(function () {
          $spinner.removeClass('is-active');
        })

        .done(function (response) {
          if ('no_location_available' === response.error) {
            if (requestParams.location) {
              response.unknownCity = requestParams.location;
            } else {
              /*
               * No location was passed, which means that this was an automatic query
               * based on IP, locale, and timezone. Since the user didn't initiate it,
               * it should fail silently. Otherwise, the error could confuse and/or
               * annoy them.
               */
              delete response.error;
            }
          }
          app.renderEventsTemplate(response, initiatedBy);
        })

        .fail(function () {
          app.renderEventsTemplate({
            'location': false,
            'events': [],
            'error': true
          }, initiatedBy);
        });
    },

    /**
     * Renders the template for the Events section of the Events & News widget.
     *
     * @since 4.8.0
     *
     * @param {Object} templateParams The various parameters that will get passed to wp.template.
     * @param {string} initiatedBy    'user' to indicate that this was triggered manually by the user;
     *                                'app' to indicate it was triggered automatically by the app itself.
     *
     * @return {void}
     */
    renderEventsTemplate: function (templateParams, initiatedBy) {
      var template,
        elementVisibility,
        $toggleButton = $('.community-events-toggle-location'),
        $locationMessage = $('#community-events-location-message'),
        $results = $('.community-events-results');

      templateParams.events = app.populateDynamicEventFields(
        templateParams.events,
        communityEventsData.time_format
      );

      /*
       * Hide all toggleable elements by default, to keep the logic simple.
       * Otherwise, each block below would have to turn hide everything that
       * could have been shown at an earlier point.
       *
       * The exception to that is that the .community-events container is hidden
       * when the page is first loaded, because the content isn't ready yet,
       * but once we've reached this point, it should always be shown.
       */
      elementVisibility = {
        '.community-events': true,
        '.community-events-loading': false,
        '.community-events-errors': false,
        '.community-events-error-occurred': false,
        '.community-events-could-not-locate': false,
        '#community-events-location-message': false,
        '.community-events-toggle-location': false,
        '.community-events-results': false
      };

      /*
       * Determine which templates should be rendered and which elements
       * should be displayed.
       */
      if (templateParams.location.ip) {
        /*
         * If the API determined the location by geolocating an IP, it will
         * provide events, but not a specific location.
         */
        $locationMessage.text(__('Attend an upcoming event near you.'));

        if (templateParams.events.length) {
          template = wp.template('community-events-event-list');
          $results.html(template(templateParams));
        } else {
          template = wp.template('community-events-no-upcoming-events');
          $results.html(template(templateParams));
        }

        elementVisibility['#community-events-location-message'] = true;
        elementVisibility['.community-events-toggle-location'] = true;
        elementVisibility['.community-events-results'] = true;

      } else if (templateParams.location.description) {
        template = wp.template('community-events-attend-event-near');
        $locationMessage.html(template(templateParams));

        if (templateParams.events.length) {
          template = wp.template('community-events-event-list');
          $results.html(template(templateParams));
        } else {
          template = wp.template('community-events-no-upcoming-events');
          $results.html(template(templateParams));
        }

        if ('user' === initiatedBy) {
          wp.a11y.speak(
            sprintf(
              /* translators: %s: The name of a city. */
              __('City updated. Listing events near %s.'),
              templateParams.location.description
            ),
            'assertive'
          );
        }

        elementVisibility['#community-events-location-message'] = true;
        elementVisibility['.community-events-toggle-location'] = true;
        elementVisibility['.community-events-results'] = true;

      } else if (templateParams.unknownCity) {
        template = wp.template('community-events-could-not-locate');
        $('.community-events-could-not-locate').html(template(templateParams));
        wp.a11y.speak(
          sprintf(
            /*
             * These specific examples were chosen to highlight the fact that a
             * state is not needed, even for cities whose name is not unique.
             * It would be too cumbersome to include that in the instructions
             * to the user, so it's left as an implication.
             */
            /*
             * translators: %s is the name of the city we couldn't locate.
             * Replace the examples with cities related to your locale. Test that
             * they match the expected location and have upcoming events before
             * including them. If no cities related to your locale have events,
             * then use cities related to your locale that would be recognizable
             * to most users. Use only the city name itself, without any region
             * or country. Use the endonym (native locale name) instead of the
             * English name if possible.
             */
            __('We couldn’t locate %s. Please try another nearby city. For example: Kansas City; Springfield; Portland.'),
            templateParams.unknownCity
          )
        );

        elementVisibility['.community-events-errors'] = true;
        elementVisibility['.community-events-could-not-locate'] = true;

      } else if (templateParams.error && 'user' === initiatedBy) {
        /*
         * Errors messages are only shown for requests that were initiated
         * by the user, not for ones that were initiated by the app itself.
         * Showing error messages for an event that user isn't aware of
         * could be confusing or unnecessarily distracting.
         */
        wp.a11y.speak(__('An error occurred. Please try again.'));

        elementVisibility['.community-events-errors'] = true;
        elementVisibility['.community-events-error-occurred'] = true;
      } else {
        $locationMessage.text(__('Enter your closest city to find nearby events.'));

        elementVisibility['#community-events-location-message'] = true;
        elementVisibility['.community-events-toggle-location'] = true;
      }

      // Set the visibility of toggleable elements.
      _.each(elementVisibility, function (isVisible, element) {
        $(element).attr('aria-hidden', !isVisible);
      });

      $toggleButton.attr('aria-expanded', elementVisibility['.community-events-toggle-location']);

      if (templateParams.location && (templateParams.location.ip || templateParams.location.latitude)) {
        // Hide the form when there's a valid location.
        app.toggleLocationForm('hide');

        if ('user' === initiatedBy) {
          /*
           * When the form is programmatically hidden after a user search,
           * bring the focus back to the toggle button so users relying
           * on screen readers don't lose their place.
           */
          $toggleButton.trigger('focus');
        }
      } else {
        app.toggleLocationForm('show');
      }
    },

    /**
     * Populate event fields that have to be calculated on the fly.
     *
     * These can't be stored in the database, because they're dependent on
     * the user's current time zone, locale, etc.
     *
     * @since 5.5.2
     *
     * @param {Array}  rawEvents  The events that should have dynamic fields added to them.
     * @param {string} timeFormat A time format acceptable by `wp.date.dateI18n()`.
     *
     * @returns {Array}
     */
    populateDynamicEventFields: function (rawEvents, timeFormat) {
      // Clone the parameter to avoid mutating it, so that this can remain a pure function.
      var populatedEvents = JSON.parse(JSON.stringify(rawEvents));

      $.each(populatedEvents, function (index, event) {
        var timeZone = app.getTimeZone(event.start_unix_timestamp * 1000);

        event.user_formatted_date = app.getFormattedDate(
          event.start_unix_timestamp * 1000,
          event.end_unix_timestamp * 1000,
          timeZone
        );

        event.user_formatted_time = dateI18n(
          timeFormat,
          event.start_unix_timestamp * 1000,
          timeZone
        );

        event.timeZoneAbbreviation = app.getTimeZoneAbbreviation(event.start_unix_timestamp * 1000);
      });

      return populatedEvents;
    },

    /**
     * Returns the user's local/browser time zone, in a form suitable for `wp.date.i18n()`.
     *
     * @since 5.5.2
     *
     * @param startTimestamp
     *
     * @returns {string|number}
     */
    getTimeZone: function (startTimestamp) {
      /*
       * Prefer a name like `Europe/Helsinki`, since that automatically tracks daylight savings. This
       * doesn't need to take `startTimestamp` into account for that reason.
       */
      var timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;

      /*
       * Fall back to an offset for IE11, which declares the property but doesn't assign a value.
       */
      if ('undefined' === typeof timeZone) {
        /*
         * It's important to use the _event_ time, not the _current_
         * time, so that daylight savings time is accounted for.
         */
        timeZone = app.getFlippedTimeZoneOffset(startTimestamp);
      }

      return timeZone;
    },

    /**
     * Get intuitive time zone offset.
     *
     * `Data.prototype.getTimezoneOffset()` returns a positive value for time zones
     * that are _behind_ UTC, and a _negative_ value for ones that are ahead.
     *
     * See https://stackoverflow.com/questions/21102435/why-does-javascript-date-gettimezoneoffset-consider-0500-as-a-positive-off.
     *
     * @since 5.5.2
     *
     * @param {number} startTimestamp
     *
     * @returns {number}
     */
    getFlippedTimeZoneOffset: function (startTimestamp) {
      return new Date(startTimestamp).getTimezoneOffset() * -1;
    },

    /**
     * Get a short time zone name, like `PST`.
     *
     * @since 5.5.2
     *
     * @param {number} startTimestamp
     *
     * @returns {string}
     */
    getTimeZoneAbbreviation: function (startTimestamp) {
      var timeZoneAbbreviation,
        eventDateTime = new Date(startTimestamp);

      /*
       * Leaving the `locales` argument undefined is important, so that the browser
       * displays the abbreviation that's most appropriate for the current locale. For
       * some that will be `UTC{+|-}{n}`, and for others it will be a code like `PST`.
       *
       * This doesn't need to take `startTimestamp` into account, because a name like
       * `America/Chicago` automatically tracks daylight savings.
       */
      var shortTimeStringParts = eventDateTime.toLocaleTimeString(undefined, {timeZoneName: 'short'}).split(' ');

      if (3 === shortTimeStringParts.length) {
        timeZoneAbbreviation = shortTimeStringParts[2];
      }

      if ('undefined' === typeof timeZoneAbbreviation) {
        /*
         * It's important to use the _event_ time, not the _current_
         * time, so that daylight savings time is accounted for.
         */
        var timeZoneOffset = app.getFlippedTimeZoneOffset(startTimestamp),
          sign = -1 === Math.sign(timeZoneOffset) ? '' : '+';

        // translators: Used as part of a string like `GMT+5` in the Events Widget.
        timeZoneAbbreviation = _x('GMT', 'Events widget offset prefix') + sign + (timeZoneOffset / 60);
      }

      return timeZoneAbbreviation;
    },

    /**
     * Format a start/end date in the user's local time zone and locale.
     *
     * @since 5.5.2
     *
     * @param {int}    startDate   The Unix timestamp in milliseconds when the the event starts.
     * @param {int}    endDate     The Unix timestamp in milliseconds when the the event ends.
     * @param {string} timeZone    A time zone string or offset which is parsable by `wp.date.i18n()`.
     *
     * @returns {string}
     */
    getFormattedDate: function (startDate, endDate, timeZone) {
      var formattedDate;

      /*
       * The `date_format` option is not used because it's important
       * in this context to keep the day of the week in the displayed date,
       * so that users can tell at a glance if the event is on a day they
       * are available, without having to open the link.
       *
       * The case of crossing a year boundary is intentionally not handled.
       * It's so rare in practice that it's not worth the complexity
       * tradeoff. The _ending_ year should be passed to
       * `multiple_month_event`, though, just in case.
       */
      /* translators: Date format for upcoming events on the dashboard. Include the day of the week. See https://www.php.net/manual/datetime.format.php */
      var singleDayEvent = __('l, M j, Y'),
        /* translators: Date string for upcoming events. 1: Month, 2: Starting day, 3: Ending day, 4: Year. */
        multipleDayEvent = __('%1$s %2$d–%3$d, %4$d'),
        /* translators: Date string for upcoming events. 1: Starting month, 2: Starting day, 3: Ending month, 4: Ending day, 5: Ending year. */
        multipleMonthEvent = __('%1$s %2$d – %3$s %4$d, %5$d');

      // Detect single-day events.
      if (!endDate || format('Y-m-d', startDate) === format('Y-m-d', endDate)) {
        formattedDate = dateI18n(singleDayEvent, startDate, timeZone);

        // Multiple day events.
      } else if (format('Y-m', startDate) === format('Y-m', endDate)) {
        formattedDate = sprintf(
          multipleDayEvent,
          dateI18n(_x('F', 'upcoming events month format'), startDate, timeZone),
          dateI18n(_x('j', 'upcoming events day format'), startDate, timeZone),
          dateI18n(_x('j', 'upcoming events day format'), endDate, timeZone),
          dateI18n(_x('Y', 'upcoming events year format'), endDate, timeZone)
        );

        // Multi-day events that cross a month boundary.
      } else {
        formattedDate = sprintf(
          multipleMonthEvent,
          dateI18n(_x('F', 'upcoming events month format'), startDate, timeZone),
          dateI18n(_x('j', 'upcoming events day format'), startDate, timeZone),
          dateI18n(_x('F', 'upcoming events month format'), endDate, timeZone),
          dateI18n(_x('j', 'upcoming events day format'), endDate, timeZone),
          dateI18n(_x('Y', 'upcoming events year format'), endDate, timeZone)
        );
      }

      return formattedDate;
    }
  };

  const wppWidget = $('#wpp_dashboard_primary');

  if (wppWidget.is(':visible')) {
    app.init();
  } else {
    $(document).on('postbox-toggled', function (event, postbox) {
      var $postbox = $(postbox);

      if ('wpp_dashboard_primary' === $postbox.attr('id') && $postbox.is(':visible')) {
        app.init();
      }
    });
  }
});

const slidesContainer = document.getElementById('wpp_sponsorship');
const sponsorshipGuid = document.getElementById('sponsorship-guide');
const wppsPlaceholder = document.getElementById('wpp_sponsorship_placeholder');
const wppsQuestion = document.querySelector('#sponsorship-guide .question');
const sponsorshipUl = document.querySelector('#sponsorship-guide ul');


async function wppInitSponsors() {
  try {
    const slidesData = Object.values(sponsors);

    wppsPlaceholder.remove();
    createSlides(slidesData);

    new KeenSlider(slidesContainer, {
      loop: true,
      duration: 5000,
      controls: true,
      indicators: true,
    });
  } catch (error) {
    console.error('Error fetching slides:', error);
  }
}

function createSlides(slidesData) {
  slidesData.forEach((slide) => {
    const slideElement = document.createElement('div');
    slideElement.classList.add('keen-slider__slide');
    slideElement.innerHTML = '<a href="' + slide.link + '" target="_blank"><img src="' + slide.image_url + '" alt="' + slide.image_alt + '" loading="lazy"></a>';
    slidesContainer.appendChild(slideElement);
  });

  if (slidesData.length > 1) {
    initWPPSSlider();
  }
}

function initWPPSSlider() {
  window.wppDashboardSlider = new KeenSlider(slidesContainer, {
    rtl: getComputedStyle(slidesContainer).direction,
    loop: true,
    duration: 3000,
    controls: true,
    indicators: true,
  }, [wppsNavigation, (slider) => {
    let timeout;
    let mouseOver = false;

    function clearNextTimeout() {
      clearTimeout(timeout);
    }

    function nextTimeout() {
      clearTimeout(timeout);
      if (mouseOver) {
        return;
      }
      timeout = setTimeout(() => {
        slider.next();
      }, 4000);
    }

    slider.on('created', () => {
      slider.container.addEventListener('mouseover', () => {
        mouseOver = true;
        clearNextTimeout();
      });
      slider.container.addEventListener('mouseout', () => {
        mouseOver = false;
        nextTimeout();
      });
      nextTimeout();
    });
    slider.on('dragStarted', clearNextTimeout);
    slider.on('animationEnded', nextTimeout);
    slider.on('updated', nextTimeout);
  },]);
}

function wppsNavigation(slider) {
  let wrapper, dots, arrowLeft, arrowRight;

  function markup(remove) {
    wrapperMarkup(remove);
    dotMarkup(remove);
    arrowMarkup(remove);
  }

  function removeElement(elment) {
    elment.parentNode.removeChild(elment);
  }

  function createDiv(className) {
    const div = document.createElement('div');
    let classNames = className.split(' ');
    classNames.forEach((name) => div.classList.add(name));
    return div;
  }

  function arrowMarkup(remove) {
    if (remove) {
      removeElement(arrowLeft);
      removeElement(arrowRight);
      return;
    }
    arrowLeft = createDiv('arrow arrow--left');
    arrowLeft.addEventListener('click', () => slider.prev());
    arrowRight = createDiv('arrow arrow--right');
    arrowRight.addEventListener('click', () => slider.next());

    wrapper.appendChild(arrowLeft);
    wrapper.appendChild(arrowRight);
  }

  function wrapperMarkup(remove) {
    if (remove) {
      const parent = wrapper.parentNode;
      while (wrapper.firstChild) {
        parent.insertBefore(wrapper.firstChild, wrapper);
      }
      removeElement(wrapper);
      return;
    }
    wrapper = createDiv('navigation-wrapper');
    slider.container.parentNode.prepend(wrapper);
    wrapper.appendChild(slider.container);
  }

  function dotMarkup(remove) {
    if (remove) {
      removeElement(dots);
      return;
    }
    dots = createDiv('dots');
    slider.track.details.slides.forEach((_e, idx) => {
      const dot = createDiv('dot');
      dot.addEventListener('click', () => slider.moveToIdx(idx));
      dots.appendChild(dot);
    });
    wrapper.appendChild(dots);
  }

  function updateClasses() {
    let slide = slider.track.details.rel;
    slide === 0 ? arrowLeft.classList.add('arrow--disabled') : arrowLeft.classList.remove('arrow--disabled');
    slide === slider.track.details.slides.length - 1 ? arrowRight.classList.add('arrow--disabled') : arrowRight.classList.remove('arrow--disabled');
    Array.from(dots.children).forEach(function (dot, idx) {
      idx === slide ? dot.classList.add('dot--active') : dot.classList.remove('dot--active');
    });
  }

  slider.on('created', () => {
    markup();
    updateClasses();
  });
  slider.on('optionsChanged', () => {
    console.log(2);
    markup(true);
    markup();
    updateClasses();
  });
  slider.on('slideChanged', () => {
    updateClasses();
  });
  slider.on('destroyed', () => {
    markup(true);
  });
}

wppInitSponsors();

wppsQuestion.addEventListener('click', function (event) {
  sponsorshipUl.classList.toggle('show');
  event.stopPropagation();
});

document.addEventListener('click', function (event) {
  if (!sponsorshipGuid.contains(event.target)) {
    sponsorshipUl.classList.remove('show');
  }
});
