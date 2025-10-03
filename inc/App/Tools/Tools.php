<?php

namespace WPParsidate\App\Tools;

use WPParsidate\Settings\Settings;

class Tools {
	public function __construct() {
		add_filter( 'wp_parsidate_tools_settings_options', [ $this, 'settings' ] );

		if ( Settings::get( 'date_in_admin_bar', false ) ) {
			add_action( 'admin_bar_menu', [ $this, 'addDateToAdminBar' ], PHP_INT_MAX );
			//add_action( 'admin_head', [ $this, 'adminBarDateStyle' ] );
		}
	}

	/**
	 * Add Jalali date to WordPress admin bar
	 *
	 * @param $adminBar
	 *
	 * @return void
	 */
	public function addDateToAdminBar( $adminBar ): void {
		$currentDate = parsidate( 'l Y/m/d', date( 'Y-m-d' ) );

		$args = array(
			'id'    => 'wpp_current_date',
			'title' => esc_html__( 'Today:&nbsp;', 'wp-parsidate' ) . $currentDate,
			'meta'  => array( 'class' => 'wpp-admin-bar-date' ),
		);

		$adminBar->add_node( $args );
	}

	public function adminBarDateStyle(): void {
		echo '<style>
			.wpp-admin-bar-date {
			    color: #fff;
			    background-color: var(--e-context-primary-color) !important;
			    border-radius: 5px 5px 0 0 !important;
			    unicode-bidi: embed !important;
			}
			</style>';
	}

	public function settings(): array {
		return array(
			// Admin bar
			'start_grid_admin_bar' => array(
				'title' => __( 'Admin Bar', 'wp-parsidate' ),
				'type'  => 'startGrid',
			),
			'date_in_admin_bar'    => array(
				'id'       => 'date_in_admin_bar',
				'title'    => __( 'Display date in the admin bar', 'wp-parsidate' ),
				'type'     => 'toggle',
				'default'  => false,
				'desc'     => __( "Display today's Jalali date in the WordPress admin bar.", 'wp-parsidate' ),
				'sanitize' => 'bool'
			),
			'end_grid_admin_bar'   => array(
				'type' => 'endGrid',
			),
		);
	}
}