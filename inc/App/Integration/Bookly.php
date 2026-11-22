<?php
/**
 * Adds Jalali date selection to Bookly Free and optional Pro calendar surfaces.
 *
 * @package WP-Parsidate
 * @subpackage Plugins/Bookly
 */

namespace WPParsidate\App\Integration;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Addons\Addon;
use WPParsidate\Core\Names;
use WPParsidate\Helper\Assets;

class Bookly extends Addon {
  public string $addonID = 'bookly';
  public string $currentTab = 'integration';

  public function wpFooterAction(): void {
    global $wp_locale;
    $hasFreeBookingForm = wp_script_is( 'bookly-bookly.min.js', 'enqueued' );
    $hasProCalendar     = wp_script_is( 'bookly-frontend-calendar.js', 'enqueued' );
    $hasAiAssistant     = wp_script_is( 'bookly-ai-assistant.js', 'enqueued' );
    $proIsActive        = class_exists( 'Bookly\Lib\Config' ) && \Bookly\Lib\Config::proActive();
    $proFormHandles     = [ 'bookly-services-form.js', 'bookly-search-form.js', 'bookly-staff-form.js', 'bookly-tags-form.js' ];
    $enqueuedProForms   = [];
    if ( $proIsActive ) {
      foreach ( $proFormHandles as $proFormHandle ) {
        if ( wp_script_is( $proFormHandle, 'enqueued' ) ) {
          $enqueuedProForms[] = $proFormHandle;
        }
      }
    }
    $hasProModernForm   = ! empty( $enqueuedProForms );

    if (
      is_admin() ||
      ! $this->getSetting( 'jalali_booking_calendar', true ) ||
      ( ! $hasFreeBookingForm && ( ! $hasProCalendar || ! $proIsActive ) && ! $hasProModernForm && ! $hasAiAssistant )
    ) {
      return;
    }

    $pluginVersion    = Assets::getVersion();
    $debugName        = WP_PARSI_DEBUG_MODE ? '' : '.min';
    $engineHandle     = WP_PARSI_KEY . '_jalali_date';
    $datepickerHandle = WP_PARSI_KEY . '_datepicker';
    $scriptHandle     = WP_PARSI_KEY . '_bookly_jalali_calendar';
    $months           = Names::getMonths();
    array_shift( $months );

    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy public hook name, kept for backward compatibility.
    do_action( 'wp_parsidate_jalali_datepicker_enqueue', 'bookly' );

    if ( ! wp_script_is( $engineHandle, 'enqueued' ) ) {
      wp_enqueue_script(
        $engineHandle,
        Assets::url( "js-admin/jalali-date$debugName.js" ),
        [],
        $pluginVersion,
        [ 'in_footer' => true ]
      );
    }

    if ( $hasFreeBookingForm ) {
      wp_enqueue_script(
        $scriptHandle,
        Assets::url( "js/bookly-jalali-calendar$debugName.js" ),
        [ 'jquery', 'bookly-frontend-globals', 'bookly-bookly.min.js', $engineHandle, $datepickerHandle ],
        $pluginVersion,
        [ 'in_footer' => true ]
      );
      wp_enqueue_style(
        $scriptHandle,
        Assets::url( 'css-admin/bookly.css' ),
        [ WP_PARSI_KEY . '_jalali_datepicker' ],
        $pluginVersion
      );

      // The form is detected in wp_footer, after WordPress has printed head styles.
      wp_print_styles( $scriptHandle );
      wp_localize_script( $scriptHandle, 'WpPdBooklyJalali', [
        'months'      => array_values( $months ),
        'weekdays'    => isset( $wp_locale->weekday_abbrev ) ? array_values( $wp_locale->weekday_abbrev ) : [],
        'firstDay'    => (int) get_option( 'start_of_week', 0 ),
        'today'       => current_time( 'Y-m-d' ),
        'calendar'    => esc_html__( 'Jalali calendar', 'wp-parsidate' ),
        'unavailable' => esc_html__( 'This date is unavailable in Bookly. Choose another date.', 'wp-parsidate' ),
      ] );
    }

    if ( $hasProCalendar && $proIsActive ) {
      $proScriptHandle = WP_PARSI_KEY . '_bookly_pro_calendar_jalali';
      wp_enqueue_script(
        $proScriptHandle,
        Assets::url( "js/bookly-pro-calendar$debugName.js" ),
        [ 'jquery', 'bookly-frontend-calendar.js', $engineHandle, $datepickerHandle ],
        $pluginVersion,
        [ 'in_footer' => true ]
      );
      wp_print_styles( WP_PARSI_KEY . '_jalali_datepicker' );
    }

    if ( $hasProModernForm ) {
      $servicesScriptHandle   = WP_PARSI_KEY . '_bookly_pro_services_form_jalali';
      $modernFormDependencies = array_merge(
        [ 'bookly-modern-booking-form.js', $engineHandle, $datepickerHandle ],
        $enqueuedProForms
      );

      wp_enqueue_script(
        $servicesScriptHandle,
        Assets::url( "js/bookly-pro-services-form$debugName.js" ),
        $modernFormDependencies,
        $pluginVersion,
        [ 'in_footer' => true ]
      );
      wp_enqueue_style(
        $servicesScriptHandle,
        Assets::url( 'css-admin/bookly.css' ),
        [ WP_PARSI_KEY . '_jalali_datepicker' ],
        $pluginVersion
      );
      wp_print_styles( $servicesScriptHandle );
      wp_print_styles( WP_PARSI_KEY . '_jalali_datepicker' );
      wp_localize_script( $servicesScriptHandle, 'WpPdBooklyProServicesFormSettings', [
        'months'   => array_values( $months ),
        'firstDay' => (int) get_option( 'start_of_week', 0 ),
      ] );
    }

    if ( $hasAiAssistant ) {
      $aiScriptHandle = WP_PARSI_KEY . '_bookly_ai_assistant_jalali';
      wp_enqueue_script(
        $aiScriptHandle,
        Assets::url( "js/bookly-ai-assistant$debugName.js" ),
        [ 'bookly-ai-assistant.js', $engineHandle ],
        $pluginVersion,
        [ 'in_footer' => true ]
      );
      wp_localize_script( $aiScriptHandle, 'WpPdBooklyAiAssistantSettings', [
        'months' => array_values( $months ),
        'format' => 'j F Y',
      ] );
    }
  }

