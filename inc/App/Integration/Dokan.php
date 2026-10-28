<?php
/**
 * Makes Dokan / Dokan Pro compatible with WP-Parsidate plugin
 *
 * Replaces datepickers, date displays and date inputs in the Dokan vendor
 * dashboard and admin area with Jalali (Shamsi) dates for display purposes.
 * Dates are stored in and submitted to the database in Gregorian format, so
 * this integration is purely presentational:
 *
 *   - Visible date inputs show Shamsi dates via a Jalali datepicker
 *   - Hidden inputs retain Gregorian values for form submission
 *   - Date display functions are filtered to output Shamsi strings
 *   - Date range filters (order listing, reports) use Jalali pickers
 *
 * The following Dokan features are covered:
 *   - Vendor dashboard product date fields (sale from/to)
 *   - Order date filters and date range pickers
 *   - Withdrawal request dates and amounts (Persian numbers)
 *   - Store hours and delivery time settings
 *   - Booking module dates (Dokan Pro + WC Bookings)
 *   - Analytics / vendor dashboard charts date ranges
 *
 * @package                 WP-Parsidate
 * @subpackage              Plugins/Dokan
 */

namespace WPParsidate\App\Integration;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Addons\Addon;
use WPParsidate\Helper\{Assets, Number};
use WPParsidate\Core\Names;

class Dokan extends Addon {
  /**
   * Addon unique identifier
   *
   * @var string
   */
  public string $addonID = 'dokan';

  /**
   * Settings tab where the addon section is displayed
   *
   * @var string
   */
  public string $currentTab = 'integration';

  /**
   * Assets handle prefix
   *
   * @var string
   */
  private const assetPrefix = WP_PARSI_KEY . '_dokan';

  /**
   * Addon assets handle
   *
   * @var string
   */
  private const assetHandle = self::assetPrefix . '-jalali-date';

  /**
   * Addon icon
   *
   * @var string
   */
  public const icon = '<svg xmlns="http://www.w3.org/2000/svg" width="95" height="30" viewBox="0 0 95 30"><g fill="none" fill-rule="nonzero"><path fill="#4d4d4f" d="M42.682 22.177c0 3.537-2.473 5.66-7.727 5.66s-7.728-2.123-7.728-5.66v-6.009c0-3.536 2.478-5.654 7.728-5.654s7.727 2.122 7.727 5.654zm-4.177-5.527c0-1.96-.61-3.086-3.569-3.086s-3.568 1.127-3.568 3.086v5.045c0 1.96.61 3.087 3.568 3.087 2.96 0 3.569-1.123 3.569-3.087zM54.414 18.127c4.79 1.705 4.95 5.591 6.909 9.128q-1.174.294-2.378.418c-4.436 0-1.927-6.687-8.55-7.778v7.778s-4.177 0-4.177-2.091V2.89s4.177 0 4.177 2.09v11.7c1.896-.29 2.96-1.704 4.405-4.436.61-1.159 1.818-1.572 3.086-1.572a7.7 7.7 0 0 1 2.273.386c-1.759 2.41-2.85 5.914-5.745 7.068M72.605 15.814c0-1.16-.646-2.187-3.119-2.187-2.7 0-5.14.932-5.14 3.637-.932-1.255-1.223-2.25-1.223-3.119 0-2.795 3.759-3.636 6.395-3.636 4.723 0 7.232 1.991 7.232 5.496v11.763a5.36 5.36 0 0 0-2.473-.513c-1.609 0-3.213.577-5.045.577-3.31 0-6.555-1.223-6.555-5.046-.004-6.009 8.614-4.431 9.928-6.972m.095 3.277c-.74 1.704-5.882.773-5.882 3.636 0 1.446 1.06 2.187 2.891 2.187 1.025 0 2.04-.196 2.991-.578zM90.736 16.295c0-1.704-.804-2.668-3.086-2.668-1.034.011-2.062.164-3.055.455v13.595s-4.177 0-4.177-2.09V10.513c.902.437 1.89.667 2.891.672 1.573 0 3.055-.672 4.791-.672 3.31 0 6.818 1.122 6.818 5.304v11.86s-4.177-.064-4.177-2.092z"/><path fill="#f1634c" d="M1.96 1.51S16.867.41 16.867 13.281s-3.94 15.454-7.395 16.268c0 0 14.368 3.46 14.368-14.64S5.518-.587 1.959 1.51"/><path fill="#f1634c" d="M14.09 23.395s-1.435 5-5.785 5.632S3.145 27.3.123 27.464a2.427 2.427 0 0 1 2.2-2.678c2.45-.331 7.04.314 10.09-1.613 0 0 1.819-.946 2.16-1.405z"/><path fill="#f1634c" d="M1.736 2.41v21.613q.225-.059.455-.091 1.29-.136 2.586-.132c.91 0 1.882-.036 2.864-.127V16.4c0-2.418-.155-4.886 0-7.3a3.95 3.95 0 0 1 2.49-3.236 4.1 4.1 0 0 1 3.333.218A19.67 19.67 0 0 0 1.736 2.409"/></g></svg>';

