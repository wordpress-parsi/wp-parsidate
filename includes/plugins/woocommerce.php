<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

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

			if ( wpp_is_active( 'woo_fix_date' ) ) {
				// Jalali datepicker
				add_action( 'admin_enqueue_scripts', array( $this, 'wpp_admin_woocommerce_jalali_datepicker_assets' ) );

				// Convert order_date using js
				add_action( 'save_post', array($this, 'wpp_change_order_date_on_save_order'), 0, 2 );
				add_action( 'admin_footer', array( $this, 'wpp_fix_show_created_order_date' ) );

				add_action( 'admin_init', array( $this, 'wpp_change_wc_report_dates' ), 1000 );
				add_filter( 'wp_insert_post_data', array( $this, 'wpp_validate_dates_on_woocommerce_save_data' ), 1, 2 );
				add_action( 'woocommerce_admin_process_variation_object', array( $this, 'wpp_convert_wc_variations_scheduled_sale_dates' ), 1000, 2 );

				add_filter( 'get_post_metadata', array( $this, 'wpp_change_wc_order_date_and_coupon_expires' ), 10, 4 );

				add_filter( 'manage_edit-shop_coupon_columns', array( $this, 'wpp_remove_wc_coupon_expiry_date_column' ), 10, 1 );
				add_action( 'manage_shop_coupon_posts_custom_column', array( $this, 'wpp_add_jalali_expiry_date_column' ), 10, 2 );
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
	 * Adds settings for toggle fixing
	 *
	 * @param array $old_settings Old settings
	 *
	 * @return          array New settings
	 * @since 4.0.0
	 */
	public function add_settings( $old_settings ) {
		$settings = array(
			'woocommerce'   => array(
				'id'   => 'woocommerce',
				'name' => __( 'WooCommerce', 'wp-parsidate' ),
				'type' => 'header'
			),
			'woo_fix_date'  => array(
				'id'      => 'woo_fix_date',
				'name'    => __( 'Jalali Datepicker', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 1,
				'std'     => 0
			),
			'woo_per_price' => array(
				'id'      => 'woo_per_price',
				'name'    => __( 'Fix prices', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 1,
				'std'     => 0
			)
		);

		return array_merge( $old_settings, $settings );
	}

	/**
	 * enqueue jalali date picker assets
	 *
	 * @since           4.0.0
	 */
	public function wpp_admin_woocommerce_jalali_datepicker_assets() {
		$screen = get_current_screen();

		if ( ! $screen || ! property_exists( $screen, 'post_type' ) ) {
			return;
		}

		$suffix         = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || wpp_is_active( 'dev_mode' ) ? '' : '.min';
		$current_screen = $screen->post_type;

		if ( wpp_is_active( 'woo_fix_date' )
		     && in_array( $current_screen, array( 'product', 'shop_order', 'shop_coupon', 'wc-reports' ) ) ) {
			wp_enqueue_script( 'wpp-jalali-datepicker', WP_PARSI_URL . 'assets/js/jalalidatepicker.min.js', array( 'jquery', 'jquery-ui-datepicker' ), WP_PARSI_VER );
			wp_enqueue_style( 'wpp-jalali-datepicker', WP_PARSI_URL . "assets/css/jalalidatepicker$suffix.css", null, WP_PARSI_VER );
		}
	}

	/**
	 * Unfortunately WooCommerce does not use standard functions for dates,
	 * so we have to change values after or before submission.
	 *
	 * @param $post_id
	 * @param $post
	 * @since 4.0.0
	 */
	public function wpp_change_order_date_on_save_order( $post_id, $post ) {
		$post_type = get_post_type( $post_id );

		if ( 'shop_order' === $post_type && ! empty( $_POST['order_date'] ) ) {
			$_POST['order_date'] = gregdate( 'Y-m-d', $_POST['order_date'] );
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

		if ( 'shop_order' !== $screen_id ) {
			return;
		}

		global $post;

		if ( ! $post ) {
			return;
		}

		$jalali_date = parsidate( 'Y-m-d', date( 'Y-m-d', strtotime( $post->post_date ) ) );

		echo '<script>jQuery(function($){$("input[name=order_date]").val("' . $jalali_date . '")})</script>';
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
					$_POST['_sale_price_dates_from'] = gregdate( 'Y-m-d', esc_attr( $_POST['_sale_price_dates_from'] ) );
				}

				if ( ! empty( $_POST['_sale_price_dates_to'] ) && $post['post_type'] == 'product' ) {
					$_POST['_sale_price_dates_to'] = gregdate( 'Y-m-d', esc_attr( $_POST['_sale_price_dates_to'] ) );
				}

				break;
			case 'shop_coupon':
				if ( ! empty( $_POST['expiry_date'] ) ) {
					$_POST['expiry_date'] = gregdate( 'Y-m-d', esc_attr( $_POST['expiry_date'] ) );
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
				return parsidate( 'Y-m-d', $metadata );
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
				$_GET['start_date'] = esc_attr( gregdate( 'Y-m-d', $_GET['start_date'] ) );
			}

			if ( ! empty( $_GET['end_date'] ) ) {
				$_GET['end_date'] = esc_attr( gregdate( 'Y-m-d', $_GET['end_date'] ) );
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
			$date_on_sale_from = wc_clean( wp_unslash( $_POST['variable_sale_price_dates_from'][ $index ] ) );

			if ( ! empty( $date_on_sale_from ) ) {
				$date_on_sale_from = gregdate( 'Y-m-d 00:00:00', $date_on_sale_from );
			}
		}

		if ( ! empty( $_POST['variable_sale_price_dates_to'][ $index ] ) ) {
			$date_on_sale_to = wc_clean( wp_unslash( $_POST['variable_sale_price_dates_to'][ $index ] ) );

			if ( ! empty( $date_on_sale_to ) ) {
				$date_on_sale_to = gregdate( 'Y-m-d 23:59:59', $date_on_sale_to );
			}
		}

		$variation->set_props(
			array(
				'date_on_sale_from' => $date_on_sale_from,
				'date_on_sale_to'   => $date_on_sale_to,
			)
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
	 * @since 4.0.0
	 */
	public function wpp_add_jalali_expiry_date_column( $column, $postid ) {
		if ( $column == 'wpp_expiry_date' ) {
			$date = get_post_meta( $postid, 'date_expires', true );

			echo ! empty( $date ) ? parsidate( 'Y-m-d', $date ) : '&ndash;';
		}
	}
}

return WPP_WooCommerce::getInstance();