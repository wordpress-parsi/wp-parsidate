<?php
/**
 * Fix dates settings
 *
 * Fix dates and time in WP Hooks.
 */

namespace WPParsidate\App\Core;

use WPParsidate\App\Integration\HookDeactivator;
use WPParsidate\Core\Names;
use WPParsidate\Helper\Number;
use WPParsidate\Helper\WordPress;
use WPParsidate\Settings\Settings;

class FixDates {
  public function __construct() {
    // @TODO: locale non-farsi is a problem
    if ( get_locale() === 'fa_IR' && Settings::get( 'persian_date' ) ) {
      add_filter( 'the_time', [ $this, 'fixPostTime' ], 10, 2 );
      add_filter( 'get_the_time', [ $this, 'fixPostTime' ], 10, 3 );

      add_filter( 'the_date', [ $this, 'fixPostDate' ], 10, 2 );
      add_filter( 'get_the_date', [ $this, 'fixPostDate' ], 100, 3 );

      add_filter( 'get_the_modified_date', [ $this, 'fixModifiedDate' ], 10, 3 );

      add_filter( 'get_comment_time', [ $this, 'fixCommentTime' ], 10, 2 );
      add_filter( 'get_comment_date', [ $this, 'fixCommentDate' ], 10, 3 );
      add_filter( 'media_view_settings', [ $this, 'fixMediaViewSettings' ], 10, 2 );

      add_filter( 'date_i18n', [ $this, 'fixDateI18n' ], 10, 4 );

      if ( ! WordPress::isSitemap() ) {
        add_filter( 'wp_date', [ $this, 'fixDateI18n' ], 10, 4 );
      }
    }
  }

  /**
   * Fixes i18n date formatting and convert them to Jalali
   *
   * @param  string  $date  Formatted date string.
   * @param  string  $format  Format to display the date.
   * @param  int  $timestamp  A sum of Unix timestamp and timezone offset in seconds.
   *                          Might be without offset if input omitted timestamp but requested GMT.
   * @param  bool  $gmt  Whether to use GMT timezone. Only applies if timestamp was not provided.
   *                          Default false.
   *
   * @return string Formatted time
   */
  public function fixDateI18n( $date, $format, $timestamp, $gmt ): string {
    if ( ( function_exists( 'pll_current_language' ) && ( pll_current_language() !== false && pll_current_language() !== "fa" ) ) ) {
      return $date;
    }

    if ( HookDeactivator::checkDisable() ) {
      return $date;
    }

    return $this->formatDate( $format, $timestamp, $date );
  }

  /**
   * Fixes Media view Select box and returns in Jalali Format Date
   *
   * @param  array  $settings  List of media view settings.
   * @param  \WP_Post  $post  Post object.
   *
   * @return  array _wpMediaViewsL10n localize script in WordPress
   * @author  Mehrshad Darzi
   */
  public function fixMediaViewSettings( $settings, $post ): array {
    if ( ! empty( $settings['months'] ) ) {
      $convDates = Settings::get( 'conv_dates' );

      for ( $i = 0, $iMax = count( $settings['months'] ); $i < $iMax; $i ++ ) {
        if ( isset( $settings['months'][ $i ]->year, $settings['months'][ $i ]->month ) ) {
          $settings['months'][ $i ]->text = parsidate(
            "F Y",
            $settings['months'][ $i ]->year . '-' . $settings['months'][ $i ]->month,
            $convDates
          );
        }
      }
    }

    return $settings;
  }

  /**
   * Fixes comment date and returns in Jalali format
   *
   * @param  string|int  $comment_date  Formatted date string or Unix timestamp.
   * @param  string  $format  PHP date format.
   * @param  \WP_Comment  $comment  The comment object.
   */
  public function fixCommentDate( $comment_date, $format, $comment ) {
    if ( $comment === null ) {
      return $comment_date;
    }

    if ( empty( $format ) ) {
      $format = (string) get_option( 'date_format' );
    }
    if ( 'c' === $format || HookDeactivator::checkDisable() ) {
      return date( $format, strtotime( $comment->comment_date ) );
    }

    return $this->formatDate( $format, $comment->comment_date, $comment_date );
  }

  /**
   * Fixes comment time and returns to Jalali format
   *
   * @param  string  $time  Comment time
   * @param  string  $format  Date format
   *
   * @return          string Formatted date
   */
  public function fixCommentTime( $time, $format = '' ): string {
    global $comment;

    if ( empty( $comment ) ) {
      return $time;
    }

    if ( empty( $format ) ) {
      $format = (string) get_option( 'time_format' );
    }
    if ( HookDeactivator::checkDisable() ) {
      return date( $format, strtotime( $comment->comment_date ) );
    }

    return $this->formatDate( $format, $comment->comment_date, $time );
  }

