<?php

namespace WPParsidate\Plugin;

use WPParsidate\Helper\HTML;
use WPParsidate\Helper\Nonce;
use WPParsidate\Helper\Param;
use WPParsidate\Settings\Settings;

class WpNotice {
  /**
   * Query string argument used to dismiss a notice without JavaScript.
   */
  public const DISMISS_ARG = 'wppd_dismiss_notice';

  /**
   * Nonce action of the dismiss link.
   */
  private const DISMISS_NONCE = 'wp_parsidate_dismiss_admin_notice_link';

  /**
   * Query string argument holding how long the notice stays dismissed.
   */
  private const DISMISS_TIME_ARG = 'wppd_dismiss_time';

  public function __construct() {
    add_action( 'admin_notices', [ $this, 'display' ] );
    add_action( 'admin_init', [ $this, 'handleDismissLink' ] );
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

      $dismissible = ! empty( $notice['dismissible'] );
      $dismissTime = isset( $notice['dismiss_time'] ) ? (int) $notice['dismiss_time'] : 0;
      $class       = 'notice';
      $class       .= isset( $notice['type'] ) ? ' notice-' . sanitize_html_class( $notice['type'] ) : '';
      $class       .= $dismissible ? ' wppd-notice-dismissible' : '';
      $class       .= ' wppd-admin-notice';
      $class       .= isset( $notice['class'] ) ? ' ' . implode( ' ', array_map( 'sanitize_html_class', explode( ' ', (string) $notice['class'] ) ) ) : '';
      $message     = $notice['message'];

      if ( isset( $notice['inside_tag'] ) ) {
        $insideTag = $notice['inside_tag'] ?: false;
      } else {
        $insideTag = 'p';
      }

      $insideTag = $insideTag ? tag_escape( $insideTag ) : false;

      printf(
        '<div class="%1$s" data-notice-id="%2$s"%3$s>%4$s%5$s%6$s%7$s</div>',
        esc_attr( $class ),
        esc_attr( $notice['id'] ),
        isset( $notice['dismissible'], $notice['dismiss_time'] ) ? ' data-notice-dismiss-time="' . esc_attr( $notice['dismiss_time'] ) . '"' : '',
        $insideTag ? '<' . esc_html( $insideTag ) . '>' : '',
        wp_kses( $message, HTML::allowedHtml() ),
        $insideTag ? '</' . esc_html( $insideTag ) . '>' : '',
        $dismissible ? $this->dismissLink( $notice['id'], $dismissTime ) : ''
      );
    }
  }

  /**
   * Dismisses a notice through a plain link.
   *
   * Runs on `admin_init` so the dismiss button keeps working when JavaScript on
   * the page is broken and the Ajax request never leaves the browser.
   *
   * @return void
   */
  public function handleDismissLink(): void {
    $id = Param::get( self::DISMISS_ARG );

    if ( empty( $id ) || ! is_string( $id ) ) {
      return;
    }

    $nonce = Param::get( '_wpnonce' );

    if ( ! is_string( $nonce ) || ! Nonce::verify( $nonce, self::DISMISS_NONCE ) ) {
      return;
    }

    $this->saveDismiss( sanitize_text_field( $id ), (int) Param::get( self::DISMISS_TIME_ARG, 0 ) );

    wp_safe_redirect( $this->currentUrl() );
    exit;
  }

  public function dismiss() {
    if ( ! Nonce::verify() ) {
      wp_send_json_error( null, 400 );
    }

    $id = Param::post( 'id' );

    if ( is_string( $id ) && $this->saveDismiss( sanitize_text_field( $id ), (int) Param::post( 'dismiss_time', 0 ) ) ) {
      wp_send_json_success();
    }

    wp_send_json_error( null, 400 );
  }

  /**
   * Renders the cross button as a real link.
   *
   * WordPress builds the dismiss button with JavaScript, so a broken script
   * means no button at all. Printing the link here keeps the notice dismissible
   * with or without JavaScript, and gives the Ajax handler a fallback URL.
   *
   * @param string $id Notice id.
   * @param int $dismissTime Seconds the dismissal lasts, 0 for forever.
   *
   * @return string
   */
  private function dismissLink( $id, int $dismissTime ): string {
    return sprintf(
      '<a href="%1$s" class="wppd-notice-dismiss"><span class="screen-reader-text">%2$s</span></a>',
      esc_url( $this->dismissUrl( (string) $id, $dismissTime ) ),
      esc_html__( 'Dismiss this notice.', 'wp-parsidate' )
    );
  }

  private function dismissUrl( string $id, int $dismissTime ): string {
    return add_query_arg(
      array(
        self::DISMISS_ARG      => $id,
        self::DISMISS_TIME_ARG => $dismissTime,
        '_wpnonce'             => Nonce::create( self::DISMISS_NONCE ),
      ),
      $this->currentUrl()
    );
  }

  /**
   * Current admin URL without the dismissal arguments.
   *
   * @return string
   */
  private function currentUrl(): string {
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Escaped on output and validated by wp_safe_redirect().
    $uri = (string) Param::server( 'REQUEST_URI', '' );

    return remove_query_arg( array( self::DISMISS_ARG, self::DISMISS_TIME_ARG, '_wpnonce' ), $uri );
  }

  private function saveDismiss( string $id, int $dismissTime ): bool {
    if ( $id === '' ) {
      return false;
    }

    Settings::save( "notice_dismissed_$id", $dismissTime === 0 ? true : time() + $dismissTime, 'side_options' );

    return true;
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
