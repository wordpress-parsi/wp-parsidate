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
    add_filter('the_time', 'wpp_fix_post_time', 10, 2);
    add_filter('the_date', 'wpp_fix_post_date', 10, 2);
    add_filter('get_comment_time', 'wpp_fix_comment_time', 10, 2);
    add_filter('get_comment_date', 'wpp_fix_comment_date', 10, 2);
    //add_filter( 'get_post_modified_time', 'wpp_fix_post_date', 10, 2 );

    add_action('date_i18n', 'wpp_fix_i18n', 10, 3);
} else {
    /*remove_filter('the_time', 'wpp_fix_post_time', 1001);
    remove_filter('the_date', 'wpp_fix_post_date', 1001);
    remove_filter('get_comment_time', 'wpp_fix_comment_time', 1001);
    remove_filter('get_comment_date', 'wpp_fix_comment_date', 1001);
    remove_filter('date_i18n', 'wpp_fix_i18n', 1001);*/
}

/**
 * Fixes post date and returns in Jalali format
 *
 * @param           string $time Post time
 * @param           string $format Date format
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

    return parsidate($format, $post->post_date, $wpp_settings['conv_dates'] == 'disable' ? 'eng' : 'per');
}

/**
 * Fixes post time and returns in Jalali format
 *
 * @param           string $time Post time
 * @param           string $format Date format
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

    return parsidate($format, $post->post_date, $wpp_settings['conv_dates'] == 'disable' ? 'eng' : 'per');
}

/**
 * Fixes comment time and returns in Jalali format
 *
 * @param           string $time Comment time
 * @param           string $fomat Date format
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

    return parsidate($format, $comment->comment_date, $wpp_settings['conv_dates'] == 'disable' ? 'eng' : 'per');
}

/**
 * Fixes comment date and returns in Jalali format
 *
 * @param           string $time Comment time
 * @param           string $format Date format
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

    return parsidate($format, $comment->comment_date, $wpp_settings['conv_dates'] == 'disable' ? 'eng' : 'per');
}

/**
 * Fixes i18n date formatting and convert them to Jalali
 *
 * @param           string $format_string Date format
 * @param           string $timestamp Unix timestamp
 * @param           string $gmt GMT timestamp
 *
 * @return          string Formatted time
 */
function wpp_fix_i18n($format_string, $timestamp, $gmt)
{
    global $wpp_settings;

    if (function_exists('debug_backtrace')) {
        $callers = debug_backtrace();

        // WordPress SEO OpenGraph Dates fix
        if (
            (isset($callers[6]['class']) && $callers[6]['class'] == 'WPSEO_OpenGraph') ||
            (isset($callers[6]['function']) && $callers[6]['function'] == 'get_the_modified_date')
        )
            return $format_string;

        // WooCommerce (Order & Product MetaBox)
        if (
            (isset($callers['4']['class']) && $callers['4']['class'] == 'WC_Meta_Box_Order_Data') ||
            (isset($callers['5']['class']) && $callers['5']['class'] == 'WC_Meta_Box_Product_Data')
        )
            return $format_string;

    }

    return parsidate($timestamp, $gmt, $wpp_settings['conv_dates'] == 'disable' ? 'eng' : 'per');
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