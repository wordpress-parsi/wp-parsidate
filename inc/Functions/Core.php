<?php
if ( ! function_exists( 'parsidate' ) ) {
  /**
   * Convert gregorian datetime to persian datetime
   *
   * @param  mixed  $format  Format
   * @param  string  $dateTime  gregorian datetime
   * @param  bool|string  $lang  true or per: convert numbers to persian, false or eng: don't convert numbers
   *
   * @return string
   */
  function parsidate( $format, $dateTime = 'now', $lang = 'per' ): string {
    $lang = is_bool( $lang ) ? ( $lang ? 'per' : 'eng' ) : $lang;

    return WPParsidate\Core\WPP_ParsiDate::getInstance()->persian_date( $format, $dateTime, $lang );
  }
}

if ( ! function_exists( 'gregdate' ) ) {
  /**
   * Convert persian datetime to gregorian datetime
   *
   * @param  mixed  $format  Format
   * @param  mixed  $datetime  Shamsi datetime
   *
   * @return false|string
   */
  function gregdate( $format, $datetime ) {
    return WPParsidate\Core\WPP_ParsiDate::getInstance()->gregorian_date( $format, $datetime );
  }
}