  /**
   * Register settings section in the integration tab
   *
   * @param array $sections Settings sections
   *
   * @return array
   */
  public function addSectionSettings( $sections ): array {
    $settings = [
      'dokan_start_grid'       => [
        'id'    => 'dokan_start_grid',
        'title' => esc_html__( 'Date Display', 'wp-parsidate' ),
        'type'  => 'startGrid',
      ],
      'fix_dates_display'      => [
        'id'       => 'fix_dates_display',
        'title'    => esc_html__( 'Shamsi date display', 'wp-parsidate' ),
        'desc'     => esc_html__( 'Display dates in Shamsi (Jalali) format throughout the Dokan vendor dashboard and admin area.', 'wp-parsidate' ),
        'type'     => 'toggle',
        'default'  => true,
        'sanitize' => 'bool',
      ],
      'dokan_end_grid_display' => [
        'type' => 'endGrid',
      ],
      'dokan_start_grid_2'     => [
        'id'    => 'dokan_start_grid_2',
        'title' => esc_html__( 'Date Picker', 'wp-parsidate' ),
        'type'  => 'startGrid',
      ],
      'fix_datepicker'         => [
        'id'       => 'fix_datepicker',
        'title'    => esc_html__( 'Jalali datepicker', 'wp-parsidate' ),
        'desc'     => sprintf(
        /* translators: %s: list of covered areas */
          esc_html__( 'Replace date and date range pickers with a Jalali datepicker in vendor dashboard forms, product edit, order filters, and store settings. Dates are submitted and stored in Gregorian. Covers: %s', 'wp-parsidate' ),
          esc_html__( 'product sale dates, order date filters, delivery time, withdrawal forms', 'wp-parsidate' )
        ),
        'type'     => 'toggle',
        'default'  => true,
        'sanitize' => 'bool',
      ],
      'fix_product_dates'      => [
        'id'       => 'fix_product_dates',
        'title'    => esc_html__( 'Product date fields', 'wp-parsidate' ),
        'desc'     => esc_html__( 'Enable Jalali picker for product sale price date fields in the vendor product edit form.', 'wp-parsidate' ),
        'type'     => 'toggle',
        'default'  => true,
        'sanitize' => 'bool',
      ],
      'dokan_end_grid_2'       => [
        'type' => 'endGrid',
      ],
      'dokan_start_grid_3'     => [
        'id'    => 'dokan_start_grid_3',
        'title' => $this->isProActive() ? esc_html__( 'Numbers & Store Hours', 'wp-parsidate' ) : esc_html__( 'Numbers', 'wp-parsidate' ),
        'type'  => 'startGrid',
      ],
      'fix_withdraw_numbers'   => [
        'id'       => 'fix_withdraw_numbers',
        'title'    => esc_html__( 'Withdrawal amounts', 'wp-parsidate' ),
        'desc'     => esc_html__( 'Convert Persian digits to English in withdrawal request forms and display Persian numbers in withdrawal history tables.', 'wp-parsidate' ),
        'type'     => 'toggle',
        'default'  => false,
        'sanitize' => 'bool',
      ],
    ];

    // Store hours live in the Dokan Pro StoreTime module.
    if ( $this->isProActive() ) {
      $settings['fix_store_hours'] = [
        'id'       => 'fix_store_hours',
        'title'    => esc_html__( 'Store hours display', 'wp-parsidate' ),
        'desc'     => esc_html__( 'Display store open/close times in Shamsi format in the vendor dashboard.', 'wp-parsidate' ),
        'type'     => 'toggle',
        'default'  => false,
        'sanitize' => 'bool',
      ];
    }

    $settings['dokan_end_grid_3'] = [
      'type' => 'endGrid',
    ];

    $sections[ $this->addonID ] = [
      'title'        => esc_html__( 'Dokan', 'wp-parsidate' ),
      'desc'         => esc_html__( 'ParsiDate integration for Dokan / Dokan Pro', 'wp-parsidate' ),
      'settings_key' => $this->addonID,
      'settings'     => $settings,
    ];

    return $sections;
  }

