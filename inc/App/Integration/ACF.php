<?php
/**
 * Makes ACF compatible with WP-Parsidate plugin
 *
 * @package                 WP-Parsidate
 * @subpackage              Plugins/ACF
 * @since                   4.0.0
 * @author                  Morteza Gransayeh
 */

namespace WPParsidate\App\Integration;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Addons\Addon;
use WPParsidate\Helper\Date;

class ACF extends Addon {
  public string $addonID = 'acf';
  public string $currentTab = 'integration';

  public function registerInitM1Action(): void {
    if ( $this->getSetting( 'fix_date', false ) ) {
      add_action( 'acf/include_field_types', [ $this, 'includeField' ] ); // v5
      add_action( 'acf/register_fields', [ $this, 'includeField' ] ); // v4

      add_action( 'acf/render_field_settings', [ $this, 'addOptionToDatePickerSettings' ] );
      add_filter( 'acf/update_value', [ $this, 'updateDatePickerValue' ], 10, 3 );
      add_filter( 'acf/load_value', [ $this, 'loadDatePickerValue' ], 10, 3 );
      add_filter( 'acf/load_field', [ $this, 'loadDatePickerField' ] );
      add_filter( 'acf/pre_format_value', [ $this, 'formatDatePickerValue' ], 10, 5 );
      add_action( 'admin_enqueue_scripts', [ $this, 'fixDatePickerScript' ], 99999 );
    }

    add_filter( 'wp_parsidate_hook_deactivator_raw_list', [ $this, 'addDateHooksToDeactivator' ] );
  }

  public function addDateHooksToDeactivator( $rawList ): string {
    $rawList .= "\nwp_date,acf_format_date\nwp_date,render_field,acf_field_date_picker\ndate_i18n,acf_format_date\ndate_i18n,render_field,acf_field_date_picker";

    return $rawList;
  }

