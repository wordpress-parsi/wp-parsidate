<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

/**
 * Makes EDD compatible with WP-Parsidate plugin
 *
 * @package                 WP-Parsidate
 * @subpackage              Plugins/EDD
 * @author                  Ehsaan
 */
class WPP_EDD {
	public static $instance = null;

	/**
	 * Hooks required tags
	 */
	private function __construct() {
		add_filter( 'wpp_plugins_compatibility_settings', array( $this, 'add_settings' ) );

		if ( wpp_is_active( 'edd_prices' ) ) {
			add_filter( 'edd_rial_currency_filter_after', 'per_number', 10, 2 );
		}

		if ( wpp_is_active( 'edd_rial_fix' ) ) {
			add_filter( 'edd_rial_currency_filter_after', array( $this, 'rial_fix' ), 10, 2 );
		}
	}

	/**
	 * Returns an instance of class
	 *
	 * @return          WPP_EDD
	 */
	public static function getInstance() {
		if ( self::$instance == null ) {
			self::$instance = new WPP_EDD();
		}

		return self::$instance;
	}

	/**
	 * RIAL fix for EDD
	 */
	public function rial_fix( $price, $did ) {
		return str_replace( 'RIAL', 'ریال', $price );
	}

	/**
	 * Adds settings for toggle fixing
	 *
	 * @param array $old_settings Old settings
	 *
	 * @return          array New settings
	 */
	public function add_settings( $old_settings ) {
		$settings = array(
			'edd'          => array(
				'id'   => 'edd',
				'name' => __( 'Easy Digital Downloads', 'wp-parsidate' ),
				'type' => 'header'
			),
			'edd_prices'   => array(
				'id'      => 'edd_prices',
				'name'    => __( 'Fix prices', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 1,
				'std'     => 0
			),
			'edd_rial_fix' => array(
				'id'      => 'edd_rial_fix',
				'name'    => __( 'Replace ریال with RIAL', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 1,
				'std'     => 0
			)
		);

		return array_merge( $old_settings, $settings );
	}
}

return WPP_EDD::getInstance();