  /**
   * Whether Dokan Pro is active.
   *
   * Dokan Lite is enough to activate the addon; Pro only unlocks the extra
   * modules and the settings that depend on them.
   *
   * @return bool
   */
  public function isProActive(): bool {
    return defined( 'DOKAN_PRO_PLUGIN_VERSION' );
  }

  /**
   * Initialization hook
   */
  public function initAction(): void {
    if ( ! defined( 'DOKAN_PLUGIN_VERSION' ) ) {
      return;
    }

    // Enqueue assets for datepicker replacement
    add_action( 'wp_enqueue_scripts', [ $this, 'enqueueFrontAssets' ] );
    add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminAssets' ] );

    // Convert submitted Persian numbers to English before Dokan/WooCommerce processing
    add_action( 'init', [ $this, 'convertPersianNumbersInPost' ] );

    // Convert submitted Jalali product dates to Gregorian before Dokan reads them
    add_action( 'init', [ $this, 'convertPostedDatesToGregorian' ] );

    // Convert the Jalali month of the vendor product filter to a Gregorian range
    add_filter( 'dokan_pre_product_listing_args', [ $this, 'convertJalaliMonthFilter' ] );

    // Replace the vendor product month list with the Jalali months that
    // actually contain products (Dokan only returns Gregorian months).
    add_filter( 'months_dropdown_results', [ $this, 'filterVendorProductMonths' ], 10, 2 );

    // Filter withdrawal amounts for display
    if ( $this->getSetting( 'fix_withdraw_numbers', false ) ) {
      add_filter( 'dokan_withdraw_amount', [ $this, 'fixWithdrawAmountDisplay' ], 100 );
      add_filter( 'dokan_withdraw_method_field_value', [ $this, 'fixWithdrawMethodDisplay' ], 100 );
    }
  }

  /**
   * Enqueue frontend (vendor dashboard) assets
   */
  public function enqueueFrontAssets(): void {
    if ( ! is_user_logged_in() ) {
      return;
    }

    // Dokan 3.x+ checks sellers with dokan_is_user_seller(); the old helper
    // dokan_user_is_vendor() no longer exists and calling it was fatal.
    if ( ! function_exists( 'dokan_is_user_seller' ) || ! dokan_is_user_seller( get_current_user_id() ) ) {
      return;
    }

    if ( ! $this->shouldEnqueueFrontend() ) {
      return;
    }

    $this->enqueueAssets( false );
  }

  /**
   * Enqueue admin assets
   */
  public function enqueueAdminAssets(): void {
    $screen = get_current_screen();
    if ( ! $screen ) {
      return;
    }

    $screenId = $screen->id;

    // Every Dokan admin page (settings, withdraw, reports, bookings, ...),
    // plus the WooCommerce product and order screens Dokan adds date fields to
    // (sale dates, delivery time, shipped date, order date).
    $isDokanScreen   = strpos( $screenId, 'dokan' ) !== false;
    $isProductScreen = in_array( $screenId, [ 'product', 'edit-product' ], true );
    $isOrderScreen   = in_array( $screenId, [ 'shop_order', 'edit-shop_order', 'woocommerce_page_wc-orders' ], true );

    if ( ! $isDokanScreen && ! $isProductScreen && ! $isOrderScreen ) {
      return;
    }

    if ( ! $this->shouldEnqueueAdmin() ) {
      return;
    }

    $this->enqueueAssets( true );
  }

