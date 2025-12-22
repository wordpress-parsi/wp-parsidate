<?php
/**
 * Makes EDD compatible with WP-Parsidate plugin
 *
 * @package                 WP-Parsidate
 * @subpackage              Plugins/EDD
 * @author                  Ehsaan
 */

namespace WPParsidate\App\Integration;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Addons\Addon;
use WPParsidate\Helper\Number;

class EDD extends Addon {
  public string $addonID = 'edd';
  public string $currentTab = 'integration';

  public function initAction(): void {
    if ( $this->getSetting( 'add_toman_currency', false ) ) {
      add_filter( 'edd_currencies', [ $this, 'addTomanCurrency' ] );
    }

    if ( $this->getSetting( 'fix_prices', false ) ) {
      add_filter( 'edd_rial_currency_filter_after', [ $this, 'fixNumbersToPersian' ], 10, 2 );
    }

    if ( $this->getSetting( 'fix_currency', false ) ) {
      add_filter( 'edd_rial_currency_filter_after', [ $this, 'fixCurrency' ], 10, 2 );
      add_filter( 'edd_toman_currency_filter_after', [ $this, 'fixCurrency' ], 10, 2 );
    }

    if ( $this->getSetting( 'remove_decimals', false ) ) {
      add_filter( 'edd_format_amount_decimals', [ $this, 'removeDecimals' ], 10, 3 );
    }
  }

  public function removeDecimals( $decimals, $amount, $currencyCode ): int {
    if ( in_array( $currencyCode, [ 'RIAL', 'TOMAN' ] ) ) {
      return 0;
    }

    return $decimals;
  }

  public function addTomanCurrency( $currencies ) {
    $currencies['TOMAN'] = esc_html__( 'Iran Toman (Toman)', 'wp-parsidate' );

    return $currencies;
  }

  /**
   * Change english number to persian
   */
  public function fixNumbersToPersian( $content ): string {
    return Number::fixNumber( $content );
  }

  /**
   * RIAL fix for EDD
   */
  public function fixCurrency( $price, $did ) {
    return str_replace( [ 'RIAL', 'TOMAN' ], [ 'ریال', 'تومان' ], $price );
  }

  public function addSectionSettings( $sections ) {
    $sections[ $this->addonID ] = array(
      'title'        => esc_html__( 'Easy Digital Downloads', 'wp-parsidate' ),
      'desc'         => esc_html__( 'ParsiDate integration for Easy Digital Downloads', 'wp-parsidate' ),
      'settings_key' => $this->addonID,
      'settings'     => [
        'edd_price_start_grid'    => array(
          'id'    => 'edd_start_grid',
          'title' => esc_html__( 'Price', 'wp-parsidate' ),
          'type'  => 'startGrid',
        ),
        'fix_prices'              => array(
          'id'       => 'fix_prices',
          'title'    => esc_html__( 'Fix prices', 'wp-parsidate' ),
          'type'     => 'toggle',
          'default'  => false,
          'sanitize' => 'bool'
        ),
        'remove_decimals'         => array(
          'id'       => 'remove_decimals',
          'title'    => esc_html__( 'Remove decimals', 'wp-parsidate' ),
          'type'     => 'toggle',
          'default'  => false,
          'sanitize' => 'bool'
        ),
        'edd_price_end_grid'      => array(
          'type' => 'endGrid',
        ),
        'edd_currency_start_grid' => array(
          'id'    => 'edd_start_grid',
          'title' => esc_html__( 'Currency', 'wp-parsidate' ),
          'type'  => 'startGrid',
        ),
        'add_toman_currency'      => array(
          'id'       => 'add_toman_currency',
          'title'    => esc_html__( 'Add "Toman" currency', 'wp-parsidate' ),
          'type'     => 'toggle',
          'default'  => false,
          'sanitize' => 'bool'
        ),
        'fix_currency'            => array(
          'id'       => 'fix_currency',
          'title'    => esc_html__( 'Replace currency name', 'wp-parsidate' ),
          'type'     => 'toggle',
          'default'  => false,
          'sanitize' => 'bool'
        ),
        'edd_currency_end_grid'   => array(
          'type' => 'endGrid',
        )
      ]
    );

    return $sections;
  }

