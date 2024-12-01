<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

if ( ! class_exists( 'WPP_EDD' ) ) {

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

			add_filter( 'wpp_settings_tabs', array( $this, 'add_tab' ) );
			add_filter( 'wpp_registered_settings', array( $this, 'add_settings' ) );

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
		 * Add EDD settings tab
		 *
		 * @param $tabs
		 *
		 * @return mixed
		 * @sicne 5.1.3
		 */
		public function add_tab( $tabs ) {
			$about_tab = $tabs['about'];

			unset( $tabs['about'] );

			$tabs['edd']   = __( 'Easy Digital Downloads', 'wp-parsidate' );
			$tabs['about'] = $about_tab;

			return $tabs;
		}

		/**
		 * Adds settings for toggle fixing
		 *
		 * @param array $old_settings Old settings
		 *
		 * @return          array New settings
		 */
		public function add_settings( $old_settings ) {
			$settings['edd'] = apply_filters( 'wpp_edd_settings', array(
				'edd_header'   => array(
					'id'   => 'edd_header',
					'name' => __( 'Localization', 'wp-parsidate' ),
					'type' => 'header'
				),
				'edd_prices'   => array(
					'id'      => 'edd_prices',
					'name'    => __( 'Fix prices', 'wp-parsidate' ),
					'type'    => 'checkbox',
					'options' => 1,
					'std'     => 0,
				),
				'edd_rial_fix' => array(
					'id'      => 'edd_rial_fix',
					'name'    => __( 'Replace ریال with RIAL', 'wp-parsidate' ),
					'type'    => 'checkbox',
					'options' => 1,
					'std'     => 0,
				),
				'edd_footer'   => array(
					'id'   => 'edd_footer',
					'type' => 'footer'
				),
			) );

			return array_merge( $old_settings, $settings );
		}

		/**
		 * RIAL fix for EDD
		 */
		public function rial_fix( $price, $did ) {
			return str_replace( 'RIAL', 'ریال', $price );
		}
	}

	return WPP_EDD::getInstance();
}