  /**
   * Check if frontend assets should be enqueued
   */
  private function shouldEnqueueFrontend(): bool {
    if (
      ! $this->getSetting( 'fix_datepicker', true )
      && ! $this->getSetting( 'fix_product_dates', true )
      && ! $this->getSetting( 'fix_dates_display', true )
    ) {
      return false;
    }

    // Dokan has no is_dokan() helper; dokan_is_seller_dashboard() covers the
    // dashboard page and all of its endpoints (products, orders, withdraw,
    // reverse withdrawal, settings, reports).
    return function_exists( 'dokan_is_seller_dashboard' ) && dokan_is_seller_dashboard();
  }

  /**
   * Check if admin assets should be enqueued
   */
  private function shouldEnqueueAdmin(): bool {
    return $this->getSetting( 'fix_datepicker', true ) || $this->getSetting( 'fix_dates_display', true );
  }

  /**
   * Enqueue Dokan Jalali datepicker assets
   *
   * @param bool $isAdmin Whether we're in admin context
   *
   * @return void
   */
  private function enqueueAssets( bool $isAdmin = false ): void {
    if ( wp_script_is( self::assetHandle, 'enqueued' ) ) {
      return;
    }

    $pluginVersion = Assets::getVersion();
    $debugName     = WP_PARSI_DEBUG_MODE ? '' : '.min';
    $libraryHandle = WP_PARSI_KEY . '_jalali_datepicker';
    $engineHandle  = WP_PARSI_KEY . '_jalali_date';

    // These assets are only shipped in the admin asset directories, and the
    // vendor dashboard is a frontend page, so both contexts load the same files.
    if ( ! wp_script_is( $engineHandle, 'enqueued' ) ) {
      wp_enqueue_script( $engineHandle, Assets::url( "js-admin/jalali-date$debugName.js" ), [], $pluginVersion, [ 'in_footer' => true ] );
    }

    $dependencies = [ $engineHandle ];

    if ( $isAdmin && ! wp_script_is( WP_PARSI_KEY . '_datepicker', 'registered' ) ) {
      // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy public hook name, kept for backward compatibility.
      do_action( 'wp_parsidate_jalali_datepicker_enqueue', 'dokan' );
    }

    if ( wp_script_is( WP_PARSI_KEY . '_datepicker', 'registered' ) ) {
      $dependencies[] = WP_PARSI_KEY . '_datepicker';
    } else {
      if ( ! wp_script_is( $libraryHandle, 'enqueued' ) ) {
        wp_enqueue_script( $libraryHandle, Assets::url( 'js-admin/jalalidatepicker.min.js' ), [ $engineHandle ], $pluginVersion, [ 'in_footer' => true ] );
        wp_enqueue_style( $libraryHandle, Assets::url( "css-admin/jalalidatepicker$debugName.css" ), null, $pluginVersion );

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy public hook name, kept for backward compatibility.
        do_action( 'wp_parsidate_jalali_datepicker_enqueued', 'dokan' );
      }

      $dependencies[] = $libraryHandle;
    }

    $jsPath         = $isAdmin ? 'js-admin' : 'js';
    $dependencies[] = 'jquery'; // The integration script is jQuery based.

    wp_enqueue_script(
      self::assetHandle,
      Assets::url( "$jsPath/dokan-jalali-date$debugName.js" ),
      $dependencies,
      $pluginVersion,
      [ 'in_footer' => true ]
    );

    // Localize script with configuration
    $months = Names::getMonths();
    array_shift( $months );

    wp_localize_script( self::assetHandle, 'WppDokanJalali', [
      'isAdmin'             => (int) $isAdmin,
      'months'              => $months,
      'persianDigits'       => true,
      'dateSeparator'       => $isAdmin ? '-' : '/',
      'datepickerEnabled'   => (int) $this->getSetting( 'fix_datepicker', true ),
      'productDatesEnabled' => (int) $this->getSetting( 'fix_product_dates', true ),
      'datesDisplayEnabled' => (int) $this->getSetting( 'fix_dates_display', true ),
    ] );

    $this->enqueueAnalyticsAssets( $isAdmin, $engineHandle, $months, $pluginVersion, $debugName );
  }

