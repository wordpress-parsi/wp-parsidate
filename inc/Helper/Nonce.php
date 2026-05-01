<?php

namespace WPParsidate\Helper;

class Nonce {
  /**
   * Create nonce
   *
   * @param  string  $action  Scalar value to add context to the nonce.
   *
   * @return string The token.
   */
  public static function create( string $action = WP_PARSI_KEY ): string {
    return wp_create_nonce( $action );
  }

  /**
   * Verifies that a correct security nonce was used with time limit.
   *
   *  A nonce is valid for between 12 and 24 hours (by default).
   *
   * @param  string|null  $nonce  Nonce value that was used for verification, usually via a form field.
   * @param  string|int  $action  Should give context to what is taking place and be the same when nonce was created.
   *
   * @return int|false 1 if the nonce is valid and generated between 0-12 hours ago,
   *                    2 if the nonce is valid and generated between 12-24 hours ago.
   *                    False if the nonce is invalid.
   */
  public static function verify( string $nonce = null, $action = WP_PARSI_KEY ) {
    $nonce = is_null( $nonce ) && isset( $_POST['nonce'] ) ? Sanitizing::text( wp_unslash( Param::post( 'nonce' ) ) ) : $nonce;
    if ( is_null( $nonce ) ) {
      return false;
    }

    return wp_verify_nonce( $nonce, $action );
  }
}
