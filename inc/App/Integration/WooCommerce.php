<?php
/**
 * Makes WooCommerce compatible with WP-Parsidate plugin
 *
 * @package                 WP-Parsidate
 * @subpackage              Plugins/WooCommerce
 */

namespace WPParsidate\App\Integration;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WPParsidate\Addons\Addon;
use WPParsidate\App\Integration\WooCommerce\{WcGateways, WooCommerceCitySelect};
use WPParsidate\Helper\{Assets, Date, Number};
use WPParsidate\Settings\Settings;

class WooCommerce extends Addon {
  public string $addonID = 'woocommerce';

  public string $currentTab = 'woocommerce';

  public function initM1Action(): void {
    add_filter( 'wp_parsidate_menus', [ $this, 'addMenu' ], 20 );
    add_filter( 'wp_parsidate_' . $this->addonID . '_settings', [ $this, 'addTabSettings' ] );
    add_filter( 'wp_parsidate_' . $this->addonID . '_tab_display_notice', '__return_false' );
    add_filter( 'wp_parsidate_' . $this->addonID . '_tab_content_display_notice', '__return_true' );

    WcGateways::getInstance();

    add_action( 'before_woocommerce_init', [ $this, 'beforeWooCommerceInit' ] );
  }

  public function initAction(): void {
    add_filter( 'woocommerce_reports_get_order_report_query', [ $this, 'fixOrderReportQueryDate' ] );

    if ( get_locale() === 'fa_IR' ) {
      if ( $this->getSetting( 'fix_prices', false ) ) {
        add_filter( 'wc_price', [ $this, 'fixNumbersToPersian' ] );
        add_filter( 'woocommerce_get_price_html', [ $this, 'fixNumbersToPersian' ] );
        add_filter( 'woocommerce_cart_item_price', [ $this, 'fixNumbersToPersian' ] );
        add_filter( 'woocommerce_cart_item_subtotal', [ $this, 'fixNumbersToPersian' ] );
        add_filter( 'woocommerce_cart_subtotal', [ $this, 'fixNumbersToPersian' ] );
        add_filter( 'woocommerce_cart_totals_coupon_html', [ $this, 'fixNumbersToPersian' ] );
        add_filter( 'woocommerce_cart_shipping_method_full_label', [ $this, 'fixNumbersToPersian' ] );
        add_filter( 'woocommerce_cart_total', [ $this, 'fixNumbersToPersian' ] );
      }

      if ( Settings::get( 'persian_date', false ) ) {
        add_action( 'wp_head', [ $this, 'fixDateTimeDirection' ] );
        add_filter( 'woocommerce_email_styles', [ $this, 'fixEmailTime' ], 9999, 2 );

        // Jalali datepicker
        add_action( 'admin_enqueue_scripts', [ $this, 'adminEnqueueScripts' ] );

        // Convert order_date using js
        add_action( 'woocommerce_process_shop_order_meta', [ $this, 'changeOrderDateOnSave' ], 1000 );
        add_filter( 'woocommerce_process_product_meta', [ $this, 'validateNonVariableProductDates' ], 1000 );
        add_action( 'woocommerce_ajax_save_product_variations', [ $this, 'validateVariableProductDates' ], 1000 );
        add_action( 'woocommerce_process_shop_coupon_meta', [ $this, 'validateCouponsDate' ], 1000 );
        add_filter( 'get_post_metadata', [ $this, 'changeOrderDateAndCouponExpiresMeta' ], 10, 4 );

        add_filter( 'manage_edit-shop_coupon_columns', [ $this, 'removeCouponExpiryDateColumn' ] );
        add_action( 'manage_shop_coupon_posts_custom_column', [ $this, 'printCouponExpiryDateColumn' ], 10, 2 );

        add_action( 'admin_footer', [ $this, 'fixShowCreatedOrderDate' ] );
        add_action( 'admin_init', [ $this, 'changeReportDates' ], 1000 );
      }

      add_filter( 'woocommerce_checkout_process', [ $this, 'acceptPersianNumbersInCheckout' ], 20 );
      add_filter( 'woocommerce_checkout_posted_data', [ $this, 'convertNonPersianValuesInCheckout' ] );

      add_filter( 'woocommerce_format_postcode', [ $this, 'acceptPersianNumbersInPostCode' ], 9999, 2 );
      // add_filter( 'woocommerce_order_get_shipping_postcode', [ $this, 'fixPersianNumbersInPostCode' ], 9999, 2 );

      // WC_Order class, get_address_prop method, Filter: 'woocommerce_order_get_[billing|shipping]_[prop]'
      add_filter( 'woocommerce_order_get_shipping_phone', [ $this, 'fixPersianNumbersInPhone' ], 9999, 2 );
      // @TODO: Sanitize or fix phone in block checkout page is a issue I cant fixed, We need add filter on phone for fix and validate in block type
      // Footprint of phone sanitizing and validating in AbstractAddressSchema::sanitize_callback
      // Fix persian number in phone field value in block type checkout page currently not worked
      // WC_Validation::is_phone has error for Persian number in phone field

      if ( $this->getSetting( 'validate_postcode', false ) ) {
        add_filter( 'woocommerce_validate_postcode', [ $this, 'validatePostcode' ], 10, 3 );
      }

      if ( $this->getSetting( 'validate_phone', false ) ) {
        add_action( 'woocommerce_after_checkout_validation', [ $this, 'validatePhoneNumber' ], 10, 2 );
      }
    }
  }