  /**
   * Enqueue the WooCommerce analytics Shamsi localizer on the Dokan React dashboard
   *
   * Dokan's analytics screens are WooCommerce admin React apps, so their date
   * range caption, legend and D3 chart axis are the same DOM the WooCommerce
   * Analytics integration already localizes. Its overlay (and the apiFetch
   * range swap, which only matches `wc-analytics/`) is disabled here: Dokan
   * queries its own `/dokan/v1/report/...` endpoints, so only display
   * conversion applies.
   *
   * @param bool   $isAdmin       Whether we're in admin context
   * @param string $engineHandle  Jalali date engine handle
   * @param array  $months        Jalali month names
   * @param string $pluginVersion Plugin version
   * @param string $debugName     Empty or `.min` asset suffix
   *
   * @return void
   */
  private function enqueueAnalyticsAssets( bool $isAdmin, string $engineHandle, array $months, string $pluginVersion, string $debugName ): void {
    if ( $isAdmin || ! $this->isAnalyticsDashboard() || ! $this->getSetting( 'fix_dates_display', true ) ) {
      return;
    }

    $handle = self::assetPrefix . '-analytics';

    if ( wp_script_is( $handle, 'enqueued' ) ) {
      return;
    }

    wp_enqueue_script(
      $handle,
      Assets::url( "js-admin/woocommerce-analytics$debugName.js" ),
      [ $engineHandle ],
      $pluginVersion,
      [ 'in_footer' => true ]
    );

    wp_localize_script( $handle, 'WpPdWcAn_SETTINGS', [
      'usePersianDigits' => true,
      'enableOverlay'    => false,
      'monthNames'       => $months,
      'weekdayShort'     => [ 'ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج' ],
    ] );
  }

  /**
   * Whether the current request is a Dokan analytics dashboard screen
   *
   * @return bool
   */
  private function isAnalyticsDashboard(): bool {
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only screen detection.
    if ( ! isset( $_GET['path'] ) || ! is_string( $_GET['path'] ) ) {
      return false;
    }

    $path = sanitize_text_field( wp_unslash( $_GET['path'] ) );
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    return false !== strpos( $path, '/analytics' );
  }

  /**
   * Convert Persian numbers in POST data to English before processing
   * This ensures Dokan/WooCommerce validation passes
   *
   * @return void
   */
  public function convertPersianNumbersInPost(): void {
    if ( ! $this->getSetting( 'fix_withdraw_numbers', false ) ) {
      return;
    }

    // Only the digits are normalized here; Dokan verifies its own nonce when it processes the request.
    // phpcs:disable WordPress.Security.NonceVerification.Missing
    if ( isset( $_POST['withdraw_amount'] ) && is_string( $_POST['withdraw_amount'] ) ) {
      $_POST['withdraw_amount'] = wp_slash( Number::toEnglish( sanitize_text_field( wp_unslash( $_POST['withdraw_amount'] ) ) ) );
    }
    // phpcs:enable WordPress.Security.NonceVerification.Missing
  }

  /**
   * Convert posted Jalali product dates to Gregorian before Dokan saves them
   *
   * Dokan reads the sale dates straight from the request and builds WC_DateTime
   * objects out of them, so `init` is the last point where the value can be
   * corrected. The Jalali pickers already submit Gregorian dates; this is the
   * fallback for a Jalali date typed by hand or posted without the script.
   *
   * @return void
   */
  public function convertPostedDatesToGregorian(): void {
    if ( ! $this->getSetting( 'fix_product_dates', true ) && ! $this->getSetting( 'fix_datepicker', true ) ) {
      return;
    }

    // Only the date format is normalized here; Dokan verifies its own nonce when it saves the product.
    // phpcs:disable WordPress.Security.NonceVerification.Missing
    foreach ( [ '_sale_price_dates_from', '_sale_price_dates_to' ] as $field ) {
      if ( empty( $_POST[ $field ] ) || ! is_string( $_POST[ $field ] ) ) {
        continue;
      }

      $gregorian = $this->convertJalaliToGregorianDate( sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );

      if ( $gregorian ) {
        $_POST[ $field ] = wp_slash( $gregorian );
      }
    }
    // phpcs:enable WordPress.Security.NonceVerification.Missing
  }

