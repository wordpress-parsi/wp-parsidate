<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if ( ! class_exists( 'WPP_WC_Pasargad_Gateway_Blocks' ) ) {
	final class WPP_WC_Pasargad_Gateway_Blocks extends AbstractPaymentMethodType {

		private $gateway;

		/**
		 * Payment method name/id/slug.
		 *
		 * @var string
		 */
		protected $name = 'pasargad';


		/**
		 * Initializes the payment method type.
		 */
		public function initialize() {
			$class_name     = get_class( $this );
			$this->settings = get_option( "woocommerce_{$this->name}_settings", [] );
			$this->gateway = new $class_name;
		}

		/**
		 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
		 *
		 * @return boolean
		 */
		public function is_active() {
			return filter_var( $this->get_setting( 'enabled', true ), FILTER_VALIDATE_BOOLEAN );
		}

		/**
		 * Returns an array of scripts/handles to be registered for this payment method.
		 *
		 * @return array
		 */
		public function get_payment_method_script_handles() {
			$script_id   = "wpp-wc-$this->name-blocks-integration";
			$script_name = "wpp-wc-$this->name-pg.js";

			wp_register_script(
				$script_id,
				WP_PARSI_URL . "assets/js/wc-pg-blocks/$script_name",
				array(
					'wc-blocks-registry',
					'wc-settings',
					'wp-element',
					'wp-html-entities',
					'wp-i18n',
				),
				false,
				true
			);

			if ( function_exists( 'wp_set_script_translations' ) ) {
				wp_set_script_translations( $script_id, 'wp-parsidate', WP_PARSI_DIR . 'languages/' );
			}

			return array( $script_id );
		}

		/**
		 * Returns an array of key=>value pairs of data made available to the payment methods script.
		 *
		 * @return array
		 */
		public function get_payment_method_data() {
			return array(
				'title'       => $this->get_setting( 'title' ),
				'description' => $this->get_setting( 'description' ),
				'supports'    => $this->get_supported_features(),
			);
		}
	}
}