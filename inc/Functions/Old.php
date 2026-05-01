<?php

use WPParsidate\App\Integration\HookDeactivator;
use WPParsidate\Helper\{Date, Number, WooCommerce, WordPress};
use WPParsidate\Settings\Settings;

if ( ! function_exists( 'wpp_is_active' ) ) {
  /**
   * Gets an option name and check that option is active or not
   *
   * @param               $option_name
   *
   * @return              bool
   * @since               4.0.0
   */
  function wpp_is_active( $option_name ): bool {
    return Settings::get( $option_name, false ) === true;
  }
}

if ( ! function_exists( 'disable_wpp' ) ) {
  /**
   * Check hook deactivator enable in current call back trace
   *
   * @return bool
   */
  function disable_wpp(): bool {
    return HookDeactivator::checkDisable();
  }
}

if ( ! function_exists( 'wpp_date_is' ) ) {
  /**
   * Validate date string
   *
   * @param $dateString
   * @param $format
   *
   * @return array
   */
  function wpp_date_is( $dateString, $format = 'Y-m-d\TH:i:sP' ): array {
    return Date::isDateString( $dateString, $format );
  }
}

if ( ! function_exists( 'wpp_is_time_validate' ) ) {
  /**
   * Validate time string
   *
   * @param  mixed  $time
   * @param  string  $default_seconds
   *
   * @return false|string
   */
  function wpp_is_time_validate( $time, string $default_seconds = '00' ) {
    return Date::isTimeString( $time, $default_seconds );
  }
}

if ( ! function_exists( 'wpp_is_postal_code_validate' ) ) {
  /**
   * Check value is postal code
   *
   * @param  mixed  $postalCode
   * @param  bool  $checkSum
   *
   * @return bool
   */
  function wpp_is_postal_code_validate( $postalCode, bool $checkSum = false ): bool {
    return WooCommerce::isPostalCode( $postalCode, $checkSum );
  }
}

if ( ! function_exists( 'wpp_is_sitemap' ) ) {
  /**
   * Checks is WordPress sitemap
   *
   * @return boolean
   */
  function wpp_is_sitemap(): bool {
    return WordPress::isSitemap();
  }
}

if ( ! function_exists( 'fix_number' ) ) {
  /**
   * Fix numbers and convert them to Persian digits style
   *
   * @param  string  $content
   *
   * @return array|string|string[]|null
   */
  function fix_number( string $content ) {
    return Number::fixNumber( $content );
  }
}

if ( ! function_exists( 'wpp_is_feed' ) ) {
  /**
   * Detects current page is feed or not
   *
   * @return bool
   */
  function wpp_is_feed(): bool {
    return WordPress::isFeed();
  }
}

if ( ! function_exists( 'per_number' ) ) {
  /**
   * Converts English digits to Persian digits
   *
   * @param  string  $number  Numbers
   *
   * @return              string Formatted numbers
   */
  function per_number( string $number ): string {
    return Number::toPersian( $number );
  }
}

if ( ! function_exists( 'eng_number' ) ) {
  /**
   * Converts Persian digits to English digits
   *
   * @param  string  $number  Numbers
   *
   * @return              string Formatted numbers
   */
  function eng_number( string $number ): string {
    return Number::toEnglish( $number );
  }
}