  /**
   * Convert a Jalali date string to Gregorian
   *
   * @param string $dateStr Jalali date in YYYY/MM/DD or YYYY-MM-DD format
   *
   * @return string|false Gregorian date in Y-m-d format or false on failure
   */
  private function convertJalaliToGregorianDate( string $dateStr ) {
    // gregdate() is the plugin's public Jalali -> Gregorian converter.
    if ( ! function_exists( 'gregdate' ) ) {
      return false;
    }

    $dateStr = Number::toEnglish( $dateStr );

    if ( ! preg_match( '/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $dateStr, $matches ) ) {
      return false;
    }

    // Only Jalali years are converted; anything else is already Gregorian.
    $jy = (int) $matches[1];
    if ( $jy < 1200 || $jy > 1599 ) {
      return false;
    }

    $converted = gregdate( 'Y-m-d', $dateStr );

    // gregdate() returns its input untouched when the date is not valid, so a
    // result that still carries a Jalali year means the conversion failed.
    if ( ! is_string( $converted ) || ! preg_match( '/^(\d{4})-\d{2}-\d{2}$/', $converted, $out ) || (int) $out[1] < 1700 ) {
      return false;
    }

    return $converted;
  }

  /**
   * Replace the vendor product month list with Jalali months
   *
   * Dokan groups products by Gregorian YEAR()/MONTH(), so its month dropdown
   * does not line up with the Jalali calendar. This returns the Jalali months
   * that actually contain a product, which removes mislabelled and empty
   * options. Only the seller dashboard is affected; wp-admin and every other
   * month dropdown keep their Gregorian values.
   *
   * @param array|object $months Month rows with `year` and `month` properties
   * @param string $postType Post type being listed
   *
   * @return array|object
   */
  public function filterVendorProductMonths( $months, $postType ) {
    if ( 'product' !== $postType || ! function_exists( 'dokan_is_seller_dashboard' ) || ! dokan_is_seller_dashboard() ) {
      return $months;
    }

    $userId = get_current_user_id();

    if ( ! $userId || ! function_exists( 'parsidate' ) ) {
      return $months;
    }

    global $wpdb;

    $cacheKey = 'vendor_product_months_' . $userId . ':' . wp_cache_get_last_changed( 'posts' );
    $rows     = wp_cache_get( $cacheKey, 'wp-parsidate-dokan' );

    if ( false === $rows ) {
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Grouped months of the vendor products, no WordPress API for it. Cached per posts change.
      $rows = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT MIN(post_date) AS first_date, MAX(post_date) AS last_date
         FROM {$wpdb->posts}
         WHERE post_type = 'product' AND post_author = %d
         GROUP BY YEAR(post_date), MONTH(post_date)",
          $userId
        )
      );

      wp_cache_set( $cacheKey, $rows, 'wp-parsidate-dokan' );
    }

    if ( empty( $rows ) ) {
      return $months;
    }

    $jalaliMonths = [];

    foreach ( $rows as $row ) {
      $first = $this->gregorianDateToJalaliMonth( $row->first_date );
      $last  = $this->gregorianDateToJalaliMonth( $row->last_date );

      if ( ! $first || ! $last ) {
        continue;
      }

      $jalaliMonths[ $first ] = true;
      $jalaliMonths[ $last ]  = true;

      // A Gregorian month spans at most two Jalali months, and both ends of
      // this range contain a product, so a gap is always a real month.
      for ( $key = $first; $key < $last; ) {
        $key = ( $key % 100 ) === 12 ? ( intdiv( $key, 100 ) + 1 ) * 100 + 1 : $key + 1;
        $jalaliMonths[ $key ] = true;
      }
    }

    if ( empty( $jalaliMonths ) ) {
      return $months;
    }

    krsort( $jalaliMonths );

    $result = [];

    foreach ( array_keys( $jalaliMonths ) as $key ) {
      $result[] = (object) [
        'year'  => intdiv( $key, 100 ),
        'month' => $key % 100,
      ];
    }