  /**
   * Fixes post modified date and returns to Jalali format
   *
   * @param  string  $time  Post modified time
   * @param  string  $format  Date format
   * @param  \WP_Post|null  $post  WP_Post object or null if no post is found.
   *
   * @return string Formatted date
   * @author Yousef Mahmoudi
   */
  public function fixModifiedDate( $time, $format, $post ): string {
    if ( $post === null ) {
      return $time;
    }

    if ( 'c' === $format ) {
      return date( $format, strtotime( $post->post_modified ) );
    }

    return $time;
  }

  /**
   * Fixes post time and returns to Jalali format
   *
   * @param  string  $time  Post time
   * @param  string  $format  Date format
   *
   * @return          string Formatted date
   */
  public function fixPostTime( $time, $format = '', $post = null ): string {
    $post = get_post( $post );

    if ( ! $post ) {
      global $post;
    }

    if ( empty( $post ) ) {
      return $time;
    }

    if ( empty( $format ) ) {
      $format = (string) get_option( 'time_format' );
    }

    if ( HookDeactivator::checkDisable() ) {
      return date( $format, strtotime( $post->post_date ) );
    }

    return $this->formatDate( $format, $post->post_date, $time );
  }

  /**
   * Fixes post date and returns to Jalali format
   *
   * @param  string  $time  Post time
   * @param  string  $format  Date format
   *
   * @return string Formatted date
   */
  public function fixPostDate( $time, $format = '', $post = null ): string {
    if ( null === $post ) {
      global $post;
    } else {
      $post = get_post( $post );
    }

    // It seems some plugin like acf does not exist $post.
    if ( ! $post ) {
      return $time;
    }
    if ( function_exists( 'pll_current_language' ) && pll_current_language() !== "fa" ) {
      return $time;
    }
    if ( empty( $format ) ) {
      $format = (string) get_option( 'date_format', 'F j, Y' );
    }

    if ( 'c' === $format || HookDeactivator::checkDisable() ) {
      return date( $format, strtotime( $post->post_date ) );
    }

    return $this->formatDate( $format, date( 'Y-m-d H:i:s', strtotime( $post->post_date ) ), $time );
  }

  /**
   * Converts a date/time value to a Jalali-formatted string, optionally appending the Gregorian date.
   *
   * @param  string  $format               PHP date format string.
   * @param  mixed   $dateTime             Date/time value (timestamp, date string, or DateTimeInterface).
   * @param  string  $fallbackGregorianDate Fallback Gregorian date string used when timestamp cannot be resolved.
   *
   * @return string Jalali-formatted date string, with optional dual-date suffix.
   */
  private function formatDate( string $format, $dateTime, string $fallbackGregorianDate = '' ): string {
    $jalaliDate = parsidate( $format, $dateTime, Settings::get( 'conv_dates' ) );

    return $this->appendGregorianDate( $jalaliDate, $format, $dateTime, $fallbackGregorianDate );
  }

  /**
   * Appends the Gregorian date to a Jalali date string when dual-date mode is enabled.
   *
   * @param  string  $jalaliDate           Already-formatted Jalali date string.
   * @param  string  $format               PHP date format string used for formatting.
   * @param  mixed   $dateTime             Original date/time value.
   * @param  string  $fallbackGregorianDate Fallback Gregorian date string used when timestamp cannot be resolved.
   *
   * @return string Jalali date string, optionally followed by a separator and the Gregorian date.
   */
  private function appendGregorianDate(
    string $jalaliDate,
    string $format,
    $dateTime,
    string $fallbackGregorianDate = ''
  ): string {
    if ( ! Settings::get( 'dual_date', false ) || ! $this->isDualDateFormat( $format ) ) {
      return $jalaliDate;
    }

    $gregorianDate = $this->formatGregorianDate( $format, $dateTime, $fallbackGregorianDate );
    if ( $gregorianDate === '' || $gregorianDate === $jalaliDate ) {
      return $jalaliDate;
    }

    $separator = apply_filters( 'wp_parsidate_dual_date_separator', ' - ', $format, $dateTime );

    return $jalaliDate . $separator . $gregorianDate;
  }

