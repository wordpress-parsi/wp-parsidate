<?php

namespace WPParsidate\Plugin;

use WPParsidate\Helper\Nonce;
use WPParsidate\Helper\Param;
use WPParsidate\Settings\Settings;

class WpNotice {
  public function __construct() {
    add_action( 'admin_notices', [ $this, 'display' ] );
    add_action( 'wp_ajax_wp_parsidate_dismiss_admin_notice', [ $this, 'dismiss' ] );
  }

  public function display() {
    $notices = apply_filters( 'wp_parsidate_wp_admin_notice', [] );

    if ( empty( $notices ) || ! is_array( $notices ) ) {
      return;
    }

    $page          = Param::get( 'page' );
    $screen        = get_current_screen();
    $currentScreen = is_null( $screen ) ? false : $screen->id;

    foreach ( $notices as $notice ) {
      if (
        ( isset( $notice['page'] ) && $notice['page'] !== $page ) ||
        ( isset( $notice['not_page'] ) && $notice['not_page'] === $page ) ||
        ( isset( $notice['screen'] ) && $notice['screen'] !== $currentScreen ) ||
        ( isset( $notice['not_screen'] ) && $notice['not_screen'] === $currentScreen ) ||
        empty( $notice['id'] ) ||
        empty( $notice['message'] ) ||
        $this->isDismissed( $notice['id'] )
      ) {
        continue;
      }
      $attributes = ' data-notice-id="' . esc_attr( $notice['id'] ) . '"';
      $class      = 'notice';
      $class      .= isset( $notice['type'] ) ? ' notice-' . esc_attr( $notice['type'] ) : '';
      $class      .= isset( $notice['dismissible'] ) && $notice['dismissible'] ? ' is-dismissible' : '';
      $class      .= ' wppd-admin-notice';
      $class      .= isset( $notice['class'] ) ? ' ' . esc_attr( $notice['class'] ) : '';
      $message    = wp_kses_post( $notice['message'] );

      if ( isset( $notice['dismissible'], $notice['dismiss_time'] ) ) {
        $attributes .= ' data-notice-dismiss-time="' . esc_attr( $notice['dismiss_time'] ) . '"';
      }

      echo "<div class='$class' $attributes><p>$message</p></div>";
    }
  }

  public function dismiss() {
    if ( ! Nonce::verify() ) {
      wp_send_json_error( null, 400 );
    }

    $id          = Param::post( 'id' );
    $dismissTime = (int) Param::post( 'dismiss_time', 0 );

    if ( ! empty( $id ) ) {
      $dismissTime = $dismissTime === 0 ? true : time() + $dismissTime;
      Settings::save( "notice_dismissed_$id", $dismissTime, 'side_options' );

      wp_send_json_success();
    }

    wp_send_json_error( null, 400 );
  }

  private function isDismissed( $id ): bool {
    $dismissed = Settings::get( "notice_dismissed_$id", false, 'side_options' );
    if ( $dismissed === true ) {
      return true;
    }

    if ( is_numeric( $dismissed ) ) {
      if ( time() < (int) $dismissed ) {
        return true;
      }

      Settings::delete( "notice_dismissed_$id", 'side_options' );
    }

    return false;
  }
}
