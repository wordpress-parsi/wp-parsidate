<?php
/**
 * Fixes dates and convert them to Jalali date.
 *
 * @author              Mobin Ghasempoor
 * @package             WP-Parsidate
 * @subpackage          Fixes/Dates
 */

global $wpp_settings;

if ( $wpp_settings['persian_date'] != 'disable' ) {
    add_filter( 'the_time', 'wpp_fix_post_time', 10, 2 );
    add_filter( 'the_date', 'wpp_fix_post_date', 10, 2 );
    add_filter( 'get_comment_time', 'wpp_fix_comment_time', 10, 2 );
    add_filter( 'get_comment_date', 'wpp_fix_comment_date', 10, 2 );
    add_action( 'date_i18n', 'wpp_fix_i18n', 10, 3 );
}

/**
 * Fixes post date and returns in Jalali format
 *
 * @param           string $time Post time
 * @param           string $format Date format
 * @return          string Formatted date
 */
function wpp_fix_post_date( $time, $format = '' ) {
    global $post, $wpp_settings;
    if ( empty( $format ) )
        $format = get_option( 'date_format' );

    if ( $wpp_settings['conv_dates'] == 'disable' )
        return parsidate( $format, $post->post_date, 'eng' );
    else
        return parsidate( $format, $post->post_date );
}

/**
 * Fixes post time and returns in Jalali format
 *
 * @param           string $time Post time
 * @param           string $format Date format
 * @return          string Formatted date
 */
function wpp_fix_post_time( $time, $format = '' ) {
    global $post, $wpp_settings;
    if ( empty( $format ) )
        $format = get_option( 'time_format' );

    if ( $wpp_settings['conv_dates'] == 'disable' )
        return parsidate( $format, $post->post_date, 'eng' );
    else
        return parsidate( $format, $post->post_date );
}

/**
 * Fixes comment time and returns in Jalali format
 *
 * @param           string $time Comment time
 * @param           string $fomat Date format
 * @return          string Formatted date
 */
function wpp_fix_comment_time( $time, $format = '' ) {
    global $comment, $wpp_settings;
    if ( empty( $format ) )
        $format = get_option( 'time_format' );

    if ( $wpp_settings['conv_dates'] == 'disable' )
        return parsidate( $format, $comment->comment_date, 'eng' );
    else
        return parsidate( $format, $comment->comment_date );
}

/**
 * Fixes comment date and returns in Jalali format
 *
 * @param           string $time Comment time
 * @param           string $fomat Date format
 * @return          string Formatted date
 */
function wpp_fix_comment_date( $time, $format = '' ) {
    global $comment, $wpp_settings;
    if ( empty( $format ) )
        $format = get_option( 'date_format' );

    if ( $wpp_settings['conv_dates'] == 'disable' )
        return parsidate( $format, $comment->comment_date, 'eng' );
    else
        return parsidate( $format, $comment->comment_date );
}

/**
 * Fixes i18n date formatting and convert them to Jalali
 *
 * @param           string $format_string Date format
 * @param           string $timestamp Unix timestamp
 * @param           string $gmt GMT timestamp
 * @return          string Formatted time
 */
function wpp_fix_i18n( $format_string, $timestamp, $gmt ) {
    global $wpp_settings;

    if(function_exists('debug_backtrace')) {
        $callers = debug_backtrace();

        // WordPress SEO OpenGraph Dates fix
        if (isset($callers[6]['class']) && $callers[6]['class'] == 'WPSEO_OpenGraph') return $format_string;
        if (isset($callers[6]['function']) && $callers[6]['function'] == 'get_the_modified_date') return $format_string;

        // WooCommerce order detail fix
        if(isset($callers['4']['class']) && $callers['4']['class']=='WC_Meta_Box_Order_Data') return $format_string;
    }

    if ( $wpp_settings['conv_dates'] == 'disable' )
        return parsidate( $timestamp, $gmt, 'eng' );
    else
        return parsidate( $timestamp, $gmt );
}
