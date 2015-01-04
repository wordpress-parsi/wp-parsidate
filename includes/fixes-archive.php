<?php
/**
 * Fixes archives and make them compatible with Shamsi date
 *
 * @package                 WP-Parsidate
 * @subpackage              Fixes/Archives
 * @author                  Mobin Ghasempoor
 */

add_filter('wp_title','wpp_fix_title');

/**
 * Fixes titles for archives
 *
 * @param                   string $title Archive title
 * @param                   string $sep Seperator
 * @param                   string $seplocation Seperator location
 * @return                  string New archive title
 */
function wpp_fix_title( $title, $sep = '»', $seplocation = 'right' ) {
    global $persian_month_names, $wp_query, $wpp_settings;
    $query = $wp_query->query;

    if ( ! is_archive() || ( is_archive() && ! isset( $query['monthnum'] )) || ( $wpp_settings['persian_date'] == 'disable' ) )
        return $title;

    if ( $seplocation == 'right' )
        $query = array_reverse( $query );

    $query['monthnum'] = $persian_month_names[intval( $query['monthnum'] )];

    $title =  implode( " $sep ", $query ) . " $sep ";

    if ( $wpp_settings['conv_page_title'] != 'disable' )
        $title = fixnumber($title);

    return $title;
}
