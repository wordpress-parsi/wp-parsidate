<?php

namespace WPParsidate\App\Convert;

class Convert {
	public function __construct() {
		new fixNumbers();
		new fixArabic();
		new fixPermalink();

		add_filter( 'wp_parsidate_convert_settings_options', [ $this, 'settings' ] );
	}

	public function settings(): array {
		return array(
			// Numbers
			'start_grid_persian_numbers' => array(
				'title' => __( 'Convert numbers to Persian', 'wp-parsidate' ),
				'type'  => 'startGrid',
			),
			'conv_number_format_i18n'    => array(
				'id'       => 'conv_number_format_i18n',
				'title'    => __( 'Numbers', 'wp-parsidate' ),
				'desc'     => __( 'Used in menus, comments, API, media, etc.', 'wp-parsidate' ),
				'type'     => 'toggle',
				'default'  => false,
				'sanitize' => 'bool'
			),
			'conv_page_title'            => array(
				'id'       => 'conv_page_title',
				'title'    => __( 'Page title', 'wp-parsidate' ),
				'type'     => 'toggle',
				'default'  => false,
				'sanitize' => 'bool'
			),
			'conv_title'                 => array(
				'id'       => 'conv_title',
				'title'    => __( 'Post title', 'wp-parsidate' ),
				'type'     => 'toggle',
				'default'  => false,
				'sanitize' => 'bool'
			),
			'conv_contents'              => array(
				'id'       => 'conv_contents',
				'title'    => __( 'Post content', 'wp-parsidate' ),
				'type'     => 'toggle',
				'default'  => false,
				'sanitize' => 'bool'
			),
			'conv_excerpt'               => array(
				'id'       => 'conv_excerpt',
				'title'    => __( 'Post excerpt', 'wp-parsidate' ),
				'type'     => 'toggle',
				'default'  => false,
				'sanitize' => 'bool'
			),
			'conv_comments'              => array(
				'id'       => 'conv_comments',
				'title'    => __( 'Comments text', 'wp-parsidate' ),
				'type'     => 'toggle',
				'default'  => false,
				'sanitize' => 'bool'
			),
			'conv_comment_count'         => array(
				'id'       => 'conv_comment_count',
				'title'    => __( 'Comments count', 'wp-parsidate' ),
				'type'     => 'toggle',
				'default'  => false,
				'sanitize' => 'bool'
			),
			'conv_dates'                 => array(
				'id'       => 'conv_dates',
				'title'    => __( 'Dates', 'wp-parsidate' ),
				'type'     => 'toggle',
				'default'  => false,
				'sanitize' => 'bool'
			),
			'conv_cats'                  => array(
				'id'       => 'conv_cats',
				'title'    => __( 'Categories', 'wp-parsidate' ),
				'type'     => 'toggle',
				'default'  => false,
				'sanitize' => 'bool'
			),
			'end_grid_persian_numbers'   => array(
				'type' => 'endGrid',
			),

			// Letters
			'start_grid_letters'         => array(
				'title' => __( 'Letters', 'wp-parsidate' ),
				'type'  => 'startGrid',
			),
			'conv_arabic'                => array(
				'id'       => 'conv_arabic',
				'title'    => __( 'Fix arabic characters', 'wp-parsidate' ),
				'type'     => 'toggle',
				'default'  => false,
				'desc'     => __( 'Fixes arabic characters caused by wrong keyboard layouts', 'wp-parsidate' ),
				'sanitize' => 'bool'
			),
			'end_grid_letters'           => array(
				'type' => 'endGrid',
			),

			// Permalinks
			'start_grid_permalinks'      => array(
				'title' => __( 'Permalinks', 'wp-parsidate' ),
				'type'  => 'startGrid',
			),
			'conv_permalinks'            => array(
				'id'       => 'conv_permalinks',
				'title'    => __( 'Fix permalinks dates', 'wp-parsidate' ),
				'type'     => 'toggle',
				'default'  => false,
				'desc'     => __( 'By enabling this, dates in permalinks converted to Shamsi (Jalali) date',
					'wp-parsidate' ),
				'sanitize' => 'bool'
			),
			'end_grid_permalinks'        => array(
				'type' => 'endGrid',
			),
		);
	}
}