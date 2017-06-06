<?php
/**
 * Fixes archives and make them compatible with Shamsi date
 *
 * @package                 WP-Parsidate
 * @subpackage              Fixes/Archives
 * @author                  Mobin Ghasempoor
 */

add_filter( 'wp_title', 'wpp_fix_title', 10000, 2 );
add_filter( 'pre_get_document_title', 'wpp_fix_title', 10000 ); // WP 4.4+

/**
 * Fixes titles for archives
 *
 * @param                   string $title Archive title
 * @param                   string $sep Separator
 * @param                   string $sep_location Separator location
 *
 * @return                  string New archive title
 */
function wpp_fix_title( $title, $sep = '-', $sep_location = 'right' ) {
	global $persian_month_names, $wp_query, $wpp_settings;
	$query = $wp_query->query;

	if ( ! is_archive() || $wpp_settings['persian_date'] == 'disable' ) {
		return $title;
	}

	if ( $sep_location == 'right' ) {
		$query = array_reverse( $query );
	}

	if ( isset( $query['monthnum'] ) ) {
		$query['monthnum'] = $persian_month_names[ intval( $query['monthnum'] ) ];
		$title = implode( " ", $query ) . " $sep " . get_bloginfo( "name" );
	}

	if ( $wpp_settings['conv_page_title'] != 'disable' ) {
		$title = fixnumber( $title );
	}

	return $title;
}
