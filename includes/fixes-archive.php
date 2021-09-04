<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

/**
 * Fixes archives and make them compatible with Shamsi date
 *
 * @package                 WP-Parsidate
 * @subpackage              Fixes/Archives
 * @author                  Mobin Ghasempoor
 */


/**
 * Fixes titles for archives
 *
 * @param string $title Archive title
 * @param string $sep Separator
 * @param string $sep_location Separator location
 *
 * @return                  string New archive title
 */
function wpp_fix_title( $title, $sep = '-', $sep_location = 'right' ) {
	global $persian_month_names, $wp_query, $wpp_settings;

	$query = $wp_query->query;

	if ( ! is_archive() || ! wpp_is_active( 'persian_date' ) ) {
		return $title;
	}

	if ( $sep_location == 'right' ) {
		$query = array_reverse( $query );
	}

	if ( isset( $query['monthnum'] ) ) {
		$query['monthnum'] = $persian_month_names[ intval( $query['monthnum'] ) ];
		$title             = implode( " ", $query ) . " $sep " . get_bloginfo( "name" );
	}

	if ( wpp_is_active( 'conv_page_title' ) ) {
		$title = fix_number( $title );
	}

	return $title;
}

add_filter( 'wp_title', 'wpp_fix_title', PHP_INT_MAX, 2 );
add_filter( 'pre_get_document_title', 'wpp_fix_title', PHP_INT_MAX ); // WP 4.4+