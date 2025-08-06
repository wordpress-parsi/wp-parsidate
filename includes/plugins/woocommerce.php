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
			add_filter( 'wpp_plugins_compatibility_settings', array( $this, 'add_settings' ) );

			add_action( 'before_woocommerce_init', array($this, 'before_woocommerce_init'));

            add_action('plugins_loaded', [$this, 'include_gateways'], 0);

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
					add_action( 'admin_enqueue_scripts', array( $this, 'wc_jalali_datepicker_assets' ) );

					// Convert order_date using js
					add_action( 'woocommerce_process_shop_order_meta', array( $this, 'change_order_date_on_save_order_object' ), 1000 );
					add_filter( 'woocommerce_process_product_meta', array( $this, 'validate_non_variable_product_dates' ), 1000 );
					add_action( 'woocommerce_ajax_save_product_variations', array( $this, 'validate_variable_product_dates' ), 1000 );
					add_action( 'woocommerce_process_shop_coupon_meta', array( $this, 'validate_wc_coupons_date' ), 1000 );
					add_filter( 'get_post_metadata', array( $this, 'change_wc_order_date_and_coupon_expires' ), 10, 4 );
					add_filter( 'manage_edit-shop_coupon_columns', array( $this, 'remove_wc_coupon_expiry_date_column' ) );
					add_action( 'manage_shop_coupon_posts_custom_column', array( $this, 'add_jalali_expiry_date_column' ), 10, 2 );
					add_action( 'admin_footer', array( $this, 'fix_show_created_order_date' ) );
					add_action( 'admin_init', array( $this, 'change_wc_report_dates' ), 1000 );
				}

				add_filter( 'woocommerce_checkout_process', array( $this, 'accept_persian_numbers_in_checkout' ), 20 );
				add_filter( 'woocommerce_checkout_posted_data', array( $this, 'convert_non_persian_values_in_checkout' ) );

				if ( wpp_is_active( 'woo_validate_postcode' ) ) {
					add_filter( 'woocommerce_validate_postcode', array( $this, 'validate_postcode' ), 10, 3 );
				}

				if ( wpp_is_active( 'woo_validate_phone' ) ) {
					add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_phone_number' ), 10, 2 );
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
         * Init Before WooCommerce Loaded
         */
        public function before_woocommerce_init()
        {
            // Include City Translate
            if ( wpp_is_active( 'woo_dropdown_cities' ) ) {
                include_once WP_PARSI_DIR . 'includes/plugins/wc-cities/wc-city-select.php';
            }

            if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', WP_PARSI_ROOT, true);
            }
        }

        public function include_gateways()
        {
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
		public function wc_jalali_datepicker_assets() {
			global $wpp_months_name;

			$screen         = get_current_screen();
			$current_screen = $screen->id;
			$suffix         = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || wpp_is_active( 'dev_mode' ) ? '' : '.min';

			$allowed_screens = array(
				'product',
				'shop_order',
				'woocommerce_page_wc-orders',
				'woocommerce_page_wc-reports',
				'shop_coupon',
			);

			if ( wpp_is_active( 'persian_date' ) && in_array( $current_screen, $allowed_screens ) ) {
				wp_enqueue_script( 'wpp_jalali_datepicker', WP_PARSI_URL . 'assets/js/jalalidatepicker.min.js', array( 'jquery-ui-datepicker' ), WP_PARSI_VER );
				wp_enqueue_style( 'wpp_jalali_datepicker', WP_PARSI_URL . "assets/css/jalalidatepicker$suffix.css", null, WP_PARSI_VER );

				do_action( 'wpp_jalali_datepicker_enqueued', 'wc' );
			}
		}

		/**
		 * Convert order date to gregorian before saved at database
		 *
		 * @param $order_id
		 *
		 * @throws WC_Data_Exception
		 * @since 5.0.2
		 */
		public function change_order_date_on_save_order_object( $order_id ) {
			$order_date = wc_get_post_data_by_key( 'order_date' );

			if ( empty( $order_date ) ) {
				return;
			}

			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				return;
			}

			$hour       = str_pad( (int) wc_get_post_data_by_key( 'order_date_hour' ), 2, '0', STR_PAD_LEFT );
			$minute     = str_pad( (int) wc_get_post_data_by_key( 'order_date_minute' ), 2, '0', STR_PAD_LEFT );
			$second     = str_pad( (int) wc_get_post_data_by_key( 'order_date_second' ), 2, '0', STR_PAD_LEFT );
			$time_stamp = "$order_date $hour:$minute:$second";
			$fixed_date = gregdate( 'Y-m-d H:i:s', $time_stamp );
			$date       = empty( $order_date ) ? current_time( 'mysql' ) : gmdate( 'Y-m-d H:i:s', strtotime( $fixed_date ) );

			$order->set_date_created( $date );
			$order->save();
		}

		/**
		 * Changes order_date field in "Edit order" screen using JS
		 *
		 * @since 4.0.0
		 */
		public function fix_show_created_order_date() {
			$current_screen = $this->get_current_screen();

			if ( 'edit_order' === $current_screen ) {
				if ( ! $this->is_hpos_enabled() ) {
					global $post;

					if ( ! $post ) {
						$jalali_date = parsidate( 'Y-m-d', date( 'Y-m-d' ), 'eng' );
					} else {
						$jalali_date = parsidate( 'Y-m-d', date( 'Y-m-d', strtotime( $post->post_date ) ), 'eng' );
					}
				} else {
					global $theorder;

					if ( ! $theorder ) {
						$jalali_date = parsidate( 'Y-m-d', date( 'Y-m-d' ), 'eng' );
					} else {
						$jalali_date = parsidate( 'Y-m-d', ! is_null( $theorder->get_date_created() ) ? $theorder->get_date_created()->getOffsetTimestamp() : '', 'eng' );
					}
				}

				wc_enqueue_js( '$("input[name=order_date]").val("' . $jalali_date . '")' );
			} elseif ( 'legacy_report' === $current_screen ) {
				$jalali_start_date = ! empty( $_GET['start_date'] ) ? parsidate( 'Y-m-d', date( 'Y-m-d', strtotime( $_GET['start_date'] ) ), 'eng' ) : '';
				$jalali_end_date   = ! empty( $_GET['end_date'] ) ? parsidate( 'Y-m-d', date( 'Y-m-d', strtotime( $_GET['end_date'] ) ), 'eng' ) : '';

				wc_enqueue_js( '$("input[name=start_date]").val("' . $jalali_start_date . '");$("input[name=end_date]").val("' . $jalali_end_date . '");' );
			} elseif ( 'product' === $current_screen ) {
				global $post;

				if ( ! $post ) {
					return;
				}

				$product = wc_get_product( $post->ID );

				if ( ! $product ) {
					return;
				}

				if ( ! $product->is_type( 'variable' ) ) {
					$sale_price_dates_from_timestamp = $product->get_date_on_sale_from( 'edit' ) ? $product->get_date_on_sale_from( 'edit' )->getOffsetTimestamp() : false;
					$sale_price_dates_to_timestamp   = $product->get_date_on_sale_to( 'edit' ) ? $product->get_date_on_sale_to( 'edit' )->getOffsetTimestamp() : false;

					$sale_price_dates_from = $sale_price_dates_from_timestamp ? eng_number( date_i18n( 'Y-m-d', $sale_price_dates_from_timestamp ) ) : '';
					$sale_price_dates_to   = $sale_price_dates_to_timestamp ? eng_number( date_i18n( 'Y-m-d', $sale_price_dates_to_timestamp ) ) : '';

					wc_enqueue_js( '$("#_sale_price_dates_from").val("' . $sale_price_dates_from . '");$("#_sale_price_dates_to").val("' . $sale_price_dates_to . '");' );
				} else {
					$dates                = array();
					$loop                 = 0;
					$available_variations = $product->get_available_variations();

					foreach ( $available_variations as $variation ) {
						$variation_id  = $variation['variation_id'];
						$variation_obj = new WC_Product_Variation( $variation_id );

						$sale_price_dates_from_timestamp = $variation_obj->get_date_on_sale_from( 'edit' ) ? $variation_obj->get_date_on_sale_from( 'edit' )->getOffsetTimestamp() : false;
						$sale_price_dates_to_timestamp   = $variation_obj->get_date_on_sale_to( 'edit' ) ? $variation_obj->get_date_on_sale_to( 'edit' )->getOffsetTimestamp() : false;

						$sale_price_dates_from = $sale_price_dates_from_timestamp ? eng_number( date_i18n( 'Y-m-d', $sale_price_dates_from_timestamp ) ) : '';
						$sale_price_dates_to   = $sale_price_dates_to_timestamp ? eng_number( date_i18n( 'Y-m-d', $sale_price_dates_to_timestamp ) ) : '';
						$dates[ $loop ]        = array(
							'start' => esc_attr( $sale_price_dates_from ),
							'end'   => esc_attr( $sale_price_dates_to ),
						);

						$loop ++;
					}

					if ( ! empty( $dates ) ) {
						wc_enqueue_js(
							'const wppVariationsDates = ' . wp_json_encode( $dates ) . '
						    $("#woocommerce-product-data").on("woocommerce_variations_loaded", function(e) {
							  wppVariationsDates.forEach((date, index) => {
                                $(`input[name="variable_sale_price_dates_from[${index}]"]`).val(date.start)
						        $(`input[name="variable_sale_price_dates_to[${index}]"]`).val(date.end)
						      })
						    })'
						);
					}
				}
			}
		}

		/**
		 * Convert selected Jalali dates to gregorian on woocommerce save non-variable products
		 *
		 * @param  $product_id $
		 *
		 * @return          void
		 * @since           4.0.0
		 */
		public function validate_non_variable_product_dates( $product_id ) {
			$props = array();

			if ( isset( $_POST['_sale_price_dates_from'] ) ) {
				$date_on_sale_from = eng_number( wc_get_post_data_by_key( '_sale_price_dates_from' ) );
                $time_on_sale_from = ((!empty($_POST['_sale_price_times_from']) and wpp_is_time_validate($_POST['_sale_price_times_from'])) ? wpp_is_time_validate($_POST['_sale_price_times_from']) : '00:00:00');

				if ( ! empty( $date_on_sale_from ) ) {
                    $props['date_on_sale_from'] = date('Y-m-d ' . $time_on_sale_from, strtotime(gregdate('Y-m-d', $date_on_sale_from)));
				}
			}

			if ( isset( $_POST['_sale_price_dates_to'] ) ) {
				$date_on_sale_to = eng_number( wc_get_post_data_by_key( '_sale_price_dates_to' ) );
                $time_on_sale_to = ((!empty($_POST['_sale_price_times_to']) and wpp_is_time_validate($_POST['_sale_price_times_to'])) ? wpp_is_time_validate($_POST['_sale_price_times_to']) : '23:59:59');

				if ( ! empty( $date_on_sale_to ) ) {
                    $props['date_on_sale_to'] = date('Y-m-d ' . $time_on_sale_to, strtotime(gregdate('Y-m-d', $date_on_sale_to)));
				}
			}

			if ( empty( $props ) ) {
				return;
			}

			$product = wc_get_product( $product_id );

			$product->set_props( $props );
			$product->save();
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
		public function change_wc_order_date_and_coupon_expires( $metadata, $object_id, $meta_key, $single ) {
			global $wpdb;

			$post_type = get_post_type( $object_id );
			$action    = isset( $_GET['action'] ) && $_GET['action'] === 'edit';

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
		public function change_wc_report_dates() {
			if ( ! empty( $_GET['page'] ) && 'wc-reports' === esc_attr( $_GET['page'] ) ) {
				if ( ! empty( $_GET['start_date'] ) ) {
					$_GET['start_date'] = gregdate( 'Y-m-d', eng_number( wc_clean( wp_unslash( $_GET['start_date'] ) ) ) );
				}

				if ( ! empty( $_GET['end_date'] ) ) {
					$_GET['end_date'] = gregdate( 'Y-m-d', eng_number( wc_clean( wp_unslash( $_GET['end_date'] ) ) ) );
				}
			}
		}

		/**
		 * Converts variations selected Jalali dates to gregorian
		 *
		 * @param $product_id
		 *
		 * @since           4.0.0
		 */
		public function validate_variable_product_dates( $product_id ) {
			if ( ! isset( $_POST['variable_post_id'] ) ) {
				return;
			}

			$max_loop   = max( array_keys( wp_unslash( $_POST['variable_post_id'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$parent     = wc_get_product( $product_id );
			$data_store = $parent->get_data_store();

			$data_store->sort_all_product_variations( $parent->get_id() );

			for ( $i = 0; $i <= $max_loop; $i ++ ) {
				if ( ! isset( $_POST['variable_post_id'][ $i ] ) ) {
					continue;
				}

				$variation_id = absint( $_POST['variable_post_id'][ $i ] );
				$variation    = wc_get_product_object( 'variation', $variation_id );
				$props        = array();

				if ( isset( $_POST['variable_sale_price_dates_from'][ $i ] ) ) {
					$date_on_sale_from = eng_number( wc_clean( wp_unslash( $_POST['variable_sale_price_dates_from'][ $i ] ) ) );

					if ( ! empty( $date_on_sale_from ) ) {
						$props['date_on_sale_from'] = gregdate( 'Y-m-d 00:00:00', $date_on_sale_from );
					}
				}

				if ( isset( $_POST['variable_sale_price_dates_to'][ $i ] ) ) {
					$date_on_sale_to = eng_number( wc_clean( wp_unslash( $_POST['variable_sale_price_dates_to'][ $i ] ) ) );

					if ( ! empty( $date_on_sale_to ) ) {
						$props['date_on_sale_to'] = gregdate( 'Y-m-d 23:59:59', $date_on_sale_to );
					}
				}

				if ( empty( $props ) ) {
					continue;
				}

				$variation->set_props( $props );
				$variation->save();
			}
		}

		public function validate_wc_coupons_date( $coupon_id ) {
			$expiry_date = eng_number( wc_get_post_data_by_key( 'expiry_date' ) );

			if ( empty( $expiry_date ) ) {
				return;
			}

			$coupon            = new WC_Coupon( $coupon_id );
			$fixed_expiry_date = strtotime( gregdate( 'Y-m-d 23:59:59', $expiry_date ) );

			$coupon->set_props( array( 'date_expires' => $fixed_expiry_date ) );
			$coupon->save();
		}

		/**
		 * Remove default wc expire date column in coupons screen and add our custom column
		 *
		 * @param $columns
		 *
		 * @return mixed
		 * @since 4.0.0
		 */
		public function remove_wc_coupon_expiry_date_column( $columns ) {
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
		public function add_jalali_expiry_date_column( $column, $postid ) {
			if ( $column === 'wpp_expiry_date' ) {
				$date = get_post_meta( $postid, 'date_expires', true );

				echo ! empty( $date ) ? parsidate( 'Y-m-d', $date ) : '&ndash;';
			}
		}

		/**
		 * Convert Non-Persian Values in checkout to Persian
		 *
		 * @method  convert_non_persian_values_in_checkout
		 * @param array $data
		 *
		 * @return  array modified $data
		 * @version 1.0.0
		 * @since   4.0.1
		 */
		public function convert_non_persian_values_in_checkout( $data ) {
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
		public function accept_persian_numbers_in_checkout() {
			if ( wpp_is_active( 'woo_accept_per_postcode' ) ) {
				if ( isset( $_POST['billing_postcode'] ) ) {
					$_POST['billing_postcode'] = eng_number( wc_get_post_data_by_key( 'billing_postcode' ) );
				}

				if ( isset( $_POST['shipping_postcode'] ) ) {
					$_POST['shipping_postcode'] = eng_number( wc_get_post_data_by_key( 'shipping_postcode' ) );
				}
			}

			if ( wpp_is_active( 'woo_accept_per_phone' ) ) {
				if ( isset( $_POST['billing_phone'] ) ) {
					$_POST['billing_phone'] = eng_number( wc_get_post_data_by_key( 'billing_phone' ) );
				}

				if ( isset( $_POST['shipping_phone'] ) ) {
					$_POST['shipping_phone'] = eng_number( wc_get_post_data_by_key( 'shipping_phone' ) );
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
		public function validate_postcode( $valid, $postcode, $country ) {
			if ( 'IR' !== $country ) {
				return $valid;
			}

            return wpp_is_postal_code_validate($postcode, apply_filters('wpp_validate_postal_code_checksum', false));
		}

		/**
		 * @param $data
		 * @param $errors
		 *
		 * @return false|void
		 */
		public function validate_phone_number( $data, $errors ) {
			// This pattern ensures the phone number follows the specified structure for both mobile and landline numbers
			if ( preg_match( '/^(0|0098|\+98)?(9\d{9}|[1-8]\d{9,10})$/', eng_number( wc_get_post_data_by_key( 'billing_phone' ) ) ) ) {
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

		/**
		 * Check WooCommerce HPOS enabled or not
		 *
		 * @return bool
		 */
		private function is_hpos_enabled() {
			return class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		}

		/**
		 * Check the current screen is the WooCommerce order edit page
		 *
		 * @return string
		 */
		private function get_current_screen() {
			if ( 'woocommerce_before_order_object_save' === current_action() ) {
				global $pagenow;

				if ( $this->is_hpos_enabled() ) {
					return isset( $_POST['post_ID'] ) && 'shop_order' === get_post_type( $_POST['post_ID'] ) && ! empty( $_POST['order_date'] ) ? 'edit_order' : '';
				}

				return is_admin() && 'post.php' === $pagenow && isset( $_POST['post_type'] ) && 'shop_order' === $_POST['post_type'] && ! empty( $_POST['order_date'] ) ? 'edit_order' : '';
			} elseif ( 'admin_footer' === current_action() ) {
				$screen         = get_current_screen();
				$current_screen = $screen->id;

				if ( in_array( $current_screen, array( 'shop_order', 'woocommerce_page_wc-orders' ) ) ) {
					return 'edit_order';
				} elseif ( 'woocommerce_page_wc-reports' === $current_screen ) {
					return 'legacy_report';
				}

				return $current_screen;
			}

			return null;
		}
	}

	return WPP_WooCommerce::getInstance();
}