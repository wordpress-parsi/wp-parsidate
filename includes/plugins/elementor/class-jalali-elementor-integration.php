<?php

namespace JalaliElementor;

defined( 'ABSPATH' ) || exit;

class WPP_Elementor_Integration {
	public function __construct() {
		// Hook to replace the core date_time control
		add_action( 'elementor/controls/register', array( $this, 'replace_date_time_control' ), 20 );

		// Enqueue scripts for Jalali date picker
		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'enqueue_editor_scripts' ) );
	}

	/**
	 * Replace Elementor's core date_time control
	 *
	 * @param $controls_manager
	 *
	 * @sicne 5.1.3
	 */
	public function replace_date_time_control( $controls_manager ) {
		// Unregister the existing date_time control
		$controls_manager->unregister( 'date_time' );

		// Register your custom date_time control
		$controls_manager->register( new \JalaliElementor\Controls\WPP_Elementor_Date_Time_Control() );
	}

	/**
	 * Enqueue scripts for Jalali date picker
	 */
	public function enqueue_editor_scripts() {
		global $wpp_months_name;

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || wpp_is_active( 'dev_mode' ) ? '' : '.min';

		wp_enqueue_script( 'wpp_jalali_datepicker', WP_PARSI_URL . 'assets/js/jalalidatepicker.min.js', array(), WP_PARSI_VER );
		wp_enqueue_style( 'wpp_jalali_datepicker', WP_PARSI_URL . "assets/css/jalalidatepicker$suffix.css", array(), WP_PARSI_VER );

		do_action( 'wpp_jalali_datepicker_enqueued', 'elementor' );

		$months_name = $wpp_months_name;

		// Remove first item (nulled string) from name of months array
		array_shift( $months_name );

		wp_localize_script( 'wpp_jalali_datepicker', 'WPP_I18N',
			array(
				'months' => $months_name,
			),
		);
	}
}

new WPP_Elementor_Integration();