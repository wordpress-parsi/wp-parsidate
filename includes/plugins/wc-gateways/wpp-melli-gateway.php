<?php

defined('ABSPATH') or exit('No direct script access allowed');

if (!function_exists('wpp_melli_payment_gateway_init')) {

    function wpp_melli_payment_gateway_init()
    {
        if (!class_exists('WPP_WC_Melli_Gateway') && !class_exists('WPP_WC_Melli_Gateway')) {
            /**
             * WPP_WC_Melli_Gateway class to add Melli Bank payment gateway to WooCommerce
             *
             * @package                 WP-Parsidate
             * @subpackage              Plugins/WooCommerce/PaymentGateways
             * @since 5.0.0
             */
            class WPP_WC_Melli_Gateway extends WC_Payment_Gateway
            {

                private $gateway_name;
                public $terminal_id;
                public $merchant_id;
                public $key;
                public $title;
                public $description;
                public $failed_massage;

                public function __construct()
                {
                    $this->id = 'melli';
                    $this->gateway_name = __('Melli Bank', 'wp-parsidate');
                    $this->method_title = $this->gateway_name;
                    $this->method_description = $this->gateway_name . ' ' . __('payment gateway (By WP-Parsidate)', 'wp-parsidate');
                    $this->has_fields = true;
                    $this->icon = apply_filters($this->id . '_logo', WP_PARSI_URL . "assets/images/$this->id-logo.png");

                    $this->init_form_fields();
                    $this->init_settings();

                    $this->terminal_id = $this->get_option('terminal_id');
                    $this->merchant_id = $this->get_option('merchant_id');
                    $this->key = $this->get_option('key');
                    $this->title = $this->get_option('title');
                    $this->description = $this->get_option('description');
                    $this->failed_massage = $this->get_option('failed_massage');

                    // Save Admin Option
                    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

                    // Handle Request
                    add_action('woocommerce_api_' . $this->get_class(), array($this, 'handle_gateway_response'));
                }

                public function get_class()
                {
                    return strtolower(get_class($this));
                }

                public function init_form_fields()
                {
                    $this->form_fields = apply_filters('wpp_wc_' . $this->id . '_gateway_config', array(
                            'enabled' => array(
                                'title' => __('Enabled/Disabled', 'wp-parsidate'),
                                'type' => 'checkbox',
                                /* translators: %s: Bank name */
                                'label' => sprintf(__('Activate or deactivate %s gateway', 'wp-parsidate'), $this->gateway_name),
                                'default' => 'no',
                                'description' => ($this->is_enable_open_ssl() === false ? '<span style="color: red;">توجه: جهت فعال سازی درگاه می بایست ماژول OpenSSL در تنظیمات PHP هاست شما فعال باشد</span>' : ''),
                            ),
                            'terminal_id' => array(
                                'title' => __('Terminal No.', 'wp-parsidate'),
                                'type' => 'text',
                                'default' => '',
                                'desc_tip' => false
                            ),
                            'merchant_id' => array(
                                'title' => __('Merchant ID', 'wp-parsidate'),
                                'type' => 'text',
                                'default' => '',
                                'desc_tip' => false
                            ),
                            'key' => array(
                                'title' => __('Gateway Key', 'wp-parsidate'),
                                'type' => 'text',
                                'default' => '',
                                'desc_tip' => false
                            ),
                            'title' => array(
                                'title' => __('Gateway title', 'wp-parsidate'),
                                'type' => 'text',
                                'description' => __('This name is displayed to the customer during the purchase process', 'wp-parsidate'),
                                'default' => $this->gateway_name
                            ),
                            'description' => array(
                                'title' => __('Gateway description', 'wp-parsidate'),
                                'type' => 'textarea',
                                'description' => __('The description that will be displayed during the purchase process for the gateway', 'wp-parsidate'),
                                /* translators: %s: Bank name */
                                'default' => sprintf(__("Secure payment by all Shetab's cards through %s", 'wp-parsidate'), $this->gateway_name)
                            ),
                            'failed_massage' => array(
                                'title' => __('Payment failed message', 'wp-parsidate'),
                                'type' => 'textarea',
                                'description' => __('Enter the text of the message you want to display to the user after an unsuccessful payment.', 'wp-parsidate'),
                                'default' => __('Your payment has failed. Please try again or contact us in case of problems.', 'wp-parsidate')
                            )
                        )
                    );
                }

                public function get_icon()
                {
                    $icon = $this->icon ? '<img src="' . esc_url(WC_HTTPS::force_https_url($this->icon)) . '" alt="' . esc_attr($this->get_title()) . '" />' : '';
                    return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
                }

                public function is_available()
                {
                    return parent::is_available();
                }

                public function encrypt_pkcs7($str, $key): string
                {
                    $key = base64_decode($key);
                    $ciphertext = OpenSSL_encrypt($str, "DES-EDE3", $key, OPENSSL_RAW_DATA);
                    return base64_encode($ciphertext);
                }

                public function api($url, $data = false)
                {
                    try {
                        $args = array(
                            'headers' => array(
                                'Content-Type' => 'application/json; charset=utf-8',
                            ),
                            'timeout' => 20,
                            'sslverify' => false
                        );

                        if ($data) {
                            $args['body'] = json_encode($data);
                        }

                        $response = wp_remote_post($url, $args);

                        if (is_wp_error($response)) {
                            return false;
                        }

                        $body = wp_remote_retrieve_body($response);
                        return !empty($body) ? json_decode($body) : false;
                    } catch (Exception $ex) {
                        return false;
                    }
                }

                public function is_enable_open_ssl(): bool
                {
                    return (extension_loaded('openssl') and function_exists('OpenSSL_encrypt'));
                }

                public function process_payment($order_id)
                {
                    // Get Order
                    $order = wc_get_order($order_id);

                    // Get Gateway RefId
                    $refId = $this->get_ref_id_from_melli($order);

                    // Action
                    do_action('wpp_wc_' . $this->id . '_gateway_process_payment', $order, $refId);

                    if ($refId['status'] === false) {

                        // setup Error Text
                        $errorText = 'خطا در اتصال به درگاه پرداخت: ' . $refId['message'];

                        // Add Notice
                        wc_add_notice($errorText, 'error');

                        // Return for Block Support
                        return [
                            'result' => 'failure',
                            'messages' => $refId['message'],
                            'reload' => false
                        ];
                    }

                    // Return
                    return [
                        'result' => 'success',
                        'redirect' => $refId['redirect']
                    ];
                }

                public function get_ref_id_from_melli($order): array
                {
                    $Amount = $this->get_amount($order);
                    $OrderId = $order->get_id();
                    $LocalDateTime = date("m/d/Y g:i:s a");
                    $TerminalId = $this->terminal_id;
                    $key = $this->key;
                    $SignData = $this->encrypt_pkcs7("$TerminalId;$OrderId;$Amount", "$key");

                    $data = apply_filters('wpp_wc_' . $this->id . '_gateway_request_payment', array(
                        'TerminalId' => $this->terminal_id,
                        'MerchantId' => $this->merchant_id,
                        'Amount' => $this->get_amount($order),
                        'SignData' => $SignData,
                        'ReturnUrl' => add_query_arg(
                            array(
                                'wc-api' => $this->get_class(),
                                'order_id' => $order->get_id()
                            ),
                            get_site_url(null, '/')
                        ),
                        'LocalDateTime' => $LocalDateTime,
                        'OrderId' => $OrderId
                    ));

                    $result = $this->api('https://sadad.shaparak.ir/vpg/api/v0/Request/PaymentRequest', $data);

                    if ($result->ResCode == 0) {

                        return [
                            'status' => true,
                            'token' => $result->Token,
                            'redirect' => "https://sadad.shaparak.ir/VPG/Purchase?Token=" . $result->Token
                        ];
                    }

                    return [
                        'status' => false,
                        'message' => $result->Description
                    ];
                }

                public function handle_gateway_response()
                {
                    $action = $_GET['action'] ?? '';
                    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
                    $order = wc_get_order($order_id);

                    switch ($action) {
                        case 'redirect':
                            $this->redirect_to_gateway($order);
                            break;

                        default:
                            $this->verify_payment($order);
                            break;
                    }
                }

                public function redirect_to_gateway($order)
                {
                    // Don't Need
                }

                public function verify_payment($order)
                {
                    $OrderId = $_POST["OrderId"] ?? '';
                    $Token = $_POST["token"] ?? '';
                    $ResCode = $_POST["ResCode"] ?? '';

                    if ($ResCode == 0) {

                        $verifyData = [
                            'Token' => $Token,
                            'SignData' => $this->encrypt_pkcs7($Token, $this->key)
                        ];
                        $result = $this->api('https://sadad.shaparak.ir/vpg/api/v0/Advice/Verify', $verifyData);

                        if ($result->ResCode != -1 && $result->ResCode == 0) {

                            // Set Payment Completed
                            $order->payment_complete($result->RetrivalRefNo);

                            // Add Order Note
                            $order->add_order_note(sprintf('پرداخت با موفقیت انجام شد. کد پیگیری: %s', $result->RetrivalRefNo));

                            // Remove cart.
                            WC()->cart->empty_cart();

                            // Action
                            do_action('wpp_wc_' . $this->id . '_gateway_completed_payment', $order, [
                                'RetrivalRefNo' => $result->RetrivalRefNo,
                                'SystemTraceNo' => $result->SystemTraceNo,
                                'OrderId' => $result->OrderId,
                            ]);

                            // Redirect
                            wp_redirect($this->get_return_url($order));
                            exit;
                        }
                    }

                    $this->set_failed_payment($order);
                }

                public function get_amount($order)
                {
                    $currency = $order->get_currency();
                    $order_total = $order->get_total();
                    $amount = intval($order_total);
                    $currency = strtolower($currency);

                    if (in_array($currency, array(
                        'irt',
                        'toman',
                        'iran toman',
                        'iranian toman',
                        'iran-toman',
                        'iran_toman',
                        'تومان',
                        'تومان ایران'
                    ))) {
                        $amount = $amount * 10;
                    } else if ('irht' === $currency) {
                        $amount = $amount * 1000 * 10;
                    } else if ('irhr' === $currency) {
                        $amount = $amount * 1000;
                    }

                    return $amount;
                }

                public function set_failed_payment($order)
                {
                    wc_add_notice($this->failed_massage, 'error');
                    do_action('wpp_wc_' . $this->id . '_gateway_failed_payment', $order);
                    wp_redirect(wc_get_checkout_url());
                    exit;
                }

            }
        }
    }

    add_action('before_woocommerce_init', 'wpp_melli_payment_gateway_init', 15);
}