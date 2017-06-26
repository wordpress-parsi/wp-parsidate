<?php
/**
 * WP-Parsidate general functions
 *
 * @author             Mobin Ghasempoor
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
 * @author
 *
 * @param    String $locale
 *
 * @return  String
 */
function wp_parsi_set_locale($locale)
{
    global $locale;
    $settings = get_option('wpp_settings');
    $user_locale = $admin_locale = $locale;
    
    if ($settings['admin_lang'] == 'enable') {
        $admin_locale = "fa_IR";
    } elseif ($locale == 'fa_IR' && $settings['admin_lang'] == 'disable') {
        $admin_locale = "en_US";
    }
    if ($settings['user_lang'] == 'enable') {
        $user_locale = "fa_IR";
    } elseif ($locale == 'fa_IR' && $settings['user_lang'] == 'disable') {
        $user_locale = "en_US";
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
 * @since               1.0
 * @return              bool True when page is feed, false when page isn't feed
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
 * @param           string $number Numbers
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
 * @param           string $number Numbers
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
 * @param           string $content Post content
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
 * @param           string $content
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
 * @param           string $content
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
 * @author          Ehsaan
 * @return          void
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
 * @author          Ehsaan
 * @return          void
 */
function wpp_dismiss_notice_action()
{
    if (isset($_GET['wpp-action']) && $_GET['wpp-action'] == 'dismiss-notice') {
        update_option('wpp_dismissed', true);
    }
}