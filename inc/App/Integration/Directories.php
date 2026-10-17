<?php
/**
 * Makes Directories / Directories Pro compatible with WP-Parsidate plugin
 *
 * Replaces the Directories form datepicker (Vanilla Calendar, which renders a
 * broken "Shamsi-looking" calendar for fa locales — wrong "today" marker and
 * Gregorian output after picking a date, see
 * https://github.com/wordpress-parsi/wp-parsidate/issues/341) with a true
 * Jalali datepicker in admin and front-end forms. The visible input shows
 * Jalali dates while the hidden input Directories submits keeps Gregorian
 * values, so dates are validated and stored in Gregorian.
 *
 * @package                 WP-Parsidate
 * @subpackage              Plugins/Directories
 */

namespace WPParsidate\App\Integration;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Addons\Addon;
use WPParsidate\Core\Names;
use WPParsidate\Helper\Assets;

class Directories extends Addon {
  /**
   * Addon unique identifier
   *
   * @var string
   */
  public string $addonID = 'directories';

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
  private const assetPrefix = WP_PARSI_KEY . '_directories';

  /**
   * Addon assets handle
   *
   * @var string
   */
  private const assetHandle = self::assetPrefix . '-jalali-datepicker';

  public function initAction(): void {
    if ( ! $this->getSetting( 'datepicker', true ) ) {
      return;
    }

    // Fires whenever Directories loads its form scripts (admin + front-end),
    // including for AJAX rendered forms. A form containing a date field
    // requests the 'datepicker'/'daterange' script options.
    add_action( 'drts_form_scripts', [ $this, 'registerFormScripts' ] );

    // Directories renders its forms after wp_enqueue_scripts has already run,
    // and a form injected over AJAX never fires the render hook on the page
    // that receives it. Both are covered by the footer: at that point
    // WordPress knows which Directories assets this request needs.
    add_action( 'wp_print_footer_scripts', [ $this, 'enqueueWhenDirectoriesLoaded' ], 1 );
    add_action( 'admin_print_footer_scripts', [ $this, 'enqueueWhenDirectoriesLoaded' ], 1 );
  }

  /**
   * Enqueue assets when Directories requests its datepicker form scripts
   *
   * @param array $options Directories form script options
   *
   * @return void
   */
  public function registerFormScripts( $options = [] ): void {
    $options = (array) $options;

    // An empty list means "every form script" (direct Form_Scripts() call).
    if ( ! empty( $options )
         && ! in_array( 'datepicker', $options, true )
         && ! in_array( 'daterange', $options, true )
    ) {
      return;
    }

    $this->enqueueAssets();
  }

  /**
   * Enqueue assets from the footer when Directories loaded its own scripts
   *
   * @return void
   */
  public function enqueueWhenDirectoriesLoaded(): void {
    if ( wp_script_is( 'drts', 'enqueued' ) || wp_script_is( 'drts-form', 'enqueued' ) ) {
      $this->enqueueAssets();
    }
  }

