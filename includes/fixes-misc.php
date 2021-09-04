<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

/**
 * Fixes numbers in selected options
 *
 * @author          Mobin Ghasempoor
 * @author          Ehsaan
 * @package         WP-Parsidate
 * @subpackage      Fixes/NumbersAndArabic
 */

if ( get_locale() == 'fa_IR' ) {
	global $wpp_settings;

	if ( wpp_is_active( 'conv_page_title' ) ) {
		add_filter( 'wp_title', 'fix_number', 1000 );
	}

	if ( wpp_is_active( 'conv_title' ) ) {
		add_filter( 'the_title', 'fix_number', 1000 );
	}

	if ( wpp_is_active( 'conv_contents' ) ) {
		add_filter( 'the_content', 'fix_number', 1000 );
	}

	if ( wpp_is_active( 'conv_excerpt' ) ) {
		add_filter( 'the_excerpt', 'fix_number', 1000 );
	}

	if ( wpp_is_active( 'conv_comments' ) ) {
		add_filter( 'comment_text', 'fix_number', 1000 );
	}

	if ( wpp_is_active( 'conv_comment_count' ) ) {
		add_filter( 'comments_number', 'fix_number', 1000 );
	}

	if ( wpp_is_active( 'conv_cats' ) ) {
		add_filter( 'wp_list_categories', 'fix_number', 1000 );
	}

	if ( wpp_is_active( 'conv_arabic' ) ) {
		add_filter( 'the_content', 'fix_arabic', 1000 );
		add_filter( 'the_title', 'fix_arabic', 1000 );
		add_filter( 'comment_text', 'fix_arabic', 1000 );
		add_filter( 'wp_list_categories', 'fix_arabic', 1000 );
		add_filter( 'the_excerpt', 'fix_arabic', 1000 );
		add_filter( 'wp_title', 'fix_arabic', 1000 );
	}
}