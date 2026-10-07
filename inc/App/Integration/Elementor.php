<?php
/**
 * Makes Elementor compatible with WP-Parsidate plugin
 *
 * Jalali date support for:
 *  - Elementor Pro Form "Date" fields (front-end): datepicker uses Jalali dates,
 *    the submission value stays Gregorian through a hidden mirrored input.
 *  - Elementor Pro "Form Submissions" admin screen: dates display in Jalali.
 *
 * @package                 WP-Parsidate
 * @subpackage              Plugins/Elementor
 */

namespace WPParsidate\App\Integration;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Addons\Addon;
use WPParsidate\Core\Names;
use WPParsidate\Helper\Assets;

class Elementor extends Addon {
  public string $addonID = 'elementor';
  public string $currentTab = 'integration';

  /**
   * Assets handle prefix
   *
   * @var string
   */
  private const assetPrefix = WP_PARSI_KEY . '_elementor';

  public function initAction(): void {
    add_action( "elementor/editor/after_enqueue_styles", [ $this, 'fixEditorStyle' ] );

    if ( $this->getSetting( 'fix_front_date_fields', false ) ) {
      add_action( 'wp_enqueue_scripts', [ $this, 'enqueueFrontAssets' ], 20 );
    }

    if ( $this->getSetting( 'fix_submissions_dates', false ) ) {
      add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminAssets' ], 20 );
    }
  }

  /**
   * Front-end assets: Jalali datepicker for Elementor form date fields
   *
   * @return void
   */
  public function enqueueFrontAssets(): void {
    if ( ! $this->shouldEnqueueFront() ) {
      return;
    }

    $pluginVersion = Assets::getVersion();
    $debugName     = WP_PARSI_DEBUG_MODE ? '' : '.min';

    $this->enqueueDatepickerAssets();

    wp_enqueue_script(
      self::assetPrefix . '-jalali-date',
      Assets::url( "js/elementor-jalali-date$debugName.js" ),
      [ WP_PARSI_KEY . '_jalali_datepicker' ],
      $pluginVersion,
      [ 'in_footer' => true ]
    );

    wp_localize_script( self::assetPrefix . '-jalali-date', 'WPP_Elementor_Jalali', [
      'months'        => $this->getMonthsNames(),
      'persianDigits' => true,
    ] );
  }

  /**
   * Admin assets: Jalali dates in Elementor Form Submissions screen
   *
   * @param string $hookSuffix Current admin page hook suffix
   *
   * @return void
   */
  public function enqueueAdminAssets( $hookSuffix = '' ): void {
    $pluginVersion = Assets::getVersion();
    $debugName     = WP_PARSI_DEBUG_MODE ? '' : '.min';

    if ( ! $this->isSubmissionsScreen( $hookSuffix ) ) {
      return;
    }

    wp_enqueue_script(
      self::assetPrefix . '-jalali-date-admin',
      Assets::url( "js-admin/elementor-jalali-date$debugName.js" ),
      [ 'moment' ],
      $pluginVersion,
      [ 'in_footer' => true ]
    );
  }

  /**
   * Elementor editor style fix (Persian fonts)
   *
   * @return void
   */
  public function fixEditorStyle(): void {
    $wpp_elementor_css = "
      body, .tipsy-inner, .elementor-button, .elementor-panel {
        font-family: Tahoma,Arial,Helvetica,Verdana,sans-serif;
      }
      .tipsy-inner {
        font-size: small;
      }";
    $wpp_elementor_css = apply_filters( "wpp_elementor_css", $wpp_elementor_css );
    wp_add_inline_style( "elementor-editor", $wpp_elementor_css );
  }

  /**
   * Whether front-end assets should be enqueued on current request
   *
   * @return bool
   */
  private function shouldEnqueueFront(): bool {
    // Elementor editor / preview iframes are always allowed
    if ( isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
      return true;
    }

    return apply_filters( 'wp_parsidate_elementor_enqueue_front_assets', true );
  }

  /**
   * Whether current admin request is an Elementor Form Submissions screen
   *
   * @param string $hookSuffix Current admin page hook suffix
   *
   * @return bool
   */
  private function isSubmissionsScreen( $hookSuffix = '' ): bool {
    if ( str_contains( (string) $hookSuffix, 'e-form-submissions' ) ) {
      return true;
    }

    if ( ! function_exists( 'get_current_screen' ) ) {
      return false;
    }

    $screen = get_current_screen();

    return $screen && str_contains( (string) $screen->id, 'e-form-submissions' );
  }

