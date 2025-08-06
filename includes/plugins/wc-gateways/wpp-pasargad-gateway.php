<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

if ( ! function_exists( 'wpp_pasargad_payment_gateway_init' ) ) {

	function wpp_pasargad_payment_gateway_init() {
		if ( ! class_exists( 'WC_Pasargad_Gateway' ) && ! class_exists( 'WPP_WC_Pasargad_Gateway' ) ) {

			/**
			 * WPP_WC_Pasargad_Gateway class to add Pasargad Bank payment gateway to WooCommerce
			 *
			 * @package                 WP-Parsidate
			 * @subpackage              Plugins/WooCommerce/PaymentGateways
			 * @since 5.0.0
			 */
			class WPP_WC_Pasargad_Gateway extends WC_Payment_Gateway {

				private $gateway_name;
                public $redirect_uri;
                public $pasargad_terminal_id;
                public $pasargad_merchant_id;
                public $description;
                public $success_massage;
                public $failed_massage;
                public $cancelled_massage;

                public function __construct() {
					$this->id                 = 'pasargad';
					$this->gateway_name       = __( 'Pasargad Bank', 'wp-parsidate' );
					$this->method_title       = $this->gateway_name;
					$this->method_description = $this->gateway_name . ' ' . __( 'payment gateway (By WP-Parsidate)', 'wp-parsidate' );
					$this->has_fields         = false;
					$this->icon               = apply_filters( $this->id . '_logo', WP_PARSI_URL . "assets/images/$this->id-logo.png" );
					$this->redirect_uri       = WC()->api_request_url( strtolower( get_class( $this ) ) );

					$this->init_form_fields();
					$this->init_settings();

					$this->pasargad_terminal_id = $this->settings['pasargad_terminal_id'];
					$this->pasargad_merchant_id = $this->settings['pasargad_merchant_id'];
					$this->title                = $this->settings['title'];
					$this->description          = $this->settings['description'];
					$this->success_massage      = $this->settings['success_massage'];
					$this->failed_massage       = $this->settings['failed_massage'];
					$this->cancelled_massage    = $this->settings['cancelled_massage'];

					add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
					add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
					add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'callback' ) );
				}

				public function init_form_fields() {
					$this->form_fields = apply_filters( 'wpp_wc_' . $this->id . '_gateway_config', array(
							'enabled'              => array(
								'title'   => __( 'Enabled/Disabled', 'wp-parsidate' ),
								'type'    => 'checkbox',
								/* translators: %s: Bank name */
								'label'   => sprintf( __( 'Activate or deactivate %s gateway', 'wp-parsidate' ), $this->gateway_name ),
								'default' => 'no'
							),
							'pasargad_terminal_id' => array(
								'title'    => __( 'Terminal No.', 'wp-parsidate' ),
								'type'     => 'text',
								'required' => true,
								'desc_tip' => true,
							),
							'pasargad_merchant_id' => array(
								'title'    => __( 'Store No.', 'wp-parsidate' ),
								'type'     => 'text',
								'required' => true,
								'desc_tip' => true,
							),
							'title'                => array(
								'title'       => __( 'Gateway title', 'wp-parsidate' ),
								'type'        => 'text',
								'description' => __( 'This name is displayed to the customer during the purchase process', 'wp-parsidate' ),
								'default'     => $this->gateway_name
							),
							'description'          => array(
								'title'       => __( 'Gateway description', 'wp-parsidate' ),
								'type'        => 'textarea',
								'description' => __( 'The description that will be displayed during the purchase process for the gateway', 'wp-parsidate' ),
								/* translators: %s: Bank name */
								'default'     => sprintf( __( "Secure payment by all Shatab's cards through %s", 'wp-parsidate' ), $this->gateway_name )
							),
							'success_massage'      => array(
								'title'       => __( 'Successful payment message', 'wp-parsidate' ),
								'type'        => 'textarea',
								'description' => __( 'Enter the text of the message you want to display to the user after successful payment.', 'wp-parsidate' ),
								'default'     => __( 'Thank you for your purchase. Your order has been successfully placed.', 'wp-parsidate' )
							),
							'failed_massage'       => array(
								'title'       => __( 'Payment failed message', 'wp-parsidate' ),
								'type'        => 'textarea',
								'description' => __( 'Enter the text of the message you want to display to the user after an unsuccessful payment.', 'wp-parsidate' ),
								'default'     => __( 'Your payment has failed. Please try again or contact us in case of problems.', 'wp-parsidate' )
							),
							'cancelled_massage'    => array(
								'title'       => __( 'Payment cancellation message', 'wp-parsidate' ),
								'type'        => 'textarea',
								'description' => __( 'Enter the text of the message you want to display after the user cancels the payment. This message will be displayed after returning from the bank.', 'wp-parsidate' ),
								'default'     => __( 'The payment remained incomplete due to your cancellation.', 'wp-parsidate' )
							)
						)
					);
				}

				public static function send_pay_request( $invoice_number, $TerminalCode, $MerchantCode, $Amount, $RedirectAddress, $Mobile = '', $Email = '' ) {
					require_once( WP_PARSI_DIR . 'includes/plugins/wc-gateways/lib/class-rsa.php' );

					$processor = new RSAProcessor( WP_PARSI_DIR . 'includes/plugins/wc-gateways/lib/pasargad-certificate.xml', RSAKeyType::XMLFile );
					$data      = array(
						'InvoiceNumber'   => $invoice_number,
						'InvoiceDate'     => parsidate( 'Y/m/d' ),
						'TerminalCode'    => $TerminalCode,
						'MerchantCode'    => $MerchantCode,
						'Amount'          => $Amount,
						'RedirectAddress' => $RedirectAddress,
						'Timestamp'       => date( 'Y/m/d H:i:s' ),
						'Action'          => 1003,
						'Mobile'          => $Mobile,
						'Email'           => $Email
					);

					$sign_data = json_encode( $data );
					$sign_data = sha1( $sign_data, true );
					$sign_data = $processor->sign( $sign_data );
					$sign      = base64_encode( $sign_data );

					$request_args = array(
						'body'        => json_encode( $data ),
						'headers'     => array(
							'Content-Type' => 'application/json',
							'Sign'         => $sign,
						),
						'timeout'     => 30,
						'redirection' => 5,
						'blocking'    => true,
					);

					$response = wp_remote_post( 'https://pep.shaparak.ir/Api/v1/Payment/GetToken', $request_args );

					if ( is_wp_error( $response ) ) {
						return false;
					}

					return json_decode( wp_remote_retrieve_body( $response ) );
				}

				public static function check_transaction_result( $transaction_ref_id, $invoice_number = '', $invoice_date = '', $TerminalCode = '', $MerchantCode = '' ) {
					$data = array(
						'InvoiceNumber'          => $invoice_number,
						'InvoiceDate'            => $invoice_date,
						'TerminalCode'           => $TerminalCode,
						'MerchantCode'           => $MerchantCode,
						'TransactionReferenceID' => $transaction_ref_id
					);

					$request_args = array(
						'body'        => json_encode( $data ),
						'headers'     => array(
							'Content-Type' => 'application/json',
						),
						'timeout'     => 30,
						'redirection' => 5,
						'blocking'    => true,
					);

					$response = wp_remote_post( 'https://pep.shaparak.ir/Api/v1/Payment/CheckTransactionResult', $request_args );

					if ( is_wp_error( $response ) ) {
						return false;
					}

					return json_decode( wp_remote_retrieve_body( $response ) );
				}

				public static function verify_request( $invoice_number, $invoice_date, $TerminalCode, $MerchantCode, $Amount ) {
					require_once( WP_PARSI_DIR . 'includes/plugins/wc-gateways/lib/class-rsa.php' );

					$processor = new RSAProcessor( WP_PARSI_DIR . 'includes/plugins/wc-gateways/lib/pasargad-certificate.xml', RSAKeyType::XMLFile );
					$data      = array(
						'InvoiceNumber' => $invoice_number,
						'InvoiceDate'   => $invoice_date,
						'TerminalCode'  => $TerminalCode,
						'MerchantCode'  => $MerchantCode,
						'Amount'        => $Amount,
						'Timestamp'     => date( 'Y/m/d H:i:s' )
					);

					$sign_data = json_encode( $data );
					$sign_data = sha1( $sign_data, true );
					$sign_data = $processor->sign( $sign_data );
					$sign      = base64_encode( $sign_data );

					$request_args = array(
						'body'        => json_encode( $data ),
						'headers'     => array(
							'Content-Type' => 'application/json',
							'Sign'         => $sign,
						),
						'timeout'     => 30,
						'redirection' => 5,
						'blocking'    => true,
					);

					$response = wp_remote_post( 'https://pep.shaparak.ir/Api/v1/Payment/VerifyPayment', $request_args );

					if ( is_wp_error( $response ) ) {
						return false;
					}

					return json_decode( wp_remote_retrieve_body( $response ) );
				}

				public static function reversal_request( $invoice_number, $invoice_date, $TerminalCode, $MerchantCode ) {
					require_once( WP_PARSI_DIR . 'includes/plugins/wc-gateways/lib/class-rsa.php' );

					$processor = new RSAProcessor( WP_PARSI_DIR . 'includes/plugins/wc-gateways/lib/pasargad-certificate.xml', RSAKeyType::XMLFile );
					$data      = array(
						'InvoiceNumber' => $invoice_number,
						'InvoiceDate'   => $invoice_date,
						'TerminalCode'  => $TerminalCode,
						'MerchantCode'  => $MerchantCode,
						'Timestamp'     => date( 'Y/m/d H:i:s' )
					);

					$sign_data = json_encode( $data );
					$sign_data = sha1( $sign_data, true );
					$sign_data = $processor->sign( $sign_data );
					$sign      = base64_encode( $sign_data );

					$request_args = array(
						'body'        => json_encode( $data ),
						'headers'     => array(
							'Content-Type' => 'application/json',
							'Sign'         => $sign,
						),
						'timeout'     => 30,
						'redirection' => 5,
						'blocking'    => true,
					);

					$response = wp_remote_post( 'https://pep.shaparak.ir/Api/v1/Payment/RefundPayment', $request_args );

					if ( is_wp_error( $response ) ) {
						return false;
					}

					return json_decode( wp_remote_retrieve_body( $response ) );
				}


				public static function error_response_handler( $code = '' ) {
					$error_responses = array(
						'-32768' => esc_html__( 'An unexpected error has occurred', 'wp-parsidate' ),
						'-1552'  => esc_html__( 'Transaction reversal is not allowed', 'wp-parsidate' ),
						'-1551'  => esc_html__( 'The transaction has already been reversed', 'wp-parsidate' ),
						'-1550'  => esc_html__( 'It is not possible to reverse the transaction in the current state', 'wp-parsidate' ),
						'-1549'  => esc_html__( 'The time allowed to request a transaction reversal has expired', 'wp-parsidate' ),
						'-1548'  => esc_html__( 'The bill payment request service call failed', 'wp-parsidate' ),
						'-1540'  => esc_html__( 'Transaction confirmation is unsuccessful', 'wp-parsidate' ),
						'-1536'  => esc_html__( 'The top-up charge request service call failed', 'wp-parsidate' ),
						'-1533'  => esc_html__( 'The transaction has already been confirmed', 'wp-parsidate' ),
						'1532'   => esc_html__( 'The transaction was confirmed by the recipient', 'wp-parsidate' ),
						'-1531'  => esc_html__( 'The transaction was unsuccessful due to your withdrawal from the bank', 'wp-parsidate' ),
						'-1530'  => esc_html__( 'The merchant is not authorized to confirm this transaction', 'wp-parsidate' ),
						'-1528'  => esc_html__( 'Payment information not found', 'wp-parsidate' ),
						'-1527'  => esc_html__( 'The purchase transaction payment request operation failed', 'wp-parsidate' ),
						'-1507'  => esc_html__( 'The transaction was sent back to the switch', 'wp-parsidate' ),
						'-1505'  => esc_html__( 'The transaction was confirmed by the merchant', 'wp-parsidate' ),
						'-132'   => esc_html__( 'The transaction amount is less than the minimum allowed', 'wp-parsidate' ),
						'-131'   => esc_html__( 'Token is invalid', 'wp-parsidate' ),
						'-130'   => esc_html__( 'Token has expired', 'wp-parsidate' ),
						'-128'   => esc_html__( 'The IP address format is not valid', 'wp-parsidate' ),
						'-127'   => esc_html__( 'The URL is not valid', 'wp-parsidate' ),
						'-126'   => esc_html__( "The merchant's identification code is not valid", 'wp-parsidate' ),
						'-121'   => esc_html__( 'The given string is not entirely numeric', 'wp-parsidate' ),
						'-120'   => esc_html__( 'The length of the input data is not valid', 'wp-parsidate' ),
						'-119'   => esc_html__( 'The organization is invalid', 'wp-parsidate' ),
						'-118'   => esc_html__( 'The value sent is not a number', 'wp-parsidate' ),
						'-117'   => esc_html__( 'The string length is less than the allowed limit', 'wp-parsidate' ),
						'-116'   => esc_html__( 'The length of the string exceeds the allowed limit', 'wp-parsidate' ),
						'-115'   => esc_html__( 'The payment ID is invalid', 'wp-parsidate' ),
						'-114'   => esc_html__( 'The bill ID is invalid', 'wp-parsidate' ),
						'-113'   => esc_html__( 'The input parameter is empty', 'wp-parsidate' ),
						'-112'   => esc_html__( 'The order number is duplicate', 'wp-parsidate' ),
						'-111'   => esc_html__( "The transaction amount is more than the merchant's limit", 'wp-parsidate' ),
						'-108'   => esc_html__( 'The ability to reverse the transaction is disabled for the merchant', 'wp-parsidate' ),
						'-107'   => esc_html__( 'The ability to send transaction confirmation is disabled for the merchant', 'wp-parsidate' ),
						'-106'   => esc_html__( 'Charging is disabled for the merchant', 'wp-parsidate' ),
						'-105'   => esc_html__( 'The top-up feature is disabled for the merchant', 'wp-parsidate' ),
						'-104'   => esc_html__( 'The ability to pay the bill is inactive for the merchant', 'wp-parsidate' ),
						'-103'   => esc_html__( 'The purchase feature is disabled for the merchant', 'wp-parsidate' ),
						'-102'   => esc_html__( 'The transaction was successfully rolled back', 'wp-parsidate' ),
						'-101'   => esc_html__( 'The merchant could not be authenticated', 'wp-parsidate' ),
						'-100'   => esc_html__( 'The merchant is inactive', 'wp-parsidate' ),
						'-1'     => esc_html__( 'Server error', 'wp-parsidate' ),
						'0'      => esc_html__( 'The operation is successful', 'wp-parsidate' ),
						'1'      => esc_html__( 'The card issuer refused to complete the transaction', 'wp-parsidate' ),
						'2'      => esc_html__( 'The confirmation operation of this transaction has already been done successfully', 'wp-parsidate' ),
						'3'      => esc_html__( 'The store merchant is invalid', 'wp-parsidate' ),
						'5'      => esc_html__( 'The transaction was abandoned', 'wp-parsidate' ),
						'6'      => esc_html__( 'An unknown error occurred', 'wp-parsidate' ),
						'8'      => esc_html__( 'By recognizing the identity of the card holder, the transaction is successful', 'wp-parsidate' ),
						'9'      => esc_html__( 'The received request is being followed up', 'wp-parsidate' ),
						'10'     => esc_html__( "The transaction with an amount lower than the requested amount (deficiency in the customer's account) has been accepted", 'wp-parsidate' ),
						'12'     => esc_html__( 'The transaction is invalid', 'wp-parsidate' ),
						'13'     => esc_html__( 'The transaction amount is incorrect', 'wp-parsidate' ),
						'14'     => esc_html__( 'The sent card number is invalid (does not exist)', 'wp-parsidate' ),
						'15'     => esc_html__( 'Card issuer is invalid (does not exist)', 'wp-parsidate' ),
						'17'     => esc_html__( 'The requesting customer has been deleted', 'wp-parsidate' ),
						'20'     => esc_html__( 'In a situation where the switch needs to query the card to accept the transaction, it may make a request from the card (terminal), this message indicates that the answer is invalid.', 'wp-parsidate' ),
						'21'     => esc_html__( 'If the response to the terminal request does not require any specific response or function, we will have this message.', 'wp-parsidate' ),
						'22'     => esc_html__( 'The transaction was suspected of malpractice (card, terminal, card holder), so it was not accepted', 'wp-parsidate' ),
						'30'     => esc_html__( 'The message format has errors', 'wp-parsidate' ),
						'31'     => esc_html__( 'The merchant is not supported by the switch', 'wp-parsidate' ),
						'32'     => esc_html__( "The transaction is incompletely completed (for example, a deposit transaction that is completed from the customer's point of view, but needs to be completed)", 'wp-parsidate' ),
						'33'     => esc_html__( 'The card has expired', 'wp-parsidate' ),
						'38'     => esc_html__( 'The number of incorrect password entries has exceeded the limit.', 'wp-parsidate' ),
						'39'     => esc_html__( 'There is no credit card account', 'wp-parsidate' ),
						'40'     => esc_html__( 'The requested operation is not supported', 'wp-parsidate' ),
						'41'     => esc_html__( 'The card has been declared lost', 'wp-parsidate' ),
						'43'     => esc_html__( 'The card has been declared stolen', 'wp-parsidate' ),
						'45'     => esc_html__( 'The bill can not be paid', 'wp-parsidate' ),
						'51'     => esc_html__( 'The account balance is insufficient', 'wp-parsidate' ),
						'54'     => esc_html__( 'The card has expired', 'wp-parsidate' ),
						'55'     => esc_html__( 'The card password is invalid', 'wp-parsidate' ),
						'56'     => esc_html__( 'The card is invalid', 'wp-parsidate' ),
						'57'     => esc_html__( 'The relevant transaction is not allowed by the card holder', 'wp-parsidate' ),
						'58'     => esc_html__( "It is not allowed to perform the relevant transaction by the operator's terminal", 'wp-parsidate' ),
						'59'     => esc_html__( 'The card is suspected of fraud', 'wp-parsidate' ),
						'61'     => esc_html__( 'The transaction amount is over the limit', 'wp-parsidate' ),
						'62'     => esc_html__( 'The card is limited', 'wp-parsidate' ),
						'63'     => esc_html__( 'The security measures have been violated', 'wp-parsidate' ),
						'65'     => esc_html__( 'The number of transaction requests exceeds the limit', 'wp-parsidate' ),
						'68'     => esc_html__( 'The response required to complete or complete the transaction has arrived too late', 'wp-parsidate' ),
						'69'     => esc_html__( 'The number of times the password has been repeated has exceeded the limit', 'wp-parsidate' ),
						'75'     => esc_html__( 'The number of incorrect password entries has exceeded the limit', 'wp-parsidate' ),
						'78'     => esc_html__( 'The card is not active', 'wp-parsidate' ),
						'79'     => esc_html__( 'The account connected to the card is invalid or has errors', 'wp-parsidate' ),
						'80'     => esc_html__( 'The transaction request has been rejected', 'wp-parsidate' ),
						'81'     => esc_html__( 'The card was not accepted', 'wp-parsidate' ),
						'83'     => esc_html__( 'The switch card service provider has not accepted the transaction', 'wp-parsidate' ),
						'84'     => esc_html__( 'In transactions that require communication with the exporter, if the exporter is not active, this message will be sent in response.', 'wp-parsidate' ),
						'91'     => esc_html__( 'The transaction authorization system is temporarily disabled or the time set for the authorization has expired', 'wp-parsidate' ),
						'92'     => esc_html__( 'The destination of the transaction was not found', 'wp-parsidate' ),
						'93'     => esc_html__( 'It is not possible to complete the transaction', 'wp-parsidate' )
					);

					return $error_responses[ $code ] ?? esc_html__( 'The payment of the transaction was unsuccessful due to cancellation on the bank page', 'wp-parsidate' );
				}


				public static function display_error( $pay_status = '', $tran_id = '', $order_id = '', $is_callback = 1 ) {
					$page_html        = '<div dir="rtl" style="font-family:inherit;font-size:12px;line-height: 25px;color:#000000;margin:-25px 0 -23px 0">';
					$succeed_color    = 'style="color:#008800"';
					$failed_color     = 'style="color: #ff0000"';
					$desc_style       = 'style="text-align:center;font-size:12px;margin:15px 0 20px;line-height:25px"';
					$back_to_checkout = sprintf(
						'<a href="%s" style="text-decoration:none">%s</a><br/>',
						esc_url( wc_get_checkout_url() ),
						__( 'Back to checkout', 'wp-parsidate' )
					);

					if ( 'retry' === $pay_status ) {
						$page_title = esc_html__( 'Temporary error in payment', 'wp-parsidate' );
						$order_note = esc_html__( 'When the customer returned from the bank, the bank server did not respond, the customer was asked to refresh the page', 'wp-parsidate' );
						$page_html  .= '<div style="color:#ff0000;font-weight:bold;font-size:12px;margin:25px">::: ' . esc_html__( 'Temporary error in payment', 'wp-parsidate' ) . ' :::</div>
						<div style="margin-bottom:21px;font-size:12px">' . esc_html__( 'The server of the internet portal has temporarily encountered a problem, to complete the transaction moments later, click on the button below', 'wp-parsidate' ) . '</div>
						<div style="margin:20px 0 25px 0;color:#008800" id="reqreload"><button onclick="reload_page()">' . esc_html__( 'Try again', 'wp-parsidate' ) . '</button></div>
						<script>
							function reload_page(){
								document.getElementById("reqreload").innerHTML = "' . esc_html__( 'Trying again please wait..', 'wp-parsidate' ) . '";
								location.reload();
							}
						</script>
					';
					} else {
						$error_messages = array(
							'reversal_done'             => array(
								'color'       => $failed_color,
								'title'       => esc_html__( 'Service delivery error', 'wp-parsidate' ),
								/* translators: %s: Tracking number */
								'message'     => sprintf( esc_html__( 'Your payment with the tracking number %s has been successfully processed at the bank, but there has been a problem with the service', 'wp-parsidate' ), esc_html( $tran_id ) ),
								'description' => esc_html__( 'The order to return the money to your account has been registered in the bank, if the payment is not returned to your account within the next few hours, contact support (the maximum time to return to the account is 72 hours)', 'wp-parsidate' ),
								'order_note'  => esc_html__( "The customer paid the amount, but when returning from the bank, there was a problem in providing the service, the order to return the money to the customer's account was registered in the bank.", 'wp-parsidate' ),
							),
							'reversal_error'            => array(
								'color'       => $failed_color,
								'title'       => esc_html__( 'Service delivery error', 'wp-parsidate' ),
								/* translators: %s: Tracking number */
								'message'     => sprintf( esc_html__( 'Your payment with the tracking number %s has been successfully made in the bank, but there is a problem in providing the service!', 'wp-parsidate' ), esc_html( $tran_id ) ),
								'description' => esc_html__( 'The order to return the money to your account has been registered in the bank, if the payment is not returned to your account within the next few hours, contact support (the maximum time to return to the account is 72 hours)', 'wp-parsidate' ),
								'order_note'  => esc_html__( "The customer paid the amount, but when he returned from the bank, there was a problem in providing the service, there was an error in registering the order to return the money to the customer's account, this customer should either be provided with the service or the amount should be returned to his bank account.", 'wp-parsidate' ),
							),
							'already_been_completed'    => array(
								'color'       => $succeed_color,
								'title'       => esc_html__( 'The order has already been paid', 'wp-parsidate' ),
								/* translators: %s: Tracking number */
								'message'     => sprintf( esc_html__( 'Your order has already been successfully placed with tracking number %s', 'wp-parsidate' ), esc_html( $tran_id ) ),
								'description' => '',
								'order_note'  => ''
							),
							'order_not_for_this_person' => array(
								'color'       => $failed_color,
								'title'       => esc_html__( 'The order number is incorrect', 'wp-parsidate' ),
								'message'     => esc_html__( 'The order number is incorrect', 'wp-parsidate' ),
								'description' => esc_html__( 'The order number is incorrect; Call for support if needed', 'wp-parsidate' ),
								'order_note'  => ''
							),
							'error_creating_order'      => array(
								'color'       => $failed_color,
								'title'       => esc_html__( 'Problem with placing an order', 'wp-parsidate' ),
								'message'     => esc_html__( 'The order number is incorrect', 'wp-parsidate' ),
								'description' => esc_html__( 'There is a problem with placing an order, please contact support', 'wp-parsidate' ),
								'order_note'  => ''
							)
						);

						if ( array_key_exists( $pay_status, $error_messages ) ) {
							$page_title = $error_messages[ $pay_status ]['title'];
							$order_note = $error_messages[ $pay_status ]['order_note'];
							$page_html  .= sprintf(
								'<span %s><strong>%s</strong></span><br/><p %s>%s<br>%s</p>%s',
								$error_messages[ $pay_status ]['color'],
								$page_title,
								$desc_style,
								$error_messages[ $pay_status ]['message'],
								$error_messages[ $pay_status ]['description'],
								$back_to_checkout
							);
						} else if ( $is_callback == 0 ) {
							$page_title = $order_note = esc_html__( 'Error sending to the bank', 'wp-parsidate' );
							$page_html  .= sprintf(
								'<span %s><strong>%s</strong></span><br/><p %s>%s</p>%s',
								$failed_color,
								$page_title,
								$desc_style,
								self::error_response_handler( $pay_status ),
								$back_to_checkout
							);
						} else {
							$page_title = esc_html__( 'Payment was not made', 'wp-parsidate' );
							$order_note = esc_html__( 'Payment was not made', 'wp-parsidate' ) . ' - ' . self::error_response_handler( $pay_status );
							$page_html  .= sprintf(
								'<span %s><strong>%s</strong></span><br/><p %s>%s<br>%s</p>%s',
								$failed_color,
								$page_title,
								$desc_style,
								self::error_response_handler( $pay_status ),
								esc_html__( 'If the payment has been deducted from your bank account, it will be automatically returned to your account by the bank (the final return time to the account is 72 hours) - contact support if needed.', 'wp-parsidate' ),
								$back_to_checkout,
							);
						}
					}

					$page_html .= '</div>';

					if ( ! empty( $order_id ) && ! empty( $order_note ) && 'order_not_for_this_person' !== $pay_status ) {
						$order = wc_get_order( $order_id );

						$order->add_order_note( $order_note );
					}

					wp_die( $page_html, $page_title );
				}

				public static function redirect_to_bank( $url = '' ) {
					if ( $url != '' ) {
						if ( headers_sent() ) {
							echo '<script type="text/javascript">window.location.assign("' . $url . '")</script>';
						} else {
							header( "Location: $url" );
						}

						exit();
					}
				}

				public function process_payment( $order_id ) {
					$order = wc_get_order( $order_id );

					return array(
						'result'   => 'success',
						'redirect' => $order->get_checkout_payment_url( true )
					);
				}

				public function receipt_page( $order_id ) {
					if ( $order_id > 0 ) {
						$order    = wc_get_order( $order_id );
						$currency = strtolower( $order->get_currency() );
						$amount   = absint( $order->get_total() );

						if ( in_array( $currency, array( 'irt', 'toman', 'iran toman', 'iranian toman', 'iran-toman', 'iran_toman', 'تومان', 'تومان ایران' ) ) ) {
							$amount = $amount * 10;
						} else if ( 'irht' === $currency ) {
							$amount = $amount * 1000 * 10;
						} else if ( 'irhr' === $currency ) {
							$amount = $amount * 1000;
						}

						$terminal_id  = $this->pasargad_terminal_id;
						$merchant_id  = $this->pasargad_merchant_id;
						$callback_url = $this->redirect_uri . "?order_id=" . $order_id;
						$order_id     = $order_id . mt_rand( 10, 100 );
						$request      = self::send_pay_request( $order_id, $terminal_id, $merchant_id, $amount, $callback_url );

						if ( isset( $request ) && $request->IsSuccess ) {
							self::redirect_to_bank( 'https://pep.shaparak.ir/payment.aspx?n=' . $request->Token );

							exit;
						} else {

							self::display_error( '', '', $order_id, 0 );
						}
					} else {
						self::display_error( 'error_creating_order', '', $order_id, 0 );
					}

					return false;
				}

				public function callback() {
					$order_id           = $_GET['order_id'] ?? '';
					$transaction_ref_id = $_REQUEST['tref'] ?? '';
					$invoice_number     = $_REQUEST['iN'] ?? '';
					$invoice_date       = $_REQUEST['iD'] ?? '';
					$terminal_id        = $this->pasargad_terminal_id;
					$merchant_id        = $this->pasargad_merchant_id;

					if ( $order_id == substr( $invoice_number, 0, - 2 ) ) {
						$order = wc_get_order( $order_id );

						if ( $order ) {
							if ( 'wc-completed' === $order->get_status() ) {
								$error_code = 'already_been_completed';
							} else {
								if ( $transaction_ref_id != '' ) {
									$check_result = self::check_transaction_result( $transaction_ref_id );
								} else {
									$check_result = self::check_transaction_result( null, $invoice_number, $invoice_date, $terminal_id, $merchant_id );
								}

								if ( isset( $check_result ) && $check_result->IsSuccess && $check_result->InvoiceNumber == $invoice_number ) {
									$amount  = $check_result->Amount;
									$request = self::verify_request( $invoice_number, $invoice_date, $terminal_id, $merchant_id, $amount );

									if ( isset( $request ) && $request->IsSuccess ) {
										if ( $order->update_status( 'processing' ) ) {
											$order->payment_complete();
											$order->add_order_note( 'پرداخت شما با موفقیت با شماره پیگیری ' . $transaction_ref_id . ' انجام شد.', 1 );

											WC()->cart->empty_cart();

											wp_safe_redirect( $this->get_return_url( $order ) );

											exit();
										} else {
											$reversal_request = self::reversal_request( $invoice_number, $invoice_date, $terminal_id, $merchant_id );

											if ( isset( $reversal_request ) && $reversal_request->IsSuccess ) {
												$error_code = 'reversal_done';
											} else {
												$error_code = 'reversal_error';
											}
										}
									} else {
										$message = $request->Message;
									}
								} else {
									$message = 'پرداخت توسط شما انجام نشده است ، در صورت نیاز با پشتیبانی تماس بگیرید';
								}
							}
						} else {
							$error_code = 'order_not_exist';
						}
					} else {
						$error_code = 'order_not_for_this_person';
					}

					self::display_error( $error_code ?? null, $transaction_ref_id, 1, $message ?? '' );

					exit;
				}
			}
		}
	}

	add_action( 'before_woocommerce_init', 'wpp_pasargad_payment_gateway_init', 15 );
}