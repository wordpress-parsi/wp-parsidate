<?php

namespace WPParsidate\Helper;

defined( 'ABSPATH' ) || exit;

class User {
  /**
   * Get user data
   *
   * @param  string  $field  User data field
   * @param  int  $userID  User ID. if not set, get current user logged-in ID
   *
   * @return false|int|mixed|string|\WP_User
   */
  public static function getData( $field = null, $userID = 0 ) {
    if ( $userID === 0 ) {
      $userID = get_current_user_id();
    }

    if ( $userID === 0 ) {
      return false;
    }

    $user = get_userdata( $userID );

    if ( is_null( $field ) ) {
      return $user;
    }

    return $user->$field ?? '';
  }
}
