<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

if ( ! function_exists( 'wpp_mellat_payment_gateway_init' ) ) {

	function wpp_mellat_payment_gateway_init() {
		if ( ! class_exists( 'WPP_WC_Mellat_Gateway' ) && ! class_exists( 'WPP_WC_Mellat_Gateway' ) ) {
			/**
			 * WPP_WC_Mellat_Gateway class to add Mellat Bank payment gateway to WooCommerce
			 *
			 * @package                 WP-Parsidate
			 * @subpackage              Plugins/WooCommerce/PaymentGateways
			 * @since 5.0.0
			 */
			class WPP_WC_Mellat_Gateway extends WC_Payment_Gateway {

				private $gateway_name;

				public function __construct() {
					$this->id                 = 'mellat';
					$this->gateway_name       = __( 'Mellat Bank', 'wp-parsidate' );
					$this->method_title       = $this->gateway_name;
					$this->method_description = $this->gateway_name . ' ' . __( 'payment gateway (By WP-Parsidate)', 'wp-parsidate' );
					$this->has_fields         = false;
					$this->icon               = apply_filters( $this->id . '_logo', WP_PARSI_URL . "assets/images/$this->id.png" );
					$this->redirect_uri       = WC()->api_request_url( strtolower( get_class( $this ) ) );

					$this->init_form_fields();
					$this->init_settings();

					$this->terminal          = $this->settings['terminal'];
					$this->username          = $this->settings['username'];
					$this->password          = $this->settings['password'];
					$this->title             = $this->settings['title'];
					$this->description       = $this->settings['description'];
					$this->success_massage   = $this->settings['success_massage'];
					$this->failed_massage    = $this->settings['failed_massage'];
					$this->cancelled_massage = $this->settings['cancelled_massage'];

					add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
					add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
					add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'callback' ) );
				}

				public function init_form_fields() {
					$this->form_fields = apply_filters( 'wpp_wc_' . $this->id . '_gateway_config', array(
							'enabled'           => array(
								'title'   => __( 'Enabled/Disabled', 'wp-parsidate' ),
								'type'    => 'checkbox',
								/* translators: %s: Bank name */
								'label'   => sprintf( __( 'Activate or deactivate %s gateway', 'wp-parsidate' ), $this->gateway_name ),
								'default' => 'no'
							),
							'terminal'          => array(
								'title'    => __( 'Terminal No.', 'wp-parsidate' ),
								'type'     => 'text',
								'default'  => '',
								'desc_tip' => false
							),
							'username'          => array(
								'title'    => __( 'Gateway user name', 'wp-parsidate' ),
								'type'     => 'text',
								'default'  => '',
								'desc_tip' => true
							),
							'password'          => array(
								'title'    => __( 'Gateway password', 'wp-parsidate' ),
								'type'     => 'text',
								'default'  => '',
								'desc_tip' => true
							),
							'title'             => array(
								'title'       => __( 'Gateway title', 'wp-parsidate' ),
								'type'        => 'text',
								'description' => __( 'This name is displayed to the customer during the purchase process', 'wp-parsidate' ),
								'default'     => $this->gateway_name
							),
							'description'       => array(
								'title'       => __( 'Gateway description', 'wp-parsidate' ),
								'type'        => 'textarea',
								'description' => __( 'The description that will be displayed during the purchase process for the gateway', 'wp-parsidate' ),
								/* translators: %s: Bank name */
								'default'     => sprintf( __( "Secure payment by all Shetab's cards through %s", 'wp-parsidate' ), $this->gateway_name )
							),
							'success_massage'   => array(
								'title'       => __( 'Successful payment message', 'wp-parsidate' ),
								'type'        => 'textarea',
								'description' => __( 'Enter the text of the message you want to display to the user after successful payment.', 'wp-parsidate' ),
								'default'     => __( 'Thank you for your purchase. Your order has been successfully placed.', 'wp-parsidate' )
							),
							'failed_massage'    => array(
								'title'       => __( 'Payment failed message', 'wp-parsidate' ),
								'type'        => 'textarea',
								'description' => __( 'Enter the text of the message you want to display to the user after an unsuccessful payment.', 'wp-parsidate' ),
								'default'     => __( 'Your payment has failed. Please try again or contact us in case of problems.', 'wp-parsidate' )
							),
							'cancelled_massage' => array(
								'title'       => __( 'Payment cancellation message', 'wp-parsidate' ),
								'type'        => 'textarea',
								'description' => __( 'Enter the text of the message you want to display after the user cancels the payment. This message will be displayed after returning from the bank.', 'wp-parsidate' ),
								'default'     => __( 'The payment remained incomplete due to your cancellation.', 'wp-parsidate' )
							)
						)
					);
				}

				public function process_payment( $order_id ) {
					$order = wc_get_order( $order_id );

					return array(
						'result'   => 'success',
						'redirect' => $order->get_checkout_payment_url( true )
					);
				}

				public function receipt_page( $order_id ) {
					WC()->session->order_id_bankmellat = $order_id;
					$order                             = wc_get_order( $order_id );
					$currency                          = $order->get_currency();
					$form                              = '<form action="" method="POST" class="bankmellat-checkout-form" id="bankmellat-checkout-form">
						<input type="submit" name="bankmellat_submit" class="button alt" id="bankmellat-payment-button" value="' . __( 'Pay off', 'wp-parsidate' ) . '"/>
						<a class="button cancel" href="' . wc_get_checkout_url() . '">' . __( 'Back to checkout', 'wp-parsidate' ) . '</a>
					 </form><br/>';

					echo wp_kses( $form, array(
						'form'  => array( 'action' => array() , 'method' => array(), 'class' => array(), 'id' => array() ),
						'input' => array( 'type' => array(), 'name' => array(), 'class' => array(), 'id' => array(), 'value' => array() ),
						'a'     => array( 'class' => array(), 'href' => array() )
					) );

					if ( isset( $_POST["bankmellat_submit"] ) ) {
						$amount = absint( $order->get_total() );

						if ( in_array( $currency, array( 'irt', 'toman', 'iran toman', 'iranian toman', 'iran-toman', 'iran_toman', 'تومان', 'تومان ایران' ) ) ) {
							$amount = $amount * 10;
						} else if ( 'irht' === $currency ) {
							$amount = $amount * 1000 * 10;
						} else if ( 'irhr' === $currency ) {
							$amount = $amount * 1000;
						}

						if ( ! class_exists( 'nusoap_client' ) ) {
							require_once( WP_PARSI_DIR . 'includes/plugins/wc-gateways/lib/nusoap.php' );
						}

						$terminalId      = $this->terminal;
						$user_name       = $this->username;
						$user_password   = $this->password;
						$order_id        = date( 'ymdHis' );
						$additional_data = 'Order_Number : ' . $order->get_order_number();
						$callback_url    = add_query_arg( 'wc_order', $order_id, $this->redirect_uri );

						$client    = new nusoap_client( 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl' );
						$namespace = 'http://interfaces.core.sw.bps.com/';
						$localDate = date( "Ymd" );
						$localTime = date( "His" );
						$payerId   = '0';
						$is_error  = 'no';
						$err       = $client->getError();

						if ( $err ) {
							$is_error   = 'yes';
							$error_code = $err;
						}

						$parameters = array(
							'terminalId'     => $terminalId,
							'userName'       => $user_name,
							'userPassword'   => $user_password,
							'orderId'        => $order_id,
							'amount'         => $amount,
							'localDate'      => $localDate,
							'localTime'      => $localTime,
							'additionalData' => $additional_data,
							'callBackUrl'    => $callback_url,
							'payerId'        => $payerId
						);
						$result     = $client->call( 'bpPayRequest', $parameters, $namespace );

						if ( $client->fault ) {
							$is_error   = 'yes';
							$error_code = sanitize_text_field( $_POST['ResCode'] );
						} else {
							$resultStr = $result;
							$err       = $client->getError();

							if ( $err ) {
								$is_error   = 'yes';
								$error_code = $err;
							} else {
								$res      = explode( ',', $resultStr );
								$res_code = $res[0];

								if ( $res_code == "0" ) {
									wc_add_notice( esc_html__( 'Connecting to the bank...', 'wp-parsidate' ) );

									add_filter('safe_style_css', function ($styles) {
										$styles[] = 'display';
										return $styles;
									});

									$connect_form = '<form id="redirect_to_mellat" method="post" action="https://bpm.shaparak.ir/pgwchannel/startpay.mellat" style="display:none!important">
										<input type="hidden"  name="RefId" value="' . esc_attr( $res[1] ) . '" />
										<input type="submit" value="' . __( 'Pay off', 'wp-parsidate' ) . '"/>
									</form>
									<script>
										document.getElementById("redirect_to_mellat").submit();
									</script>';

									echo wp_kses( $connect_form, array(
										'form'   => array( 'id' => array(), 'method' => array(), 'action' => array(), 'style' => array() ),
										'input'  => array( 'type' => array(), 'name' => array(), 'value' => array() ),
										'script' => array( 'language' => array(), 'type' => array() )
									) );
								} else {
									$is_error   = 'yes';
									$error_code = $res_code;
								}
							}
						}

						if ( 'yes' == $is_error ) {
							$fault      = $error_code;
							$order_note = sprintf( __( 'Error sending to the bank: %s', 'wp-parsidate' ), $this->error_response_handler( $fault ) );

							$order->add_order_note( $order_note );

							wc_add_notice( sprintf( __( 'The following error occurred while connecting to the bank: <br/>%s', 'wp-parsidate' ), $this->error_response_handler( $fault ) ), 'error' );
						}
					}
				}

				public function Return_from_BankMellat_Gateway_By_HANNANStd() {
					if ( isset( $_GET['wc_order'] ) ) {
						$order_id = sanitize_text_field( $_GET['wc_order'] );
					} else {
						$order_id = WC()->session->order_id_bankmellat;
					}

					if ( $order_id ) {
						$order    = wc_get_order( $order_id );
						$currency = $order->get_currency();

						if ( 'completed' !== $order->get_status() ) {
							$amount = absint( $order->get_total() );

							if ( in_array( $currency, array( 'irt', 'toman', 'iran toman', 'iranian toman', 'iran-toman', 'iran_toman', 'تومان', 'تومان ایران' ) ) ) {
								$amount = $amount * 10;
							} else if ( 'irht' === $currency ) {
								$amount = $amount * 1000 * 10;
							} else if ( 'irhr' === $currency ) {
								$amount = $amount * 1000;
							}

							if ( ! class_exists( 'nusoap_client' ) ) {
								require_once( WP_PARSI_DIR . 'includes/plugins/wc-gateways/lib/nusoap.php' );
							}

							$terminalId    = $this->terminal;
							$user_name     = $this->username;
							$user_password = $this->password;
							$order_id      = sanitize_text_field( $_POST['SaleOrderId'] );

							if ( sanitize_text_field( $_POST['SaleOrderId'] ) ) {
								update_post_meta( $order_id, 'WC_BankMellat_settleSaleOrderId', sanitize_text_field( $_POST['SaleOrderId'] ) );
							}

							if ( sanitize_text_field( $_POST['SaleReferenceId'] ) ) {
								update_post_meta( $order_id, 'WC_BankMellat_settleSaleReferenceId', sanitize_text_field( $_POST['SaleReferenceId'] ) );
							}

							$client    = new nusoap_client( 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl' );
							$namespace = 'http://interfaces.core.sw.bps.com/';

							if ( sanitize_text_field( $_POST['ResCode'] != 0 ) ) {
								if ( $_POST['ResCode'] == 17 || $_POST['ResCode'] == '17' ) {
									$status         = 'cancelled';
									$transaction_id = sanitize_text_field( $_POST['SaleReferenceId'] );
									$fault          = 0;
								} else {
									$status         = 'failed';
									$transaction_id = sanitize_text_field( $_POST['SaleReferenceId'] );
									$fault          = $_POST['ResCode'];
								}
							} else {
								$status_bm = "";
								$reverse   = "";
								$rev_to_u  = 0;
								$err       = $client->getError();

								if ( $err ) {
									$status_bm = 0;
									$reverse   = 1;
								} else {
									$order_id              = sanitize_text_field( $_POST['SaleOrderId'] );
									$verifySaleOrderId     = sanitize_text_field( $_POST['SaleOrderId'] );
									$verifySaleReferenceId = sanitize_text_field( $_POST['SaleReferenceId'] );
									$parameters            = array(
										'terminalId'      => $terminalId,
										'userName'        => $user_name,
										'userPassword'    => $user_password,
										'orderId'         => $order_id,
										'saleOrderId'     => $verifySaleOrderId,
										'saleReferenceId' => $verifySaleReferenceId
									);
									$result                = $client->call( 'bpVerifyRequest', $parameters, $namespace );

									if ( $client->fault ) {
										$status_bm = 0;
										$reverse   = 1;
									} else {
										$err = $client->getError();

										if ( $err ) {
											$status_bm = 0;
											$reverse   = 1;
										} else {
											if ( $result == 0 ) {
												$inquirySaleOrderId     = sanitize_text_field( $_POST['SaleOrderId'] );
												$inquirySaleReferenceId = sanitize_text_field( $_POST['SaleReferenceId'] );

												if ( $err ) {
													$status_bm = 0;
													$reverse   = 1;
												} else {
													$parameters = array(
														'terminalId'      => $terminalId,
														'userName'        => $user_name,
														'userPassword'    => $user_password,
														'orderId'         => $order_id,
														'saleOrderId'     => $inquirySaleOrderId,
														'saleReferenceId' => $inquirySaleReferenceId
													);
													$result     = $client->call( 'bpInquiryRequest', $parameters, $namespace );

													if ( $result == 0 ) {
														if ( $client->fault ) {
															$status_bm = 0;
															$reverse   = 1;
														} else {
															$err = $client->getError();

															if ( $err ) {
																$status_bm = 0;
																$reverse   = 1;
															} else {
																$status_bm = 1;
															}
														}
													} else {
														$status_bm = 0;
														$reverse   = 0;
													}
												}
											} else {
												$status_bm = 0;
												$reverse   = 0;
											}
										}
									}
								}

								if ( 1 == $status_bm ) {
									$settleSaleOrderId     = sanitize_text_field( $_POST['SaleOrderId'] );
									$settleSaleReferenceId = sanitize_text_field( $_POST['SaleReferenceId'] );
									$err                   = $client->getError();

									if ( $err ) {
										$status_bm = 0;
									} else {
										$parameters = array(
											'terminalId'      => $terminalId,
											'userName'        => $user_name,
											'userPassword'    => $user_password,
											'orderId'         => $order_id,
											'saleOrderId'     => $settleSaleOrderId,
											'saleReferenceId' => $settleSaleReferenceId
										);
										$result     = $client->call( 'bpSettleRequest', $parameters, $namespace );

										if ( $result == 0 ) {
											if ( $client->fault ) {
												$status_bm = 0;
											} else {
												$err = $client->getError();

												if ( $err ) {
													$reverse   = 1;
													$status_bm = 0;
												} else {
													$status         = 'completed';
													$transaction_id = sanitize_text_field( $_POST['SaleReferenceId'] );
													$fault          = 0;
													$verify_id      = $verifySaleReferenceId;
												}
											}
										} else {
											$status_bm = 0;
											$reverse   = 1;
										}
									}
								}

								if ( $reverse == 1 ) {
									$order_id                = sanitize_text_field( $_POST['SaleOrderId'] );
									$reversalSaleOrderId     = sanitize_text_field( $_POST['SaleOrderId'] );
									$reversalSaleReferenceId = sanitize_text_field( $_POST['SaleReferenceId'] );
									$err                     = $client->getError();

									if ( $err ) {
										$status_bm = 0;
									} else {
										$parameters = array(
											'terminalId'      => $terminalId,
											'userName'        => $user_name,
											'userPassword'    => $user_password,
											'orderId'         => $order_id,
											'saleOrderId'     => $reversalSaleOrderId,
											'saleReferenceId' => $reversalSaleReferenceId
										);
										$result     = $client->call( 'bpReversalRequest', $parameters, $namespace );

										if ( $client->fault ) {
											$status_bm = 0;
										} else {
											$err = $client->getError();
											if ( $err ) {
												$status_bm = 0;
											} else {
												$status         = 'failed';
												$transaction_id = sanitize_text_field( $_POST['SaleReferenceId'] );
												$fault          = $result;

												if ( $result == 0 ) {
													$rev_to_u = 2;
												} else {
													$rev_to_u = 1;
												}
											}
										}
									}
								}
							}

							if ( $status != 'cancelled' && $status != 'completed' ) {
								$status = 'failed';
							}

							if ( $status == 'failed' ) {
								$transaction_id = sanitize_text_field( $_POST['SaleReferenceId'] );
								$fault          = sanitize_text_field( $_POST['ResCode'] );

								if ( $_POST['ResCode'] == 17 || $_POST['ResCode'] == '17' ) {
									$status         = 'cancelled';
									$transaction_id = sanitize_text_field( $_POST['SaleReferenceId'] );
									$fault          = 0;
								}
							}

							$SaleOrderId = $order_id ?? 0;

							if ( $status == 'completed' ) {
								if ( ! empty( $transaction_id ) ) {
									update_post_meta( $order_id, '_transaction_id', $transaction_id );
								}

								$order->payment_complete( $transaction_id );
								WC()->cart->empty_cart();

								/* translators: %1$s: Transaction reference code. %2$s: Transaction request number. */
								$order_note = sprintf( __( 'The payment was successful.<br/>Tracking code (transaction reference code): %1$s<br/>Transaction request number: %2$s', 'wp-parsidate' ), $transaction_id, $SaleOrderId );

								$order->add_order_note( $order_note, 1 );

								$notice = wpautop( wptexturize( $this->success_massage ) );
								$notice = str_replace( array( "{transaction_id}", "{SaleOrderId}" ), array( $transaction_id, $SaleOrderId ), $notice );

								wc_add_notice( $notice );

								wp_safe_redirect( add_query_arg( 'wc_status', 'success', $this->get_return_url( $order ) ) );
							} elseif ( $status == 'cancelled' ) {
								$tr_id         = ! empty( $transaction_id ) ? ( '<br/>' . __( 'Tracking code (transaction reference code): ', 'wp-parsidate' ) . $transaction_id ) : '';
								$sale_order_id = ! empty( $SaleOrderId ) ? ( '<br/>' . __( 'Transaction request number: ', 'wp-parsidate' ) . $SaleOrderId ) : '';
								/* translators: %1$s: Transaction reference code. %2$s: Transaction request number. */
								$order_note = sprintf( __( 'The user canceled the payment during the transaction. %1$s %1$s', 'wp-parsidate' ), $tr_id, $sale_order_id );

								$order->add_order_note( $order_note, 1 );

								$notice = wpautop( wptexturize( $this->cancelled_massage ) );
								$notice = str_replace( array( "{transaction_id}", "{SaleOrderId}" ), array( $transaction_id, $SaleOrderId ), $notice );

								if ( $notice ) {
									wc_add_notice( $notice, 'error' );
								}

								wp_redirect( wc_get_checkout_url() );
							} else {
								$tr_id         = ! empty( $transaction_id ) ? ( '<br/>' . __( 'Tracking code (transaction reference code): ', 'wp-parsidate' ) . $transaction_id ) : '';
								$sale_order_id = ! empty( $SaleOrderId ) ? ( '<br/>' . __( 'Transaction request number: ', 'wp-parsidate' ) . $SaleOrderId ) : '';
								/* translators: %1$s: Error message. %2$s: Transaction reference code. %3$s: Transaction request number. */
								$order_note = sprintf( __( 'Error while returning from bank: %1$s %2$s %3$s', 'wp-parsidate' ), $this->error_response_handler( $fault ), $tr_id, $sale_order_id );

								$order->add_order_note( $order_note, 1 );

								$notice = wpautop( wptexturize( $this->failed_massage ) );
								$notice = str_replace( array( "{transaction_id}", "{SaleOrderId}", "{fault}" ), array( $transaction_id, $SaleOrderId, $this->error_response_handler( $fault ) ), $notice );

								if ( $notice ) {
									wc_add_notice( $notice, 'error' );
								}

								if ( $rev_to_u == 2 ) {
									$order->add_order_note( __( 'The amount paid by the user was returned through the bank.', 'wp-parsidate' ), 1 );
								}

								if ( $rev_to_u == 1 ) {
									$order->add_order_note( __( "The paid amount must be returned to the user's account because a system error occurred while returning the amount.", 'wp-parsidate' ), 1 );
								}

								wp_redirect( wc_get_checkout_url() );
							}

						} else {
							$transaction_id = get_post_meta( $order_id, '_transaction_id', true );
							$SaleOrderId    = get_post_meta( $order_id, 'WC_BankMellat_settleSaleOrderId', true );
							$notice         = wpautop( wptexturize( $this->success_massage ) );
							$notice         = str_replace( array( "{transaction_id}", "{SaleOrderId}" ), array( $transaction_id, $SaleOrderId ), $notice );

							if ( $notice ) {
								wc_add_notice( $notice );
							}

							wp_redirect( add_query_arg( 'wc_status', 'success', $this->get_return_url( $order ) ) );
						}
					} else {
						$notice = wpautop( wptexturize( $this->failed_massage ) );
						$notice = str_replace( "{fault}", __( 'The order number is incorrect', 'wp-parsidate' ), $notice );

						wc_add_notice( $notice, 'error' );

						wp_redirect( wc_get_checkout_url() );
					}
					exit;
				}

				private static function error_response_handler( $err_code ) {
					$error_responses = array(
						'settle' => __( 'Manual Settle operation completed successfully.', 'wp-parsidate' ),
						'-2'     => __( 'Failed to connect to the bank.', 'wp-parsidate' ),
						'-1'     => __( 'Failed to connect to the bank.', 'wp-parsidate' ),
						'11'     => __( 'The card number is not valid.', 'wp-parsidate' ),
						'12'     => __( 'Insufficient inventory.', 'wp-parsidate' ),
						'13'     => __( 'Your second password is incorrect.', 'wp-parsidate' ),
						'14'     => __( 'The number of incorrect password entries has exceeded the limit.', 'wp-parsidate' ),
						'15'     => __( 'The card is not valid.', 'wp-parsidate' ),
						'16'     => __( 'Withdrawal times are more than allowed.', 'wp-parsidate' ),
						'17'     => __( 'You have abandoned the transaction.', 'wp-parsidate' ),
						'18'     => __( 'The expiration date of the card has passed.', 'wp-parsidate' ),
						'19'     => __( 'Withdrawal amount is more than allowed.', 'wp-parsidate' ),
						'111'    => __( 'Card issuer is invalid.', 'wp-parsidate' ),
						'112'    => __( 'An error has occurred with the card issuing switch.', 'wp-parsidate' ),
						'113'    => __( 'An answer was not received from the card issuer.', 'wp-parsidate' ),
						'114'    => __( 'The cardholder is not authorized to perform this transaction.', 'wp-parsidate' ),
						'21'     => __( 'The recipient is not valid.', 'wp-parsidate' ),
						'23'     => __( 'A security error has occurred.', 'wp-parsidate' ),
						'24'     => __( 'Acceptor user information is not valid.', 'wp-parsidate' ),
						'25'     => __( 'The amount is invalid.', 'wp-parsidate' ),
						'31'     => __( 'Answer is invalid.', 'wp-parsidate' ),
						'32'     => __( 'The format of the entered information is not correct.', 'wp-parsidate' ),
						'33'     => __( 'The account is invalid.', 'wp-parsidate' ),
						'34'     => __( 'A system error has occurred.', 'wp-parsidate' ),
						'35'     => __( 'The date is invalid.', 'wp-parsidate' ),
						'41'     => __( 'Request number is duplicate.', 'wp-parsidate' ),
						'42'     => __( 'There is no such transaction.', 'wp-parsidate' ),
						'43'     => __( 'Verify has already been requested', 'wp-parsidate' ),
						'44'     => __( 'Verify request not found.', 'wp-parsidate' ),
						'45'     => __( 'The transaction has already been settled.', 'wp-parsidate' ),
						'46'     => __( 'The transaction has not been settled.', 'wp-parsidate' ),
						'47'     => __( 'Settle transaction not found.', 'wp-parsidate' ),
						'48'     => __( 'The transaction has already been reversed.', 'wp-parsidate' ),
						'49'     => __( 'Refund transaction not found.', 'wp-parsidate' ),
						'412'    => __( 'The bill ID is invalid.', 'wp-parsidate' ),
						'413'    => __( 'The payment ID is invalid.', 'wp-parsidate' ),
						'414'    => __( 'The organization issuing the bill is not valid.', 'wp-parsidate' ),
						'415'    => __( 'Working session time has ended.', 'wp-parsidate' ),
						'416'    => __( 'An error has occurred in registering information.', 'wp-parsidate' ),
						'417'    => __( 'Payer ID is invalid.', 'wp-parsidate' ),
						'418'    => __( 'An error occurred in the definition of customer information.', 'wp-parsidate' ),
						'419'    => __( 'The number of times the information has been entered has exceeded the limit.', 'wp-parsidate' ),
						'421'    => __( 'The IP is not valid.', 'wp-parsidate' ),
						'51'     => __( 'The transaction is duplicate.', 'wp-parsidate' ),
						'54'     => __( 'The reference transaction does not exist.', 'wp-parsidate' ),
						'55'     => __( 'The transaction is invalid.', 'wp-parsidate' ),
						'61'     => __( 'An error occurred in the deposit.', 'wp-parsidate' )
					);

					if ( array_key_exists( $err_code, $error_responses ) ) {
						return $error_responses[ $err_code ];
					}

					return __( 'A system error occurred during payment.', 'wp-parsidate' );
				}
			}
		}
	}

	add_action( 'plugins_loaded', 'wpp_mellat_payment_gateway_init', 0 );
}