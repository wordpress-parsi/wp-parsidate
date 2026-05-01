<?php

namespace WPParsidate\App\Integration\WooCommerce;

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

use WPParsidate\Helper\Cache;
use WPParsidate\Settings\Settings;

/**
 * Add Iranian payment gateways to WP-Parsidate
 *
 * @package                 WP-Parsidate
 * @subpackage              Plugins/WooCommerce/PaymentGateways
 */
class WcGateways {
  public static $instance = null;

  /**
   * Hooks required tags
   */
  public function __construct() {
    add_action( 'before_woocommerce_init', [ $this, 'includeFiles' ] );
    add_filter( 'wp_parsidate_woocommerce_settings_options', [ $this, 'addSettings' ] );
    add_filter( 'woocommerce_payment_gateways', [ $this, 'registerSelectedGateways' ] );
    add_action( 'woocommerce_blocks_loaded', [ $this, 'registerOrderApprovalPaymentMethodType' ] );
  }

  public function gateways(): array {
    return array(
      'parsian'  => esc_html__( 'Parsian Bank', 'wp-parsidate' ),
      'pasargad' => esc_html__( 'Pasargad Bank', 'wp-parsidate' ),
      'mellat'   => esc_html__( 'Mellat Bank (Behpardakht)', 'wp-parsidate' ),
      'melli'    => esc_html__( 'Melli Bank (Sadad)', 'wp-parsidate' ),
    );
  }

  /**
   * Includes files for plugin
   *
   * @return         void
   * @since          2.0
   */
  public function includeFiles(): void {
    $implemented_gateways = array_keys( $this->gateways() );

    $selected_gateways = $this->getSelectedGateways();
    $maybe_include     = array_intersect( $implemented_gateways, $selected_gateways );

    foreach ( $maybe_include as $filename ) {
      $file_path = __DIR__ . "/wc-gateways/wpp-$filename-gateway.php";

      if ( file_exists( $file_path ) ) {
        require_once( $file_path );
      }
    }
  }

  /**
   * Returns an instance of class
   *
   * @return          WcGateways
   */
  public static function getInstance(): ?WcGateways {
    if ( self::$instance === null ) {
      self::$instance = new WcGateways();
    }

    return self::$instance;
  }

  /**
   * Adds settings for toggle fixing
   *
   * @param  array  $wooSettings  WooCommerce section settings
   *
   * @return          array New settings
   * @since 4.0.0
   */
  public function addSettings( $wooSettings ): array {
    $gateWays        = $this->gateways();
    $gateWaySettings = [];

    foreach ( $gateWays as $code => $name ) {
      $gateWaySettings[ $code . '_gateway_enable' ] = array(
        'id'       => $code . '_gateway_enable',
        'title'    => $name,
        'type'     => 'toggle',
        'default'  => false,
        'sanitize' => 'bool'
      );
    }

    $settings                          = array(
      'woo_gateways_start_grid' => array(
        'id'    => 'woo_product_start_grid',
        'title' => esc_html__( 'Payment Gateways', 'wp-parsidate' ),
        'type'  => 'startGrid',
      )
    );
    $settings                          = array_merge( $settings, $gateWaySettings );
    $settings['woo_gateways_end_grid'] = array( 'type' => 'endGrid' );

    $wooSettings['settings'] = array_merge( $wooSettings['settings'], $settings );

    return $wooSettings;
  }

  /**
   * @param $methods
   *
   * @return mixed
   * @since 5.0.0
   */
  public function registerSelectedGateways( $methods ) {
    $selected_pgs = $this->getSelectedGateways();

    if ( empty( $selected_pgs ) ) {
      return $methods;
    }

    foreach ( $selected_pgs as $method ) {
      $methods[] = 'WPP_WC_' . ucfirst( $method ) . '_Gateway';
    }

    return $methods;
  }

  public function registerOrderApprovalPaymentMethodType(): void {
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
      return;
    }

    add_action( 'woocommerce_blocks_payment_method_type_registration',
      function ( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
        $implemented_gateways = array_keys( $this->gateways() );

        $selected_gateways = self::getSelectedGateways();
        $maybe_include     = array_intersect( $implemented_gateways, $selected_gateways );

        foreach ( $maybe_include as $gateway ) {
          $block_path = __DIR__ . "/wc-gateways/blocks/wpp-$gateway-pg-block.php";

          if ( file_exists( $block_path ) ) {
            require_once( $block_path );

            $class_name = 'WPP_WC_' . ucfirst( $gateway ) . '_Gateway_Blocks';

            $payment_method_registry->register( new $class_name );
          }
        }
      }
    );
  }

  public function is_soap_enabled() {
    return extension_loaded( 'soap' );
  }

  private function getSelectedGateways() {
    $cache = Cache::get( 'woocommerce_active_gateways', false );
    if ( is_array( $cache ) ) {
      return $cache;
    }

    $gateWays       = array_keys( $this->gateways() );
    $activeGateways = [];
    foreach ( $gateWays as $code ) {
      if ( Settings::get( $code . '_gateway_enable', false, 'woocommerce' ) ) {
        $activeGateways[] = $code;
      }
    }

    $activeGateways = apply_filters( 'wpp_get_selected_wc_payment_gateways', $activeGateways );
    Cache::set( 'woocommerce_active_gateways', $activeGateways );

    return $activeGateways;
  }
}
