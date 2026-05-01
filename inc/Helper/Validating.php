<?php

namespace WPParsidate\Helper;

defined( 'ABSPATH' ) || exit;

class Validating {
  /**
   * Validating timestamp number
   *
   * @param  mixed  $timestamp
   *
   * @return bool
   */
  public static function isTimeStamp( $timestamp ): bool {
    return ( (string) (int) $timestamp === $timestamp )
           && ( $timestamp <= PHP_INT_MAX )
           && ( $timestamp >= ~PHP_INT_MAX );
  }

  /**
   * Validating URL
   *
   * @param  mixed  $url
   *
   * @return bool
   */
  public static function isUrl( $url ): bool {
    return is_string( $url ) && ! empty( $url ) && filter_var( $url, FILTER_VALIDATE_URL );
  }

  /**
   * Check is external url from current website
   *
   * @param  mixed  $url
   *
   * @return bool
   */
  public static function isExternalLink( $url ): bool {
    return self::isUrl( $url ) && wp_parse_url( get_bloginfo( 'url' ), PHP_URL_HOST ) !== wp_parse_url( $url,
        PHP_URL_HOST );
  }
}
