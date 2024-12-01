<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

if ( ! class_exists( 'WPP_ACF' ) ) {

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
				'version' => '1.0.0',
				'url'     => WP_PARSI_URL . 'includes/plugins/',
				'path'    => WP_PARSI_DIR . 'includes/plugins/'
			);

			add_filter( 'wpp_settings_tabs', array( $this, 'add_tab' ) );
			add_filter( 'wpp_registered_settings', array( $this, 'add_settings' ) );

			if ( wpp_is_active( 'acf_fix_date' ) ) {
				add_action( 'acf/include_field_types', array( $this, 'include_files' ) ); // v5
				add_action( 'acf/register_fields', array( $this, 'include_files' ) ); // v4
			}
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
		public function include_files( $version = false ) {
			$version = $version ? (float) $version : 4;

			include_once( 'acf-fields/class-wpp-acf-datepicker-v' . (float) $version . '.php' );
			include_once( 'acf-fields/class-wpp-acf-timepicker-v' . (float) $version . '.php' );
		}


		/**
		 * Add ACF settings tab
		 *
		 * @param $tabs
		 *
		 * @return mixed
		 * @sicne 5.1.3
		 */
		public function add_tab( $tabs ) {
			$about_tab = $tabs['about'];

			unset( $tabs['about'] );

			$tabs['acf']   = __( 'Advanced Custom Fields', 'wp-parsidate' );
			$tabs['about'] = $about_tab;

			return $tabs;
		}

		/**
		 * Adds settings for toggle fixing
		 *
		 * @param array $old_settings Old settings
		 *
		 * @return          array New settings
		 */
		public function add_settings( $old_settings ) {
			$settings['acf'] = apply_filters( 'wpp_acf_settings', array(
				'acf_header'       => array(
					'id'   => 'acf_header',
					'name' => __( 'Localization', 'wp-parsidate' ),
					'type' => 'header',
				),
				'acf_fix_date'     => array(
					'id'      => 'acf_fix_date',
					'name'    => __( 'Jalali Datepicker', 'wp-parsidate' ),
					'type'    => 'checkbox',
					'options' => 1,
					'std'     => 0,
				),
				'acf_persian_date' => array(
					'id'      => 'acf_persian_date',
					'name'    => __( 'Save dates in Jalali format (Not recommended)', 'wp-parsidate' ),
					'type'    => 'checkbox',
					'options' => 1,
					'std'     => 0,
				),
				'acf_footer'       => array(
					'id'   => 'acf_footer',
					'type' => 'footer',
				),
			) );

			return array_merge( $old_settings, $settings );
		}
	}

	return WPP_ACF::getInstance();
}