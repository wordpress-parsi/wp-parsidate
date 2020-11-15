<?php
/**
 * WP-Parsidate general functions
 *
 * @author             Mobin Ghasempoor
 * @author             Morteza Geransayeh
 * @author             Ehsaan
 * @package            WP-Parsidate
 * @subpackage         Core/General
 */
global $wpp_settings;

add_filter('login_headerurl', 'wpp_login_headerurl', 10, 2);
add_filter('locale', 'wp_parsi_set_locale', 0);
add_action('admin_notices', 'wpp_activation_notice');
add_action('admin_init', 'wpp_dismiss_notice_action');

/**
 * Change Locale WordPress Admin and Front-end user
 * @param String $locale
 *
 * @return  String
 * @author
 *
 */
function wp_parsi_set_locale($locale)
{
    global $locale;
    $settings = get_option('wpp_settings');
    $user_locale = $admin_locale = $locale;

    if ($settings['admin_lang'] == 'enable') {
        $admin_locale = "fa_IR";
    } elseif ($settings['admin_lang'] == 'disable') {
        $admin_locale = $locale;
    }
    if ($settings['user_lang'] == 'enable') {
        $user_locale = "fa_IR";
    } elseif ($settings['user_lang'] == 'disable') {
        $user_locale = $locale;
    }

    $locale_s = is_admin() ? $admin_locale : $user_locale;

    if (!empty($locale_s)) {
        $locale = $locale_s;
    }

    setlocale(LC_ALL, $locale);

    return $locale;
}


/**
 * Detects current page is feed or not
 *
 * @return              bool True when page is feed, false when page isn't feed
 * @since               1.0
 */
function wpp_is_feed()
{
    if (is_feed()) {
        return true;
    }

    $path = $_SERVER['REQUEST_URI'];
    $exts = array('xml', 'gz', 'xsl');
    $ext = pathinfo($path, PATHINFO_EXTENSION);

    return in_array($ext, $exts);
}

/**
 * Converts English digits to Persian digits
 *
 * @param string $number Numbers
 *
 * @return          string Formatted numbers
 */
function per_number($number)
{
    return str_replace(
        range(0, 9),
        array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'),
        $number
    );
}

/**
 * Converts Persian digits to English digits
 *
 * @param string $number Numbers
 *
 * @return          string Formatted numbers
 */
function eng_number($number)
{
    return str_replace(
        array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'),
        range(0, 9),
        $number
    );
}

/**
 * Converts English numbers to Persian numbers in post contents
 *
 * @param string $content Post content
 *
 * @return          string Formatted content
 */
function persian_number($content)
{
    return (
    isset($content[1]) ? per_number($content[1]) : $content[0]
    );
}

/**
 * Fix numbers and convert them to Persian digits style
 *
 * @param string $content
 *
 * @return          mixed
 */
function fixnumber($content)
{
    return preg_replace_callback('/(?:&#\d{2,4};)|(?:[0]?[a-z][\x20-\x3B=\x3F-\x7F]*)|(\d+[\.\d]*)|<\s*[^>]+>/i', 'persian_number', $content);
}

/**
 * Fix arabic foreign characters
 *
 * @param string $content
 *
 * @return          mixed
 */
function fixarabic($content)
{
    return str_replace(array('ي', 'ك', '٤', '٥', '٦', 'ة'), array('ی', 'ک', '۴', '۵', '۶', 'ه'), $content);
}

/**
 * Change login header url in wp-login.php
 *
 * @return          string
 */
function wpp_login_headerurl()
{
    return 'http://wp-parsi.com';
}

/**
 * Notice for the activation.
 * Added dismiss feature.
 *
 * @return          void
 * @author          Ehsaan
 */
function wpp_activation_notice()
{
    $dismissed = get_option('wpp_dismissed', false);

    if (!$dismissed) {
        global $wpp_settings;

        if ($wpp_settings['persian_date'] != 'enable') {
            $output = sprintf(__('<div class="updated wpp-message"><p>ParsiDate activated, you may need to configure it to work properly. <a href="%s">Go to configuartion page</a> &ndash; <a href="%s">Dismiss</a></p></div>', 'wp-parsidate'), admin_url('admin.php?page=wp-parsi-settings'), add_query_arg('wpp-action', 'dismiss-notice'));
            echo $output;
        }
    }
}

/**
 * Dismiss the notice action
 *
 * @return          void
 * @author          Ehsaan
 */
function wpp_dismiss_notice_action()
{
    if (isset($_GET['wpp-action']) && $_GET['wpp-action'] == 'dismiss-notice') {
        update_option('wpp_dismissed', true);
    }
}

/**
 * parsidate_check_format()
 * checks format for iso definitions
 *
 * @param  string  $format
 *
 * @return boolean
 */
function parsidate_check_format( $format ) {
	return in_array( $format, array(
		'Z', // Timezone offset in seconds // -43200 through 50400
		'T', // Timezone abbreviation // Examples: EST, MDT
		'O', // Difference to Greenwich time (GMT) in hours // Example: +0200
		'P', // Difference to Greenwich time (GMT) with colon between hours and minutes // Example: +02:00
		'U', // Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)
		'u', // Microseconds // Example: 654321
		'e', // Timezone identifier // Examples: UTC, GMT, Atlantic/Azores
		'r', // RFC 2822 formatted date // Example: Thu, 21 Dec 2000 16:01:07 +0200
		'c', // ISO 8601 date // 2004-02-12T15:19:21+00:00 // 'Y-m-d\TH:i:s\Z'
		'G', // 24-hour format of an hour without leading zeros // 0 through 23
		'I', // Whether or not the date is in daylight saving time // 1 if Daylight Saving Time, 0 otherwise.

		// Commented this lines, because user/system want to convert these formats.
		/*'Y-m-d_H-i-s',
		'Y-m-d_G-i-s',
		'Y-m-d H:i:s',
		'Y-m-d G:i:s',
		'd-M-Y H:i',*/

		DATE_W3C, // eq `c`
		DATE_ISO8601, // eq `c`
		DATE_RFC2822, // eq `r`
		'Y-m-d\TH:i:s+00:00', // eq `DATE_W3C` @SEE: http://jochenhebbrecht.be/site/node/761
		'Y-m-d\TH:i:sP',
	) );
}
