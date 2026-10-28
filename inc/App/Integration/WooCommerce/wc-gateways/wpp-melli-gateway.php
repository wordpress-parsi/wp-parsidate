<?php

use WPParsidate\Helper\Assets;

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

if ( ! function_exists( 'wpp_melli_payment_gateway_init' ) ) {

  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Legacy public function, kept for backward compatibility.
  function wpp_melli_payment_gateway_init() {
    if ( ! class_exists( 'WPP_WC_Melli_Gateway' ) && ! class_exists( 'WPP_WC_Melli_Gateway' ) ) {
      /**
       * WPP_WC_Melli_Gateway class to add Melli Bank payment gateway to WooCommerce
       *
       * @package                 WP-Parsidate
       * @subpackage              Plugins/WooCommerce/PaymentGateways
       * @since 5.0.0
       */
      // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Legacy class name, kept for backward compatibility.
      class WPP_WC_Melli_Gateway extends WC_Payment_Gateway {

        // Order meta holding the payment tokens requested for the order
        private const META_TOKENS = '_wpp_melli_tokens';

        // Order meta holding the order id handed to the bank in the signed request
        private const META_ORDER_ID = '_wpp_melli_order_id';

        private $gateway_name;
        public $terminal_id;
        public $merchant_id;
        public $key;
        public $title;
        public $description;
        public $failed_massage;

        public function __construct() {
          $this->id                 = 'melli';
          $this->gateway_name       = esc_html__( 'Melli Bank', 'wp-parsidate' );
          $this->method_title       = $this->gateway_name;
          $this->method_description = $this->gateway_name . ' ' . esc_html__( 'payment gateway (By WP-Parsidate)',
              'wp-parsidate' );
          $this->has_fields         = true;
          // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Legacy public hook name, kept for backward compatibility.
          $this->icon               = apply_filters( $this->id . '_logo',
            Assets::url( "images/woocommerce/$this->id-logo.png" ) );

          $this->init_form_fields();
          $this->init_settings();

          $this->terminal_id    = $this->get_option( 'terminal_id' );
          $this->merchant_id    = $this->get_option( 'merchant_id' );
          $this->key            = $this->get_option( 'key' );
          $this->title          = $this->get_option( 'title' );
          $this->description    = $this->get_option( 'description' );
          $this->failed_massage = $this->get_option( 'failed_massage' );

          // Save Admin Option
          add_action( 'woocommerce_update_options_payment_gateways_' . $this->id,
            array( $this, 'process_admin_options' ) );

          // Handle Request
          add_action( 'woocommerce_api_' . $this->get_class(), array( $this, 'handle_gateway_response' ) );
        }

        public function get_class() {
          return strtolower( get_class( $this ) );
        }

        public function init_form_fields() {
          // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy public hook name, kept for backward compatibility.
          $this->form_fields = apply_filters( 'wpp_wc_' . $this->id . '_gateway_config', array(
              'enabled'        => array(
                'title'       => esc_html__( 'Enabled/Disabled', 'wp-parsidate' ),
                'type'        => 'checkbox',
                /* translators: %s: Bank name */
                'label'       => sprintf( esc_html__( 'Activate or deactivate %s gateway', 'wp-parsidate' ),
                  $this->gateway_name ),
                'default'     => 'no',
                'description' => ( $this->is_enable_open_ssl() === false ? '<span style="color: red;">توجه: جهت فعال سازی درگاه می بایست ماژول OpenSSL در تنظیمات PHP هاست شما فعال باشد</span>' : '' ),
              ),
              'terminal_id'    => array(
                'title'    => esc_html__( 'Terminal No.', 'wp-parsidate' ),
                'type'     => 'text',
                'default'  => '',
                'desc_tip' => false
              ),
              'merchant_id'    => array(
                'title'    => esc_html__( 'Merchant ID', 'wp-parsidate' ),
                'type'     => 'text',
                'default'  => '',
                'desc_tip' => false
              ),
              'key'            => array(
                'title'    => esc_html__( 'Gateway Key', 'wp-parsidate' ),
                'type'     => 'text',
                'default'  => '',
                'desc_tip' => false
              ),
              'title'          => array(
                'title'       => esc_html__( 'Gateway title', 'wp-parsidate' ),
                'type'        => 'text',
                'description' => esc_html__( 'This name is displayed to the customer during the purchase process',
                  'wp-parsidate' ),
                'default'     => $this->gateway_name
              ),
              'description'    => array(
                'title'       => esc_html__( 'Gateway description', 'wp-parsidate' ),
                'type'        => 'textarea',
                'description' => esc_html__( 'The description that will be displayed during the purchase process for the gateway',
                  'wp-parsidate' ),
                /* translators: %s: Bank name */
                'default'     => sprintf( esc_html__( "Secure payment by all Shetab's cards through %s",
                  'wp-parsidate' ), $this->gateway_name )
              ),
              'failed_massage' => array(
                'title'       => esc_html__( 'Payment failed message', 'wp-parsidate' ),
                'type'        => 'textarea',
                'description' => esc_html__( 'Enter the text of the message you want to display to the user after an unsuccessful payment.',
                  'wp-parsidate' ),
                'default'     => esc_html__( 'Your payment has failed. Please try again or contact us in case of problems.',
                  'wp-parsidate' )
              )
            )
          );
        }

        public function get_icon() {
          $icon = $this->icon ? '<img src="' . esc_url_raw( WC_HTTPS::force_https_url( $this->icon ) ) . '" alt="' . esc_attr( $this->get_title() ) . '" />' : '';

          // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce hook.
          return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
        }

        public function is_available() {
          return parent::is_available();
        }

        public function encrypt_pkcs7( $str, $key ): string {
          $key        = base64_decode( $key );
          $ciphertext = OpenSSL_encrypt( $str, "DES-EDE3", $key, OPENSSL_RAW_DATA );

          return base64_encode( $ciphertext );
        }

        public function api( $url, $data = false ) {
          try {
            $args = array(
              'headers'   => array(
                'Content-Type' => 'application/json; charset=utf-8',
              ),
              'timeout'   => 20,
              'sslverify' => false
            );

            if ( $data ) {
              $args['body'] = json_encode( $data );
            }

            $response = wp_remote_post( $url, $args );

            if ( is_wp_error( $response ) ) {
              return false;
            }

            $body = wp_remote_retrieve_body( $response );

            return ! empty( $body ) ? json_decode( $body ) : false;
          } catch ( Exception $ex ) {
            return false;
          }
        }

        public function is_enable_open_ssl(): bool {
          return ( extension_loaded( 'openssl' ) and function_exists( 'OpenSSL_encrypt' ) );
        }

        public function process_payment( $order_id ) {
          // Get Order
          $order = wc_get_order( $order_id );

          // Get Gateway RefId
          $refId = $this->get_ref_id_from_melli( $order );

          // Action
          // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy public hook name, kept for backward compatibility.
          do_action( 'wpp_wc_' . $this->id . '_gateway_process_payment', $order, $refId );

          if ( $refId['status'] === false ) {

            // setup Error Text
            $errorText = 'خطا در اتصال به درگاه پرداخت: ' . $refId['message'];

            // Add Notice
            wc_add_notice( $errorText, 'error' );

            // Return for Block Support
            return [
              'result'   => 'failure',
              'messages' => $refId['message'],
              'reload'   => false
            ];
          }

          // Remember which token was issued for this order, so the callback can be
          // tied back to it (see verify_payment()). Without this binding the callback
          // has no way to tell which order the bank confirmation belongs to.
          $this->remember_token( $order, (string) $refId['token'], (string) ( $refId['orderId'] ?? $order->get_id() ) );

          // Return
          return [
            'result'   => 'success',
            'redirect' => $refId['redirect']
          ];
        }

        public function get_ref_id_from_melli( $order ): array {
          $Amount        = $this->get_amount( $order );
          $OrderId       = $order->get_id();
          $LocalDateTime = gmdate( "m/d/Y g:i:s a" );
          $TerminalId    = $this->terminal_id;
          $key           = $this->key;
          $SignData      = $this->encrypt_pkcs7( "$TerminalId;$OrderId;$Amount", "$key" );

          // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy public hook name, kept for backward compatibility.
          $data = apply_filters( 'wpp_wc_' . $this->id . '_gateway_request_payment', array(
            'TerminalId'    => $this->terminal_id,
            'MerchantId'    => $this->merchant_id,
            'Amount'        => $this->get_amount( $order ),
            'SignData'      => $SignData,
            'ReturnUrl'     => add_query_arg(
              array(
                'wc-api'   => $this->get_class(),
                'order_id' => $order->get_id()
              ),
              get_site_url( null, '/' )
            ),
            'LocalDateTime' => $LocalDateTime,
            'OrderId'       => $OrderId
          ) );

          // Keep the values actually sent to the bank: the callback is validated against them.
          $bankOrderId = (string) ( $data['OrderId'] ?? $OrderId );

          $result = $this->api( 'https://sadad.shaparak.ir/vpg/api/v0/Request/PaymentRequest', $data );

          if ( is_object( $result ) && (int) $result->ResCode === 0 ) {

            return [
              'status'   => true,
              'token'    => $result->Token,
              'orderId'  => $bankOrderId,
              'redirect' => "https://sadad.shaparak.ir/VPG/Purchase?Token=" . $result->Token
            ];
          }

          return [
            'status'  => false,
            'message' => is_object( $result ) && isset( $result->Description ) ? $result->Description : $this->failed_massage
          ];
        }

        public function handle_gateway_response() {
          // phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing -- Bank callback request, it cannot carry a WordPress nonce. The payment is verified with the bank API.
          $action   = sanitize_text_field( wp_unslash( $_GET['action'] ?? '' ) );
          $order_id = (int) sanitize_text_field( wp_unslash( $_GET['order_id'] ?? 0 ) );
          // phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
          $order    = wc_get_order( $order_id );

          if ( ! $order ) {
            wp_die( 'سفارش یافت نشد' );
          }

          // Only orders created with this gateway may be completed here.
          if ( $order->get_payment_method() !== $this->id ) {
            wp_die( 'درخواست پرداخت نامعتبر است' );
          }

          if ( $action === 'redirect' ) {
            $this->redirect_to_gateway( $order );

            return;
          }

          $this->verify_payment( $order );
        }

        public function redirect_to_gateway( $order ) {
          // Don't Need
        }

        public function verify_payment( $order ) {
          // phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing -- Bank callback request, it cannot carry a WordPress nonce. The payment is verified with the bank API.
          $Token   = sanitize_text_field( wp_unslash( $_POST["token"] ?? '' ) );
          $ResCode = sanitize_text_field( wp_unslash( $_POST["ResCode"] ?? '' ) );
          // phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing

          if ( $ResCode != 0 ) {
            $this->set_failed_payment( $order );
          }

          // A paid order must never be completed twice: the same callback may arrive
          // more than once, but no other receipt may be redeemed on it.
          if ( $order->is_paid() || $order->get_transaction_id() ) {

            if ( $Token !== '' && $this->is_issued_token( $order, $Token ) ) {
              wp_safe_redirect( esc_url_raw( $this->get_return_url( $order ) ) );
              exit;
            }

            $this->reject_payment( $order, 'order is already paid' );
          }

          /**
           * The order is taken from the query string and every value of this request
           * is attacker controlled, so the order must never be completed on the
           * strength of the request alone. The token is what the bank issued for this
           * order, and the amount was part of the signed request that created it.
           */
          if ( $Token === '' ) {
            $this->reject_payment( $order, 'token is missing' );
          }

          $issued_tokens = $order->get_meta( self::META_TOKENS );
          $has_tokens    = is_array( $issued_tokens ) && ! empty( $issued_tokens );

          if ( $has_tokens && ! $this->is_issued_token( $order, $Token ) ) {
            $this->reject_payment( $order, 'token was not issued for this order' );
          }

          $verifyData = [
            'Token'    => $Token,
            'SignData' => $this->encrypt_pkcs7( $Token, $this->key )
          ];
          $result     = $this->api( 'https://sadad.shaparak.ir/vpg/api/v0/Advice/Verify', $verifyData );

          // A failed or malformed answer from the bank must never complete an order.
          if ( ! is_object( $result ) || (int) $result->ResCode !== 0 ) {
            $this->set_failed_payment( $order );
          }

          $expectedOrderId = (string) $order->get_meta( self::META_ORDER_ID );

          // Orders started before this meta existed always used the order ID.
          if ( $expectedOrderId === '' ) {
            $expectedOrderId = (string) $order->get_id();
          }

          // The bank reports the order id the token was issued for, so it is the only
          // trustworthy answer to "which order did this payment confirm?".
          $bankOrderId = isset( $result->OrderId ) ? (string) $result->OrderId : '';

          if ( ! $has_tokens && $bankOrderId === '' ) {
            $this->reject_payment( $order, 'no token is on record for this order and the bank did not report the order id' );
          }

          if ( $bankOrderId !== '' && $bankOrderId !== $expectedOrderId ) {
            $this->reject_payment( $order, 'bank order id does not belong to this order' );
          }

          $retrieval_ref_no = isset( $result->RetrivalRefNo ) ? (string) $result->RetrivalRefNo : '';

          if ( $retrieval_ref_no === '' || ! ctype_digit( $retrieval_ref_no ) ) {
            $this->reject_payment( $order, 'RetrivalRefNo is missing or malformed' );
          }

          // A single receipt may complete a single order, ever.
          if ( $this->receipt_in_use( $retrieval_ref_no, $order->get_id() ) ) {
            $this->reject_payment( $order, 'RetrivalRefNo was already used by another order' );
          }

          // Set Payment Completed
          $order->payment_complete( $retrieval_ref_no );

          // Add Order Note
          $order->add_order_note( sprintf( 'پرداخت با موفقیت انجام شد. کد پیگیری: %s',
            $retrieval_ref_no ) );

          // Remove cart.
          WC()->cart->empty_cart();

          // Action
          // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy public hook name, kept for backward compatibility.
          do_action( 'wpp_wc_' . $this->id . '_gateway_completed_payment', $order, [
            'RetrivalRefNo' => $retrieval_ref_no,
            'SystemTraceNo' => isset( $result->SystemTraceNo ) ? $result->SystemTraceNo : '',
            'OrderId'       => $bankOrderId,
          ] );

          // Redirect
          wp_safe_redirect( esc_url_raw( $this->get_return_url( $order ) ) );
          exit;
        }

        /**
         * Remember a token that was requested for this order
         *
         * The payment page can be loaded more than once, so every token issued for the
         * order is kept until one of them completes it.
         *
         * @param WC_Order $order
         * @param string $token
         * @param string $bank_order_id Order id handed to the bank with the token
         *
         * @return void
         */
        private function remember_token( $order, string $token, string $bank_order_id ): void {
          $tokens = $order->get_meta( self::META_TOKENS );
          $tokens = is_array( $tokens ) ? $tokens : array();
          $tokens = array_values( array_unique( array_merge( $tokens, array( $token ) ) ) );
          $tokens = array_slice( $tokens, - 10 );

          $order->update_meta_data( self::META_TOKENS, $tokens );
          $order->update_meta_data( self::META_ORDER_ID, $bank_order_id );
          $order->save();
        }

        /**
         * Check whether a token was requested for this order
         *
         * @param WC_Order $order
         * @param string $token
         *
         * @return bool
         */
        private function is_issued_token( $order, string $token ): bool {
          $tokens = $order->get_meta( self::META_TOKENS );

          if ( $token === '' || ! is_array( $tokens ) ) {
            return false;
          }

          return in_array( $token, array_map( 'strval', $tokens ), true );
        }

        /**
         * Check whether a receipt already completed another order
         *
         * @param string $retrieval_ref_no
         * @param int $order_id Order currently being processed
         *
         * @return bool
         */
        private function receipt_in_use( $retrieval_ref_no, $order_id ): bool {
          if ( (string) $retrieval_ref_no === '' || ! function_exists( 'wc_get_orders' ) ) {
            return false;
          }

          $orders = wc_get_orders( array(
            'limit'          => 1,
            'return'         => 'ids',
            'payment_method' => $this->id,
            'transaction_id' => (string) $retrieval_ref_no,
            'exclude'        => array( (int) $order_id ),
          ) );

          return ! empty( $orders );
        }

        /**
         * Reject a callback that cannot be proven to pay for this order
         *
         * The order status is never touched: an unauthenticated request must not be
         * able to change the state of an order it has not paid for.
         *
         * @param WC_Order $order
         * @param string $reason Internal reason, written to the WooCommerce log
         *
         * @return void
         */
        private function reject_payment( $order, string $reason ): void {
          if ( function_exists( 'wc_get_logger' ) ) {
            wc_get_logger()->warning(
              sprintf( 'Melli callback rejected for order #%1$s (%2$s).', $order->get_id(), $reason ),
              array( 'source' => 'wpp-' . $this->id )
            );
          }

          $this->set_failed_payment( $order );
        }

        public function get_amount( $order ) {
          $currency    = $order->get_currency();
          $order_total = $order->get_total();
          $amount      = (int) $order_total;
          $currency    = strtolower( $currency );

          if ( in_array( $currency, array(
            'irt',
            'toman',
            'iran toman',
            'iranian toman',
            'iran-toman',
            'iran_toman',
            'تومان',
            'تومان ایران'
          ) ) ) {
            $amount *= 10;
          } elseif ( 'irht' === $currency ) {
            $amount *= 1000 * 10;
          } elseif ( 'irhr' === $currency ) {
            $amount *= 1000;
          }

          return $amount;
        }

        public function set_failed_payment( $order ) {
          wc_add_notice( $this->failed_massage, 'error' );
          // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy public hook name, kept for backward compatibility.
          do_action( 'wpp_wc_' . $this->id . '_gateway_failed_payment', $order );
          wp_safe_redirect( esc_url_raw( wc_get_checkout_url() ) );
          exit;
        }
      }
    }
  }

  add_action( 'before_woocommerce_init', 'wpp_melli_payment_gateway_init', 15 );
}