  /**
   * Init Before WooCommerce Loaded
   */
  public function beforeWooCommerceInit(): void {
    // Include City Translate
    if ( $this->getSetting( 'dropdown_cities', false ) ) {
      new WooCommerceCitySelect();
    }

    if ( class_exists( FeaturesUtil::class ) ) {
      FeaturesUtil::declare_compatibility( 'custom_order_tables', WP_PARSI_ROOT, true );
      FeaturesUtil::declare_compatibility( 'product_instance_caching', WP_PARSI_ROOT, true );
    }
  }

  /**
   * @param $data
   * @param  \WP_Error  $errors  WP Error
   *
   * @return void
   */
  public function validatePhoneNumber( $data, $errors ): void {
    // This pattern ensures the phone number follows the specified structure for both mobile and landline numbers
    if ( ! preg_match( '/^(0|0098|\+98)?(9\d{9}|[1-8]\d{9,10})$/',
      Number::toEnglish( wc_get_post_data_by_key( 'billing_phone' ) ) ) ) {
      $errors->add( 'invalid_phone', esc_html__( '<strong>Phone number</strong> is invalid.', 'wp-parsidate' ) );
    }
  }

  /**
   * Validate Iranian customer postal code
   *
   * @param $valid
   * @param $postcode
   * @param $country
   *
   * @return bool
   */
  public function validatePostcode( $valid, $postcode, $country ): bool {
    if ( 'IR' !== $country ) {
      return $valid;
    }

    return \WPParsidate\Helper\WooCommerce::isPostalCode( $postcode,
      apply_filters( 'wp_parsidate_validate_postal_code_checksum', false ) );
  }

