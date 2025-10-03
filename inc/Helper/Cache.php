<?php

namespace WPParsidate\Helper;

defined( 'ABSPATH' ) || die();

class Cache {
	private const cacheGroup = WP_PARSI_KEY;

	public static function exists( $key, $useDBCache = true ): bool {
		return self::get( $key, $useDBCache ) !== false;
	}

	public static function set( $key, $value, $expireTime = 0, $useDBCache = true ): bool {
		$cache = $cache2 = wp_cache_set( $key, $value, self::cacheGroup, $expireTime );

		if ( $useDBCache && $expireTime > 0 ) {
			$cache2 = set_transient( $key . '_' . self::cacheGroup, $value, $expireTime );
		}

		return $cache || $cache2;
	}

	public static function get( $key, $useDBCache = true ) {
		if ( ( $value = wp_cache_get( $key, self::cacheGroup ) ) !== false ) {
			return $value;
		}

		if ( $useDBCache && ( $value = get_transient( $key . '_' . self::cacheGroup ) ) !== false ) {
			return $value;
		}

		return false;
	}

	public static function delete( $key ): bool {
		$cache  = wp_cache_delete( $key, self::cacheGroup );
		$cache2 = delete_transient( $key . '_' . self::cacheGroup );

		return $cache || $cache2;
	}
}