  public function adminEnqueueScriptsAction(): void {
    add_action( 'admin_footer', [ $this, 'adminFooterAction' ], PHP_INT_MAX );
  }

  public function adminFooterAction(): void {
    $hasCalendarJs  = wp_script_is( 'bookly-calendar.js', 'enqueued' );
    $hasCardJs      = wp_script_is( 'bookly-appointment-card.js', 'enqueued' );
    $hasDateRangeJs = wp_script_is( 'bookly-daterangepicker.js', 'enqueued' );

    if (
      is_network_admin() ||
      ! $this->getSetting( 'jalali_booking_calendar', true ) ||
      ( ! $hasCalendarJs && ! $hasCardJs && ! $hasDateRangeJs )
    ) {
      return;
    }

    $pluginVersion = Assets::getVersion();
    $debugName     = WP_PARSI_DEBUG_MODE ? '' : '.min';
    $engineHandle  = WP_PARSI_KEY . '_jalali_date';
    $scriptHandle  = WP_PARSI_KEY . '_bookly_admin_jalali_calendar';
    $pickerHandle  = WP_PARSI_KEY . '_jalali_datepicker';
    $dependencies  = [ 'jquery', $engineHandle, $pickerHandle ];

    do_action( 'wp_parsidate_jalali_datepicker_enqueue', 'bookly-admin' );

    if ( ! wp_script_is( $engineHandle, 'enqueued' ) ) {
      wp_enqueue_script(
        $engineHandle,
        Assets::url( "js-admin/jalali-date$debugName.js" ),
        [],
        $pluginVersion,
        [ 'in_footer' => true ]
      );
    }

    $months = Names::getMonths();
    array_shift( $months );

    if ( $hasCalendarJs || $hasCardJs ) {
      foreach ( [ 'bookly-calendar.js', 'bookly-appointment-card.js' ] as $booklyHandle ) {
        if ( wp_script_is( $booklyHandle, 'enqueued' ) ) {
          $dependencies[] = $booklyHandle;
        }
      }

      wp_enqueue_script(
        $scriptHandle,
        Assets::url( "js/bookly-admin-jalali-calendar$debugName.js" ),
        $dependencies,
        $pluginVersion,
        [ 'in_footer' => true ]
      );

      wp_localize_script( $scriptHandle, 'WpPdBooklyAdminJalali', [
        'months' => array_values( $months ),
      ] );
    }

    // Bookly's admin date filters and the appointment dialog date field are
    // daterangepicker instances; they are presented in Jalali here while the
    // values Bookly receives stay Gregorian.
    if ( $hasDateRangeJs ) {
      $rangeHandle = WP_PARSI_KEY . '_bookly_admin_jalali_daterange';

      wp_enqueue_script(
        $rangeHandle,
        Assets::url( "js/bookly-admin-jalali-daterange$debugName.js" ),
        [ 'jquery', $engineHandle, $pickerHandle, 'bookly-daterangepicker.js' ],
        $pluginVersion,
        [ 'in_footer' => true ]
      );

      wp_localize_script( $rangeHandle, 'WpPdBooklyAdminJalaliDaterange', [
        'months'           => array_values( $months ),
        'separator'        => ' - ',
        'rangePlaceholder' => esc_html__( 'Jalali range: 1405-09-01 - 1405-09-15', 'wp-parsidate' ),
        'datePlaceholder'  => esc_html__( 'Jalali date: 1405-09-01', 'wp-parsidate' ),
      ] );
    }

    wp_print_styles( $pickerHandle );
  }

