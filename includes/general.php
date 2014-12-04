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
update_option( 'WPLANG', 'fa_IR' );

function wpp_change_locale() {
    return 'fa_IR';
}
if ( $wpp_settings['persian_lang'] != 'disable' ) {
    add_filter( 'locale', 'wpp_change_locale', 1 );
    add_filter( 'plugin_locale', 'wpp_change_locale', 1 );
    add_filter( 'theme_locale', 'wpp_change_locale', 1 );
}
add_filter( 'login_headerurl', 'wpp_login_headerurl', 10, 2 );

/**
 * Detects current page is feed or not
 *
 * @since               1.0
 * @return              bool True when page is feed, false when page isn't feed
 */
function wpp_is_feed() {
    if ( is_feed() )
        return true;

    $path = $_SERVER['REQUEST_URI'];
    $exts = array( 'xml', 'gz', 'xsl' );
    $ext = pathinfo( $path, PATHINFO_EXTENSION );

    return in_array( $ext, $exts );
}

/**
 * Converts English digits to Persian digits
 *
 * @param           string $number Numbers
 * @return          string Formatted numbers
 */
function per_number( $number ) {
    return str_replace(
        range( 0, 9 ),
        array( '۰','۱','۲','۳','۴','۵','۶','۷','۸','۹' ),
        $number
    );
}

/**
 * Converts Persian digits to English digits
 *
 * @param           string $number Numbers
 * @return          string Formatted numbers
 */
function eng_number( $number ) {
    return str_replace(
        array( '۰','۱','۲','۳','۴','۵','۶','۷','۸','۹' ),
        range( 0, 9 ),
        $number
    );
}

/**
 * Converts English numbers to Persian numbers in post contents
 *
 * @param           string $content Post content
 * @return          string Formatted content
 */
function persian_number( $content ) {
    return(
        isset($content[1]) ? per_number( $content[1] ) : $content[0]
    );
}

/**
 * Fix numbers and convert them to Persian digits style
 *
 * @param           string $content
 * @return          mixed
 */
function fixnumber( $content ) {
	return preg_replace_callback( '/(?:&#\d{2,4};)|(?:[0]?[a-z][\x20-\x3B=\x3F-\x7F]*)|(\d+[\.\d]*)|<\s*[^>]+>/i','persian_number',$content);
}

/**
 * Fix arabic foreign characters
 *
 * @param           string $content
 * @return          mixed
 */
function fixarabic( $content ) {
    return str_replace( array( 'ي','ك','٤','٥','٦','ة' ), array( 'ی','ک','۴','۵','۶','ه' ), $content );
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