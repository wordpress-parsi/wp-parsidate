<?php

namespace WPParsidate\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Param class.
 */
class Param {

  /**
   * Get field from query string.
   *
   * @param  string  $id  Field id to get.
   * @param  mixed  $default  Default value to return if field is not found.
   * @param  int  $filter  The ID of the filter to apply.
   * @param  int  $flag  The ID of the flag to apply.
   *
   * @return mixed
   */
  public static function get( $id, $default = false, $filter = FILTER_DEFAULT, $flag = [] ) {
    // PHPCS ignore reason: Nonce check is already happening before this logic in `AdminPages` class.
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
    return isset( $_GET[ $id ] ) ? filter_var( wp_unslash( $_GET[ $id ] ), $filter, $flag ) : $default;
  }

  /**
   * Get field from FORM post.
   *
   * @param  string  $id  Field id to get.
   * @param  mixed  $default  Default value to return if field is not found.
   * @param  int  $filter  The ID of the filter to apply.
   * @param  int  $flag  The ID of the flag to apply.
   *
   * @return mixed
   */
  public static function post( $id, $default = false, $filter = FILTER_DEFAULT, $flag = [] ) {
    // PHPCS ignore reason: Nonce check is already happening before this logic in `AdminPages` class.
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
    if ( isset( $_POST[ $id ] ) ) {
      // PHPCS ignore reason: Sanitize posted data with `Sanitizing` class
      // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
      return is_array( $_POST[ $id ] ) ? filter_var_array( $_POST[ $id ], $filter ) : filter_var( $_POST[ $id ],
        $filter, $flag );
    }

    return $default;
  }

  /**
   * Get field from request.
   *
   * @param  string  $id  Field id to get.
   * @param  mixed  $default  Default value to return if field is not found.
   * @param  int  $filter  The ID of the filter to apply.
   * @param  int  $flag  The ID of the flag to apply.
   *
   * @return mixed
   */
  public static function request( $id, $default = false, $filter = FILTER_DEFAULT, $flag = [] ) {
    // PHPCS ignore reason: Nonce check is already happening before this logic in `AdminPages` class.
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
    return isset( $_REQUEST[ $id ] ) ? filter_var( wp_unslash( $_REQUEST[ $id ] ), $filter, $flag ) : $default;
  }

  /**
   * Get field from FORM server.
   *
   * @param  string  $id  Field id to get.
   * @param  mixed  $default  Default value to return if field is not found.
   * @param  int  $filter  The ID of the filter to apply.
   * @param  int  $flag  The ID of the flag to apply.
   *
   * @return mixed
   */
  public static function server( $id, $default = false, $filter = FILTER_DEFAULT, $flag = [] ) {
    return isset( $_SERVER[ $id ] ) ? filter_var( wp_unslash( $_SERVER[ $id ] ), $filter, $flag ) : $default;
  }

  /**
   * Decode form.serelize() jQuery Post String
   * Return like $_POST['Form_Input_Name or ID']
   *
   * @source https://stackoverflow.com/a/5788352/3224296
   */
  public static function decodeSerialize( $queryString ): array {
    $a     = explode( '&', $queryString );
    $i     = 0;
    $store = array();
    while ( $i < count( $a ) ) {
      $b              = explode( '=', $a[ $i ] );
      $arrayName      = htmlspecialchars( urldecode( $b[0] ) );
      $cleanArrayName = str_replace( '[]', '', $arrayName );
      $arrayValue     = htmlspecialchars( urldecode( $b[1] ) );

      if ( strpos( $arrayName, '[]' ) !== false ) {
        $store[ $cleanArrayName ][] = $arrayValue;
      } else {
        $store[ $cleanArrayName ] = $arrayValue;
      }
      $i ++;
    }

    return $store;
  }
}
