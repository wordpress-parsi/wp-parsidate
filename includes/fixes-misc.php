<?php
/**
 * Fixes numbers in selected options
 *
 * @author          Mobin Ghasempoor
 * @author          Ehsaan
 * @package         WP-Parsidate
 * @subpackage      Fixes/NumbersAndArabic
 */

global $wpp_settings;

if ( $wpp_settings['conv_page_title'] != 'disable' )
    add_filter( 'wp_title', 'fixnumber' );

if ( $wpp_settings['conv_title'] != 'disable' )
    add_filter( 'the_title', 'fixnumber' );

if ( $wpp_settings['conv_contents'] != 'disable' )
    add_filter( 'the_content', 'fixnumber' );

if ( $wpp_settings['conv_excerpt'] != 'disable' )
    add_filter( 'the_excerpt', 'fixnumber' );

if ( $wpp_settings['conv_comments'] != 'disable' )
    add_filter( 'comment_text', 'fixnumber' );

if ( $wpp_settings['conv_comment_count'] != 'disable' )
    add_filter( 'comments_number', 'fixnumber' );

if ( $wpp_settings['conv_cats'] != 'disable' )
    add_filter( 'wp_list_categories', 'fixnumber' );

if ( $wpp_settings['conv_arabic'] != 'disable' ) {
    add_filter( 'the_content', 'fixarabic' );
    add_filter( 'the_title', 'fixarabic' );
    add_filter( 'comment_text', 'fixarabic' );
    add_filter( 'wp_list_categories', 'fixarabic' );
    add_filter( 'the_excerpt', 'fixarabic' );
}