  /**
   * Enqueue bundled JalaliDatePicker assets (and the conversion engine)
   *
   * @return void
   */
  private function enqueueDatepickerAssets(): void {
    $pluginVersion = Assets::getVersion();
    $debugName     = WP_PARSI_DEBUG_MODE ? '' : '.min';

    wp_enqueue_script( WP_PARSI_KEY . '_jalali_date', Assets::url( "js-admin/jalali-date$debugName.js" ), [], $pluginVersion, [ 'in_footer' => true ] );

    wp_enqueue_script( WP_PARSI_KEY . '_jalali_datepicker', Assets::url( 'js-admin/jalalidatepicker.min.js' ), [ WP_PARSI_KEY . '_jalali_date' ], $pluginVersion, [ 'in_footer' => true ] );
    wp_enqueue_style( WP_PARSI_KEY . '_jalali_datepicker', Assets::url( "css-admin/jalalidatepicker$debugName.css" ), null, $pluginVersion );

    do_action( 'wp_parsidate_jalali_datepicker_enqueued', 'elementor' );
  }

  /**
   * Localized month names for the datepicker
   *
   * @return array
   */
  private function getMonthsNames(): array {
    $months = Names::getMonths();
    array_shift( $months ); // Remove empty leading item

    return $months;
  }

  /**
   * Add Elementor settings section to Integration tab
   *
   * @hook  wp_parsidate_integration_settings_sections
   *
   * @param array $sections Integration tab sections
   *
   * @return array
   */
  public function addSectionSettings( $sections ): array {
    $sections[ $this->addonID ] = array(
      'title'        => esc_html__( 'Elementor', 'wp-parsidate' ),
      'desc'         => esc_html__( 'Jalali date support for Elementor Pro forms and Form Submissions', 'wp-parsidate' ),
      'settings_key' => $this->addonID,
      'settings'     => [
        'elementor_start_grid'  => [
          'id'    => 'elementor_start_grid',
          'title' => esc_html__( 'Elementor', 'wp-parsidate' ),
          'type'  => 'startGrid',
        ],
        'fix_front_date_fields' => [
          'id'       => 'fix_front_date_fields',
          'title'    => esc_html__( 'Jalali datepicker for form date fields', 'wp-parsidate' ),
          'desc'     => esc_html__( 'Convert Elementor form "Date" fields to a Jalali (Shamsi) datepicker. Submitted values stay in Gregorian format.', 'wp-parsidate' ),
          'type'     => 'toggle',
          'default'  => false,
          'sanitize' => 'bool'
        ],
        'fix_submissions_dates' => [
          'id'       => 'fix_submissions_dates',
          'title'    => esc_html__( 'Jalali dates in Form Submissions', 'wp-parsidate' ),
          'desc'     => esc_html__( 'Display Jalali dates in the Elementor "Form Submissions" admin screen', 'wp-parsidate' ),
          'type'     => 'toggle',
          'default'  => false,
          'sanitize' => 'bool'
        ],
        'elementor_end_grid'    => [
          'type' => 'endGrid',
        ],
      ]
    );

    return $sections;
  }

  public function info(): array {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 100 100"><path fill="#010051" d="M50 0C22.383 0 0 22.383 0 50c0 27.608 22.383 50 50 50s50-22.383 50-50C99.99 22.383 77.608 0 50 0M37.502 70.827h-8.329V29.164h8.33zm33.324 0H45.831v-8.33h24.995zm0-16.667H45.831V45.83h24.995zm0-16.667H45.831v-8.329h24.995z"/></svg>';

    return array(
      'id'               => $this->addonID,
      'title'            => esc_html__( 'Elementor', 'wp-parsidate' ),
      'desc'             => esc_html__( 'ParsiDate integration for Elementor', 'wp-parsidate' ),
      'force_enable'     => false,
      'icon'             => $svg,
      'image_link'       => 'https://wordpress.org/plugins/elementor/',
      'tags'             => [ esc_html__( 'Elementor', 'wp-parsidate' ) ],
      'cat'              => 'integration',
      'settings_key'     => $this->addonID,
      'requires_plugins' => [
        'elementor/elementor.php' => array(
          'is_wp_plugin' => true,
          'is_free'      => true,
          'plugin_link'  => 'https://wordpress.org/plugins/elementor/',
          'define_check' => 'ELEMENTOR_PLUGIN_BASE'
        )
      ]
    );
  }
}
