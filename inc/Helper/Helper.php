<?php

namespace WPParsidate\Helper;

class Helper {
  /**
   * Reorder array list
   *
   * @param  array  $array  Input array
   * @param  array  $orders  Order number lists
   *
   * @return array|false Reordered array
   */
  public static function reorderArray( $array, $orders ) {
    $reorderArray = [];
    $orders       = array_map( 'intval', $orders );

    if ( count( array_unique( array_keys( $orders ) ) ) !== count( array_unique( array_values( $orders ) ) ) ) {
      return false;
    }

    foreach ( $orders as $index => $order ) {
      $reorderArray[ $order ] = $array[ $index ];
    }

    ksort( $reorderArray );

    return $reorderArray;
  }

  /**
   * Random string
   *
   * @param  int  $length  String length
   * @param  bool  $smallAlphabet  Use small alphabet
   * @param  bool  $largeAlphabet  Use large alphabet
   * @param  bool  $numbers  Use numbers
   *
   * @return false|string Random string, Otherwise return false if empty
   */
  public static function randomString( $length, $smallAlphabet = true, $largeAlphabet = true, $numbers = true ) {
    $strings = [];
    if ( $smallAlphabet ) {
      $strings = array_merge( $strings, range( 'a', 'z' ) );
    }
    if ( $largeAlphabet ) {
      $strings = array_merge( $strings, range( 'A', 'Z' ) );
    }
    if ( $numbers ) {
      $strings = array_merge( $strings, range( 1, 9 ) );
    }

    if ( empty( $strings ) ) {
      return false;
    }

    shuffle( $strings );

    $strings = implode( "", $strings );

    return substr( str_shuffle( $strings ), 0, $length );
  }

  /**
   * Inserts any number of scalars or arrays at the point
   * in the haystack immediately after the search key ($needle) was found,
   * or at the end if the needle is not found or not supplied.
   * Modifies $haystack in place.
   * https://stackoverflow.com/a/7257599/3224296
   *
   * @param  array &$haystack  the associative array to search. This will be modified by the function
   * @param  int  $needle  the key to search for
   * @param  array  $stuff  one or more arrays or scalars to be inserted into $haystack
   *
   * @return array the index at which $needle was found
   */
  public static function arrayInsertAfter( array $haystack, int $needle = 0, array $stuff = [] ): array {
    return array_merge( array_slice( $haystack, 0, $needle, true ), $stuff,
      array_slice( $haystack, $needle, count( $haystack ) - 1, true ) );
  }

  /**
   * URL to key
   * Use for cache base on url string
   *
   * @param  string  $url
   * @param  bool  $hostOnly
   *
   * @return false|string Key string, if url isn't valid return false
   */
  public static function urlToKey( string $url, $hostOnly = false ) {
    if ( Validating::isUrl( $url ) ) {
      if ( $hostOnly ) {
        $url = wp_parse_url( $url, PHP_URL_HOST );
      } else {
        $url = wp_parse_url( $url, PHP_URL_HOST ) . wp_parse_url( $url, PHP_URL_PATH );
        $url = trim( $url, '/' );
      }

      return Sanitizing::title( $url );
    }

    return false;
  }
}
