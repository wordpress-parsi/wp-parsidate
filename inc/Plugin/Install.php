<?php
/**
 * Plugin installer helper
 *
 * @author              Parsa Kafi
 * @package             WP-Parsidate
 * @subpackage          Core/Install
 */

namespace WPParsidate\Plugin;

use WPParsidate\Helper\WordPress;

class Install {
  public static function run(): void {
    $pluginData     = get_plugin_data( WP_PARSI_ROOT );
    $currentVersion = $pluginData['Version'];
    $oldVersion     = get_option( WP_PARSI_KEY . '_plugin_version', '5.1.8' );
    $oldSettings    = get_option( 'wpp_settings', [] );
    $settings       = get_option( WP_PARSI_KEY, [] );

    if ( ! empty( $oldSettings ) && empty( $settings ) && version_compare( $oldVersion, '6.0', '<' ) ) {
      $pluginSettings = array(
        // Core
        'admin_lang'            => self::isEnable( $oldSettings, 'admin_lang' ),
        'user_lang'             => self::isEnable( $oldSettings, 'user_lang' ),
        'persian_date'          => self::isEnable( $oldSettings, 'persian_date' ),
        'months_name_type'      => $oldSettings['months_name_type'] ?? 'persian',
        'disable_widget_block'  => self::isEnable( $oldSettings, 'disable_widget_block' ),
        'enable_fonts'          => self::isEnable( $oldSettings, 'enable_fonts' ),
        'debug_mode'            => self::isEnable( $oldSettings, 'dev_mode' ),
        'multilingual_support'  => self::isEnable( $oldSettings, 'wpp_multilingual_support' ),

        // Convert
        'conv_page_title'       => self::isEnable( $oldSettings, 'conv_page_title' ),
        'conv_title'            => self::isEnable( $oldSettings, 'conv_title' ),
        'conv_contents'         => self::isEnable( $oldSettings, 'conv_contents' ),
        'conv_excerpt'          => self::isEnable( $oldSettings, 'conv_excerpt' ),
        'conv_comments'         => self::isEnable( $oldSettings, 'conv_comments' ),
        'conv_comment_count'    => self::isEnable( $oldSettings, 'conv_comment_count' ),
        'conv_dates'            => self::isEnable( $oldSettings, 'conv_dates' ),
        'conv_cats'             => self::isEnable( $oldSettings, 'conv_cats' ),
        'conv_arabic'           => self::isEnable( $oldSettings, 'conv_arabic' ),
        'conv_permalinks'       => self::isEnable( $oldSettings, 'conv_permalinks' ),

        // Tools
        'date_in_admin_bar'     => self::isEnable( $oldSettings, 'date_in_admin_bar' ),

        // Integration
        'hook_deactivator_list' => $oldSettings['dis_input'] ?? '',
      );
      update_option( WP_PARSI_KEY, $pluginSettings, false );

      // WooCommerce Settings
      if ( WordPress::isPluginActivated( 'woocommerce/woocommerce.php' ) ) {
        $wooSettings = array(
          'fix_prices'           => self::isEnable( $oldSettings, 'woo_per_price' ),
          'fix_persian_postcode' => self::isEnable( $oldSettings, 'woo_accept_per_postcode' ),
          'fix_persian_phone'    => self::isEnable( $oldSettings, 'woo_accept_per_phone' ),
          'dropdown_cities'      => self::isEnable( $oldSettings, 'woo_dropdown_cities' ),
          'validate_postcode'    => self::isEnable( $oldSettings, 'woo_validate_postcode' ),
          'validate_phone'       => self::isEnable( $oldSettings, 'woo_validate_phone' ),
        );

        if ( ! empty( $oldSettings['woo_gateways'] ) && is_array( $oldSettings['woo_gateways'] ) ) {
          $activeGateWays = array_keys( $oldSettings['woo_gateways'] );
          $gateWays       = array( 'parsian', 'pasargad', 'mellat', 'melli' );
          foreach ( $gateWays as $gateWay ) {
            if ( in_array( $gateWay, $activeGateWays, true ) ) {
              $wooSettings[ $gateWay . '_gateway_enable' ] = true;
            }
          }
        }

        update_option( WP_PARSI_KEY . '_woocommerce', $wooSettings, false );
      }

      // ACF Settings
      if ( WordPress::isPluginActivated( 'advanced-custom-fields/acf.php' ) ) {
        $acfSettings = array(
          'fix_date'          => self::isEnable( $oldSettings, 'acf_fix_date' ),
          'save_persian_date' => self::isEnable( $oldSettings, 'acf_persian_date' ),
        );

        update_option( WP_PARSI_KEY . '_acf', $acfSettings, false );
      }

      // EDD Settings
      if ( WordPress::isPluginActivated( 'easy-digital-downloads/easy-digital-downloads.php' ) ) {
        $eddSettings = array(
          'fix_prices'   => self::isEnable( $oldSettings, 'edd_prices' ),
          'fix_currency' => self::isEnable( $oldSettings, 'edd_rial_fix' ),
        );

        update_option( WP_PARSI_KEY . '_edd', $eddSettings, false );
      }
    }

    update_option( WP_PARSI_KEY . '_plugin_version', $currentVersion, false );
  }

  /**
   * Check old setting is enable
   */
  private static function isEnable( $array, $key ): bool {
    return isset( $array[ $key ] ) && $array[ $key ] === 'enable';
  }
}