  /**
   * Determines whether a given date format is suitable for dual-date display.
   *
   * Machine-readable formats (e.g. ISO 8601, RFC, Unix timestamp) are excluded.
   *
   * @param  string  $format  PHP date format string to evaluate.
   *
   * @return bool True if the format contains human-readable date components, false otherwise.
   */
  private function isDualDateFormat( string $format ): bool {
    $machineFormats = array_filter( [
      'c',
      'r',
      'U',
      'u',
      'timestamp',
      DATE_ATOM,
      DATE_COOKIE,
      DATE_ISO8601,
      DATE_RFC822,
      DATE_RFC850,
      DATE_RFC1036,
      DATE_RFC1123,
      DATE_RFC2822,
      DATE_RFC3339,
      defined( 'DATE_RFC3339_EXTENDED' ) ? DATE_RFC3339_EXTENDED : null,
      defined( 'DATE_RFC7231' ) ? DATE_RFC7231 : null,
      DATE_RSS,
      DATE_W3C,
    ] );

    if ( in_array( $format, $machineFormats, true ) ) {
      return false;
    }

    $format = preg_replace( '/\\\\./', '', $format );

    return preg_match( '/[dDjlNSwzWFmMntLoYy]/', $format ) === 1;
  }

  /**
   * Formats a date/time value as a Gregorian date string, optionally converting digits to Persian.
   *
   * @param  string  $format               PHP date format string.
   * @param  mixed   $dateTime             Date/time value (timestamp, date string, or DateTimeInterface).
   * @param  string  $fallbackGregorianDate Fallback string returned when timestamp cannot be resolved.
   *
   * @return string Formatted Gregorian date string.
   */
  private function formatGregorianDate( string $format, $dateTime, string $fallbackGregorianDate = '' ): string {
    $timestamp = $this->getTimestamp( $dateTime );

    if ( is_null( $timestamp ) ) {
      $date = $fallbackGregorianDate;
    } else {
      $date = date( $format, $timestamp );
      $date = $this->translateGregorianDateNames( $date, $timestamp );
    }

    if ( Settings::get( 'conv_dates', false ) ) {
      $date = Number::toPersian( $date );
    }

    return apply_filters( 'wp_parsidate_dual_date_gregorian', $date, $format, $dateTime, $fallbackGregorianDate );
  }

  /**
   * Replaces English month and weekday names in a formatted date string with their Persian equivalents.
   *
   * @param  string  $date       Formatted date string containing English month/day names.
   * @param  int     $timestamp  Unix timestamp used to determine the current month and weekday.
   *
   * @return string Date string with English names replaced by Persian names.
   */
  private function translateGregorianDateNames( string $date, int $timestamp ): string {
    $months      = Names::getGregorianMonths();
    $shortMonths = Names::getGregorianMonths( null, true );
    $weekDays    = Names::getGregorianWeekDays();
    $shortDays   = Names::getGregorianWeekDays( null, true );

    $englishMonths      = array( '', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August',
      'September', 'October', 'November', 'December' );
    $englishShortMonths = array( '', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov',
      'Dec' );
    $englishWeekDays    = array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
    $englishShortDays   = array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' );
    $replacements       = [];

    $month = (int) date( 'n', $timestamp );
    $day   = (int) date( 'w', $timestamp );

    if ( isset( $months[ $month ] ) ) {
      $replacements[ $englishMonths[ $month ] ] = $months[ $month ];
    }

    if ( isset( $shortMonths[ $month ] ) ) {
      $replacements[ $englishShortMonths[ $month ] ] = $shortMonths[ $month ];
    }

    if ( isset( $weekDays[ $day ] ) ) {
      $replacements[ $englishWeekDays[ $day ] ] = $weekDays[ $day ];
    }

    if ( isset( $shortDays[ $day ] ) ) {
      $replacements[ $englishShortDays[ $day ] ] = $shortDays[ $day ];
    }

    return strtr( $date, $replacements );
  }

  /**
   * Resolves a date/time value to a Unix timestamp.
   *
   * Accepts a DateTimeInterface object, a numeric timestamp, or a date string.
   *
   * @param  mixed  $dateTime  Date/time value to resolve.
   *
   * @return int|null Unix timestamp, or null if the value cannot be parsed.
   */
  private function getTimestamp( $dateTime ): ?int {
    if ( $dateTime instanceof \DateTimeInterface ) {
      return $dateTime->getTimestamp();
    }

    if ( is_numeric( $dateTime ) && (int) $dateTime == $dateTime ) {
      return (int) $dateTime;
    }

    $timestamp = strtotime( (string) $dateTime );

    return $timestamp === false ? null : $timestamp;
  }
}
