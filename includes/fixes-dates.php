<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

/**
 * Fixes dates and convert them to Jalali date.
 *
 * @author              Mobin Ghasempoor
 * @package             WP-Parsidate
 * @subpackage          Fixes/Dates
 */

global $wpp_settings;

if ( get_locale() === 'fa_IR' && wpp_is_active( 'persian_date' ) ) {
	add_filter( 'the_time', 'wpp_fix_post_time', 10, 2 );
	add_filter( 'the_date', 'wpp_fix_post_date', 10, 3 );
	add_filter( 'get_the_time', 'wpp_fix_post_time', 10, 2 );
	add_filter( 'get_the_date', 'wpp_fix_post_date', 100, 3 );
	add_filter( 'get_the_modified_date', 'wpp_fix_modified_date', 10, 3 );
	add_filter( 'get_comment_time', 'wpp_fix_comment_time', 10, 2 );
	add_filter( 'get_comment_date', 'wpp_fix_comment_date', 10, 3 );
	//add_filter('get_post_modified_time', 'wpp_fix_post_modified_time', 10, 3);
	add_filter( 'date_i18n', 'wpp_fix_i18n', 10, 4 );
    add_filter( 'media_view_settings', 'wpp_fix_media_view_settings', 10, 2 );

	if ( ! wpp_is_sitemap() ) {
		add_filter( 'wp_date', 'wpp_fix_i18n', 10, 4 );
	}

}

/**
 * Fixes post date and returns to Jalali format
 *
 * @param string $time Post time
 * @param string $format Date format
 *
 * @return          string Formatted date
 */
function wpp_fix_post_date( $time, $format = '', $post = null ) {
	if ( null === $post ) {
		global $post;
	} else {
		$post = get_post( $post );
	}

	// It seems some plugin like acf does not exist $post.
	if ( ! $post ) {
		return $time;
	}
	if (function_exists( 'pll_current_language' ) && pll_current_language() !== "fa"  ) {
		return $time;
	}
	if ( empty( $format ) ) {
		$format = get_option( 'date_format' );
	}

	if ( 'c' === $format || ! disable_wpp() ) {
		return date( $format, strtotime( $post->post_date ) );
	}

	return parsidate( $format, date( 'Y-m-d H:i:s', strtotime( $post->post_date ) ), ! wpp_is_active( 'conv_dates' ) ? 'eng' : 'per' );
}

/**
 * Fixes post modified date and returns to Jalali format
 *
 * @param string $time Post modified time
 * @param string $format Date format
 * @param WP_Post|null	$post	WP_Post object or null if no post is found.
 *
 * @return string Formatted date
 * @author Yousef Mahmoudi
 */
function wpp_fix_modified_date( $time, $format, $post ) {
	if ( empty( $post ) ) {
		return $time;
	}
	
	if ( 'c' === $format ) {
		return date( $format, strtotime( $post->post_modified ) );
	}
	
	return $time;
}

/**
 * Fixes post date and returns to Jalali format
 *
 * @param string $time Post time
 * @param string $format Date format
 * @param bool $gmt retrieve the GMT time. Default false.
 *
 * @return  string Formatted date
 * @author  Parsa Kafi
 */
function wpp_fix_post_modified_time( $time, $format, $gmt ) {
	if ( ! disable_wpp() ) {
		return $time;
	}

	return parsidate( $format, $time, ! wpp_is_active( 'conv_dates' ) ? 'eng' : 'per' );
}

/**
 * Fixes post time and returns to Jalali format
 *
 * @param string $time Post time
 * @param string $format Date format
 *
 * @return          string Formatted date
 */
function wpp_fix_post_time( $time, $format = '', $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		global $post;
	}

	if ( empty( $post ) ) {
		return $time;
	}

	if ( empty( $format ) ) {
		$format = get_option( 'time_format' );
	}

	if ( ! disable_wpp() ) {
		return date( $format, strtotime( $post->post_date ) );
	}

	return parsidate( $format, $post->post_date, wpp_is_active( 'conv_dates' ) ? 'eng' : 'per' );
}

