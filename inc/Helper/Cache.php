<?php

namespace WPParsidate\Helper;

defined( 'ABSPATH' ) || die();

class Cache {
  private const cacheGroup = WP_PARSI_KEY;

  /**
   * Determines cache is existing
   *
   * @param  string  $key  Cache key
   * @param  bool  $useDBCache  Use DB Cache
   *
   * @return bool True, if cache exist
   */
  public static function exists( string $key, bool $useDBCache = true ): bool {
    return self::get( $key, $useDBCache ) !== false;
  }

  /**
   * Set cache value
   *
   * @param  string  $key  Cache key
   * @param  mixed  $value  Cache value
   * @param  int  $expireTime  Expire time base on second's
   * @param  bool  $useDBCache  Use DB Cache
   *
   * @return bool True, if cache exist
   */
  public static function set( string $key, $value, int $expireTime = 0, bool $useDBCache = true ): bool {
    $cache = $cache2 = wp_cache_set( $key, $value, self::cacheGroup, $expireTime );

    if ( $useDBCache && $expireTime > 0 ) {
      $cache2 = set_transient( $key . '_' . self::cacheGroup, $value, $expireTime );
    }

    return $cache || $cache2;
  }

  /**
   * Get cache value
   *
   * @param  string  $key  Cache key
   * @param  bool  $useDBCache  Use DB Cache
   *
   * @return mixed Cache value, Return false if not exists
   */
  public static function get( string $key, bool $useDBCache = true ) {
    if ( ( $value = wp_cache_get( $key, self::cacheGroup ) ) !== false ) {
      return $value;
    }

    if ( $useDBCache && ( $value = get_transient( $key . '_' . self::cacheGroup ) ) !== false ) {
      return $value;
    }

    return false;
  }


  /**
   * Delete cache
   *
   * @param  string  $key  Cache key
   * @param  bool  $deleteDBCache  Delete DB Cache
   *
   * @return bool True, if cache successfully deleted
   */
  public static function delete( string $key, bool $deleteDBCache = true ): bool {
    $cache = wp_cache_delete( $key, self::cacheGroup );

    $cache2 = false;
    if ( $deleteDBCache ) {
      $cache2 = delete_transient( $key . '_' . self::cacheGroup );
    }

    return $cache || $cache2;
  }
}
