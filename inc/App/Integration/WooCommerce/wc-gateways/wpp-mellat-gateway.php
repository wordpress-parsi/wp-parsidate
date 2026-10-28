<?php

use WPParsidate\Helper\Assets;

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

if ( ! function_exists( 'wpp_mellat_payment_gateway_init' ) ) {

  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Legacy public function, kept for backward compatibility.
  function wpp_mellat_payment_gateway_init() {
    if ( ! class_exists( 'WPP_WC_Mellat_Gateway' ) && ! class_exists( 'WPP_WC_Mellat_Gateway' ) ) {
      /**
       * WPP_WC_Mellat_Gateway class to add Mellat Bank payment gateway to WooCommerce
       *
       * @package                 WP-Parsidate
       * @subpackage              Plugins/WooCommerce/PaymentGateways
       * @since 5.0.0
       */
      // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Legacy class name, kept for backward compatibility.
      class WPP_WC_Mellat_Gateway extends WC_Payment_Gateway {

        // Order meta holding the transaction that was started for the order
        private const META_SALE_ORDER_ID = '_wpp_mellat_sale_order_id';

        // Order meta holding the amount (Rial) handed to the bank for the order
        private const META_AMOUNT = '_wpp_mellat_amount';

        // Order meta holding the SaleReferenceId that completed the order
        private const META_SALE_REFERENCE_ID = '_wpp_mellat_sale_reference_id';

        private $gateway_name;
        public $method_title;
        public $terminal_id;
        public $username;
        public $password;
        public $title;
        public $description;
        public $failed_massage;

        public function __construct() {
          $this->id                 = 'mellat';
          $this->gateway_name       = esc_html__( 'Mellat Bank', 'wp-parsidate' );
          $this->method_title       = $this->gateway_name;
          $this->method_description = $this->gateway_name . ' ' . esc_html__( 'payment gateway (By WP-Parsidate)',
              'wp-parsidate' );
          $this->has_fields         = true;
          // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Legacy public hook name, kept for backward compatibility.
          $this->icon               = apply_filters( $this->id . '_logo',
            Assets::url( "images/woocommerce/$this->id-logo.png" ) );
          $this->init_form_fields();
          $this->init_settings();

          $this->terminal_id    = $this->get_option( 'terminal' );
          $this->username       = $this->get_option( 'username' );
          $this->password       = $this->get_option( 'password' );
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
                'title'   => esc_html__( 'Enabled/Disabled', 'wp-parsidate' ),
                'type'    => 'checkbox',
                /* translators: %s: Bank name */
                'label'   => sprintf( esc_html__( 'Activate or deactivate %s gateway',
                  'wp-parsidate' ), $this->gateway_name ),
                'default' => 'no'
              ),
              'terminal'       => array(
                'title'    => esc_html__( 'Terminal No.', 'wp-parsidate' ),
                'type'     => 'text',
                'default'  => '',
                'desc_tip' => false
              ),
              'username'       => array(
                'title'    => esc_html__( 'Gateway user name', 'wp-parsidate' ),
                'type'     => 'text',
                'default'  => '',
                'desc_tip' => true
              ),
              'password'       => array(
                'title'    => esc_html__( 'Gateway password', 'wp-parsidate' ),
                'type'     => 'text',
                'default'  => '',
                'desc_tip' => true
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

        public function process_payment( $order_id ) {
          // Get Order
          $order = wc_get_order( $order_id );

          // Get Gateway RefId
          $refId = $this->get_ref_id_from_mellat( $order );

          // Action
          // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy public hook name, kept for backward compatibility.
          do_action( 'wpp_wc_' . $this->id . '_gateway_process_payment', $order, $refId );

          if ( $refId['status'] === false ) {

            // Add Notice
            wc_add_notice( $refId['message'], 'error' );

            // Return for Block Support
            return [
              'result'   => 'failure',
              'messages' => $refId['message'],
              'reload'   => false
            ];
          }

          // Remember which transaction was started for this order, so the callback
          // can be tied back to it (see verify_payment()). Without this binding the
          // callback has no way to tell which order the bank confirmation belongs to.
          $order->update_meta_data( self::META_SALE_ORDER_ID, (string) ( $refId['saleOrderId'] ?? $order->get_id() ) );
          $order->update_meta_data( self::META_AMOUNT, (int) ( $refId['amount'] ?? $this->get_amount( $order ) ) );
          $order->save();

          // Save Session
          WC()->session->set( 'mellat_ref_id', $refId['refId'] );
          WC()->session->set( 'mellat_order_id', $order_id );

          // Return
          return [
            'result'   => 'success',
            'redirect' => add_query_arg(
              array(
                'wc-api'   => $this->get_class(),
                'action'   => 'redirect',
                'order_id' => $order_id
              ),
              get_site_url( null, '/' )
            )
          ];
        }

        public function get_ref_id_from_mellat( $order ) {
          $description = 'خرید به شماره سفارش: ' . $order->get_order_number();
          $description .= ' | خریدار: ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

          // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy public hook name, kept for backward compatibility.
          $parameters = apply_filters( 'wpp_wc_' . $this->id . '_gateway_request_payment', array(
            'terminalId'     => $this->terminal_id,
            'userName'       => $this->username,
            'userPassword'   => $this->password,
            'orderId'        => $order->get_id(),
            'amount'         => $this->get_amount( $order ),
            'localDate'      => gmdate( 'Ymd' ),
            'localTime'      => gmdate( 'His' ),
            'additionalData' => $description,
            'callBackUrl'    => add_query_arg(
              array(
                'wc-api'   => $this->get_class(),
                'order_id' => $order->get_id()
              ),
              get_site_url( null, '/' )
            ),
            'payerId'        => $order->get_customer_id()
          ) );
          // Keep the values actually sent to the bank: the callback is validated against them.
          $saleOrderId = (string) ( $parameters['orderId'] ?? $order->get_id() );
          $amount      = (int) ( $parameters['amount'] ?? $this->get_amount( $order ) );

          $response = $this->soap_call( 'bpPayRequest', $parameters );

          if ( $response['fault'] ) {

            return [
              'status'  => false,
              'message' => $this->failed_massage
            ];
          }

          if ( ! $response['status'] ) {

            return [
              'status'  => false,
              'message' => 'خطا در ارتباط با بانک ملت: ' . $response['message']
            ];
          }

          $res     = explode( ',', $response['result'] );
          $ResCode = $res[0];
          if ( $ResCode == "0" && ! empty( $res[1] ) ) {

            return [
              'status'      => true,
              'refId'       => $res[1],
              'saleOrderId' => $saleOrderId,
              'amount'      => $amount
            ];
          }

          return [
            'status'  => false,
            'message' => 'خطا در دریافت RefId از بانک ملت: ' . $this->get_error_message( $ResCode )
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
          // The RefId lives in the current session, so it may only be used for the
          // order that session started it for.
          if ( ! WC()->session || (int) WC()->session->get( 'mellat_order_id' ) !== $order->get_id() ) {
            $this->set_failed_payment( $order );
          }

          $refId = WC()->session->get( 'mellat_ref_id' );

          if ( empty( $refId ) ) {
            $this->set_failed_payment( $order );
          }

          ?>
          <html lang="fa-IR">
          <head>
            <meta charset="UTF-8"/>
          </head>
          <body onload="document.forms['mellat_redirect'].submit()">
          <form name="mellat_redirect" method="post"
                action="https://bpm.shaparak.ir/pgwchannel/startpay.mellat">
            <input type="hidden" name="RefId" value="<?php echo esc_attr( $refId ); ?>">
          </form>
          <script type="text/javascript">
            setTimeout(function () {
              document.forms['mellat_redirect'].submit();
            }, 300);
          </script>
          </body>
          </html>
          <?php
          exit;
        }

        public function verify_payment( $order ) {
          // phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing -- Bank callback request, it cannot carry a WordPress nonce. The payment is verified with the bank API.
          $resCode         = sanitize_text_field( wp_unslash( $_POST['ResCode'] ?? '' ) );
          $saleOrderId     = sanitize_text_field( wp_unslash( $_POST['SaleOrderId'] ?? '' ) );
          $saleReferenceId = sanitize_text_field( wp_unslash( $_POST['SaleReferenceId'] ?? '' ) );
          $CardHolderInfo  = sanitize_text_field( wp_unslash( $_POST['CardHolderInfo'] ?? '' ) );
          $CardHolderPan   = sanitize_text_field( wp_unslash( $_POST['CardHolderPan'] ?? '' ) );
          $FinalAmount     = sanitize_text_field( wp_unslash( $_POST['FinalAmount'] ?? '' ) );
          // phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing

          $params = [
            'ResCode'         => $resCode,
            'SaleOrderId'     => $saleOrderId,
            'SaleReferenceId' => $saleReferenceId,
            'CardHolderInfo'  => $CardHolderInfo,
            'CardHolderPan'   => $CardHolderPan,
            'FinalAmount'     => $FinalAmount,
          ];

          if ( $resCode != '0' ) {

            $error_message = $this->get_error_message( $resCode );
            wc_add_notice( $error_message, 'error' );
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy public hook name, kept for backward compatibility.
            do_action( 'wpp_wc_' . $this->id . '_gateway_failed_payment', $order );
            wp_safe_redirect( esc_url_raw( wc_get_checkout_url() ) );
            exit;
          }

          /**
           * Everything below decides whether the payment the bank confirms actually
           * belongs to this order. The callback is a public URL and every value in the
           * POST body is attacker controlled, so the order must never be completed on
           * the strength of that body alone.
           */

          // A paid order must never be completed twice (replay of a real receipt).
          if ( $order->is_paid() || $order->get_transaction_id() ) {

            if ( $saleReferenceId !== '' && (string) $order->get_transaction_id() === $saleReferenceId ) {
              wp_safe_redirect( esc_url_raw( $this->get_return_url( $order ) ) );
              exit;
            }

            $this->reject_payment( $order, 'order is already paid' );
          }

          // The SaleOrderId verified by the bank has to be the one that was sent to the
          // bank for THIS order. Otherwise a receipt of another (cheaper) order could be
          // replayed against this one.
          $expectedSaleOrderId = (string) $order->get_meta( self::META_SALE_ORDER_ID );

          // Orders started before this meta existed always used the order ID.
          if ( $expectedSaleOrderId === '' ) {
            $expectedSaleOrderId = (string) $order->get_id();
          }

          if ( $saleOrderId === '' || $expectedSaleOrderId !== $saleOrderId ) {
            $this->reject_payment( $order, 'SaleOrderId does not belong to this order' );
          }

          if ( $saleReferenceId === '' || ! ctype_digit( $saleReferenceId ) ) {
            $this->reject_payment( $order, 'SaleReferenceId is missing or malformed' );
          }

          // The amount confirmed by the bank has to match the amount requested for this order.
          $expectedAmount = (int) $order->get_meta( self::META_AMOUNT );

          if ( $expectedAmount <= 0 ) {
            $expectedAmount = (int) $this->get_amount( $order );
          }

          // FinalAmount is not sent by every terminal, so it is only enforced when present.
          if ( $FinalAmount !== '' && (int) $FinalAmount > 0 && (int) $FinalAmount !== $expectedAmount ) {
            $this->reject_payment( $order, 'verified amount does not match the order total' );
          }

          // A single receipt may complete a single order, ever.
          if ( $this->sale_reference_id_in_use( $saleReferenceId, $order->get_id() ) ) {
            $this->reject_payment( $order, 'SaleReferenceId was already used by another order' );
          }

          // Verify Payment
          $verify_result = $this->verify( $params );
          if ( $verify_result['status'] !== true ) {

            $this->set_failed_payment( $order );
          }

          // Settle Request
          $settle_result = $this->verify( $params, 'bpSettleRequest' );
          if ( $settle_result['status'] !== true ) {

            $this->set_failed_payment( $order );
          }

          // Set Payment Completed
          $order->update_meta_data( self::META_SALE_REFERENCE_ID, $saleReferenceId );
          $order->save();
          $order->payment_complete( $saleReferenceId );

          // Add Order Note
          $order->add_order_note( sprintf( 'پرداخت با موفقیت انجام شد. کد پیگیری: %s',
            $saleReferenceId ) );

          // Remove WC Session
          if ( WC()->session ) {
            WC()->session->__unset( 'mellat_ref_id' );
            WC()->session->__unset( 'mellat_order_id' );
          }

          // Remove cart.
          if ( WC()->cart ) {
            WC()->cart->empty_cart();
          }

          // Action
          // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy public hook name, kept for backward compatibility.
          do_action( 'wpp_wc_' . $this->id . '_gateway_completed_payment', $order, $params );

          // Redirect
          wp_safe_redirect( esc_url_raw( $this->get_return_url( $order ) ) );
          exit;
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
            $amount = $amount * 10;
          } elseif ( 'irht' === $currency ) {
            $amount = $amount * 1000 * 10;
          } elseif ( 'irhr' === $currency ) {
            $amount = $amount * 1000;
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

        /**
         * Reject a callback that cannot be proven to pay for this order
         *
         * The order status is never touched: an unauthenticated request must not be able
         * to change the state of an order it has not paid for.
         *
         * @param WC_Order $order
         * @param string $reason Internal reason, written to the WooCommerce log
         *
         * @return void
         */
        private function reject_payment( $order, string $reason ): void {
          if ( function_exists( 'wc_get_logger' ) ) {
            wc_get_logger()->warning(
              sprintf( 'Mellat callback rejected for order #%1$s (%2$s).', $order->get_id(), $reason ),
              array( 'source' => 'wpp-' . $this->id )
            );
          }

          $this->set_failed_payment( $order );
        }

        /**
         * Check whether a SaleReferenceId already completed another order
         *
         * @param string $saleReferenceId
         * @param int $orderId Order currently being processed
         *
         * @return bool
         */
        private function sale_reference_id_in_use( $saleReferenceId, $orderId ): bool {
          if ( ! function_exists( 'wc_get_orders' ) ) {
            return false;
          }

          $orders = wc_get_orders( array(
            'limit'          => 1,
            'return'         => 'ids',
            'payment_method' => $this->id,
            'transaction_id' => $saleReferenceId,
            'exclude'        => array( (int) $orderId ),
          ) );

          return ! empty( $orders );
        }

        public function verify( $params, $method = 'bpVerifyRequest' ): array {
          $orderId               = $params["SaleOrderId"];
          $verifySaleReferenceId = $params['SaleReferenceId'];

          $parameters = array(
            'terminalId'      => $this->terminal_id,
            'userName'        => $this->username,
            'userPassword'    => $this->password,
            'orderId'         => $orderId,
            'saleOrderId'     => $orderId,
            'saleReferenceId' => $verifySaleReferenceId
          );
          $response   = $this->soap_call( $method, $parameters );

          if ( ! $response['status'] ) {

            return [
              'status'  => false,
              'message' => $response['message'] !== '' ? $response['message'] : $this->failed_massage,
              'code'    => ''
            ];
          }

          $result = $response['result'];

          // Check Success
          if ( $result == '0' ) {

            return [
              'status' => true
            ];
          }

          // Check Error Code
          if ( is_numeric( $result ) ) {

            return [
              'status'  => false,
              'message' => 'خطا در اعتبار سنجی پرداخت ملت رخ داده است. کد خطا: ' . $this->get_error_message( $result ),
              'code'    => ''
            ];
          }

          return [
            'status'  => false,
            'message' => $this->failed_massage,
            'code'    => ''
          ];
        }

        /**
         * Call a method of the Mellat (Behpardakht) web service
         *
         * Uses the PHP SOAP extension when it is available, otherwise posts the SOAP
         * envelope with the WordPress HTTP API.
         *
         * @param string $method Web service method, e.g. bpPayRequest
         * @param array $parameters Method parameters, in the order of the WSDL
         *
         * @return array{status: bool, fault: bool, result: string, message: string}
         */
        private function soap_call( string $method, array $parameters ): array {
          $endpoint = 'https://bpm.shaparak.ir/pgwchannel/services/pgw';

          if ( class_exists( 'SoapClient' ) ) {
            try {
              $client = new SoapClient( $endpoint . '?wsdl', array(
                'encoding'           => 'UTF-8',
                'connection_timeout' => 30,
                'exceptions'         => true,
              ) );
              $result = $client->__soapCall( $method, array( $parameters ) );

              return array(
                'status'  => true,
                'fault'   => false,
                'result'  => is_object( $result ) && isset( $result->return ) ? (string) $result->return : '',
                'message' => '',
              );
            } catch ( SoapFault $e ) {
              return array( 'status' => false, 'fault' => true, 'result' => '', 'message' => $e->getMessage() );
            } catch ( Exception $e ) {
              return array( 'status' => false, 'fault' => false, 'result' => '', 'message' => $e->getMessage() );
            }
          }

          $fields = '';
          foreach ( $parameters as $name => $value ) {
            $name   = preg_replace( '/[^A-Za-z0-9_]/', '', (string) $name );
            $fields .= '<' . $name . '>' . htmlspecialchars( (string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8' ) . '</' . $name . '>';
          }

          $method = preg_replace( '/[^A-Za-z0-9_]/', '', $method );
          $body   = '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:int="http://interfaces.core.sw.bps.com/">'
                    . '<soapenv:Header/><soapenv:Body>'
                    . '<int:' . $method . '>' . $fields . '</int:' . $method . '>'
                    . '</soapenv:Body></soapenv:Envelope>';

          $response = wp_remote_post( $endpoint, array(
            'timeout' => 30,
            'headers' => array(
              'Content-Type' => 'text/xml; charset=utf-8',
              'SOAPAction'   => '""',
            ),
            'body'    => $body,
          ) );

          if ( is_wp_error( $response ) ) {
            return array( 'status' => false, 'fault' => false, 'result' => '', 'message' => $response->get_error_message() );
          }

          $xml = wp_remote_retrieve_body( $response );

          if ( preg_match( '#<(?:[\w-]+:)?faultstring[^>]*>(.*?)</(?:[\w-]+:)?faultstring>#s', $xml, $matches ) ) {
            return array( 'status' => false, 'fault' => true, 'result' => '', 'message' => html_entity_decode( $matches[1], ENT_QUOTES | ENT_XML1, 'UTF-8' ) );
          }

          if ( ! preg_match( '#<(?:[\w-]+:)?return[^>]*>(.*?)</(?:[\w-]+:)?return>#s', $xml, $matches ) ) {
            return array( 'status' => false, 'fault' => false, 'result' => '', 'message' => 'Invalid response from the bank' );
          }

          return array(
            'status'  => true,
            'fault'   => false,
            'result'  => trim( html_entity_decode( $matches[1], ENT_QUOTES | ENT_XML1, 'UTF-8' ) ),
            'message' => '',
          );
        }

        public function get_error_message( $resCode ) {
          $messages = array(
            '11' => 'شماره کارت نامعتبر است',
            '12' => 'موجودی کافی نیست',
            '13' => 'رمز نادرست است',
            '14' => 'تعداد دفعات وارد کردن رمز بیش از حد مجاز است',
            '15' => 'کارت نامعتبر است',
            '16' => 'دفعات برداشت وجه بیش از حد مجاز است',
            '17' => 'کاربر از انجام تراکنش منصرف شده است',
            '18' => 'تاریخ انقضای کارت گذشته است',
            '19' => 'مبلغ برداشت وجه بیش از حد مجاز است',
            '21' => 'پذیرنده نامعتبر است',
            '23' => 'خطای امنیتی رخ داده است',
            '24' => 'اطلاعات کاربری پذیرنده نامعتبر است',
            '25' => 'مبلغ نامعتبر است',
            '31' => 'پاسخ نامعتبر است',
            '32' => 'فرمت اطلاعات وارد شده صحیح نیست',
            '33' => 'حساب نامعتبر است',
            '34' => 'خطای سیستمی',
            '35' => 'تاریخ نامعتبر است',
            '41' => 'شماره درخواست تکراری است',
            '42' => 'تراکنش Sale یافت نشد',
            '43' => 'قبلا درخواست Verify داده شده است',
            '44' => 'درخواست Verify یافت نشد',
            '45' => 'تراکنش Settle شده است',
            '46' => 'تراکنش Settle نشده است',
            '47' => 'تراکنش Settle یافت نشد',
            '48' => 'تراکنش Reverse شده است',
            '49' => 'تراکنش Refund یافت نشد',
            '51' => 'تراکنش تکراری است',
            '54' => 'تراکنش مرجع موجود نیست',
            '55' => 'تراکنش نامعتبر است',
            '61' => 'خطا در واریز'
          );

          return $messages[ $resCode ] ?? 'کد خطا: ' . $resCode;
        }

      }
    }
  }

  add_action( 'before_woocommerce_init', 'wpp_mellat_payment_gateway_init', 15 );
}
