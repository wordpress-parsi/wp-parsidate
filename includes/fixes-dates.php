<?php
/**
 * Fixes dates and convert them to Jalali date.
 *
 * @author              Mobin Ghasempoor
 * @package             WP-Parsidate
 * @subpackage          Fixes/Dates
 */

global $wpp_settings;

if (get_locale() == 'fa_IR' && $wpp_settings['persian_date'] != 'disable') {
    // add_filter('the_time', 'wpp_fix_post_time', 10, 2); // no need, instead we filter `get_the_time`
    // add_filter('the_date', 'wpp_fix_post_date', 10, 2); // no need, instead we filter `get_the_date`
    add_filter('get_the_time', 'wpp_fix_get_the_time', 10, 3);
    add_filter('get_the_date', 'wpp_fix_get_the_date', 10, 3);
    add_filter('get_comment_time', 'wpp_fix_comment_time', 10, 2);
    add_filter('get_comment_date', 'wpp_fix_comment_date', 10, 2);
    //add_filter('get_post_modified_time', 'wpp_fix_post_modified_time', 10, 3);
    add_filter('date_i18n', 'wpp_fix_i18n', 10, 4);
    add_filter('wp_date', 'wpp_fix_i18n', 10, 4);
    add_filter( 'media_view_settings', 'wpp_fix_media_view_settings', 10, 2 );
}

/**
 * Fixes post date and returns in Jalali format
 *
 * @param string $time Post time
 * @param string $format Date format
 *
 * @return          string Formatted date
 */
function wpp_fix_post_date($time, $format = '')
{
    global $post, $wpp_settings;

    // It's seems some plugin like acf does not exits $post.
    if (empty($post)) {
        return $time;
    }

    if (empty($format)) {
        $format = get_option('date_format');
    }

    if (!disable_wpp())
        return date($format, strtotime($post->post_modified));

    return parsidate($format, $post->post_date, $wpp_settings['conv_dates'] == 'disable' ? 'eng' : 'per');
}

/**
 * Filters the `get_the_date` hook in order to convert post date into Jalali.
 *
 * @param string $the_date The formatted date.
 * @param string $d PHP date format. Defaults to 'date_format' option if not
 *                  specified.
 * @param int|WP_Post $post The post object or ID.
 *
 * @return          string Formatted date
 */
function wpp_fix_get_the_date($the_date, $d, $post)
{
    global $wpp_settings;

    if (!disable_wpp())
        return $the_date;

    $post = get_post( $post );

    if ( ! $post ) {
        return $the_date;
    }

    if ( '' == $d ) {
        $d = get_option('date_format');
    }

    return parsidate($d, $post->post_date, $wpp_settings['conv_dates'] == 'disable' ? 'eng' : 'per');
}

/**
 * Filters the `get_the_time` hook in order to convert post time into Jalali.
 *
 * @param string $the_time The formatted time.
 * @param string $d Format to use for retrieving the time the post was written.
 *                  Accepts 'G', 'U', or php date format value specified in
 *                  'time_format' option. Default empty.
 * @param int|WP_Post $post WP_Post object or ID.
 *
 * @return          string Formatted time
 */
function wpp_fix_get_the_time($the_time, $d, $post)
{
    global $wpp_settings;

    if (!disable_wpp())
        return $the_time;

    $post = get_post( $post );

    if ( ! $post ) {
        return $the_time;
    }

    if ( '' == $d ) {
        $d = get_option('time_format');
    }

    return parsidate($d, $post->post_date, $wpp_settings['conv_dates'] == 'disable' ? 'eng' : 'per');
}

/**
 * Fixes post date and returns in Jalali format
 *
 * @param   string $time Post time
 * @param   string $format Date format
 * @param   bool $gmt retrieve the GMT time. Default false.
 *
 * @return  string Formatted date
 * @author  Parsa Kafi
 */
