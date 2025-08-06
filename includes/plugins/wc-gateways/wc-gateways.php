<?php

defined('ABSPATH') or exit('No direct script access allowed');

if (!class_exists('WPP_WC_Gateways')) {
    /**
     * Add Iranian payment gateways to WP-Parsidate
     *
     * @package                 WP-Parsidate
     * @subpackage              Plugins/WooCommerce/PaymentGateways
     */
    class WPP_WC_Gateways
    {
        public static $instance = null;

        /**
         * Hooks required tags
         */
        private function __construct()
        {

            add_action('before_woocommerce_init', [$this, 'include_files'], 10);
            add_filter('wpp_woocommerce_settings', array($this, 'add_settings'));
            add_filter('woocommerce_payment_gateways', array($this, 'register_selected_gateways'));
            add_action('woocommerce_blocks_loaded', array($this, 'register_order_approval_payment_method_type'));
        }

        public function gateways(): array
        {
            return array(
                'parsian' => __('Parsian Bank', 'wp-parsidate'),
                'pasargad' => __('Pasargad Bank', 'wp-parsidate'),
                'mellat' => __('Mellat Bank (Behpardakht)', 'wp-parsidate'),
                'melli' => __('Melli Bank (Sadad)', 'wp-parsidate'),
            );
        }

        /**
         * Includes files for plugin
         *
         * @return         void
         * @since          2.0
         */
        public function include_files()
        {
            $implemented_gateways = array_keys($this->gateways());

            $selected_gateways = $this->get_selected_gateways();
            $maybe_include = array_intersect($implemented_gateways, $selected_gateways);

            foreach ($maybe_include as $filename) {
                $file_path = WP_PARSI_DIR . "includes/plugins/wc-gateways/wpp-$filename-gateway.php";

                if (file_exists($file_path)) {
                    require_once($file_path);
                }
            }
        }

        /**
         * Returns an instance of class
         *
         * @return          WPP_WC_Gateways
         */
        public static function getInstance()
        {
            if (self::$instance == null) {
                self::$instance = new WPP_WC_Gateways();
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
        public function add_settings($old_settings)
        {
            $settings = array(
                'woo_gateways' => array(
                    'id' => 'woo_gateways',
                    'name' => __('Payment gateways', 'wp-parsidate'),
                    'type' => 'multicheck',
                    'options' => $this->gateways(),
                    'std' => array(),
                )
            );

            return array_merge($old_settings, $settings);
        }

        /**
         * @param $methods
         *
         * @return mixed
         * @since 5.0.0
         */
        public function register_selected_gateways($methods)
        {
            $selected_pgs = self::get_selected_gateways();

            if (empty($selected_pgs)) {
                return $methods;
            }

            foreach ($selected_pgs as $method) {
                $methods[] = 'WPP_WC_' . ucfirst($method) . '_Gateway';
            }

            return $methods;
        }

        public function register_order_approval_payment_method_type()
        {
            if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
                return;
            }

            add_action('woocommerce_blocks_payment_method_type_registration',
                function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                    $implemented_gateways = array_keys($this->gateways());

                    $selected_gateways = self::get_selected_gateways();
                    $maybe_include = array_intersect($implemented_gateways, $selected_gateways);

                    foreach ($maybe_include as $gateway) {
                        $block_path = WP_PARSI_DIR . "includes/plugins/wc-gateways/blocks/wpp-$gateway-pg-block.php";

                        if (file_exists($block_path)) {
                            require_once($block_path);

                            $class_name = 'WPP_WC_' . ucfirst($gateway) . '_Gateway_Blocks';

                            $payment_method_registry->register(new $class_name);
                        }
                    }
                }
            );
        }

        public function is_soap_enabled()
        {
            return extension_loaded('soap');
        }

        private function get_selected_gateways()
        {
            global $wpp_settings;

            return apply_filters('wpp_get_selected_wc_payment_gateways', $wpp_settings['woo_gateways'] ?? array());
        }
    }

    return WPP_WC_Gateways::getInstance();
}
