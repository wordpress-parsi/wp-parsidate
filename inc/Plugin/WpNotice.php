<?php

namespace WPParsidate\Plugin;

use WPParsidate\Helper\HTML;
use WPParsidate\Helper\Nonce;
use WPParsidate\Helper\Param;
use WPParsidate\Settings\Settings;

class WpNotice {
  public function __construct() {
    add_action( 'admin_notices', [ $this, 'display' ] );
    add_action( 'wp_ajax_wp_parsidate_dismiss_admin_notice', [ $this, 'dismiss' ] );
  }

  public function display() {
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy public hook name, kept for backward compatibility.
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
      $class      = 'notice';
      $class      .= isset( $notice['type'] ) ? ' notice-' . sanitize_html_class( $notice['type'] ) : '';
      $class      .= isset( $notice['dismissible'] ) && $notice['dismissible'] ? ' is-dismissible' : '';
      $class      .= ' wppd-admin-notice';
      $class      .= isset( $notice['class'] ) ? ' ' . implode( ' ', array_map( 'sanitize_html_class', explode( ' ', (string) $notice['class'] ) ) ) : '';
      $message    = $notice['message'];

      if ( isset( $notice['inside_tag'] ) ) {
        $insideTag = $notice['inside_tag'] ?: false;
      } else {
        $insideTag = 'p';
      }

      $insideTag = $insideTag ? tag_escape( $insideTag ) : false;

      printf(
        '<div class="%1$s" data-notice-id="%2$s"%3$s>%4$s%5$s%6$s</div>',
        esc_attr( $class ),
        esc_attr( $notice['id'] ),
        isset( $notice['dismissible'], $notice['dismiss_time'] ) ? ' data-notice-dismiss-time="' . esc_attr( $notice['dismiss_time'] ) . '"' : '',
        $insideTag ? '<' . esc_html( $insideTag ) . '>' : '',
        wp_kses( $message, HTML::allowedHtml() ),
        $insideTag ? '</' . esc_html( $insideTag ) . '>' : ''
      );
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