  /**
   * Enqueue the Jalali datepicker and the Directories integration script
   *
   * @return void
   */
  private function enqueueAssets(): void {
    if ( wp_script_is( self::assetHandle, 'enqueued' ) ) {
      return;
    }

    $pluginVersion = Assets::getVersion();
    $debugName     = WP_PARSI_DEBUG_MODE ? '' : '.min';
    $isAdmin       = is_admin();
    $libraryHandle = WP_PARSI_KEY . '_jalali_datepicker';
    $engineHandle  = WP_PARSI_KEY . '_jalali_date';

    // Jalali <-> Gregorian conversion engine (window.WpPdJalaliDate). The
    // integration script holds no conversion math of its own, so the engine
    // has to load first in both contexts: wp-admin does not always enqueue it.
    if ( ! wp_script_is( $engineHandle, 'enqueued' ) ) {
      wp_enqueue_script( $engineHandle, Assets::url( "js-admin/jalali-date$debugName.js" ), [], $pluginVersion, [ 'in_footer' => true ] );
    }

    $dependencies = [ $engineHandle ];

    if ( $isAdmin && ! wp_script_is( WP_PARSI_KEY . '_datepicker', 'registered' ) ) {
      // Standard wp-parsidate admin chain: jalalidatepicker + datepicker.js
      // (watcher, date separator, per field opt-outs) + styles + month names.
      do_action( 'wp_parsidate_jalali_datepicker_enqueue', 'directories' );
    }

    if ( wp_script_is( WP_PARSI_KEY . '_datepicker', 'registered' ) ) {
      $dependencies[] = WP_PARSI_KEY . '_datepicker';
    } else {
      if ( ! wp_script_is( $libraryHandle, 'enqueued' ) ) {
        wp_enqueue_script( $libraryHandle, Assets::url( 'js-admin/jalalidatepicker.min.js' ), [ $engineHandle ], $pluginVersion, [ 'in_footer' => true ] );
        wp_enqueue_style( $libraryHandle, Assets::url( "css-admin/jalalidatepicker$debugName.css" ), null, $pluginVersion );

        do_action( 'wp_parsidate_jalali_datepicker_enqueued', 'directories' );
      }

      $dependencies[] = $libraryHandle;
    }

    wp_enqueue_script(
      self::assetHandle,
      Assets::url( ( $isAdmin ? 'js-admin' : 'js' ) . "/directories-jalali-datepicker$debugName.js" ),
      $dependencies,
      $pluginVersion,
      [ 'in_footer' => true ]
    );

    $months = Names::getMonths();
    array_shift( $months ); // Remove empty leading item

    wp_localize_script( self::assetHandle, 'WpPdDrtsJalali', [
      'context'       => $isAdmin ? 'admin' : 'front',
      'months'        => $months,
      'persianDigits' => true,
      // Must match the active jalaliDatepicker separatorChars.date
      'dateSeparator' => $isAdmin ? '-' : '/',
    ] );
  }

  /**
   * Register the Directories settings section in the integration tab
   *
   * @param array $sections Settings sections
   *
   * @return array
   */
  public function addSectionSettings( $sections ) {
    $sections[ $this->addonID ] = array(
      'title'        => esc_html__( 'Directories', 'wp-parsidate' ),
      'desc'         => esc_html__( 'ParsiDate integration for Directories / Directories Pro', 'wp-parsidate' ),
      'settings_key' => $this->addonID,
      'settings'     => [
        'directories_start_grid' => array(
          'id'    => 'directories_start_grid',
          'title' => esc_html__( 'Datepicker', 'wp-parsidate' ),
          'type'  => 'startGrid',
        ),
        'datepicker'             => array(
          'id'       => 'datepicker',
          'title'    => esc_html__( 'Jalali datepicker', 'wp-parsidate' ),
          'desc'     => esc_html__( 'Replaces the Directories date, date/time and date range fields with a Jalali (Shamsi) datepicker in admin and front-end forms. Dates are still validated and stored in Gregorian in the database.', 'wp-parsidate' ),
          'type'     => 'toggle',
          'default'  => true,
          'sanitize' => 'bool'
        ),
        'directories_end_grid'   => array(
          'type' => 'endGrid',
        ),
      ]
    );

    return $sections;
  }

  /**
   * Addon information used by the addon registry and activation check
   *
   * @return array
   */
  public function info(): array {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path fill="#2f6bff" d="M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2Zm0 6a4 4 0 1 1 0 8 4 4 0 0 1 0-8Zm0 1.8a2.2 2.2 0 1 0 0 4.4 2.2 2.2 0 0 0 0-4.4Z"/></svg>';

    return array(
      'id'             => $this->addonID,
      'title'          => esc_html__( 'Directories', 'wp-parsidate' ),
      'desc'           => esc_html__( 'ParsiDate integration for Directories / Directories Pro', 'wp-parsidate' ),
      'force_enable'   => false,
      'icon'           => $svg,
      'image_link'     => 'https://directoriespro.com/',
      'tags'           => [ esc_html__( 'Directory', 'wp-parsidate' ) ],
      'cat'            => 'integration',
      'settings_key'   => $this->addonID,
      'requires_plugins' => [
        'directories/directories.php' => array(
          'is_wp_plugin'   => true,
          'is_free'        => true,
          'plugin_link'    => 'https://wordpress.org/plugins/directories/',
          'function_check' => 'drts',
        )
      ]
    );
  }
}
