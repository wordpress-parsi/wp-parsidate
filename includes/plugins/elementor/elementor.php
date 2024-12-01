<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

if ( ! class_exists( 'WPP_Elementor' ) ) {

	/**
	 * Make Elementor compatible with WP-Parsidate
	 */
	class WPP_Elementor {
		public static $instance = null;

		public function __construct() {
			$this->include_files();

			add_filter( 'wpp_settings_tabs', array( $this, 'add_tab' ) );
			add_filter( 'wpp_registered_settings', array( $this, 'add_settings' ) );
			add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'add_styles' ) );
		}

		/**
		 * Returns an instance of class
		 *
		 * @return          WPP_Elementor
		 */
		public static function getInstance() {
			if ( self::$instance == null ) {
				self::$instance = new WPP_Elementor();
			}

			return self::$instance;
		}

		private function include_files() {
			if ( wpp_is_active( 'elem_date_picker' ) ) {
				require_once __DIR__ . '/class-jalali-date-time-control.php';
				require_once __DIR__ . '/class-jalali-elementor-integration.php';
			}
		}

		/**
		 * Add Elementor settings tab
		 *
		 * @param $tabs
		 *
		 * @return mixed
		 * @sicne 5.1.3
		 */
		public function add_tab( $tabs ) {
			$about_tab = $tabs['about'];

			unset( $tabs['about'] );

			$tabs['elementor'] = __( 'Elementor', 'wp-parsidate' );
			$tabs['about']     = $about_tab;

			return $tabs;
		}

		/**
		 * Add Elementor settings
		 *
		 * @param array $old_settings Old settings
		 *
		 * @return          array New settings
		 * @since 4.0.0
		 */
		public function add_settings( $old_settings ) {
			$settings['elementor'] = apply_filters( 'wpp_elementor_settings', array(
				'elem_date_header' => array(
					'id'   => 'elem_date_header',
					'name' => __( 'Date Picker', 'wp-parsidate' ),
					'type' => 'header',
				),
				'elem_date_picker' => array(
					'id'      => 'elem_date_picker',
					'name'    => __( 'Enable Jalali date-picker', 'wp-parsidate' ),
					'type'    => 'checkbox',
					'options' => 1,
					'std'     => 0,
				),
				'elem_date_footer' => array(
					'id'   => 'elem_date_footer',
					'type' => 'footer',
				),
			) );

			return array_merge( $old_settings, $settings );
		}

		public function add_styles( $value = '' ) {
			$wpp_elementor_css = "
      body, .tipsy-inner, .elementor-button, .elementor-panel {
        font-family: Vazir,Tahoma,Arial,Helvetica,Verdana,sans-serif;
      }
      .flatpickr-calendar.open{left:-20000px!important;}
      .tipsy-inner {
        font-size: small;
      }";
			$wpp_elementor_css = apply_filters( "wpp_elementor_css", $wpp_elementor_css );

			wp_add_inline_style( "elementor-editor", $wpp_elementor_css );
		}
	}

	return WPP_Elementor::getInstance();
}