    return $result;
  }

  /**
   * Convert a Gregorian datetime to a Jalali YYYYMM month key
   *
   * @param string $date Gregorian datetime
   *
   * @return int Jalali month key or 0 when the date cannot be converted
   */
  private function gregorianDateToJalaliMonth( $date ): int {
    $jalali = parsidate( 'Ym', $date, 'eng' );

    if ( ! is_string( $jalali ) || ! preg_match( '/^(1[2-5]\d{2})(0[1-9]|1[0-2])$/', $jalali, $matches ) ) {
      return 0;
    }

    return (int) ( $matches[1] . $matches[2] );
  }

  /**
   * Convert the Jalali month of the vendor product filter to a Gregorian range
   *
   * Dokan copies the `date` request value into WP_Query's `m` argument, which
   * only understands Gregorian YYYYMM. The products page rebuilds that dropdown
   * with Jalali YYYYMM values, so the selected month is translated here into an
   * exact post_date range. Gregorian values are left untouched.
   *
   * @param array $args Product listing query arguments
   *
   * @return array
   */
  public function convertJalaliMonthFilter( $args ): array {
    if ( empty( $args['m'] ) || ! is_string( $args['m'] ) ) {
      return $args;
    }

    $value = $args['m'];

    if ( strlen( $value ) !== 6 || ! ctype_digit( $value ) ) {
      return $args;
    }

    $year  = (int) substr( $value, 0, 4 );
    $month = (int) substr( $value, 4, 2 );

    if ( $year < 1200 || $year > 1599 || $month < 1 || $month > 12 ) {
      return $args;
    }

    if ( ! function_exists( 'gregdate' ) ) {
      return $args;
    }

    $nextYear  = 12 === $month ? $year + 1 : $year;
    $nextMonth = 12 === $month ? 1 : $month + 1;

    $start = gregdate( 'Y-m-d H:i:s', sprintf( '%04d-%02d-01 00:00:00', $year, $month ) );
    $end   = gregdate( 'Y-m-d H:i:s', sprintf( '%04d-%02d-01 00:00:00', $nextYear, $nextMonth ) );

    if ( ! is_string( $start ) || ! is_string( $end ) ) {
      return $args;
    }

    unset( $args['m'] );

    $dateQuery = isset( $args['date_query'] ) && is_array( $args['date_query'] ) ? $args['date_query'] : [];

    $dateQuery[] = [
      'after'     => $start,
      'inclusive' => true,
      'column'    => 'post_date',
    ];
    $dateQuery[] = [
      'before'    => $end,
      'inclusive' => false,
      'column'    => 'post_date',
    ];

    $args['date_query'] = $dateQuery;

    return $args;
  }

  /**
   * Fix withdrawal amount display for Persian numbers
   *
   * @param string $amount
   *
   * @return string
   */
  public function fixWithdrawAmountDisplay( string $amount ): string {
    return Number::fixNumber( $amount );
  }

  /**
   * Fix withdrawal method field display for Persian numbers
   *
   * @param string $value
   *
   * @return string
   */
  public function fixWithdrawMethodDisplay( string $value ): string {
    return Number::fixNumber( $value );
  }

  /**
   * Addon information used by the addon registry and activation check
   *
   * @return array
   */
  public function info(): array {
    return [
      'id'               => $this->addonID,
      'title'            => esc_html__( 'Dokan', 'wp-parsidate' ),
      'desc'             => esc_html__( 'ParsiDate integration for Dokan / Dokan Pro — Jalali dates throughout the vendor dashboard.', 'wp-parsidate' ),
      'force_enable'     => false,
      'has_page'         => false,
      'icon'             => self::icon,
      'image_link'       => 'https://wedevs.com/dokan/',
      'tags'             => [ esc_html__( 'E-Commerce', 'wp-parsidate' ), esc_html__( 'Marketplace', 'wp-parsidate' ) ],
      'cat'              => 'integration',
      'settings_key'     => $this->addonID,
      'requires_plugins' => [
        'dokan-lite/dokan.php'    => [
          'is_wp_plugin' => true,
          'is_free'      => true,
          'plugin_link'  => 'https://wordpress.org/plugins/dokan/',
          'class_check'  => 'WeDevs_Dokan',
          'define_check' => 'DOKAN_PLUGIN_VERSION',
        ],
        'dokan-pro/dokan-pro.php' => [
          'is_wp_plugin' => true,
          'is_free'      => false,
          'plugin_link'  => 'https://wedevs.com/dokan/',
          'define_check' => 'DOKAN_PRO_PLUGIN_VERSION',
          'optional'     => true,
        ],
      ],
    ];
  }
}