function wpp_fix_post_modified_time($time, $format, $gmt)
{
    global $wpp_settings;

    if (!disable_wpp())
        return $time;

    return parsidate($format, $time, $wpp_settings['conv_dates'] == 'disable' ? 'eng' : 'per');
}

/**
 * Fixes post time and returns in Jalali format
 *
 * @param string $time Post time
 * @param string $format Date format
 *
 * @return          string Formatted date
 */
function wpp_fix_post_time($time, $format = '')
{
    global $post, $wpp_settings;

    if (empty($post)) {
        return $time;
    }

    if (empty($format)) {
        $format = get_option('time_format');
    }
    if (!disable_wpp())
        return date($format, strtotime($post->post_date));
    return parsidate($format, $post->post_date, $wpp_settings['conv_dates'] == 'disable' ? 'eng' : 'per');
}

/**
 * Fixes comment time and returns in Jalali format
 *
 * @param string $time Comment time
 * @param string $format Date format
 *
 * @return          string Formatted date
 */
function wpp_fix_comment_time($time, $format = '')
{
    global $comment, $wpp_settings;

    if (empty($comment)) {
        return $time;
    }

    if (empty($format)) {
        $format = get_option('time_format');
    }
    if (!disable_wpp())
        return date($format, strtotime($comment->comment_date));
    return parsidate($format, $comment->comment_date, $wpp_settings['conv_dates'] == 'disable' ? 'eng' : 'per');
}

/**
 * Fixes comment date and returns in Jalali format
 *
 * @param string $time Comment time
 * @param string $format Date format
 *
 * @return          string Formatted date
 */
function wpp_fix_comment_date($time, $format = '')
{
    global $comment, $wpp_settings;

    if (empty($comment)) {
        return $time;
    }

    if (empty($format)) {
        $format = get_option('date_format');
    }
    if (!disable_wpp())
        return date($format, strtotime($comment->comment_date));
    return parsidate($format, $comment->comment_date, $wpp_settings['conv_dates'] == 'disable' ? 'eng' : 'per');
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
function wpp_fix_i18n($date, $format, $timestamp, $gmt)
{
    global $wpp_settings, $post;
    $post_id = !empty($post) ? $post->ID : null;

    if (!disable_wpp())
        return $format;

    if ($post_id != null && get_post_type($post_id) == 'shop_order' && isset($_GET['post'])) // TODO: Remove after implement convert date for woocommerce
        return $date;
    else
        return parsidate($format, $timestamp, $wpp_settings['conv_dates'] == 'disable' ? 'eng' : 'per');
}

function wpp_fix_wp_date($date, $format, $timestamp, $timezone)
{
    global $wpp_settings;

    if (!disable_wpp())
        return $format;

    return parsidate($format, $timestamp, $wpp_settings['conv_dates'] == 'disable' ? 'eng' : 'per');
}

function array_key_exists_r($needle, $haystack, $value = null)
{
    $result = array_key_exists($needle, $haystack);
    if ($result) {
        if ($value != null && $haystack[$needle])
            return 1;
        return $result;
    }
    foreach ($haystack as $v) {
        if (is_array($v) || is_object($v))
            $result = array_key_exists_r($needle, $v);
        if ($result)
            return $result;
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
function wpp_fix_media_view_settings( $settings, $post )
{
	global $wpp_settings;

	if ( isset( $settings['months'] ) and ! empty( $settings['months'] ) ) {
		for ( $i = 0; $i < count( $settings['months'] ); $i ++ ) {
			if ( isset( $settings['months'][ $i ]->year ) and isset( $settings['months'][ $i ]->month ) ) {
				$settings['months'][ $i ]->text = parsidate( "F Y", $settings['months'][ $i ]->year . '-' . $settings['months'][ $i ]->month, $wpp_settings['conv_dates'] == 'disable' ? 'eng' : 'per' );
			}
		}
	}

	return $settings;
}