  public function info(): array {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" clip-rule="evenodd" viewBox="0 0 105 105"><g fill="#35495c"><path d="M89.635 15.378C80.136 5.876 67.007 0 52.51 0 38.009 0 24.882 5.876 15.38 15.378 5.876 24.88 0 38.009 0 52.508s5.876 27.628 15.378 37.13c9.502 9.504 22.631 15.378 37.132 15.378 14.499 0 27.628-5.879 37.127-15.378 9.502-9.504 15.378-22.631 15.378-37.13s-5.878-27.628-15.38-37.13m-1.732 72.524c-9.057 9.058-21.572 14.661-35.395 14.661S26.169 96.96 17.111 87.902 2.45 66.33 2.45 52.508c0-13.823 5.603-26.337 14.661-35.395S38.683 2.452 52.506 2.452s26.337 5.603 35.395 14.661 14.66 21.572 14.66 35.395c.002 13.822-5.601 26.337-14.658 35.394"/><path d="M97.678 52.07C97.442 27.324 77.311 7.336 52.51 7.336c-24.803 0-44.936 19.99-45.168 44.736L28.054 31.36 35 38.306 19.843 53.463h65.333L70.019 38.306l6.946-6.946zm-45.17-7.762L33.262 24.112h12.79v-9.955c0-2.584 2.906-4.702 6.456-4.702s6.456 2.116 6.456 4.702v9.955h12.79zM58.122 77.93c-1.026-.667-2.326-1.267-3.894-1.788-1.199-.413-2.186-.803-2.956-1.179-.764-.372-1.328-.785-1.689-1.221-.37-.444-.543-.976-.543-1.586 0-.488.147-.952.462-1.391.309-.438.792-.803 1.448-1.079.663-.28 1.507-.431 2.555-.436.844.004 1.615.072 2.312.193.689.125 1.3.276 1.823.449.53.179.958.348 1.295.507l1.166-3.516c-.705-.354-1.588-.648-2.66-.886-.91-.201-1.952-.317-3.135-.357v-3.638h-3.205v3.82q-.772.126-1.473.33c-1.179.348-2.192.831-3.03 1.462-.831.628-1.475 1.363-1.917 2.205-.444.842-.663 1.77-.672 2.768q.01 1.733.906 3.052.897 1.318 2.547 2.297c1.094.652 2.4 1.214 3.91 1.687 1.133.363 2.057.742 2.761 1.131q1.059.58 1.542 1.28c.328.466.49 1.006.484 1.61 0 .663-.195 1.238-.575 1.735q-.565.737-1.663 1.148c-.731.276-1.623.411-2.669.42a15 15 0 0 1-2.481-.217 16 16 0 0 1-2.24-.562 12 12 0 0 1-1.838-.766l-1.127 3.662c.51.28 1.146.532 1.925.764q1.168.35 2.575.562c.932.138 1.89.208 2.864.217l.171-.002v3.761h3.205v-4.063a12 12 0 0 0 1.162-.282q1.93-.579 3.199-1.591c.851-.67 1.481-1.446 1.899-2.321a6.4 6.4 0 0 0 .624-2.787c0-1.155-.247-2.177-.757-3.056-.515-.887-1.281-1.664-2.311-2.336"/></g></svg>';

    return array(
      'id'               => $this->addonID,
      'title'            => esc_html__( 'Easy Digital Downloads', 'wp-parsidate' ),
      'desc'             => esc_html__( 'ParsiDate integration for Easy Digital Downloads', 'wp-parsidate' ),
      'force_enable'     => true,
      'icon'             => $svg,
      'tags'             => [ esc_html__( 'Download', 'wp-parsidate' ) ],
      'cat'              => 'ecommerce',
      'settings_key'     => $this->addonID,
      'requires_plugins' => [
        'easy-digital-downloads/easy-digital-downloads.php' => array(
          'is_wp_plugin'   => true,
          'is_free'        => true,
          'plugin_link'    => 'https://wordpress.org/plugins/easy-digital-downloads/',
          'function_check' => 'EDD'
        )
      ]
    );
  }
}
