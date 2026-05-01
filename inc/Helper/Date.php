<?php

namespace WPParsidate\Helper;

use DateTime;

class Date {
  /**
   * Change date format
   *
   * @param  string  $date  Date string
   * @param  string  $dateFormat  Input date format
   * @param  string  $returnFormat  Output date format
   *
   * @return string Date with output format
   */
  public static function changeDateFormat( string $date, string $dateFormat, string $returnFormat ): string {
    return DateTime::createFromFormat( $dateFormat, $date )->format( $returnFormat );
  }

  /**
   * Determines is gregorian/shamsi date string
   *
   * @param  string  $dateString  Date string
   * @param  string  $format  Date format
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
    if ( $dateParts['error_count'] > 0 || $dateParts['warning_count'] > 0 ) {
      return $default;
    }

    $year  = $dateParts['year'];
    $month = $dateParts['month'];
    $day   = $dateParts['day'];

    if ( $year > 1900 ) {
      if ( checkdate( $month, $day, $year ) ) {
        return [
          'status' => true,
          'type'   => 'gregorian',
          'value'  => $dateString
        ];
      }
    } elseif ( $year < 1500 ) {
      if ( $month >= 1 && $month <= 12 && $day >= 1 && $day <= 31 ) {
        if ( $month > 6 && $day > 30 ) {
          return $default;
        }

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
   * @param  mixed  $time  Time string
   * @param  string  $seconds  Seconds string value
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
