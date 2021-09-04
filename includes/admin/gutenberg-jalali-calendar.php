<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

/**
 * Gutenberg Jalali Calendar
 *
 * This package, will add a Jalali calendar to WordPress Gutenberg editor
 * introduced from version v5.0.
 *
 * @author              Alireza Dabiri Nejad / Alirdn
 * @package             WP-Parsidate
 * @subpackage          Admin/Gutenber_Jalali_Calendar
 */

/**
 * Enqueue Gutenberg Jalali Calendar assets for backend editor.
 *
 * @uses {wp-plugins}
 * @uses {wp-i18n} to internationalize the block's text.
 * @uses {wp-compose}
 * @uses {wp-components}
 * @uses {wp-element} for WP Element abstraction — structure of blocks.
 * @uses {wp-editor} for WP editor styles.
 * @uses {wp-edit-post} to internationalize the block's text.
 * @uses {wp-data}
 * @uses {wp-date}
 * @since 3.0.0
 */
if ( ! function_exists( 'wpp_gutenberg_jalali_calendar_editor_assets' ) ) {
	function wpp_gutenberg_jalali_calendar_editor_assets() {
		wp_enqueue_script(
			'wpp_gutenberg_jalali_calendar_editor_scripts',
			WP_PARSI_URL . 'assets/js/gutenberg-jalali-calendar.build.js',
			array(
				'wp-plugins',
				'wp-i18n',
				'wp-compose',
				'wp-components',
				'wp-element',
				'wp-editor',
				'wp-edit-post',
				'wp-data',
				'wp-date'
			),
			true
		);

		// Styles.
		wp_enqueue_style(
			'wpp_gutenberg_jalali_calendar_editor_styles',
			WP_PARSI_URL . 'assets/css/gutenberg-jalali-calendar.build.css',
			array( 'wp-edit-blocks' )
		);
	}
}

// Hook: Editor assets.
if ( version_compare( get_bloginfo( 'version' ), '5.0.0', '>=' ) && wpp_is_active( 'persian_date' ) ) {
	add_action( 'enqueue_block_editor_assets', 'wpp_gutenberg_jalali_calendar_editor_assets' );
}
