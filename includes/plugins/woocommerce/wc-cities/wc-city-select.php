<?php
/**
 * Plugin Name: WC City Select
 * Plugin URI:  https://wordpress.org/plugins/wc-city-select/
 * Description: City Select for WooCommerce. Show a dropdown select as the cities input.
 * Version:     1.0.7
 * Author:      8manos
 * Author URI:  http://8manos.com
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * WC requires at least: 2.2
 * WC tested up to:      7.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'WC_City_Select' ) ) {
	class WC_City_Select {
		const VERSION = '1.0.1';

		private $plugin_path;
		private $plugin_url;

		private $cities;
		private $dropdown_cities;

		public function __construct() {
			$this->plugin_path = WP_PARSI_DIR . 'includes/plugins/wc-cities';
			$this->plugin_url  = WP_PARSI_URL;

			add_filter( 'woocommerce_billing_fields', array( $this, 'billing_fields' ), 10, 2 );
			add_filter( 'woocommerce_shipping_fields', array( $this, 'shipping_fields' ), 10, 2 );
			add_filter( 'woocommerce_form_field_city', array( $this, 'form_field_city' ), 10, 4 );

			//js scripts
			add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
		}

		public function billing_fields( $fields, $country ) {
			$fields['billing_city']['type'] = 'city';

			return $fields;
		}

		public function shipping_fields( $fields, $country ) {
			$fields['shipping_city']['type'] = 'city';

			return $fields;
		}

		public function get_cities( $cc = null ) {
			if ( empty( $this->cities ) ) {
				$this->load_country_cities();
			}

			if ( ! is_null( $cc ) ) {
				return isset( $this->cities[ $cc ] ) ? $this->cities[ $cc ] : false;
			} else {
				return $this->cities;
			}
		}

		public function load_country_cities() {
			global $cities;

			// Load only the city files the shop owner wants/needs.
			$allowed = array_merge( WC()->countries->get_allowed_countries(), WC()->countries->get_shipping_countries() );

			if ( $allowed ) {
				foreach ( $allowed as $code => $country ) {
					if ( ! isset( $cities[ $code ] ) && file_exists( $this->get_plugin_path() . '/cities/' . $code . '.php' ) ) {
						include( $this->get_plugin_path() . '/cities/' . $code . '.php' );
					}
				}
			}

			$this->cities = apply_filters( 'wc_city_select_cities', $cities );
		}

		private function add_to_dropdown( $item ) {
			$this->dropdown_cities[] = $item;
		}

		public function form_field_city( $field, $key, $args, $value ) {
			// Do we need a clear div?
			if ( ( ! empty( $args['clear'] ) ) ) {
				$after = '<div class="clear"></div>';
			} else {
				$after = '';
			}

			// Required markup
			if ( $args['required'] ) {
				$args['class'][] = 'validate-required';
				$required        = ' <abbr class="required" title="' . esc_attr__( 'required', 'woocommerce' ) . '">*</abbr>';
			} else {
				$required = '';
			}

			// Custom attribute handling
			$custom_attributes = array();

			if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) ) {
				foreach ( $args['custom_attributes'] as $attribute => $attribute_value ) {
					$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
				}
			}

			// Validate classes
			if ( ! empty( $args['validate'] ) ) {
				foreach ( $args['validate'] as $validate ) {
					$args['class'][] = 'validate-' . $validate;
				}
			}

			// field p and label
			$field = '<p class="form-row ' . esc_attr( implode( ' ', $args['class'] ) ) . '" id="' . esc_attr( $args['id'] ) . '_field">';

			if ( $args['label'] ) {
				$field .= '<label for="' . esc_attr( $args['id'] ) . '" class="' . esc_attr( implode( ' ', $args['label_class'] ) ) . '">' . $args['label'] . $required . '</label>';
			}

			// Get Country
			$country_key = $key == 'billing_city' ? 'billing_country' : 'shipping_country';
			$current_cc  = WC()->checkout->get_value( $country_key );
			$state_key   = $key == 'billing_city' ? 'billing_state' : 'shipping_state';
			$current_sc  = WC()->checkout->get_value( $state_key );

			// Get country cities
			$cities = $this->get_cities( $current_cc );

			if ( is_array( $cities ) ) {
				$field .= '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="city_select ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . implode( ' ', $custom_attributes ) . ' placeholder="' . esc_attr( $args['placeholder'] ) . '">
					<option value="">' . __( 'Select an option&hellip;', 'woocommerce' ) . '</option>';

				if ( $current_sc && $cities[ $current_sc ] ) {
					$this->dropdown_cities = $cities[ $current_sc ];
				} else {
					$this->dropdown_cities = [];
					array_walk_recursive( $cities, array( $this, 'add_to_dropdown' ) );
					sort( $this->dropdown_cities );
				}

				foreach ( $this->dropdown_cities as $city_name ) {
					$field .= '<option value="' . esc_attr( $city_name ) . '" ' . selected( $value, $city_name, false ) . '>' . $city_name . '</option>';
				}

				$field .= '</select>';

			} else {
				$field .= '<input type="text" class="input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" value="' . esc_attr( $value ) . '"  placeholder="' . esc_attr( $args['placeholder'] ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" ' . implode( ' ', $custom_attributes ) . ' />';
			}

			// field description and close wrapper
			if ( $args['description'] ) {
				$field .= '<span class="description">' . esc_attr( $args['description'] ) . '</span>';
			}

			$field .= '</p>' . $after;

			return $field;
		}

		public function load_scripts() {
			if ( is_cart() || is_checkout() || is_wc_endpoint_url( 'edit-address' ) ) {
				$city_select_path = $this->get_plugin_url() . 'assets/js/city-select.js';

				wp_enqueue_script( 'wc-city-select', $city_select_path, array( 'jquery', 'woocommerce' ), self::VERSION, true );

				$cities = json_encode( $this->get_cities() );

				wp_localize_script( 'wc-city-select',
					'wc_city_select_params',
					array(
						'cities'                => $cities,
						'i18n_select_city_text' => esc_attr__( 'Select an option&hellip;', 'woocommerce' )
					)
				);
			}
		}

		public function get_plugin_path() {
			if ( $this->plugin_path ) {
				return $this->plugin_path;
			}

			return $this->plugin_path = plugin_dir_path( __FILE__ );
		}

		public function get_plugin_url() {
			if ( $this->plugin_url ) {
				return $this->plugin_url;
			}

			return $this->plugin_url = plugin_dir_url( __FILE__ );
		}
	}

	$GLOBALS['wc_city_select'] = new WC_City_Select();
}