  /**
   * Convert Non-Persian Values in checkout to Persian
   *
   * @method  convertNonPersianValuesInCheckout
   * @param  array  $data
   *
   * @return  array modified $data
   * @version 1.0.0
   * @since   4.0.1
   */
  public function convertNonPersianValuesInCheckout( $data ): array {
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
        $data[ $field ] = self::fixPersianCharacters( $data[ $field ] );
      }
    }

    return apply_filters( "wpp_woocommerce_checkout_modified_persian_fields", $data );
  }

  /**
   * replace Arabic characters with equivalent character in Persian
   *
   * @method  fixPersianCharacters
   * @param  string  $string
   *
   * @return  string filtered $string
   * @version 1.0.0
   * @since   4.0.1
   */
  public static function fixPersianCharacters( $string ): string {
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
  public function acceptPersianNumbersInCheckout(): void {
    if ( $this->getSetting( 'fix_persian_postcode', false ) ) {
      if ( isset( $_POST['billing_postcode'] ) ) {
        $_POST['billing_postcode'] = Number::toEnglish( wc_get_post_data_by_key( 'billing_postcode' ) );
      }

      if ( isset( $_POST['shipping_postcode'] ) ) {
        $_POST['shipping_postcode'] = Number::toEnglish( wc_get_post_data_by_key( 'shipping_postcode' ) );
      }
    }

    if ( $this->getSetting( 'fix_persian_phone', false ) ) {
      if ( isset( $_POST['billing_phone'] ) ) {
        $_POST['billing_phone'] = Number::toEnglish( wc_get_post_data_by_key( 'billing_phone' ) );
      }

      if ( isset( $_POST['shipping_phone'] ) ) {
        $_POST['shipping_phone'] = Number::toEnglish( wc_get_post_data_by_key( 'shipping_phone' ) );
      }
    }
  }

  /**
   * Fix persian postal code in WooCommerce checkout
   *
   * @since 6.0
   */
  public function acceptPersianNumbersInPostCode( $postcode, $country ) {
    if ( 'IR' === $country && $this->getSetting( 'fix_persian_postcode', false ) ) {
      $postcode = Number::toEnglish( $postcode );
    }

    return $postcode;
  }

  /**
   * Fix non-persian digits in checkout phone field
   *
   * @param  string  $phone  The address property value.
   * @param  \WC_Order  $order  The order object being read.
   *
   * @since 6.0
   */
  public function fixPersianNumbersInPhone( $phone, $order ): string {
    if ( $order->get_shipping_country() === 'IR' && $this->getSetting( 'fix_persian_phone', false ) ) {
      $phone = Number::toEnglish( $phone );
    }

    return $phone;
  }


  /**
   * Changes gregorian dates to Jalali date on wc report screen
   *
   * @since           4.0.0
   */
  public function changeReportDates(): void {
    $page      = sanitize_text_field( wp_unslash( $_GET['page'] ?? '' ) );
    $startDate = sanitize_text_field( wp_unslash( $_GET['start_date'] ?? '' ) );
    $endDate   = sanitize_text_field( wp_unslash( $_GET['end_date'] ?? '' ) );

    if ( ! empty( $page ) && 'wc-reports' === $page ) {
      if ( ! empty( $startDate ) ) {
        $_GET['start_date'] = gregdate( 'Y-m-d', Number::toEnglish( wc_clean( $startDate ) ) );
      }

      if ( ! empty( $endDate ) ) {
        $_GET['end_date'] = gregdate( 'Y-m-d', Number::toEnglish( wc_clean( $endDate ) ) );
      }
    }
  }

  /**
   * Changes order_date field in "Edit order" screen using JS
   *
   * @since 4.0.0
   */
  public function fixShowCreatedOrderDate(): void {
    $current_screen = $this->getCurrentScreen();

    if ( 'edit_order' === $current_screen ) {
      if ( ! \WPParsidate\Helper\WooCommerce::hposEnabled() ) {
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
          $jalali_date = parsidate( 'Y-m-d',
            ! is_null( $theorder->get_date_created() ) ? $theorder->get_date_created()->getOffsetTimestamp() : '',
            'eng' );
        }
      }

      wp_add_inline_script( 'wpp_jalali_datepicker', 'jQuery("input[name=order_date]").val("' . $jalali_date . '")' );

    } elseif ( 'legacy_report' === $current_screen ) {
      $startDate = sanitize_text_field( wp_unslash( $_GET['start_date'] ?? '' ) );
      $endDate   = sanitize_text_field( wp_unslash( $_GET['end_date'] ?? '' ) );

      $jalali_start_date = ! empty( $startDate ) ? parsidate( 'Y-m-d',
        date( 'Y-m-d', strtotime( $startDate ) ), 'eng' ) : '';
      $jalali_end_date   = ! empty( $endDate ) ? parsidate( 'Y-m-d',
        date( 'Y-m-d', strtotime( $endDate ) ), 'eng' ) : '';

      wp_add_inline_script( 'wpp_jalali_datepicker',
        'jQuery("input[name=start_date]").val("' . $jalali_start_date . '");jQuery("input[name=end_date]").val("' . $jalali_end_date . '");' );

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

        $sale_price_dates_from = $sale_price_dates_from_timestamp ? Number::toEnglish( date_i18n( 'Y-m-d',
          $sale_price_dates_from_timestamp ) ) : '';
        $sale_price_dates_to   = $sale_price_dates_to_timestamp ? Number::toEnglish( date_i18n( 'Y-m-d',
          $sale_price_dates_to_timestamp ) ) : '';

        wp_add_inline_script( 'wpp_jalali_datepicker',
          'jQuery("#_sale_price_dates_from").val("' . $sale_price_dates_from . '");jQuery("#_sale_price_dates_to").val("' . $sale_price_dates_to . '");' );

      } else {
        $dates                = array();
        $loop                 = 0;
        $available_variations = $product->get_available_variations();

        foreach ( $available_variations as $variation ) {
          $variation_id  = $variation['variation_id'];
          $variation_obj = new \WC_Product_Variation( $variation_id );

          $sale_price_dates_from_timestamp = $variation_obj->get_date_on_sale_from( 'edit' ) ? $variation_obj->get_date_on_sale_from( 'edit' )->getOffsetTimestamp() : false;
          $sale_price_dates_to_timestamp   = $variation_obj->get_date_on_sale_to( 'edit' ) ? $variation_obj->get_date_on_sale_to( 'edit' )->getOffsetTimestamp() : false;

          $sale_price_dates_from = $sale_price_dates_from_timestamp ? Number::toEnglish( date_i18n( 'Y-m-d',
            $sale_price_dates_from_timestamp ) ) : '';
          $sale_price_dates_to   = $sale_price_dates_to_timestamp ? Number::toEnglish( date_i18n( 'Y-m-d',
            $sale_price_dates_to_timestamp ) ) : '';
          $dates[ $loop ]        = array(
            'start' => esc_attr( $sale_price_dates_from ),
            'end'   => esc_attr( $sale_price_dates_to ),
          );

          $loop ++;
        }

        if ( ! empty( $dates ) ) {
          wp_add_inline_script( 'wpp_jalali_datepicker',
            'const wppVariationsDates = ' . wp_json_encode( $dates ) . '
						    jQuery("#woocommerce-product-data").on("woocommerce_variations_loaded", function(e) {
							  wppVariationsDates.forEach((date, index) => {
                                jQuery(`input[name="variable_sale_price_dates_from[${index}]"]`).val(date.start)
						        jQuery(`input[name="variable_sale_price_dates_to[${index}]"]`).val(date.end)
						      })
						    })'
          );
        }
      }
    }
  }

  /**
   * Remove default wc expire date column in coupons screen and add our custom column
   *
   * @param $columns
   *
   * @return mixed
   * @since 4.0.0
   */
  public function removeCouponExpiryDateColumn( $columns ) {
    unset( $columns['expiry_date'] );

    $columns['wpp_expiry_date'] = esc_html__( 'Expiry date', 'wp-parsidate' );

    return $columns;
  }

  /**
   * Fill our custom date expires column value
   *
   * @param $column
   * @param $postID
   *
   * @since 4.0.0
   */
  public function printCouponExpiryDateColumn( $column, $postID ): void {
    if ( $column === 'wpp_expiry_date' ) {
      $date = get_post_meta( $postID, 'date_expires', true );

      echo ! empty( $date ) ? esc_html( parsidate( 'Y-m-d', $date ) ) : '-';
    }
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
  public function changeOrderDateAndCouponExpiresMeta( $metadata, $object_id, $meta_key, $single ) {
    global $wpdb;

    $post_type  = get_post_type( $object_id );
    $editAction = sanitize_text_field( wp_unslash( $_GET['action'] ?? '' ) ) === 'edit';

    if ( $editAction && 'shop_coupon' === $post_type && 'date_expires' === $meta_key ) {
      $metadata = $wpdb->get_var(
        $wpdb->prepare(
          "
						SELECT meta_value
						From $wpdb->postmeta
						WHERE post_id = %d
							AND meta_key = %s
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

  public function validateCouponsDate( $coupon_id ): void {
    $expiry_date = Number::toEnglish( wc_get_post_data_by_key( 'expiry_date' ) );

    if ( empty( $expiry_date ) ) {
      return;
    }

    $coupon            = new \WC_Coupon( $coupon_id );
    $fixed_expiry_date = strtotime( gregdate( 'Y-m-d 23:59:59', $expiry_date ) );

    $coupon->set_props( array( 'date_expires' => $fixed_expiry_date ) );
    $coupon->save();
  }

  /**
   * Converts variations selected Jalali dates to gregorian
   *
   * @param $product_id
   *
   * @since           4.0.0
   */
  public function validateVariableProductDates( $product_id ): void {
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
        $date_on_sale_from = Number::toEnglish( sanitize_text_field( wp_unslash( $_POST['variable_sale_price_dates_from'][ $i ] ) ) );

        if ( ! empty( $date_on_sale_from ) ) {
          $props['date_on_sale_from'] = gregdate( 'Y-m-d 00:00:00', $date_on_sale_from );
        }
      }

      if ( isset( $_POST['variable_sale_price_dates_to'][ $i ] ) ) {
        $date_on_sale_to = Number::toEnglish( sanitize_text_field( wp_unslash( $_POST['variable_sale_price_dates_to'][ $i ] ) ) );

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

  /**
   * Convert selected Jalali dates to gregorian on woocommerce save non-variable products
   *
   * @param  $product_id  $
   *
   * @return          void
   * @author HamidReza Yazdani
   *
   * @since           4.0.0
   */
  public function validateNonVariableProductDates( $product_id ) {
    $props = array();

    if ( isset( $_POST['_sale_price_dates_from'] ) ) {
      $date_on_sale_from  = Number::toEnglish( wc_get_post_data_by_key( '_sale_price_dates_from' ) );
      $salePriceTimesFrom = sanitize_text_field( wp_unslash( $_POST['_sale_price_times_from'] ?? '' ) );
      $time_on_sale_from  = ! empty( $salePriceTimesFrom ) && Date::isTimeString( $salePriceTimesFrom ) ? Date::isTimeString( $salePriceTimesFrom ) : '00:00:00';

      if ( ! empty( $date_on_sale_from ) ) {
        $props['date_on_sale_from'] = date( 'Y-m-d ' . $time_on_sale_from,
          strtotime( gregdate( 'Y-m-d', $date_on_sale_from ) ) );
      }
    }

    if ( isset( $_POST['_sale_price_dates_to'] ) ) {
      $date_on_sale_to  = Number::toEnglish( wc_get_post_data_by_key( '_sale_price_dates_to' ) );
      $salePriceTimesTo = sanitize_text_field( wp_unslash( $_POST['_sale_price_times_to'] ?? '' ) );
      $time_on_sale_to  = ! empty( $salePriceTimesTo ) && Date::isTimeString( $salePriceTimesTo ) ? Date::isTimeString( $salePriceTimesTo ) : '23:59:59';

      if ( ! empty( $date_on_sale_to ) ) {
        $props['date_on_sale_to'] = date( 'Y-m-d ' . $time_on_sale_to,
          strtotime( gregdate( 'Y-m-d', $date_on_sale_to ) ) );
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
   * Convert order date to gregorian before saved at database
   *
   * @param $order_id
   *
   * @throws \WC_Data_Exception
   * @since 5.0.2
   */
  public function changeOrderDateOnSave( $order_id ): void {
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
    $date       = gmdate( 'Y-m-d H:i:s', strtotime( $fixed_date ) );

    $order->set_date_created( $date );
    $order->save();
  }

  /**
   * enqueue jalali date picker assets
   *
   * @since           4.0.0
   */
  public function adminEnqueueScripts(): void {
    $screen         = get_current_screen();
    $current_screen = is_null( $screen ) ? false : $screen->id;
    $pluginVersion  = Assets::getVersion();
    $debugName      = WP_PARSI_DEBUG_MODE ? '' : '.min';

    $allowed_screens = array(
      'product',
      'shop_order',
      'woocommerce_page_wc-orders',
      'woocommerce_page_wc-reports',
      'shop_coupon',
    );

    if ( in_array( $current_screen, $allowed_screens, true ) && Settings::get( 'persian_date' ) ) {
      wp_enqueue_script( 'wpp_jalali_datepicker', Assets::url( 'js-admin/jalalidatepicker.min.js' ),
        array( 'jquery-ui-datepicker' ), $pluginVersion, [ 'in_footer' => true ] );
      wp_enqueue_style( 'wpp_jalali_datepicker', Assets::url( "css-admin/jalalidatepicker$debugName.css" ),
        null, $pluginVersion );

      do_action( 'wpp_jalali_datepicker_enqueued', 'wc' );
    }
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
  public function fixEmailTime( $style, $email ): string {
    return $style . 'time{unicode-bidi:embed!important}';
  }

  /**
   * Fixes jalali order date direction in WooCommerce my-account endpoints (Issue: https://github.com/wordpress-parsi/wp-parsidate/issues/154)
   *
   * @return void
   * @since 5.0.0
   */
  public function fixDateTimeDirection(): void {
    if ( is_woocommerce() || is_wc_endpoint_url() || is_cart() || is_checkout() ) {
      echo '<style>mark.order-date,time{unicode-bidi:embed!important}</style>';
    }
  }

  public function fixNumbersToPersian( $content ): string {
    return Number::fixNumber( $content );
  }

  /**
   * @param $report_data
   *
   * @return mixed
   */
  public function fixOrderReportQueryDate( $report_data ) {
    $report_data['where'] = preg_replace_callback( "/posts.post_date\s.=?\s'([^']+)'/i",
      [ __CLASS__, 'fixOrderReportQueryDateCallback' ], $report_data['where'] );

    return $report_data;
  }

  /**
   * @param $date
   *
   * @return array|mixed|string|string[]
   */
  public function fixOrderReportQueryDateCallback( $date ) {
    $startDate = sanitize_text_field( wp_unslash( $_GET['start_date'] ?? '' ) );
    $endDate   = sanitize_text_field( wp_unslash( $_GET['end_date'] ?? '' ) );

    if ( empty( $startDate ) || empty( $endDate ) ) {
      return $date[0];
    }

    if ( strpos( $date[0], '=' ) === false ) {
      if ( (int) $endDate > 1900 ) {
        return $date[0];
      }

      $dt = gregdate( 'Y-m-d', $endDate );
      $dt = date( 'Y-m-d', strtotime( "$dt +1 day" ) );
    } else {
      if ( (int) $startDate > 1900 ) {
        return $date[0];
      }

      $dt = gregdate( 'Y-m-d', $startDate );
    }

    return substr_replace( $date[0], $dt, - 20, 10 );
  }

  /**
   * Check the current screen is the WooCommerce order edit page
   *
   * @return string
   */
  private function getCurrentScreen(): ?string {
    if ( 'woocommerce_before_order_object_save' === current_action() ) {
      global $pagenow;

      if ( \WPParsidate\Helper\WooCommerce::hposEnabled() ) {
        $postID = (int) sanitize_text_field( wp_unslash( $_POST['post_ID'] ?? 0 ) );

        return $postID && 'shop_order' === get_post_type( $postID ) ? 'edit_order' : '';
      }

      return is_admin() && 'post.php' === $pagenow && isset( $_POST['post_type'] ) && 'shop_order' === $_POST['post_type'] && ! empty( $_POST['order_date'] ) ? 'edit_order' : '';
    }

    if ( 'admin_footer' === current_action() ) {
      $screen         = get_current_screen();
      $current_screen = is_null( $screen ) ? '' : $screen->id;

      if ( in_array( $current_screen, array( 'shop_order', 'woocommerce_page_wc-orders' ) ) ) {
        return 'edit_order';
      }

      if ( 'woocommerce_page_wc-reports' === $current_screen ) {
        return 'legacy_report';
      }

      return $current_screen;
    }

    return null;
  }

  public function addTabSettings(): array {
    $settings = array(
      'title'        => esc_html__( 'WooCommerce', 'wp-parsidate' ),
      'desc'         => esc_html__( 'ParsiDate integration for WooCommerce', 'wp-parsidate' ),
      'settings_key' => $this->addonID,
      'settings'     => [
        'woo_product_start_grid'  => array(
          'id'    => 'woo_product_start_grid',
          'title' => esc_html__( 'Products', 'wp-parsidate' ),
          'type'  => 'startGrid',
        ),
        'fix_prices'              => array(
          'id'       => 'fix_prices',
          'title'    => esc_html__( 'Fix prices', 'wp-parsidate' ),
          'type'     => 'toggle',
          'default'  => false,
          'sanitize' => 'bool'
        ),
        'woo_product_end_grid'    => array(
          'type' => 'endGrid',
        ),
        'woo_checkout_start_grid' => array(
          'id'    => 'woo_checkout_start_grid',
          'title' => esc_html__( 'Checkout page', 'wp-parsidate' ),
          'type'  => 'startGrid',
        ),
        'fix_persian_postcode'    => array(
          'id'       => 'fix_persian_postcode',
          'title'    => esc_html__( 'Fix persian postcode', 'wp-parsidate' ),
          'type'     => 'toggle',
          'default'  => false,
          'sanitize' => 'bool'
        ),
        'fix_persian_phone'       => array(
          'id'       => 'fix_persian_phone',
          'title'    => esc_html__( 'Fix persian phone', 'wp-parsidate' ),
          'type'     => 'toggle',
          'default'  => false,
          'sanitize' => 'bool'
        ),
        'dropdown_cities'         => array(
          'id'       => 'dropdown_cities',
          'title'    => esc_html__( 'Display cities as a drop-down list', 'wp-parsidate' ),
          'type'     => 'toggle',
          'default'  => false,
          'sanitize' => 'bool'
        ),
        'validate_postcode'       => array(
          'id'       => 'validate_postcode',
          'title'    => esc_html__( 'Postcode validation', 'wp-parsidate' ),
          'type'     => 'toggle',
          'default'  => false,
          'sanitize' => 'bool'
        ),
        'validate_phone'          => array(
          'id'       => 'validate_phone',
          'title'    => esc_html__( 'Phone number validation', 'wp-parsidate' ),
          'type'     => 'toggle',
          'default'  => false,
          'sanitize' => 'bool'
        ),
        'woo_checkout_end_grid'   => array(
          'type' => 'endGrid',
        )
      ]
    );

    return apply_filters( 'wp_parsidate_' . $this->addonID . '_settings_options', $settings );
  }

  public function addMenu( $menus ) {
    $menus[ $this->addonID ] = array(
      'title' => esc_html__( 'WooCommerce', 'wp-parsidate' ),
      'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 183.6 47.5">
  <path d="M70.141 3.572c-3.638 0-6.011 1.187-8.125 5.167L52.36 26.945V10.77c0-4.827-2.287-7.198-6.521-7.198-4.237 0-6.014 1.439-8.132 5.504l-9.145 17.869V10.94c0-5.167-2.119-7.368-7.284-7.368H10.776c-3.981 0-6.182 1.864-6.182 5.25 0 3.389 2.116 5.422 6.013 5.422h4.319v20.41c0 5.757 3.895 9.146 9.486 9.146 5.59 0 8.13-2.203 10.924-7.37l6.097-11.431v9.655c0 5.671 3.726 9.146 9.402 9.146 5.674 0 7.79-1.949 11.011-7.37l14.055-23.711c3.051-5.167.933-9.147-5.842-9.147h.082Zm36.908 0c-11.517 0-20.24 8.554-20.24 20.157 0 11.601 8.806 20.071 20.24 20.071s20.157-8.553 20.242-20.071c0-11.603-8.808-20.157-20.242-20.157m0 27.863c-4.319 0-7.283-3.217-7.283-7.706 0-4.49 2.964-7.792 7.283-7.792 4.32 0 7.285 3.302 7.285 7.792 0 4.489-2.879 7.706-7.285 7.706m51.794-27.863c-11.431 0-20.242 8.554-20.242 20.157 0 11.601 8.811 20.071 20.242 20.071 11.435 0 20.241-8.553 20.241-20.071s-8.806-20.157-20.241-20.157m0 27.863c-4.404 0-7.197-3.217-7.197-7.706 0-4.49 2.879-7.792 7.197-7.792 4.319 0 7.284 3.302 7.284 7.792 0 4.489-2.88 7.706-7.284 7.706" style="stroke:#3c3c3c;fill:none;paint-order:fill;stroke-width:7px;fill-rule:evenodd;clip-rule:evenodd"/>
</svg>'
    );

    return $menus;
  }

  public function info(): array {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" x="0" y="0" viewBox="0 0 183.6 47.5"><style>.st0{fill-rule:evenodd;clip-rule:evenodd;fill:#873eff}</style><path d="M77.4 0c-4.3 0-7.1 1.4-9.6 6.1L56.4 27.6V8.5c0-5.7-2.7-8.5-7.7-8.5s-7.1 1.7-9.6 6.5L28.3 27.6V8.7c0-6.1-2.5-8.7-8.6-8.7H7.3C2.6 0 0 2.2 0 6.2s2.5 6.4 7.1 6.4h5.1v24.1c0 6.8 4.6 10.8 11.2 10.8s9.6-2.6 12.9-8.7l7.2-13.5v11.4c0 6.7 4.4 10.8 11.1 10.8s9.2-2.3 13-8.7l16.6-28C87.8 4.7 85.3 0 77.3 0zM108.6 0C95 0 84.7 10.1 84.7 23.8s10.4 23.7 23.9 23.7 23.8-10.1 23.9-23.7c0-13.7-10.4-23.8-23.9-23.8m0 32.9c-5.1 0-8.6-3.8-8.6-9.1s3.5-9.2 8.6-9.2 8.6 3.9 8.6 9.2-3.4 9.1-8.6 9.1M159.7 0c-13.5 0-23.9 10.1-23.9 23.8s10.4 23.7 23.9 23.7 23.9-10.1 23.9-23.7S173.2 0 159.7 0m0 32.9c-5.2 0-8.5-3.8-8.5-9.1s3.4-9.2 8.5-9.2 8.6 3.9 8.6 9.2-3.4 9.1-8.6 9.1" class="st0"/></svg>';

    return array(
      'id'               => $this->addonID,
      'title'            => esc_html__( 'WooCommerce', 'wp-parsidate' ),
      'desc'             => esc_html__( 'ParsiDate integration for WooCommerce', 'wp-parsidate' ),
      'force_enable'     => true,
      'has_page'         => false,
      'icon'             => $svg,
      'tags'             => [ esc_html__( 'WooCommerce', 'wp-parsidate' ) ],
      'cat'              => 'ecommerce',
      'settings_key'     => $this->addonID,
      'requires_plugins' => [
        'woocommerce/woocommerce.php' => array(
          'is_wp_plugin'   => true,
          'is_free'        => true,
          'plugin_link'    => 'https://wordpress.org/plugins/woocommerce/',
          'function_check' => '',
          'class_check'    => 'WooCommerce',
        )
      ]
    );
  }
}
