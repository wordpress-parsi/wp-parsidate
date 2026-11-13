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
  public static ?WcGateways $instance = null;

  /**
   * Gateways this plugin loads in the current request (enabled and not taken over by ParsiGate)
   *
   * @var array|null
   */
  private ?array $selectedGateways = null;

  /**
   * Gateways switched on in Parsi Date settings, taken over or not
   *
   * @var array|null
   */
  private ?array $enabledGateways = null;

  /**
   * Hooks required tags
   */
  public function __construct() {
    add_action( 'before_woocommerce_init', [ $this, 'includeFiles' ] );
    add_filter( 'wp_parsidate_woocommerce_settings_options', [ $this, 'addSettings' ] );
    add_filter( 'woocommerce_payment_gateways', [ $this, 'registerSelectedGateways' ] );
    add_action( 'woocommerce_blocks_loaded', [ $this, 'registerOrderApprovalPaymentMethodType' ] );

    new GatewayMigration( $this );
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
   * @param array $wooSettings WooCommerce section settings
   *
   * @return          array New settings
   * @since 4.0.0
   */
  public function addSettings( $wooSettings ): array {
    // The built-in gateways are deprecated in favour of ParsiGate. Sites that never
    // switched one on no longer see the toggles, so no new site starts using them.
    $gateWays        = array_intersect_key( $this->gateways(), array_flip( $this->getEnabledGateways() ) );
    $takenOver       = $this->getTakenOverGateways();
    $gateWaySettings = [];

    if ( empty( $gateWays ) ) {
      return $wooSettings;
    }

    foreach ( $gateWays as $code => $name ) {
      $suffix = in_array( $code, $takenOver, true )
        /* translators: %s: Gateway name */
        ? esc_html__( '%s (moved to ParsiGate)', 'wp-parsidate' )
        /* translators: %s: Gateway name */
        : esc_html__( '%s (deprecated, move to ParsiGate)', 'wp-parsidate' );

      $gateWaySettings[ $code . '_gateway_enable' ] = array(
        'id'       => $code . '_gateway_enable',
        'title'    => sprintf( $suffix, $name ),
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

        $selected_gateways = $this->getSelectedGateways();
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

  /**
   * Gateways this plugin should load: switched on in settings and not taken over by ParsiGate
   *
   * Kept for the current request only; the old never-expiring object cache entry
   * stayed stale on sites with a persistent object cache.
   *
   * @return array Gateway codes
   */
  private function getSelectedGateways(): array {
    if ( $this->selectedGateways !== null ) {
      return $this->selectedGateways;
    }

    $activeGateways = array_values( array_diff( $this->getEnabledGateways(), $this->getTakenOverGateways() ) );

    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy public hook name, kept for backward compatibility.
    $activeGateways = apply_filters( 'wpp_get_selected_wc_payment_gateways', $activeGateways );

    $this->selectedGateways = is_array( $activeGateways ) ? $activeGateways : [];

    // Drop the stale entry older versions left in a persistent object cache.
    Cache::delete( 'woocommerce_active_gateways', false );

    return $this->selectedGateways;
  }

  /**
   * Gateways switched on in Parsi Date settings, whether ParsiGate took them over or not
   *
   * @return array Gateway codes
   */
  public function getEnabledGateways(): array {
    if ( $this->enabledGateways !== null ) {
      return $this->enabledGateways;
    }

    $this->enabledGateways = [];

    foreach ( array_keys( $this->gateways() ) as $code ) {
      if ( Settings::get( $code . '_gateway_enable', false, 'woocommerce' ) ) {
        $this->enabledGateways[] = $code;
      }
    }

    return $this->enabledGateways;
  }

  /**
   * Gateways ParsiGate has taken over
   *
   * ParsiGate adds a gateway code to this filter only after it has imported that
   * gateway's settings and handles its old callback URL (wc-api=wpp_wc_*_gateway).
   * Parsi Date then stops loading its own copy, so the two never run side by side.
   *
   * @return array Gateway codes
   */
  public function getTakenOverGateways(): array {
    $takenOver = apply_filters( 'wp_parsidate_legacy_gateways_taken_over', [] );

    if ( ! is_array( $takenOver ) ) {
      return [];
    }

    return array_values( array_intersect( array_keys( $this->gateways() ), $takenOver ) );
  }

  /**
   * Gateways that still run from Parsi Date and must move to ParsiGate
   *
   * @return array Gateway codes
   */
  public function getPendingMigrationGateways(): array {
    return array_values( array_diff( $this->getEnabledGateways(), $this->getTakenOverGateways() ) );
  }

  /**
   * Whether the gateway is enabled in the WooCommerce payment settings
   *
   * @param string $code Gateway code
   *
   * @return bool True, if customers can pay with it
   */
  public function isLiveInWooCommerce( string $code ): bool {
    $options = get_option( 'woocommerce_' . $code . '_settings', [] );

    return is_array( $options ) && ( $options['enabled'] ?? 'no' ) === 'yes';
  }
}
