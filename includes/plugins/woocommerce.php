<?php

/**
 * Makes WooCommerce compatible with WP-Parsidate plugin
 *
 * @package                 WP-Parsidate
 * @subpackage              Plugins/WooCommerce
 * @author                  Ehsaan
 * @author                  Farhan Nisi
 */
class WPP_WooCommerce {
	public static $instance = null;

	/**
	 * Hooks required tags
	 */
	private function __construct() {
		global $wpp_settings;
		add_filter( 'wpp_plugins_compability_settings', array( $this, 'add_settings' ) );

		if ( isset( $wpp_settings['woo_per_price'] ) && $wpp_settings['woo_per_price'] != 'disable' ) {
			add_filter( 'woocommerce_sale_price_html', 'per_number' );
			add_filter( 'woocommerce_price_html', 'per_number' );
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
	 * @param           array $old_settings Old settings
	 *
	 * @return          array New settings
	 */
	public function add_settings( $old_settings ) {
		$options  = array(
			'enable'  => __( 'Enable', 'wp-parsidate' ),
			'disable' => __( 'Disable', 'wp-parsidate' )
		);
		$settings = array(
			'woocommerce'   => array(
				'id'   => 'woocommerce',
				'name' => __( 'WooCommerce', 'wp-parsidate' ),
				'type' => 'header'
			),
			'woo_per_price' => array(
				'id'      => 'woo_per_price',
				'name'    => __( 'Fix prices', 'wp-parsidate' ),
				'type'    => 'radio',
				'options' => $options,
				'std'     => 'disable'
			)
		);

		return array_merge( $old_settings, $settings );
	}
}

return WPP_WooCommerce::getInstance();
