<?php
/**
 * Makes Pods compatible with WP-Parsidate
 *
 * Pods date and datetime fields keep Gregorian values in storage. This addon
 * replaces their visible inputs with Jalali datepicker controls and converts
 * submitted values before Pods validation and pre-save processing.
 *
 * @package    WP-Parsidate
 * @subpackage Plugins/Pods
 */

namespace WPParsidate\App\Integration;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Addons\Addon;
use WPParsidate\Helper\{Assets, Number};
use WPParsidate\Core\Names;

class Pods extends Addon {
  public string $addonID = 'pods';
  public string $currentTab = 'integration';

  private const assetPrefix = WP_PARSI_KEY . '_pods';
  private const assetHandle = self::assetPrefix . '-jalali-datepicker';

  public function initAction(): void {
    if ( ! class_exists( '\Pods', false ) && ! function_exists( 'pods' ) ) {
      return;
    }

    if ( ! $this->getSetting( 'jalali_date_fields', true ) ) {
      return;
    }

    add_filter( 'pods_field_date_validate', [ $this, 'validateDateField' ], 10, 2 );
    add_filter( 'pods_field_datetime_validate', [ $this, 'validateDateField' ], 10, 2 );
    add_filter( 'pods_api_pre_save_pod_item', [ $this, 'preparePodItemDates' ], 10, 3 );

    add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ], 20 );
    add_action( 'wp_enqueue_scripts', [ $this, 'enqueueAssets' ], 20 );
  }

  /**
   * Validate Gregorian and Jalali date/datetime values without changing them.
   */
  public function validateDateField( $valid, $value ): bool {
    if ( true !== $valid || ! is_string( $value ) || '' === trim( $value ) ) {
      return is_bool( $valid ) ? $valid : false;
    }

    $type = current_filter() === 'pods_field_datetime_validate' ? 'datetime' : 'date';

    return self::isValidValue( $value, $type );
  }

  /**
   * Normalize date/datetime values before Pods runs field pre-save handlers.
   *
   * @param array $pieces Pieces returned by Pods for the save operation.
   *
   * @return array
   */
  public function preparePodItemDates( array $pieces ): array {
    foreach ( [ 'fields', 'object_fields', 'custom_fields', 'custom_data' ] as $pieceName ) {
      if ( empty( $pieces[ $pieceName ] ) || ! is_array( $pieces[ $pieceName ] ) ) {
        continue;
      }

      foreach ( $pieces[ $pieceName ] as $fieldName => &$fieldData ) {
        if ( is_array( $fieldData ) && isset( $fieldData['type'], $fieldData['value'] )
             && in_array( $fieldData['type'], [ 'date', 'datetime' ], true )
        ) {
          $fieldData['value'] = self::toGregorianStorage( $fieldData['value'], $fieldData['type'] );
          continue;
        }

        if ( in_array( $pieceName, [ 'custom_fields', 'custom_data' ], true ) && is_string( $fieldName ) ) {
          $fieldType = $this->findFieldType( $pieces, $fieldName );
          if ( $fieldType ) {
            $pieces[ $pieceName ][ $fieldName ] = self::toGregorianStorage( $fieldData, $fieldType );
          }
        }
      }
      unset( $fieldData );
    }

    return $pieces;
  }

  private function findFieldType( array $pieces, string $fieldName ): ?string {
    foreach ( [ 'fields', 'object_fields' ] as $pieceName ) {
      if ( empty( $pieces[ $pieceName ][ $fieldName ] ) || ! is_array( $pieces[ $pieceName ][ $fieldName ] ) ) {
        continue;
      }

      $type = $pieces[ $pieceName ][ $fieldName ]['type'] ?? '';
      if ( in_array( $type, [ 'date', 'datetime' ], true ) ) {
        return $type;
      }
    }

    return null;
  }

  public static function isValidValue( $value, string $type = 'date' ): bool {
    if ( ! is_string( $value ) ) {
      return false;
    }

    $value = trim( $value );
    if ( '' === $value ) {
      return true;
    }

    $gregorian = self::toGregorianStorage( $value, $type );

    if ( 'datetime' === $type ) {
      return (bool) preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $gregorian );
    }

    return (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $gregorian );
  }

  /**
   * Convert a Persian or Gregorian value to Pods Gregorian storage format.
   *
   * @param mixed $value
   * @param string $type date|datetime
   *
   * @return mixed
   */
  public static function toGregorianStorage( $value, string $type = 'date' ) {
    if ( is_array( $value ) ) {
      foreach ( $value as $key => $item ) {
        $value[ $key ] = self::toGregorianStorage( $item, $type );
      }

      return $value;
    }

    if ( ! is_string( $value ) ) {
      return $value;
    }

    $value = Number::toEnglish( trim( $value ) );
    if ( '' === $value ) {
      return $value;
    }

    if ( 'datetime' === $type ) {
      $converted = gregdate( 'Y-m-d H:i:s', $value );
      if ( is_string( $converted ) && preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $converted ) ) {
        return $converted;
      }

      return $value;
    }

    $converted = gregdate( 'Y-m-d', $value );
    if ( is_string( $converted ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $converted ) ) {
      return $converted;
    }

    return $value;
  }

  /**
   * Convert a Gregorian Pods storage value to a Jalali display value.
   */
  public static function toJalaliDisplay( $value, string $type = 'date' ): string {
    if ( ! is_string( $value ) || '' === trim( $value ) ) {
      return $value;
    }

    $displayFormat = 'datetime' === $type ? 'Y-m-d H:i:s' : 'Y-m-d';

    return parsidate( $displayFormat, trim( $value ), 'eng' );
  }

  public function enqueueAssets(): void {
    if ( ! $this->getSetting( 'jalali_date_fields', true ) ) {
      return;
    }

    $pluginVersion = Assets::getVersion();
    $debugName     = WP_PARSI_DEBUG_MODE ? '' : '.min';
    $engineHandle  = WP_PARSI_KEY . '_jalali_date';
    $libraryHandle = WP_PARSI_KEY . '_jalali_datepicker';
    $isAdmin       = is_admin();

    if ( ! wp_script_is( $engineHandle, 'registered' ) && ! wp_script_is( $engineHandle, 'enqueued' ) ) {
      wp_enqueue_script( $engineHandle, Assets::url( 'js-admin/jalali-date' . $debugName . '.js' ), [], $pluginVersion, [ 'in_footer' => true ] );
    }

    if ( ! wp_script_is( $libraryHandle, 'registered' ) && ! wp_script_is( $libraryHandle, 'enqueued' ) ) {
      wp_enqueue_script( $libraryHandle, Assets::url( 'js-admin/jalalidatepicker.min.js' ), [ $engineHandle ], $pluginVersion, [ 'in_footer' => true ] );
      wp_enqueue_style( $libraryHandle, Assets::url( 'css-admin/jalalidatepicker' . $debugName . '.css' ), null, $pluginVersion );
      // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
      do_action( 'wp_parsidate_jalali_datepicker_enqueued', 'pods' );
    }

    wp_enqueue_script(
      self::assetHandle,
      Assets::url( ( $isAdmin ? 'js-admin' : 'js' ) . '/pods-jalali-datepicker' . $debugName . '.js' ),
      [ $libraryHandle, $engineHandle ],
      $pluginVersion,
      [ 'in_footer' => true ]
    );

    $months = Names::getMonths();
    array_shift( $months );
    wp_localize_script( self::assetHandle, 'WpPdPodsJalali', [
      'context'       => $isAdmin ? 'admin' : 'front',
      'months'        => $months,
      'persianDigits' => true,
      'dateSeparator' => $isAdmin ? '-' : '/',
    ] );
  }

  public function addSectionSettings( $sections ): array {
    $sections[ $this->addonID ] = [
      'title'        => esc_html__( 'Pods', 'wp-parsidate' ),
      'desc'         => esc_html__( 'ParsiDate integration for Pods', 'wp-parsidate' ),
      'settings_key' => $this->addonID,
      'settings'     => [
        'pods_start_grid'    => [
          'id'    => 'pods_start_grid',
          'title' => esc_html__( 'Date Fields', 'wp-parsidate' ),
          'type'  => 'startGrid',
        ],
        'jalali_date_fields' => [
          'id'       => 'jalali_date_fields',
          'title'    => esc_html__( 'Jalali date and datetime fields', 'wp-parsidate' ),
          'desc'     => esc_html__( 'Replace Pods date and datetime fields with a Jalali picker. Values remain stored in Gregorian format.', 'wp-parsidate' ),
          'type'     => 'toggle',
          'default'  => true,
          'sanitize' => 'bool',
        ],
        'pods_end_grid'      => [
          'type' => 'endGrid',
        ],
      ],
    ];

    return $sections;
  }

  public function info(): array {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2" viewBox="0 0 256 256"><rect width="100%" height="100%" style="fill:#95bf3b"/><path d="M.267 87.946c0 48.531 39.652 87.873 88.563 87.873 35.978 0 66.946-21.286 80.808-51.858-3.861-11.707-7.202-32.304 6.124-52.891a87 87 0 0 0-2.135-8.561c-40.865 40.27-73.857 44.041-98.698 43.385-.229-.007-1.065-.142-1.287-.639 0 0-.362-1.61.697-1.61 47.59 0 74.694-27.787 96.838-48.104a87 87 0 0 0-4.105-8.804C143.2 40.221 134.027 20.819 130.77 10.53 118.287 3.858 104.004.071 88.83.071 39.919.071.267 39.414.267 87.946m112.672-61.394s33.811-3.651 43.768 37.807c-36.947-2.528-43.768-37.807-43.768-37.807M99.411 43.996s29.976-.993 36.8 36.204c-32.451-4.659-36.8-36.204-36.8-36.204M82.946 58.779s26.976-1.747 33.879 31.561c-29.323-3.272-33.879-31.561-33.879-31.561m73.761 5.58.112.008-.081.121zm-88.116 6.168s23.703-1.538 29.77 27.729c-25.766-2.873-29.77-27.729-29.77-27.729M49.6 73.397s22.77-1.474 28.597 26.638C53.448 97.274 49.6 73.397 49.6 73.397M30.803 74.69s20.665-1.337 25.953 24.176C34.294 96.36 30.803 74.69 30.803 74.69m-20.438 3.735s17.336-4.523 24.805 16.209q-1.219.098-2.365.097c-17.393 0-22.44-16.306-22.44-16.306m147.552 1.726-.039-.116.12.033zc12.905 38.6-10.08 59.609-10.08 59.609s-16.151-32.938 10.08-59.609m-21.706.049.096.014-.075.1zm-19.386 10.14.088.01-.066.091zm22.091 3.347-.012-.116.102.064zc4.038 38.816-21.133 51.555-21.133 51.555s-8.134-34.563 21.133-51.555m-103.69.942-.034.067-.022-.062zm63.135 3.627.075.008-.056.083zm-41.605.61.068.008-.051.07zm-30.572 6.342s14.58-13.774 33.023 1.585c-5.633 4.776-10.964 6.419-15.634 6.419-10.305 0-17.389-8.004-17.389-8.004m52.013-5.173.074.008-.056.08zm38.321 4.461.001-.104.084.071zc-.346 34.802-23.83 42.496-23.83 42.496s-3.693-31.626 23.83-42.496m-57.258 2.251v.09l-.053-.044zm-11.982 18.878s7.771-20.057 30.091-16.15c-6.381 14.435-16.419 17.18-23.124 17.18-4.11 0-6.967-1.03-6.967-1.03M77.4 109.404l.033.082-.064-.011zm18.308 1.226.016-.089.063.077zc-5.34 29.922-26.693 31.805-26.693 31.805s1.4-28.003 26.693-31.805" style="fill:#fff" transform="translate(39.985 40.398)"/><path d="M99.873 6.253c-51.625 0-93.623 42-93.623 93.623s41.998 93.623 93.623 93.623 93.625-41.999 93.625-93.623c0-51.623-41.999-93.623-93.625-93.623m0 193.415c-55.025 0-99.791-44.767-99.791-99.792S44.848.085 99.873.085c55.026 0 99.793 44.766 99.793 99.791s-44.767 99.792-99.793 99.792" style="fill:#fff" transform="translate(28.468 28.468)"/></svg>';

    return [
      'id'               => $this->addonID,
      'title'            => esc_html__( 'Pods', 'wp-parsidate' ),
      'desc'             => esc_html__( 'ParsiDate integration for Pods date and datetime fields', 'wp-parsidate' ),
      'force_enable'     => false,
      'icon'             => $svg,
      'image_link'       => 'https://wordpress.org/plugins/pods/',
      'tags'             => [ esc_html__( 'Meta', 'wp-parsidate' ) ],
      'cat'              => 'integration',
      'settings_key'     => $this->addonID,
      'requires_plugins' => [
        'pods/init.php' => [
          'is_wp_plugin' => true,
          'is_free'      => true,
          'plugin_link'  => 'https://wordpress.org/plugins/pods/',
          'class_check'  => 'Pods',
          'define_check' => 'PODS_VERSION',
        ],
      ],
    ];
  }
}
