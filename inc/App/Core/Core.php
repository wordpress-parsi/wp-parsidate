<?php

namespace WPParsidate\App\Core;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Helper\WordPress;
use WPParsidate\Settings\Settings;

class Core {
	public function __construct() {
		new fixTitle();
		new fixDates();
		new Locale();

		add_filter( 'wp_parsidate_core_settings_options', [ $this, 'settings' ] );
		add_action( 'init', [ $this, 'disableGutenbergBlocksWidget' ] );
	}

	/**
	 * disable wp widget block that introduced in WordPress 5.8
	 *
	 * @since               4.0.0
	 */
	public function disableGutenbergBlocksWidget(): void {
		if ( Settings::get( 'disable_widget_block', false ) ) {
			add_filter( 'gutenberg_use_widgets_block_editor', '__return_false' );
			add_filter( 'use_widgets_block_editor', '__return_false' );
		}
	}

	public function settings(): array {
		$settings = array(
			// Locale
			'start_grid_language'  => array(
				'title' => __( 'Change Locale', 'wp-parsidate' ),
				'type'  => 'startGrid',
			),
			'admin_lang'           => array(
				'id'       => 'admin_lang',
				'title'    => __( 'Change Locale in admin', 'wp-parsidate' ),
				'type'     => 'toggle',
				'default'  => false,
				'desc'     => __( 'This option change WordPress locale to Persian in Admin', 'wp-parsidate' ),
				'sanitize' => 'bool'
			),
			'user_lang'            => array(
				'id'       => 'user_lang',
				'title'    => __( 'Change Locale in theme', 'wp-parsidate' ),
				'type'     => 'toggle',
				'default'  => false,
				'desc'     => __( 'This option change WordPress locale to Persian in theme', 'wp-parsidate' ),
				'sanitize' => 'bool'
			),
			'end_grid_language'    => array(
				'type' => 'endGrid',
			),

			// Date
			'start_grid_date'      => array(
				'title' => __( 'Date', 'wp-parsidate' ),
				'type'  => 'startGrid',
			),
			'persian_date'         => array(
				'id'       => 'persian_date',
				'title'    => __( 'Shamsi date', 'wp-parsidate' ),
				'type'     => 'toggle',
				'default'  => false,
				'desc'     => __( 'By enabling this, Dates will convert to Shamsi (Jalali) dates', 'wp-parsidate' ),
				'sanitize' => 'bool'
			),
			'months_name_type'     => array(
				'id'       => 'months_name_type',
				'title'    => __( 'Months name type', 'wp-parsidate' ),
				'type'     => 'select',
				'options'  => array(
					'persian' => __( 'Persian', 'wp-parsidate' ),
					'dari'    => __( 'Dari', 'wp-parsidate' ),
					'kurdish' => __( 'Kurdish', 'wp-parsidate' ),
					'pashto'  => __( 'Pashto', 'wp-parsidate' ),
				),
				'default'  => 'persian',
				'sanitize' => 'text'
			),
			'end_grid_date'        => array(
				'type' => 'endGrid',
			),

			// Admin
			'start_grid_admin'     => array(
				'title' => __( 'Admin', 'wp-parsidate' ),
				'type'  => 'startGrid',
			),
			'disable_widget_block' => array(
				'id'       => 'disable_widget_block',
				'title'    => __( 'Disable Widget Block', 'wp-parsidate' ),
				'type'     => 'toggle',
				'default'  => false,
				'desc'     => __( 'By enabling this, Widget Block Editor disabled', 'wp-parsidate' ),
				'sanitize' => 'bool'
			),
			'enable_fonts'         => array(
				'id'       => 'enable_fonts',
				'title'    => __( 'Vazir Font', 'wp-parsidate' ),
				'type'     => 'toggle',
				'default'  => false,
				'desc'     => __( 'By enabling this option, the Vazir font will be enable in whole admin area.',
					'wp-parsidate' ),
				'sanitize' => 'bool'
			),
			'end_grid_admin'       => array(
				'type' => 'endGrid',
			),

			// Plugin
			'start_grid_plugin'    => array(
				'title' => __( 'Plugin', 'wp-parsidate' ),
				'type'  => 'startGrid',
			),
			'dev_mode'             => array(
				'id'       => 'dev_mode',
				'title'    => __( 'Debug Mode', 'wp-parsidate' ),
				'type'     => 'toggle',
				'default'  => false,
				'desc'     => __( 'By enabling this option, the uncompressed version of the JS and CSS files will be loaded.',
					'wp-parsidate' ),
				'sanitize' => 'bool'
			),
			'end_grid_plugin'      => array(
				'type' => 'endGrid',
			),
		);

		if ( WordPress::isMultilingualActive() ) {
			$settings = array_merge( $settings, array(
				'start_grid_multilingual' => array(
					'title' => __( 'Multilingual', 'wp-parsidate' ),
					'type'  => 'startGrid',
				),
				'multilingual_support'    => array(
					'id'       => 'multilingual_support',
					'title'    => __( 'Multilingual compatibility', 'wp-parsidate' ),
					'type'     => 'toggle',
					'default'  => false,
					'desc'     => __( 'By enabling this, ParsiDate options only work in persian locale',
						'wp-parsidate' ),
					'sanitize' => 'bool'
				),
				'end_grid_multilingual'   => array(
					'type' => 'endGrid',
				),
			) );
		}

		return $settings;
	}
}