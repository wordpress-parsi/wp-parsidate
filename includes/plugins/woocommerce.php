<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

if ( ! class_exists( 'WPP_WooCommerce' ) ) {
	/**
	 * Makes WooCommerce compatible with WP-Parsidate plugin
	 *
	 * @package                 WP-Parsidate
	 * @subpackage              Plugins/WooCommerce
	 */
	class WPP_WooCommerce {
		public static $instance = null;

		/**
		 * Hooks required tags
		 */
		private function __construct() {
			$this->include_files();

			add_filter( 'wpp_plugins_compatibility_settings', array( $this, 'add_settings' ) );

			if ( class_exists( 'WooCommerce' ) && get_locale() === 'fa_IR' ) {
				if ( wpp_is_active( 'woo_per_price' ) ) {
					add_filter( 'wc_price', 'fix_number' );
					add_filter( 'woocommerce_get_price_html', 'fix_number' );
					add_filter( 'woocommerce_cart_item_price', 'fix_number' );
					add_filter( 'woocommerce_cart_item_subtotal', 'fix_number' );
					add_filter( 'woocommerce_cart_subtotal', 'fix_number' );
					add_filter( 'woocommerce_cart_totals_coupon_html', 'fix_number' );
					add_filter( 'woocommerce_cart_shipping_method_full_label', 'fix_number' );
					add_filter( 'woocommerce_cart_total', 'fix_number' );
				}

				if ( wpp_is_active( 'persian_date' ) ) {
					add_action( 'wp_head', array( $this, 'fix_wc_date_time_direction' ) );
					add_filter( 'woocommerce_email_styles', array( $this, 'fix_emails_order_date_direction' ), 9999, 2 );

					// Jalali datepicker
					add_action( 'admin_enqueue_scripts', array( $this, 'wpp_admin_woocommerce_jalali_datepicker_assets' ) );

					// Convert order_date using js
					add_action( 'woocommerce_before_order_object_save', array( $this, 'wpp_change_order_date_on_save_order_object' ), 99, 2 );
					add_action( 'admin_footer', array( $this, 'wpp_fix_show_created_order_date' ) );
					add_action( 'admin_init', array( $this, 'wpp_change_wc_report_dates' ), 1000 );
					add_filter( 'wp_insert_post_data', array( $this, 'wpp_validate_dates_on_woocommerce_save_data' ), 1, 2 );
					add_action( 'woocommerce_admin_process_variation_object', array( $this, 'wpp_convert_wc_variations_scheduled_sale_dates' ), 1000, 2 );
					add_filter( 'get_post_metadata', array( $this, 'wpp_change_wc_order_date_and_coupon_expires' ), 10, 4 );
					add_filter( 'manage_edit-shop_coupon_columns', array( $this, 'wpp_remove_wc_coupon_expiry_date_column' ), 10, 1 );
					add_action( 'manage_shop_coupon_posts_custom_column', array( $this, 'wpp_add_jalali_expiry_date_column' ), 10, 2 );
				}

				add_filter( 'woocommerce_checkout_process', array( $this, 'wpp_accept_persian_numbers_in_checkout' ), 20 );
				add_filter( 'woocommerce_checkout_posted_data', array( $this, 'wpp_convert_non_persian_values_in_checkout' ) );

				if ( wpp_is_active( 'woo_validate_postcode' ) ) {
					add_filter( 'woocommerce_validate_postcode', array( $this, 'wpp_validate_postcode' ), 10, 3 );
				}

				if ( wpp_is_active( 'woo_validate_phone' ) ) {
					add_action( 'woocommerce_after_checkout_validation', array( $this, 'wpp_validate_phone_number' ), 10, 2 );
				}
			}
		}

		/**
		 * Returns an instance of class
		 *
		 * @return          WPP_WooCommerce
		 */
		public static function getInstance() {
			if ( self::$instance == null ) {
				self::$instance = new WPP_WooCommerce();
			}

			return self::$instance;
		}

		/**
		 * Includes files for WooCommerce payment gateways
		 *
		 * @return         void
		 * @since          4.0.1
		 */
		public function include_files() {
			if ( wpp_is_active( 'woo_dropdown_cities' ) ) {
				include_once WP_PARSI_DIR . 'includes/plugins/wc-cities/wc-city-select.php';
			}

			require_once( WP_PARSI_DIR . 'includes/plugins/wc-gateways/wc-gateways.php' );
		}

		/**
		 * Adds settings for toggle fixing
		 *
		 * @param array $old_settings Old settings
		 *
		 * @return          array New settings
		 * @since 4.0.0
		 */
		public function add_settings( $old_settings ) {
			$settings = apply_filters( 'wpp_woocommerce_settings', array(
				'woocommerce'             => array(
					'id'   => 'woocommerce',
					'name' => __( 'WooCommerce', 'wp-parsidate' ),
					'type' => 'header',
				),
				'woo_per_price'           => array(
					'id'      => 'woo_per_price',
					'name'    => __( 'Fix prices', 'wp-parsidate' ),
					'type'    => 'checkbox',
					'options' => 1,
					'std'     => 0,
				),
				'woo_accept_per_postcode' => array(
					'id'      => 'woo_accept_per_postcode',
					'name'    => __( 'Fix persian postcode', 'wp-parsidate' ),
					'type'    => 'checkbox',
					'options' => 1,
					'std'     => 0,
				),
				'woo_dropdown_cities'     => array(
					'id'      => 'woo_dropdown_cities',
					'name'    => __( 'Display cities as a drop-down list', 'wp-parsidate' ),
					'type'    => 'checkbox',
					'options' => 1,
					'std'     => 0,
				),
				'woo_accept_per_phone'    => array(
					'id'      => 'woo_accept_per_phone',
					'name'    => __( 'Fix persian phone', 'wp-parsidate' ),
					'type'    => 'checkbox',
					'options' => 1,
					'std'     => 0,
				),
				'woo_validate_postcode'   => array(
					'id'      => 'woo_validate_postcode',
					'name'    => __( 'Postcode validation', 'wp-parsidate' ),
					'type'    => 'checkbox',
					'options' => 1,
					'std'     => 0,
				),
				'woo_validate_phone'      => array(
					'id'      => 'woo_validate_phone',
					'name'    => __( 'Phone number validation', 'wp-parsidate' ),
					'type'    => 'checkbox',
					'options' => 1,
					'std'     => 0,
				),
			) );

			return array_merge( $old_settings, $settings );
		}

		/**
		 * enqueue jalali date picker assets
		 *
		 * @since           4.0.0
		 */
		public function wpp_admin_woocommerce_jalali_datepicker_assets() {
			global $wpp_months_name;

			$screen         = get_current_screen();
			$current_screen = $screen->id;
			$suffix         = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || wpp_is_active( 'dev_mode' ) ? '' : '.min';

			if ( wpp_is_active( 'persian_date' ) && in_array( $current_screen, array( 'product', 'shop_order', 'shop_coupon', 'woocommerce_page_wc-reports' ) ) ) {
				wp_enqueue_script( 'wpp_jalali_datepicker', WP_PARSI_URL . 'assets/js/jalalidatepicker.min.js', array( 'jquery', 'jquery-ui-datepicker' ), WP_PARSI_VER );
				wp_enqueue_style( 'wpp_jalali_datepicker', WP_PARSI_URL . "assets/css/jalalidatepicker$suffix.css", null, WP_PARSI_VER );

				do_action( 'wpp_jalai_datepicker_enqueued', 'wc' );
			}
		}

		/**
		 * Convert order date to gregorian before saved at database
		 *
		 * @param $order
		 * @param $data
		 *
		 * @since 5.0.2
		 */
		public function wpp_change_order_date_on_save_order_object( $order, $data ) {
			global $pagenow;

			if ( is_admin() && 'post.php' === $pagenow && isset( $_POST['post_type'] ) && 'shop_order' === $_POST['post_type'] && ! empty( $_POST['order_date'] ) ) {

				// Sanitize Text Field
				$_POST['order_date'] = sanitize_text_field( eng_number( $_POST['order_date'] ) );

				// Check Validate Date
				if ( ! preg_match( '/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $_POST['order_date'] ) ) {
					return;
				}

				// Check Date Time If Before Proceed
				$explode = explode( "-", $_POST['order_date'] );

				if ( (int) $explode[0] > 2000 ) {
					return;
				}

				// Convert To Gregorian
				$_POST['order_date'] = gregdate( 'Y-m-d', trim( $_POST['order_date'] ) );
			}
		}

		/**
		 * Changes order_date field in "Edit order" screen using JS
		 *
		 * @since 4.0.0
		 */
		public function wpp_fix_show_created_order_date() {
			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';

			if ( 'shop_order' === $screen_id ) {
				global $post;

				if ( ! $post ) {
					return;
				}

				$jalali_date = parsidate( 'Y-m-d', date( 'Y-m-d', strtotime( $post->post_date ) ), 'eng' );

				echo '<script>jQuery(function($){$("input[name=order_date]").val("' . $jalali_date . '")})</script>';
			} elseif ( 'woocommerce_page_wc-reports' === $screen_id ) {
				$jalali_start_date = ! empty($_GET['start_date']) ?parsidate( 'Y-m-d', date( 'Y-m-d', strtotime( $_GET['start_date'] ) ), 'eng' ) : '';
				$jalali_end_date = ! empty($_GET['end_date']) ?parsidate( 'Y-m-d', date( 'Y-m-d', strtotime( $_GET['end_date'] ) ), 'eng' ) : '';

				echo '<script>jQuery(function($){$("input[name=start_date]").val("' . $jalali_start_date . '");$("input[name=end_date]").val("' . $jalali_end_date . '")})</script>';
			}
		}

		/**
		 * Convert selected Jalali dates to gregorian on woocommerce save data
		 *
		 * @param           $post
		 * @param           $arg
		 *
		 * @return          mixed
		 * @since           4.0.0
		 */
		public function wpp_validate_dates_on_woocommerce_save_data( $post, $arg ) {
			if ( empty( $post['post_type'] ) ) {
				return $post;
			}

			switch ( $post['post_type'] ) {
				case 'product':
					if ( ! empty( $_POST['_sale_price_dates_from'] ) ) {
						$_POST['_sale_price_dates_from'] = gregdate( 'Y-m-d', sanitize_text_field( eng_number( $_POST['_sale_price_dates_from'] ) ) );
					}

					if ( ! empty( $_POST['_sale_price_dates_to'] ) ) {
						$_POST['_sale_price_dates_to'] = gregdate( 'Y-m-d', sanitize_text_field( eng_number( $_POST['_sale_price_dates_to'] ) ) );
					}

					break;
				case 'shop_coupon':
					if ( ! empty( $_POST['expiry_date'] ) ) {
						$_POST['expiry_date'] = gregdate( 'Y-m-d', sanitize_text_field( eng_number( $_POST['expiry_date'] ) ) );
					}

					break;
			}

			return $post;
		}

		/**
		 * Changes coupon expire date on load coupon data
		 * We use $wpdb to avoid creating an infinite loop
		 *
		 * @param $metadata
		 * @param $object_id
		 * @param $meta_key
		 * @param $single
		 *
		 * @return int|mixed|string
		 *
		 * @since           4.0.0
		 */
		public function wpp_change_wc_order_date_and_coupon_expires( $metadata, $object_id, $meta_key, $single ) {
			global $wpdb;

			$post_type = get_post_type( $object_id );
			$action    = isset( $_GET['action'] ) && $_GET['action'] == 'edit';

			if ( $action && 'shop_coupon' === $post_type && 'date_expires' === $meta_key ) {
				$metadata = $wpdb->get_var(
					$wpdb->prepare(
						"
						SELECT meta_value
						From $wpdb->postmeta
						WHERE post_id = %d
							AND meta_key = '%s'
					",
						$object_id,
						$meta_key
					)
				);

				if ( ! empty( $metadata ) ) {
					return parsidate( 'Y-m-d', $metadata, 'eng' );
				}
			}

			return $metadata;
		}

		/**
		 * Changes gregorian dates to Jalali date on wc report screen
		 *
		 * @since           4.0.0
		 */
		public function wpp_change_wc_report_dates() {
			if ( ! empty( $_GET['page'] ) && 'wc-reports' === esc_attr( $_GET['page'] ) ) {
				if ( ! empty( $_GET['start_date'] ) ) {
					$_GET['start_date'] = gregdate( 'Y-m-d', sanitize_text_field( eng_number( $_GET['start_date'] ) ) );
				}

				if ( ! empty( $_GET['end_date'] ) ) {
					$_GET['end_date'] = gregdate( 'Y-m-d', sanitize_text_field( eng_number( $_GET['end_date'] ) ) );
				}
			}
		}

		/**
		 * Converts variations selected Jalali dates to gregorian
		 *
		 * @param           $variation
		 * @param           $index
		 *
		 * @since           4.0.0
		 */
		public function wpp_convert_wc_variations_scheduled_sale_dates( $variation, $index ) {
			$date_on_sale_from = '';
			$date_on_sale_to   = '';

			if ( ! empty( $_POST['variable_sale_price_dates_from'][ $index ] ) ) {
				$date_on_sale_from = eng_number( $_POST['variable_sale_price_dates_from'][ $index ] );
				$date_on_sale_from = wc_clean( wp_unslash( $date_on_sale_from ) );

				if ( ! empty( $date_on_sale_from ) ) {
					$date_on_sale_from = gregdate( 'Y-m-d 00:00:00', $date_on_sale_from );
				}
			}

			if ( ! empty( $_POST['variable_sale_price_dates_to'][ $index ] ) ) {
				$date_on_sale_to = eng_number( $_POST['variable_sale_price_dates_to'][ $index ] );
				$date_on_sale_to = wc_clean( wp_unslash( $date_on_sale_to ) );

				if ( ! empty( $date_on_sale_to ) ) {
					$date_on_sale_to = gregdate( 'Y-m-d 23:59:59', $date_on_sale_to );
				}
			}

			$variation->set_props(
				array(
					'date_on_sale_from' => $date_on_sale_from,
					'date_on_sale_to'   => $date_on_sale_to,
				),
			);

			$variation->save();
		}

		/**
		 * Remove default wc expire date column in coupons screen and add our custom column
		 *
		 * @param $columns
		 *
		 * @return mixed
		 * @since 4.0.0
		 */
		public function wpp_remove_wc_coupon_expiry_date_column( $columns ) {
			unset( $columns['expiry_date'] );

			$columns['wpp_expiry_date'] = __( 'Expiry date', 'woocommerce' );

			return $columns;
		}

		/**
		 * Fill our custom date expires column value
		 *
		 * @param $column
		 * @param $postid
		 *
		 * @since 4.0.0
		 */
		public function wpp_add_jalali_expiry_date_column( $column, $postid ) {
			if ( $column == 'wpp_expiry_date' ) {
				$date = get_post_meta( $postid, 'date_expires', true );

				echo ! empty( $date ) ? parsidate( 'Y-m-d', $date ) : '&ndash;';
			}
		}

		/**
		 * Convert Non-Persian Values in checkout to Persian
		 *
		 * @method  wpp_convert_non_persian_values_in_checkout
		 * @param array $data
		 *
		 * @return  array modified $data
		 * @version 1.0.0
		 * @since   4.0.1
		 */
		public function wpp_convert_non_persian_values_in_checkout( $data ) {
			$persian_fields = array(
				'billing_postcode',
				'billing_city',
				'billing_address_1',
				'billing_address',
				'billing_address_2',
				'billing_state',
				'shipping_postcode',
				'shipping_city',
				'shipping_address_1',
				'shipping_address',
				'shipping_address_2',
				'shipping_state',
				'billing_first_name',
				'billing_last_name',
				'billing_company',
				'billing_email',
				'shipping_first_name',
				'shipping_last_name',
				'shipping_company',
			);

			/**
			 * here we pass those fields we want to convert from arabic to persian
			 * other developers can hook into this filter and add their fields too
			 *
			 * @var array $persian_fields
			 */
			$supported_persian_fields = apply_filters( "wpp_woocommerce_checkout_persian_fields", $persian_fields );

			foreach ( $supported_persian_fields as $field ) {
				if ( isset( $data[ $field ] ) ) {
					$data[ $field ] = $this->fix_persian_characters( $data[ $field ] );
				}
			}

			return apply_filters( "wpp_woocommerce_checkout_modified_persian_fields", $data );
		}

		/**
		 * replace Arabic characters with equivalent character in Persian
		 *
		 * @method  fix_persian_characters
		 * @param string $string
		 *
		 * @return  string filtered $string
		 * @version 1.0.0
		 * @since   4.0.1
		 */
		public static function fix_persian_characters( $string ) {
			$characters = array(
				'ك'  => 'ک',
				'دِ' => 'د',
				'بِ' => 'ب',
				'زِ' => 'ز',
				'ذِ' => 'ذ',
				'شِ' => 'ش',
				'سِ' => 'س',
				'ى'  => 'ی',
				'ي'  => 'ی',
				'١'  => '۱',
				'٢'  => '۲',
				'٣'  => '۳',
				'٤'  => '۴',
				'٥'  => '۵',
				'٦'  => '۶',
				'٧'  => '۷',
				'٨'  => '۸',
				'٩'  => '۹',
				'٠'  => '۰',
			);

			$characters = apply_filters( "wpp_arabic_persian_characters_list", $characters );

			return str_replace( array_keys( $characters ), array_values( $characters ), $string );
		}

		/**
		 * Fix persian postal code & phone numbers in WooCommerce checkout
		 *
		 * @since 4.1.0
		 */
		public function wpp_accept_persian_numbers_in_checkout() {
			if ( wpp_is_active( 'woo_accept_per_postcode' ) ) {
				if ( isset( $_POST['billing_postcode'] ) ) {
					$_POST['billing_postcode'] = eng_number( sanitize_text_field( $_POST['billing_postcode'] ) );
				}

				if ( isset( $_POST['shipping_postcode'] ) ) {
					$_POST['shipping_postcode'] = eng_number( sanitize_text_field( $_POST['shipping_postcode'] ) );
				}
			}

			if ( wpp_is_active( 'woo_accept_per_phone' ) ) {
				if ( isset( $_POST['billing_phone'] ) ) {
					$_POST['billing_phone'] = eng_number( sanitize_text_field( $_POST['billing_phone'] ) );
				}

				if ( isset( $_POST['shipping_phone'] ) ) {
					$_POST['shipping_phone'] = eng_number( sanitize_text_field( $_POST['shipping_phone'] ) );
				}
			}
		}

		/**
		 * Validate Iranian customer postal code
		 *
		 * @param $valid
		 * @param $postcode
		 * @param $country
		 *
		 * @return bool|mixed
		 */
		public function wpp_validate_postcode( $valid, $postcode, $country ) {
			if ( 'IR' != $country ) {
				return $valid;
			}

			// based on https://github.com/VahidN/DNTPersianUtils.Core/blob/34b9ae00ad3584bc9ef34033c6402d1b8ae7a148/src/DNTPersianUtils.Core/Validators/IranCodesUtils.cs#L13
			return (bool) preg_match( '/\b(?!(\d)\1{3})[13-9]{4}[1346-9][013-9]{5}\b/', $postcode );
		}

		/**
		 * @param $data
		 * @param $errors
		 *
		 * @return false|void
		 */
		public function wpp_validate_phone_number( $data, $errors ) {
			// This pattern ensures the phone number follows the specified structure for both mobile and landline numbers
			if ( preg_match( '/^(0|0098|\+98)?(9\d{9}|[1-8]\d{9,10})$/', eng_number( sanitize_text_field( $_POST['billing_phone'] ) ) ) ) {
				return false;
			}

			$errors->add( 'validation', __( '<strong>Phone number</strong> is invalid.', 'wp-parsidate' ) );
		}

		/**
		 * Fixes jalali order date direction in WooCommerce emails (Issue: https://github.com/wordpress-parsi/wp-parsidate/issues/154)
		 *
		 * @param $style
		 * @param $email
		 *
		 * @return string
		 * @since 5.0.0
		 */
		public function fix_emails_order_date_direction( $style, $email ) {
			return $style . 'time{unicode-bidi:embed!important}';
		}

		/**
		 * Fixes jalali order date direction in WooCommerce my-account endpoints (Issue: https://github.com/wordpress-parsi/wp-parsidate/issues/154)
		 *
		 * @return void
		 * @since 5.0.0
		 */
		public function fix_wc_date_time_direction() {
			if ( is_woocommerce() || is_wc_endpoint_url() || is_cart() || is_checkout() ) {
				echo '<style>mark.order-date,time{unicode-bidi:embed!important}</style>';
			}
		}
	}

	return WPP_WooCommerce::getInstance();
}