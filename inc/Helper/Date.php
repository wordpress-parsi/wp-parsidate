<?php

namespace WPParsidate\Helper;

use DateTime;
use DateTimeZone;
use WPParsidate\Core\WPP_ParsiDate;

class Date {
  /**
   * Get time zone offset base on timezone input
   *
   * @param DateTimeZone|string $timezone
   *
   * @return float|int
   * @throws \DateInvalidTimeZoneException
   */
  public static function getTimeZoneOffset( $timezone ) {
    $dtz      = $timezone instanceof DateTimeZone ? $timezone : new DateTimeZone( $timezone );
    $datetime = new DateTime( 'now', $dtz );

    return $dtz->getOffset( $datetime ) / HOUR_IN_SECONDS;
  }

  /**
   * Will return an offset using the WordPress timezone set by the user
   * Example return values: -04:00, +00:00, or +5:45
   *
   * @source https://gist.github.com/andrewjmead/9aef80a495dc36221ff84ddfb3ac3181
   */
  public static function getLocalOffset(): string {
    // Start with the offset such as -4, 0, or 5.75
    $offset_number = (float) get_option( 'gmt_offset' );

    // Build a string to represent the offset such as -04:00, +00:00, or +5:45
    $result = '';

    // Start with either - or +
    $result .= $offset_number < 0 ? '-' : '+';

    $whole_part  = abs( $offset_number );
    $hour_part   = floor( $whole_part );
    $minute_part = $whole_part - $hour_part;

    $hours   = strval( $hour_part );
    $minutes = strval( $minute_part * 60 );

    // Add hour part to result
    $result .= str_pad( $hours, 2, '0', STR_PAD_LEFT );

    // Add separator
    $result .= ':';

    // Add minute part to result
    $result .= str_pad( $minutes, 2, '0', STR_PAD_LEFT );

    return $result;
  }

  /**
   * Change date format
   *
   * @param string $date Date string
   * @param string $dateFormat Input date format
   * @param string $returnFormat Output date format
   *
   * @return string Date with output format
   */
  public static function changeDateFormat( string $date, string $dateFormat, string $returnFormat ): string {
    return DateTime::createFromFormat( $dateFormat, $date )->format( $returnFormat );
  }

  /**
   * Determines is gregorian/shamsi date string
   *
   * @param string $dateString Date string
   * @param string $format Date format
   *
   * @return array
   */
  public static function isDateString( string $dateString, string $format = 'Y-m-d\TH:i:sP' ): array {
    $default = [
      'status' => false,
      'type'   => null,
      'value'  => ''
    ];

    $dateString = Number::toEnglish( $dateString );
    $dateParts  = date_parse_from_format( $format, $dateString );
    if ( $dateParts['error_count'] > 0 ) {
      return $default;
    }

    $year  = $dateParts['year'];
    $month = $dateParts['month'];
    $day   = $dateParts['day'];

    if ( $year > 1900 ) {
      if ( $dateParts['warning_count'] === 0 && checkdate( $month, $day, $year ) ) {
        return [
          'status' => true,
          'type'   => 'gregorian',
          'value'  => $dateString
        ];
      }
    } elseif ( $year < 1500 ) {
      // PHP checks Gregorian month lengths even for Jalali years. Only
      // reinterpret calendar warnings for the date formats used by the plugin.
      // Other custom formats keep their original validation behavior.
      if ( $dateParts['warning_count'] > 0 ) {
        if ( ! in_array( $format, [ 'Y-m-d', 'Y-m-d\TH:i', 'Y-m-d\TH:i:sP', 'Y-m-d\TH:i:sO' ], true ) ) {
          return $default;
        }

        // PHP accepts malformed numeric offsets. Do not turn those previously
        // rejected date strings into valid Jalali datetimes.
        if ( $format === 'Y-m-d\TH:i:sP' && ! preg_match( '/[+-](?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $dateString ) ) {
          return $default;
        }
        if ( $format === 'Y-m-d\TH:i:sO' && ! preg_match( '/[+-](?:[01][0-9]|2[0-3])[0-5][0-9]$/D', $dateString ) ) {
          return $default;
        }

        $safeDate = preg_replace( '/^[0-9]{1,4}-[0-9]{1,2}-[0-9]{1,2}/', '2000-01-01', $dateString, 1, $replacements );
        if ( $replacements !== 1 ) {
          return $default;
        }
        $safeParts = date_parse_from_format( $format, $safeDate );
        if ( $safeParts['error_count'] > 0 || $safeParts['warning_count'] > 0 ) {
          return $default;
        }
      }

      if ( $month < 1 || $month > 12 || $day < 1 ) {
        return $default;
      }
      if ( $month <= 6 ) {
        $lastDay = 31;
      } elseif ( $month <= 11 ) {
        $lastDay = 30;
      } else {
        $lastDay = WPP_ParsiDate::getInstance()->IsPerLeapYear( $year ) ? 30 : 29;
      }
      if ( $day <= $lastDay ) {
        return [
          'status' => true,
          'type'   => 'jalali',
          'value'  => $dateString
        ];
      }
    }

    return $default;
  }

  /**
   * Determines is time string
   *
   * @param mixed $time Time string
   * @param string $seconds Seconds string value
   *
   * @return false|string Return time string if is time, Otherwise false
   */
  public static function isTimeString( $time, string $seconds = '00' ) {
    if ( ! is_string( $time ) ) {
      return false;
    }

    if ( preg_match( '/^(?:2[0-3]|[01][0-9]):[0-5][0-9](?::[0-5][0-9])?$/', $time ) ) {
      if ( substr_count( $time, ':' ) === 1 ) {
        $time .= ':' . $seconds;
      }

      return $time;
    }

    return false;
  }
}
