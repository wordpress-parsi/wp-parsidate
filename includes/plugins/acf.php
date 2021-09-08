<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

/**
 * Makes ACF compatible with WP-Parsidate plugin
 *
 * @package                 WP-Parsidate
 * @subpackage              Plugins/ACF
 * @since                   4.0.0
 */
class WPP_ACF {
	public static $instance = null;
	var $settings;

	/**
	 * Hooks required tags
	 */
	private function __construct() {
		$this->settings = array(
			'version'	=> '1.0.0',
			'url'		=> WP_PARSI_URL . 'includes/plugins/',
			'path'		=> WP_PARSI_DIR . 'includes/plugins/'
		);

		add_filter( 'wpp_plugins_compatibility_settings', array( $this, 'add_settings' ) );
		add_action( 'acf/include_field_types', array( $this, 'wpp_acf_include_field' ) ); // v5
		add_action( 'acf/register_fields', array( $this, 'wpp_acf_include_field' ) ); // v4
	}

	/**
	 * Returns an instance of class
	 *
	 * @return          WPP_ACF
	 * @since           4.0.0
	 */
	public static function getInstance() {
		if ( self::$instance == null ) {
			self::$instance = new WPP_ACF();
		}

		return self::$instance;
	}

	/**
	 *  This function will include the field type class
	 *
	 * @param               $version (int) major ACF version. Defaults to false
	 *
	 * @return              void
	 * @since               4.0.0
	 */
	public function wpp_acf_include_field( $version = false ) {
		$version = $version ? (float)$version : 4;

		include_once( 'acf-fields/class-wpp-acf-datepicker-v' . (float) $version . '.php' );
		include_once( 'acf-fields/class-wpp-acf-timepicker-v' . (float) $version . '.php' );
	}

	/**
	 * Adds settings for toggle fixing
	 *
	 * @param           array $old_settings Old settings
	 *
	 * @return          array New settings
	 */
	public function add_settings( $old_settings ) {
		$settings = array(
			'acf'          => array(
				'id'   => 'acf',
				'name' => __( 'Advanced Custom Fields (ACF)', 'wp-parsidate' ),
				'type' => 'header'
			),
			'acf_persian_date' => array(
				'id'      => 'acf_persian_date',
				'name'    => __( 'Save dates in Jalali format (Not recommended)', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 1,
				'std'     => 0
			)
		);

		return array_merge( $old_settings, $settings );
	}
}

return WPP_ACF::getInstance();