/**
 * Fixes comment time and returns to Jalali format
 *
 * @param string $time Comment time
 * @param string $format Date format
 *
 * @return          string Formatted date
 */
function wpp_fix_comment_time( $time, $format = '' ) {
	global $comment;

	if ( empty( $comment ) ) {
		return $time;
	}

	if ( empty( $format ) ) {
		$format = get_option( 'time_format' );
	}
	if ( ! disable_wpp() ) {
		return date( $format, strtotime( $comment->comment_date ) );
	}

	return parsidate( $format, $comment->comment_date, ! wpp_is_active( 'conv_dates' ) ? 'eng' : 'per' );
}

/**
 * Fixes comment date and returns in Jalali format
 *
 * @param string|int $comment_date Formatted date string or Unix timestamp.
 * @param string $format PHP date format.
 * @param \WP_Comment $comment The comment object.
 */
function wpp_fix_comment_date($comment_date, $format, $comment)
{
    if (empty($comment)) {
        return $comment_date;
    }

    if (empty($format)) {
        $format = get_option('date_format');
    }
    if ('c' === $format || !disable_wpp()) {
        return date($format, strtotime($comment->comment_date));
    }

    return parsidate($format, $comment->comment_date, !wpp_is_active('conv_dates') ? 'eng' : 'per');
}

/**
 * Fixes i18n date formatting and convert them to Jalali
 *
 * @param string $date Formatted date string.
 * @param string $format Format to display the date.
 * @param int $timestamp A sum of Unix timestamp and timezone offset in seconds.
 *                          Might be without offset if input omitted timestamp but requested GMT.
 * @param bool $gmt Whether to use GMT timezone. Only applies if timestamp was not provided.
 *                          Default false.
 *
 * @return          string Formatted time
 */
function wpp_fix_i18n( $date, $format, $timestamp, $gmt ) {
	global $post;

	if((function_exists( 'pll_current_language' ) && pll_current_language() !== "fa" )){
		return $date;
	}

	if ( ! disable_wpp() ) {
		return $format;
	}

	return parsidate( $format, $timestamp, ! wpp_is_active( 'conv_dates' ) ? 'eng' : 'per' );
}

/**
 * Convert date to Jalali
 *
 * @param $date
 * @param $format
 * @param $timestamp
 * @param $timezone
 *
 * @return int|mixed|string
 */
function wpp_fix_wp_date( $date, $format, $timestamp, $timezone ) {
	if ( ! disable_wpp() ) {
		return $format;
	}

	return parsidate( $format, $timestamp, ! wpp_is_active( 'conv_dates' ) ? 'eng' : 'per' );
}

function array_key_exists_r( $needle, $haystack, $value = null ) {
	$result = array_key_exists( $needle, $haystack );

	if ( $result ) {
		if ( $value != null && $haystack[ $needle ] ) {
			return 1;
		}

		return true;
	}

	foreach ( $haystack as $v ) {
		if ( is_array( $v ) || is_object( $v ) ) {
			$result = array_key_exists_r( $needle, $v );
		}

		if ( $result ) {
			return $result;
		}
	}

	return $result;
}

/**
 * Fixes Media view Select box and returns in Jalali Format Date
 *
 * @param   array   $settings List of media view settings.
 * @param   WP_Post $post     Post object.
 *
 * @return  array _wpMediaViewsL10n localize script in WordPress
 * @author  Mehrshad Darzi
 */
function wpp_fix_media_view_settings($settings, $post)
{
    global $wpp_settings;

    if (isset($settings['months']) and !empty($settings['months'])) {
        for ($i = 0; $i < count($settings['months']); $i++) {
            if (isset($settings['months'][$i]->year) and isset($settings['months'][$i]->month)) {
                $conv_dates_status = 'eng';
                if (isset($wpp_settings['conv_dates']) && $wpp_settings['conv_dates'] != 'disable') {
                    $conv_dates_status = 'per';
                }
                $settings['months'][$i]->text = parsidate(
                    "F Y",
                    $settings['months'][$i]->year . '-' . $settings['months'][$i]->month,
                    $conv_dates_status
                );
            }
        }
    }

    return $settings;
}