<?php
if ( ! function_exists( 'parsidate' ) ) {
  /**
   * convert gregorian datetime to persian datetime
   *
   * @param  mixed  $input
   * @param  string  $dateTime
   * @param  bool|string  $lang
   *
   * @return string
   */
  function parsidate( $input, $dateTime = 'now', $lang = 'per' ): string {
    $lang = is_bool( $lang ) ? ( $lang ? 'per' : 'eng' ) : $lang;

    return WPParsidate\Core\WPP_ParsiDate::getInstance()->persian_date( $input, $dateTime, $lang );
  }
}

if ( ! function_exists( 'gregdate' ) ) {
  /**
   * gregdate()
   * convert persian datetime to gregorian datetime
   *
   * @param  mixed  $input
   * @param  mixed  $datetime
   *
   * @return false|string
   */
  function gregdate( $input, $datetime ) {
    return WPParsidate\Core\WPP_ParsiDate::getInstance()->gregorian_date( $input, $datetime );
  }
}