  public function fixDatePickerScript(): void {
    wp_add_inline_script( 'wpp_jalali_datepicker', "document.addEventListener('DOMContentLoaded', function () {
          setTimeout(function () {
            jQuery('.acf-date-picker input.hasDatepicker').on('keyup change', function () {
              let acfDatePickerParent = jQuery(this).closest('div.acf-date-picker');
              if (acfDatePickerParent.length)
                acfDatePickerParent.children('input[type=\"hidden\"]').val(jQuery(this).val().replaceAll('-', ''));
            });
          }, 2000);
        });" );
  }

  public function addOptionToDatePickerSettings( $field ): void {
    if ( $field['type'] === 'date_picker' ) {
      acf_render_field_setting( $field, array(
        'label'        => esc_html__( 'Convert to Shamsi Date', 'wp-parsidate' ),
        'instructions' => '',
        'name'         => 'jalali_date',
        'type'         => 'true_false',
        'ui'           => 1,
      ) );
    }
  }

  public function formatDatePickerValue( $currentValue, $value, $postID, $field, $escapeHTML ): ?string {
    if ( $field['type'] === 'date_picker' && $field['jalali_date'] && ! is_admin() ) {
      return $escapeHTML ? esc_html( $escapeHTML ) : $value;
    }

    return $currentValue;
  }

  public function loadDatePickerField( $field ) {
    if ( $field['type'] === 'date_picker' && is_admin() ) {
      $field['display_format'] = 'Y-m-d';
    }

    return $field;
  }

  public function loadDatePickerValue( $value, $postID, $field ) {
    if ( $field['type'] === 'date_picker' && ! empty( $value ) ) {
      if ( ! $field['jalali_date'] && ! is_admin() ) {
        return $value;
      }

      $value = Date::changeDateFormat( $value, 'Ymd', 'Y-m-d' );
      $value = parsidate( is_admin() ? 'Y-m-d' : $field['return_format'], $value, ! is_admin() );
    }

    return $value;
  }

  public function updateDatePickerValue( $value, $postID, $field ) {
    if ( $field['type'] === 'date_picker' && ! empty( $value ) ) {
      if ( is_numeric( $value ) && strlen( $value ) === 8 ) {
        $year  = substr( $value, 0, 4 );
        $month = substr( $value, 4, 2 );
        $day   = substr( $value, 6, 2 );

        $value = "$year-$month-$day";
      }

      $value = gregdate( 'Ymd', $value );
    }

    return $value;
  }

  /**
   *  This function will include the field type class
   *
   * @param               $version (int) major ACF version. Defaults to false
   *
   * @return              void
   * @since               4.0.0
   */
  public function includeField( $version = false ): void {
    $version = $version ? (int) $version : 4;

    include_once( 'ACF/class-wpp-acf-datepicker-v' . (float) $version . '.php' );
    //include_once( 'ACF/class-wpp-acf-timepicker-v' . (float) $version . '.php' );
  }

  public function addSectionSettings( $sections ) {
    $sections[ $this->addonID ] = array(
      'title'        => esc_html__( 'Advanced Custom Fields', 'wp-parsidate' ),
      'desc'         => esc_html__( 'ParsiDate integration for Advanced Custom Fields (ACF)', 'wp-parsidate' ),
      'settings_key' => $this->addonID,
      'settings'     => [
        'acf_start_grid'    => array(
          'id'    => 'edd_start_grid',
          'title' => esc_html__( 'Advanced Custom Fields', 'wp-parsidate' ),
          'type'  => 'startGrid',
        ),
        'fix_date'          => array(
          'id'       => 'fix_date',
          'title'    => esc_html__( 'Jalali Datepicker', 'wp-parsidate' ),
          'type'     => 'toggle',
          'default'  => false,
          'sanitize' => 'bool'
        ),
        'save_persian_date' => array(
          'id'       => 'save_persian_date',
          'title'    => esc_html__( 'Save dates in Jalali format (Not recommended)', 'wp-parsidate' ),
          'type'     => 'toggle',
          'default'  => false,
          'sanitize' => 'bool'
        ),
        'acf_end_grid'      => array(
          'type' => 'endGrid',
        )
      ]
    );

    return $sections;
  }

  public function info(): array {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 256 256"><path fill="url(#a)" d="M56.96 7.041 7.04 56.97A23.95 23.95 0 0 0 0 73.932v170.066C0 250.639 5.36 256 12 256h232c6.64 0 12-5.361 12-12.002V12.002C256 5.36 250.64 0 244 0H73.96C67.6 0 61.48 2.52 57 7.041z"/><g filter="url(#b)"><path fill="#fff" d="M181.129 168.663h-18.533V94.079h49.403v17.368h-30.87v13.309h29.096v16.936h-29.096v26.974z"/><path fill="#002447" d="M157.707 137.362h18.37c-2.718 18.983-18.677 31.538-38.011 31.538-21.172 0-38.41-15.871-38.41-37.243a36.8 36.8 0 0 1 2.831-14.483 36.7 36.7 0 0 1 8.294-12.19A38.47 38.47 0 0 1 138.066 94.1c19.155 0 35.56 12.652 37.892 31.331h-18.351c-5.518-21.159-39.9-19.09-39.9 6.226 0 25.318 34.813 27.183 40 5.711z" opacity=".05"/><path fill="#fff" d="M153.948 137.362c-3.152 10.68-14.369 17.03-25.41 14.371-11.048-2.669-17.97-13.379-15.676-24.266 2.29-10.89 12.973-18.065 24.196-16.248a20.03 20.03 0 0 1 11.997 6.237 20.56 20.56 0 0 1 4.79 7.972h17.814c-2.332-18.722-18.782-31.328-37.892-31.328a38.47 38.47 0 0 0-27.291 10.878 36.7 36.7 0 0 0-8.299 12.195 36.8 36.8 0 0 0-2.83 14.49c0 21.372 17.138 37.237 38.426 37.237 19.319 0 35.232-12.555 37.999-31.538h-17.827z"/><path fill="#002447" d="M98.588 157.812H72.796L68.58 168.65H48.842L79.048 94h13.164l31.407 74.675H102.78l-4.198-10.863zm-18.46-18.478-.647 1.685h12.522l-.434-1.265-5.83-16.008z" opacity=".05"/><path fill="#fff" d="M93.704 157.812h-25.75l-4.213 10.838H44L74.209 94h13.164l31.407 74.675H97.95zm-18.418-18.478-.644 1.685h12.522l-.437-1.265-5.827-16.008-5.611 15.588z"/></g><defs><radialGradient id="a" cx="0" cy="0" r="1" gradientTransform="rotate(45)scale(362.039)" gradientUnits="userSpaceOnUse"><stop stop-color="#0ecad4"/><stop offset="1" stop-color="#006bd6"/></radialGradient><filter id="b" width="183.999" height="90.9" x="36" y="86" color-interpolation-filters="sRGB" filterUnits="userSpaceOnUse"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" result="hardAlpha" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/><feOffset/><feGaussianBlur stdDeviation="4"/><feColorMatrix values="0 0 0 0 0 0 0 0 0 0.141176 0 0 0 0 0.278431 0 0 0 0.1 0"/><feBlend in2="BackgroundImageFix" result="effect1_dropShadow_84_8417"/><feBlend in="SourceGraphic" in2="effect1_dropShadow_84_8417" result="shape"/></filter></defs></svg>';

    return array(
      'id'               => $this->addonID,
      'title'            => esc_html__( 'Advanced Custom Fields', 'wp-parsidate' ),
      'desc'             => esc_html__( 'ParsiDate integration for Advanced Custom Fields (ACF)', 'wp-parsidate' ),
      'force_enable'     => true,
      'icon'             => $svg,
      'tags'             => [ esc_html__( 'Meta', 'wp-parsidate' ) ],
      'cat'              => 'customizations',
      'settings_key'     => $this->addonID,
      'requires_plugins' => [
        'advanced-custom-fields/acf.php' => array(
          'is_wp_plugin' => true,
          'is_free'      => true,
          'plugin_link'  => 'https://wordpress.org/plugins/advanced-custom-fields/',
          'class_check'  => 'ACF'
        )
      ]
    );
  }
}
