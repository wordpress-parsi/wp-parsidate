<?php

defined('ABSPATH') or exit('No direct script access allowed');

if (!function_exists('wpp_mellat_payment_gateway_init')) {

    function wpp_mellat_payment_gateway_init()
    {
        if (!class_exists('WPP_WC_Mellat_Gateway') && !class_exists('WPP_WC_Mellat_Gateway')) {
            /**
             * WPP_WC_Mellat_Gateway class to add Mellat Bank payment gateway to WooCommerce
             *
             * @package                 WP-Parsidate
             * @subpackage              Plugins/WooCommerce/PaymentGateways
             * @since 5.0.0
             */
            class WPP_WC_Mellat_Gateway extends WC_Payment_Gateway
            {

                private $gateway_name;
                public $method_title;
                public $terminal_id;
                public $username;
                public $password;
                public $title;
                public $description;
                public $failed_massage;

                public function __construct()
                {
                    $this->id = 'mellat';
                    $this->gateway_name = __('Mellat Bank', 'wp-parsidate');
                    $this->method_title = $this->gateway_name;
                    $this->method_description = $this->gateway_name . ' ' . __('payment gateway (By WP-Parsidate)', 'wp-parsidate');
                    $this->has_fields = true;
                    $this->icon = apply_filters($this->id . '_logo', WP_PARSI_URL . "assets/images/$this->id-logo.png");

                    $this->init_form_fields();
                    $this->init_settings();

                    $this->terminal_id = $this->get_option('terminal');
                    $this->username = $this->get_option('username');
                    $this->password = $this->get_option('password');
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
                                'default' => 'no'
                            ),
                            'terminal' => array(
                                'title' => __('Terminal No.', 'wp-parsidate'),
                                'type' => 'text',
                                'default' => '',
                                'desc_tip' => false
                            ),
                            'username' => array(
                                'title' => __('Gateway user name', 'wp-parsidate'),
                                'type' => 'text',
                                'default' => '',
                                'desc_tip' => true
                            ),
                            'password' => array(
                                'title' => __('Gateway password', 'wp-parsidate'),
                                'type' => 'text',
                                'default' => '',
                                'desc_tip' => true
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

                public function process_payment($order_id)
                {
                    // Get Order
                    $order = wc_get_order($order_id);

                    // Get Gateway RefId
                    $refId = $this->get_ref_id_from_mellat($order);

                    // Action
                    do_action('wpp_wc_' . $this->id . '_gateway_process_payment', $order, $refId);

                    if ($refId['status'] === false) {

                        // Add Notice
                        wc_add_notice($refId['message'], 'error');

                        // Return for Block Support
                        return [
                            'result' => 'failure',
                            'messages' => $refId['message'],
                            'reload' => false
                        ];
                    }

                    // Save Session
                    WC()->session->set('mellat_ref_id', $refId['refId']);
                    WC()->session->set('mellat_order_id', $order_id);

                    // Return
                    return [
                        'result' => 'success',
                        'redirect' => add_query_arg(
                            array(
                                'wc-api' => $this->get_class(),
                                'action' => 'redirect',
                                'order_id' => $order_id
                            ),
                            get_site_url(null, '/')
                        )
                    ];
                }

                public function get_ref_id_from_mellat($order)
                {
                    if (!class_exists('nusoap_client')) {
                        require_once(WP_PARSI_DIR . 'includes/plugins/wc-gateways/lib/nusoap.php');
                    }

                    $client = new nusoap_client('https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl');
                    $err = $client->getError();
                    if ($err) {

                        return [
                            'status' => false,
                            'message' => 'خطا در ارتباط با بانک ملت: ' . $err
                        ];
                    }

                    $description = 'خرید به شماره سفارش: ' . $order->get_order_number();
                    $description .= ' | خریدار: ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

                    $parameters = apply_filters('wpp_wc_' . $this->id . '_gateway_request_payment', array(
                        'terminalId' => $this->terminal_id,
                        'userName' => $this->username,
                        'userPassword' => $this->password,
                        'orderId' => $order->get_id(),
                        'amount' => $this->get_amount($order),
                        'localDate' => date('Ymd'),
                        'localTime' => date('His'),
                        'additionalData' => $description,
                        'callBackUrl' => add_query_arg(
                            array(
                                'wc-api' => $this->get_class(),
                                'order_id' => $order->get_id()
                            ),
                            get_site_url(null, '/')
                        ),
                        'payerId' => $order->get_customer_id()
                    ));
                    $result = $client->call('bpPayRequest', $parameters, 'http://interfaces.core.sw.bps.com/');

                    if ($client->fault) {

                        return [
                            'status' => false,
                            'message' => $this->failed_massage
                        ];
                    } else {

                        $resultStr = $result;
                        $err = $client->getError();
                        if ($err) {

                            return [
                                'status' => false,
                                'message' => 'خطا در ارتباط با بانک ملت: ' . $err
                            ];
                        } else {

                            $res = explode(',', $resultStr);
                            $ResCode = $res[0];
                            if ($ResCode == "0") {

                                return [
                                    'status' => true,
                                    'refId' => $res[1]
                                ];
                            } else {

                                return [
                                    'status' => false,
                                    'message' => 'خطا در دریافت RefId از بانک ملت: ' . $this->get_error_message($ResCode)
                                ];
                            }
                        }
                    }
                }

                public function handle_gateway_response()
                {
                    $action = $_GET['action'] ?? '';
                    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
                    $order = wc_get_order($order_id);

                    if (!$order) {
                        wp_die('سفارش یافت نشد');
                    }

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
                    $refId = WC()->session->get('mellat_ref_id');

                    if (empty($refId)) {
                        wc_add_notice($this->failed_massage, 'error');
                        wp_redirect(wc_get_checkout_url());
                        exit;
                    }

                    ?>
                    <html lang="fa-IR">
                    <head>
                        <meta charset="UTF-8"/>
                    </head>
                    <body onload="document.forms['mellat_redirect'].submit()">
                    <form name="mellat_redirect" method="post"
                          action="https://bpm.shaparak.ir/pgwchannel/startpay.mellat">
                        <input type="hidden" name="RefId" value="<?php echo esc_attr($refId); ?>">
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

                public function verify_payment($order)
                {
                    $resCode = $_POST['ResCode'] ?? '';
                    $saleOrderId = $_POST['SaleOrderId'] ?? '';
                    $saleReferenceId = $_POST['SaleReferenceId'] ?? '';
                    $CardHolderInfo = $_POST['CardHolderInfo'] ?? '';
                    $CardHolderPan = $_POST['CardHolderPan'] ?? '';
                    $FinalAmount = $_POST['FinalAmount'] ?? '';

                    $params = [
                        'ResCode' => $resCode,
                        'SaleOrderId' => $saleOrderId,
                        'SaleReferenceId' => $saleReferenceId,
                        'CardHolderInfo' => $CardHolderInfo,
                        'CardHolderPan' => $CardHolderPan,
                        'FinalAmount' => $FinalAmount,
                    ];

                    if ($resCode == '0') {

                        // Verify Payment
                        $verify_result = $this->verify($params);
                        if ($verify_result['status'] === true) {

                            // Settle Request
                            $settle_result = $this->verify($params, 'bpSettleRequest');
                            if ($settle_result['status'] === true) {

                                // Set Payment Completed
                                $order->payment_complete($saleReferenceId);

                                // Add Order Note
                                $order->add_order_note(sprintf('پرداخت با موفقیت انجام شد. کد پیگیری: %s', $saleReferenceId));

                                // Remove WC Session
                                WC()->session->__unset('mellat_ref_id');
                                WC()->session->__unset('mellat_order_id');

                                // Remove cart.
                                WC()->cart->empty_cart();

                                // Action
                                do_action('wpp_wc_' . $this->id . '_gateway_completed_payment', $order, $params);

                                // Redirect
                                wp_redirect($this->get_return_url($order));
                                exit;
                            } else {

                                $this->set_failed_payment($order);
                            }
                        } else {

                            $this->set_failed_payment($order);
                        }
                    } else {

                        $error_message = $this->get_error_message($resCode);
                        wc_add_notice($error_message, 'error');
                        do_action('wpp_wc_' . $this->id . '_gateway_failed_payment', $order);
                        wp_redirect(wc_get_checkout_url());
                        exit;
                    }
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

                public function verify($params, $method = 'bpVerifyRequest'): array
                {
                    if (!class_exists('nusoap_client')) {
                        require_once(WP_PARSI_DIR . 'includes/plugins/wc-gateways/lib/nusoap.php');
                    }

                    $client = new nusoap_client('https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl');
                    $orderId = $params["SaleOrderId"];
                    $verifySaleReferenceId = $params['SaleReferenceId'];

                    $err = $client->getError();
                    if ($err) {

                        return [
                            'status' => false,
                            'message' => $err,
                            'code' => ''
                        ];
                    }

                    $parameters = array(
                        'terminalId' => $this->terminal_id,
                        'userName' => $this->username,
                        'userPassword' => $this->password,
                        'orderId' => $orderId,
                        'saleOrderId' => $orderId,
                        'saleReferenceId' => $verifySaleReferenceId
                    );
                    $result = $client->call($method, $parameters, 'http://interfaces.core.sw.bps.com/');

                    // Check Success
                    if ($result == '0') {

                        return [
                            'status' => true
                        ];
                    }

                    // Check Error Code
                    if (is_numeric($result)) {

                        return [
                            'status' => false,
                            'message' => 'خطا در اعتبار سنجی پرداخت ملت رخ داده است. کد خطا: ' . $this->get_error_message($result),
                            'code' => ''
                        ];
                    }

                    return [
                        'status' => false,
                        'message' => $this->failed_massage,
                        'code' => ''
                    ];
                }

                public function get_error_message($resCode)
                {
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

                    return $messages[$resCode] ?? 'کد خطا: ' . $resCode;
                }

            }
        }
    }

    add_action('before_woocommerce_init', 'wpp_mellat_payment_gateway_init', 15);
}