  public function addSectionSettings( $sections ): array {
    $sections[ $this->addonID ] = [
      'title'        => esc_html__( 'Bookly', 'wp-parsidate' ),
      'desc'         => esc_html__( 'ParsiDate integration for Bookly Free', 'wp-parsidate' ),
      'settings_key' => $this->addonID,
      'settings'     => [
        'jalali_booking_calendar' => [
          'id'       => 'jalali_booking_calendar',
          'title'    => esc_html__( 'Jalali booking and admin calendar', 'wp-parsidate' ),
          'desc'     => esc_html__( 'Use Jalali date selection in the Bookly Free booking form, admin calendar, and optional Pro calendars, services, search, staff, and tags forms. Bookly continues to receive Gregorian dates.', 'wp-parsidate' ),
          'type'     => 'toggle',
          'default'  => true,
          'sanitize' => 'bool',
        ],
      ],
    ];

    return $sections;
  }

  public function info(): array {
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256" preserveAspectRatio="none" style="display:block" viewBox="0 0 1024 1024"><defs><linearGradient id="a" x1="384.243" x2="398.028" y1="809.818" y2="798.767" gradientUnits="userSpaceOnUse"><stop class="stop0" offset="0" stop-color="#efe9d7"/><stop class="stop1" offset="1" stop-color="#f7e5de"/></linearGradient></defs><path fill="#ff5900" d="M69.888 0H958c4.8 7.198 29.963 17.815 40.876 28.71 12.543 12.525 16.263 24.946 25.123 34.846v907.185c-16.87 26.882-24.583 40.099-57.274 52.419-.862.32-.9.31-1.67.84H64.251c-13.41-9.58-25.204-13.5-36.814-25.935C16.27 986.106 12.459 971.36 0 961.649V68.928c10.504-7.775 17.851-28.504 30.492-40.861C42.282 16.542 63.28 9.797 69.888 0"/><path fill="#201712" d="M69.888 0H958c4.8 7.198 29.963 17.815 40.876 28.71 12.543 12.525 16.263 24.946 25.123 34.846V348.97L0 349.028v-280.1c10.504-7.775 17.851-28.504 30.492-40.861C42.282 16.542 63.28 9.797 69.888 0"/><path fill="#fefefe" d="M265.663 156.274c19.851-42.501 49.56-52.31 93.563-38.052 11.942 3.869 33.407 31.547 36.206 42.949 14.241 58.002-53.129 93.145-98.981 70.939a61.43 61.43 0 0 1-30.556-33.874c-10.955-14.625-12.356-27.457-.232-41.962"/><path fill="#a79e8f" d="M265.663 156.274c.494 10.795.885 31.372.232 41.962-10.955-14.625-12.356-27.457-.232-41.962"/><path fill="#201712" d="M318.629 140.137c19.56-1.442 40.836 2.362 45.823 24.298 5.317 23.386-2.991 44.663-27.39 50.336-21.15 1.668-38.038-2.088-44.253-24.975-6.243-22.993 2.255-43.264 25.82-49.659"/><path fill="#fefefe" d="M467.609 113.379c16.594-2.019 37.159 1.286 51.14 11.633 25.87 19.146 33.1 62.141 14.265 88.35-10.22 14.221-30.814 21.936-47.692 24.449-19.111.895-39.683-.917-54.816-13.3-27.494-22.497-27.599-64.551-5.667-90.885 12.883-15.469 24.115-18.121 42.77-20.247"/><path fill="#201712" d="M469.872 140.121c19.051-1.691 37.311 5.766 40.785 25.703 4.301 24.686-4.191 43.994-29.364 49.248a47.35 47.35 0 0 1-25.362-5.155c-19.286-9.886-22.799-32.451-14.443-51.796 4.175-9.664 18.415-15.187 28.384-18"/><path fill="#fefefe" d="M128.888 113.985a442 442 0 0 1 18.386.028c33.124 1.107 94.158-11.307 94.429 35.392.061 10.358-11.471 19.3-9.644 26.092 11.388 9.695 13.788 10.173 14.816 26.112 3.138 48.681-70.708 32.122-101.214 35.582-4.008.239-12.374-.146-16.692-.225-1.494-38.166-.585-84.574-.081-122.981"/><path fill="#706b6b" d="M128.888 113.985a442 442 0 0 1 18.386.028c-3.613.5-4.93.028-7.265 2.106-.505 10.183-.804 117.226.189 119.565 2.26.838 3.093 1.08 5.463 1.507-4.008.239-12.374-.146-16.692-.225-1.494-38.166-.585-84.574-.081-122.981"/><path fill="#201712" d="M164.804 185.099c11.883-.146 42.016-2.061 50.646 3.255 4.041 4.943 5.36 18.328.703 24.119-17.388 4.884-33.304 4.377-51.207 4.333a512 512 0 0 1-.142-31.707M165.365 139.554c10.593-.174 66.02-4.969 45.232 20.621-14.502 6.331-30.009 4.991-45.649 4.776-.265-6.491-.818-19.385.417-25.397"/><path fill="#fefefe" d="M594.114 170.34c6.14-6.635 14.472-14.305 20.02-21.089 25.323-30.956 22.653-35.315 63.121-35.384-6.996 7.569-13.3 19.528-20.92 27.207-9.746 9.821-20.298 16.673-29.565 27.15 8.251 12.769 22.346 28.675 32.323 40.38 6.809 8.805 15.225 20.344 22.771 28.213-25.232.229-36.747 3.092-52.645-19.024-4.103-5.707-10.601-12.428-14.621-18.935-2.17-3.513-2.967-8.452-5.569-9.912l-1.783.819c-13.22 27.469-13.307 13.964-13.437 47.1l-30.91-.143c-.315-12.094-1.816-118.252.686-121.646l4.423-.65 26.084-.221zM885.544 113.446c1.74-.42 2.048-.19 3.712.366 1.075 9.374-35.936 63.34-39.581 77.237-4.213 16.063-3.12 29.223-3.083 45.819-9.527-.56-21.249-.13-30.947-.018 1.152-40.218 2.845-43.215-18.466-75.65-7.736-13.159-20.05-38.476-30.018-47.358 11.669.241 22.281.492 33.938 1.383 11.817 18.21 14.708 36.928 33.123 52.333 12.662-15.894 21.913-42.655 31.173-52.681 5.247-.785 14.575-1.092 20.149-1.431M696.98 114.518l26.069-.405.087 100.567c18.398-.939 42.469-.282 60.886.247.113 4.242.948 18.311-.494 21.337l-1.778.208c-30.031.681-60.075.67-90.106-.032.385-12.967-.563-118.518 1.336-121.433zM270.608 588.773c47.774.602 97.227-.184 145.22-.182l332.004.221c-1.623 76.524.232 153.691-.142 230.269-.11 22.559 3.8 34.984-11.385 53.318-14.719 13.944-24.797 13.626-44.763 13.654-85.43.119-170.916.046-256.342.07l-78.735.007c-17.755-.002-36.637.629-54.165-1.753-14.368-1.952-29.58-18.084-30.608-32.819-1.879-26.956-1.188-54.358-1.188-81.413z"/><path fill="#ff5900" d="M395.755 786.339c10.438-.229 22.324-.836 32.535.781.764 12.518.511 19.975-.446 32.45-11.788.98-19.871 1.276-31.69.839l-4.711-.478c-7.434-4.233-7.29-23.207-3.006-29.666 2.261-3.409 3.873-3.121 7.318-3.926"/><path fill="url(#a)" d="m396.154 820.409-4.711-.478c-7.434-4.233-7.29-23.207-3.006-29.666 2.261-3.409 3.873-3.121 7.318-3.926-.885 11.888-1.081 22.198.399 34.07"/><path fill="#ff5900" d="M395.926 754.03c-6.969-.837-7.692-1.848-9.721-8.752-2.048-6.969-1.774-15.352 1.872-21.737 3.433-2.462 3.097-1.74 7.828-2.299 12.609-.399 19.966-.559 32.349 1.28 1.063 10.801 1.236 20.162-.136 30.96-12.568 1.487-19.554 1.652-32.192.548"/><path fill="#f2e2d3" d="M395.926 754.03c-6.969-.837-7.692-1.848-9.721-8.752-2.048-6.969-1.774-15.352 1.872-21.737 3.433-2.462 3.097-1.74 7.828-2.299-.72 10.859-1.297 21.945.021 32.788"/><path fill="#ff5900" d="M591.524 786.075c10.718-.121 22.139-.466 32.732.798.978 11.743.719 21.173-.34 32.829a209 209 0 0 1-32.068.596c-1.566-10.437-.88-23.614-.324-34.223M657.24 786.365c11.318-.575 21.039-.706 32.287.682.605 13.013 1.076 19.933-1.056 32.56-11.747 1.636-19.389 1.429-31.175.577-1.731-9.989-1.642-23.833-.056-33.819M461.082 786.383a275 275 0 0 1 31.808.165c.777 12.622.969 20.45-.505 32.966-9.839 2.106-20.989 1.423-31.065 1.019-1.807-10.109-1.731-24.018-.238-34.15M531.293 786.379a136.2 136.2 0 0 1 27.053.692c.657 6.914 2.416 26.09-1.198 31.857-.745 1.188-2.705 1.125-4.393 1.398-9.338.762-16.984.244-26.266-.232-.629-7.784-2.943-25.176.467-32.059.583-1.176 2.729-1.292 4.337-1.656M330.198 786.309a257 257 0 0 1 31.755.367c.604 12.204.853 21.009-.452 33.123-11.278 1.605-19.842 1.222-31.125.318-1.326-13.356-1.826-20.564-.178-33.808M668.877 655.43c6.708-.372 13.991.339 20.743.804a169.5 169.5 0 0 1-.581 31.915c-3.114.426-6.628.651-9.789.919-8.109.463-14.238.355-22.377.086-1.214-9.684-.696-23.197-.411-33.156zM657.148 721.153c11.33-.417 21.243-.297 32.506.88.263 6.719 1.55 26.627-.974 31.584-9.968 1.891-21.803 1.199-32.021.788-.553-10.691-1.227-22.69.489-33.252M604.592 655.467c6.545-.237 13.509.436 20.073.899.316 7.511.764 24.934-1.174 31.421-2.209 1.57-7.022 1.236-9.991 1.297-7.1.31-14.577.151-21.716.105-1.192-10.604-.796-22.486-.482-33.191zM591.701 721.041c11.441-.257 21.158-.163 32.539 1.03a182.6 182.6 0 0 1-.092 31.639c-11.641 1.266-21.14 1.259-32.749.519-.725-10.161-.414-23.085.302-33.188M526.184 721.467c11.008-1.079 21.687-.548 32.668.388.196 7.473 1.524 26.936-1.96 32.238-10.812 1.028-20.239.687-31.032.027a252 252 0 0 1 .324-32.653M471.71 655.472c5.455-.323 15.63.389 21.42.633.485 10.053 1.7 21.12-.875 30.725-2.408 2.679-1.501 1.896-6.005 2.244-8.463.19-17.187.091-25.673.097-.6-6.781-1.273-25.691.459-32.082 2.141-1.636 7.644-1.464 10.674-1.617M332.264 721.323c10.338-.629 19.353-.455 29.65.201.424 7.827 1.746 23.392-.661 31.152-.395 1.276-1.884 1.298-3.498 1.767-10.622.519-17.929.339-28.428-.133-.268-6.975-2.348-29.374 2.937-32.987M461.158 720.861c10.964-.105 20.902-.063 31.839 1.162.282 6.352 1.616 27.456-1.264 31.787-10.344 1.49-21.009.87-31.383.21-.459-10.583-.733-22.714.808-33.159M539.808 655.472l19.081.691c.119 11.392.195 20.897-.58 32.301l-13.692.833c-5.705.113-12.627-.227-18.421-.364-.566-11.151-1.181-21.74-.053-32.869z"/><path fill="#fefefe" d="M400.95 472.826c.303-3.286.149-6.154 2.402-8.478 4.256-2.403 10.599-.502 15.622.402.312 12.305 2.48 54.078-2.084 66.628-.438 1.205-6.967 3.619-8.963 4.404-5.264 2.195-5.393 2.971-7.187 8.403 34.681 17.437 29.29-37.413 28.985-55.39 8.77-.229 18.127-.643 26.852-.617-.096 16.436-4.448 81.539 33.748 51.701 8.429-6.585 5.805-39.71 5.664-51.606l27.667.613c-.679 17.403-6.036 45.795 10.015 55.965 8.566.484 8.484-.117 15.948-4.228 1.409.747 1.306.697 2.906.671l.616-1.264c-3.886-4.079-12.347-1.807-16.337-5.112-6.423-7.017-3.907-58.151-3.627-70.284 5.037-.877 11.766-2.999 15.998.265 2.123 2.969 1.591 6.122 1.488 9.828 2.106 4.298 4.239 9.576 7.68 12.746 2.07.625 2.607.9 4.735.868 9.39-.124 17.226-.025 26.596.463-.84 13.831-7.967 78.581 31.823 52.899 8.827-5.697 6.394-41.014 6.282-53.62l15.152.046c8.684-1.087 14.392 2.495 19.969-.8 2.724-3.979 1.86-4.045 2.184-9.636.121-4.111-.195-8.902 1.858-12.342 4.494-3.288 6.36-2.282 12.057-1.402q1.97.303 3.937.631c1.043 15.573 1.176 51.412-.542 66.915l-10.535 4.094a42.2 42.2 0 0 0-6.675 9.56c34.959 13.54 29.012-38.519 28.953-57.115 15.803.617 28.176.171 40.885 12.067 16.335 15.291 12.479 47.675 12.567 69.744-22.135-.554-46.216.023-68.577.028l-134.82-.044c-90.235-.035-183.818-1.273-273.682.167 2.185-35.493-8.767-73.741 39.446-81.539 6.047-.654 12.691-.237 18.81-.047-.358 13.414-1.811 37.957-1.188 50.479 8.884 8.443 19.803 12.9 29.349 3.046 9.88-10.199 6.838-39.689 6.835-53.733 10.13-1.303 27.303 2.083 34.873-2.031 1.936-3.04 1.558-8.538 2.315-13.345"/><path fill="#fefefe" d="M533.177 464.634c5.037-.877 11.766-2.999 15.998.265 2.123 2.969 1.591 6.122 1.488 9.828-1.003 12.507 1.337 51.58-3.672 59.157-3.856 2.234-6.584.278-10.187 1.034-6.423-7.017-3.907-58.151-3.627-70.284M665.084 477.693c.121-4.111-.195-8.902 1.858-12.342 4.494-3.288 6.36-2.282 12.057-1.402q1.97.303 3.937.631c1.043 15.573 1.176 51.412-.542 66.915l-10.535 4.094c-1.11-.546-2.004-1.272-3.017-1.997-3.386-6.828-2.112-44.219-3.758-55.899M400.95 472.826c.303-3.286.149-6.154 2.402-8.478 4.256-2.403 10.599-.502 15.622.402.312 12.305 2.48 54.078-2.084 66.628-.438 1.205-6.967 3.619-8.963 4.404-1.287-.466-2.019-1.221-3.07-2.097-1.944-6.005-3.221-50.792-3.907-60.859"/><path fill="#fe8b50" d="M400.95 472.826c.686 10.067 1.963 54.854 3.907 60.859 1.051.876 1.783 1.631 3.07 2.097-5.264 2.195-5.393 2.971-7.187 8.403-5.866-3.99-10.068-7.69-15.441-12.322-1.754-12.601-1.325-29.782-1.379-42.898-7.024-.044-13.145-.313-20.158-.763 10.13-1.303 27.303 2.083 34.873-2.031 1.936-3.04 1.558-8.538 2.315-13.345M550.663 474.727c2.106 4.298 4.239 9.576 7.68 12.746 2.07.625 2.607.9 4.735.868.224 19.598 5.509 69.615-29.407 56.51 8.566.484 8.484-.117 15.948-4.228 1.409.747 1.306.697 2.906.671l.616-1.264c-3.886-4.079-12.347-1.807-16.337-5.112 3.603-.756 6.331 1.2 10.187-1.034 5.009-7.577 2.669-46.65 3.672-59.157M665.084 477.693c1.646 11.68.372 49.071 3.758 55.899 1.013.725 1.907 1.451 3.017 1.997a42.2 42.2 0 0 0-6.675 9.56c-18.26-14.805-9.792-35.501-12.2-54.642-2.289-2.177-6.381-1.948-10.053-2.378 8.684-1.087 14.392 2.495 19.969-.8 2.724-3.979 1.86-4.045 2.184-9.636"/><path fill="#f8c1ac" d="M309.956 488.457c6.047-.654 12.691-.237 18.81-.047-.358 13.414-1.811 37.957-1.188 50.479-1.743-2.216-7.569-5.95-8.679-8.601-4.889-11.671.273-28.337-3.869-40.213-.416-1.193-3.325-1.303-5.074-1.618"/><path fill="#fefefe" d="M470.223 463.171c20.094-.873 14.982 9.12 15.807 26.521.552 11.624 1.537 36.293-5.97 45.193l-3.575 1.51c-10.673-1.118-9.938-21.59-9.771-29.851.195-9.602-1.253-36.191 3.509-43.373M607.315 463.323c3.566.007 4.757.131 8.267.775 1.939 5.472 1.947 58.752-.418 67.598-.423 1.583-2.482 1.686-4.148 2.122-3.429-.099-8.161.162-10.557-2.046-1.601-7.328-2.249-59.761-.554-66.666 2.339-1.836 4.006-1.413 7.41-1.783M336.605 463.401c5.079-.154 10.195.288 15.27.625 1.113 8.607 1.858 64.132-1.049 69.263-4.937.404-9.886.189-14.84.077-.124-11.072-3.108-64.297.619-69.965"/></svg>';

    return [
      'id'               => $this->addonID,
      'title'            => esc_html__( 'Bookly', 'wp-parsidate' ),
      'desc'             => esc_html__( 'Jalali date selection for the Bookly Free booking form and admin calendar, with optional Pro calendar, services, search, staff, and tags form support', 'wp-parsidate' ),
      'force_enable'     => false,
      'icon'             => $icon,
      'image_link'       => 'https://wordpress.org/plugins/bookly-responsive-appointment-booking-tool/',
      'tags'             => [ esc_html__( 'Booking', 'wp-parsidate' ) ],
      'cat'              => 'integration',
      'settings_key'     => $this->addonID,
      'requires_plugins' => [
        'bookly-responsive-appointment-booking-tool/main.php' => [
          'is_wp_plugin' => true,
          'is_free'      => true,
          'plugin_link'  => 'https://wordpress.org/plugins/bookly-responsive-appointment-booking-tool/',
          'class_check'  => 'Bookly\\Lib\\Config',
        ],
      ],
    ];
  }
}
