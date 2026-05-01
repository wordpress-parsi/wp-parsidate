<?php

namespace WPParsidate\Helper;

defined( 'ABSPATH' ) || die();

class Notice {
  private static array $messages = [];

  /**
   * Add notice and display immediately
   *
   * @param  string  $key  Notice key
   * @param  array  $notices  Notice list
   * @param  bool  $echo  True, print notice
   *
   * @return string
   */
  public static function addAndDisplay( string $key, array $notices, bool $echo = true ): string {
    self::clear( $key );

    foreach ( $notices as $notice ) {
      if ( is_array( $notice ) ) {
        self::add( $key, $notice['message'], $notice['type'] ?? null, $notice['link_title'] ?? null,
          $notice['link'] ?? null );
      }
    }

    return self::display( $key, null, $echo );
  }

  /**
   * Add notice
   *
   * @param  string  $key  Notice key
   * @param  string  $message  Notice message
   * @param  string|null  $type  Notice type (default, info, success, warning, error)
   * @param  string|null  $linkTitle  Notice link title
   * @param  string|null  $link  Notice link url
   *
   * @return void
   */
  public static function add(
    string $key,
    string $message,
    ?string $type = null,
    ?string $linkTitle = null,
    ?string $link = null
  ): void {
    if ( ! $key || ! $message ) {
      return;
    }

    $type                     = self::getType( $type );
    self::$messages[ $key ][] = array(
      'type'       => $type,
      'message'    => $message,
      'link_title' => $linkTitle,
      'link'       => $link,
    );
  }

  /**
   * Clear notice(s) by the Key
   *
   * @param  string  $key  Notice key
   *
   * @return void
   */
  public static function clear( string $key ): void {
    self::$messages[ $key ] = array();
  }

  /**
   * Display notice(s)
   *
   * @param  string  $key  Notice key
   * @param  string|null  $type  Notice type, if is null display all type of notice
   * @param  bool  $echo  Print notice
   *
   * @return string Notice(s) HTML
   */
  public static function display( string $key, ?string $type = null, bool $echo = true ): string {
    $type     = is_null( $type ) ? $type : self::getType( $type );
    $messages = self::$messages[ $key ] ?? [];
    $notices  = $noticeWrap = '';

    if ( ! empty( $messages ) ) {
      foreach ( $messages as $message ) {
        if ( ! is_null( $type ) && $message['type'] !== $type ) {
          continue;
        }

        $notices .= self::html( $message['type'], $message['message'], $message['link_title'] ?? null,
          $message['link'] ?? null );
      }

      self::clear( $key );
    }

    if ( ! empty( $notices ) ) {
      $noticeWrap = '<div class="' . WP_PARSI_KEY_SLUG . '-notices">' . $notices . '</div>';
    }

    if ( $echo ) {
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo $noticeWrap;
    } else {
      return $noticeWrap;
    }

    return '';
  }

  /**
   * Get HTML of a notice
   *
   * @param  string  $type  Type
   * @param  string  $message  Message
   * @param  string|null  $linkTitle  Link title
   * @param  string|null  $link  Link URL
   *
   * @return string HTML of notice
   */
  public static function html( string $type, string $message, ?string $linkTitle = '', ?string $link = '' ): string {
    $type = self::getType( $type );

    $link = $link && $linkTitle ? '<a href="' . $link . '" ' . ( Validating::isExternalLink( $link ) ? 'target="_blank"' : '' ) . ' class="' . TELIGRO_CLASS_PREFIX . 'notice-link">' . $linkTitle . '</a>' : '';

    return '<div class="' . WP_PARSI_CLASS_PREFIX . 'notice ' . WP_PARSI_CLASS_PREFIX . 'notice-' . $type . '" ><div>' . self::getIcon( $type ) . '<p>' . $message . '</p></div>' . $link . '</div>';
  }

  /**
   * Get notice icon base on type
   *
   * @param  string  $type  Type of notice
   *
   * @return string HTML of Icon
   */
  private static function getIcon( $type ): string {
    $icons = array(
      'default' => '<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M7.85 17.2542H7.4C3.8 17.2542 2 16.3569 2 11.8704V7.38385C2 3.79462 3.8 2 7.4 2H14.6C18.2 2 20 3.79462 20 7.38385V11.8704C20 15.4596 18.2 17.2542 14.6 17.2542H14.15C13.871 17.2542 13.601 17.3888 13.43 17.6132L12.08 19.4078C11.486 20.1974 10.514 20.1974 9.92 19.4078L8.57 17.6132C8.426 17.4158 8.093 17.2542 7.85 17.2542Z" stroke="#3c3c3c" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
<path opacity="0.6" d="M7 8H16" stroke="#3c3c3c" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path opacity="0.6" d="M7 12H12" stroke="#3c3c3c" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>',
      'info'    => '<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M11 20.1666C16.0417 20.1666 20.1667 16.0416 20.1667 11C20.1667 5.95831 16.0417 1.83331 11 1.83331C5.95833 1.83331 1.83333 5.95831 1.83333 11C1.83333 16.0416 5.95833 20.1666 11 20.1666Z" stroke="#4F8CCF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path opacity="0.6" d="M11 7.33331V11.9166" stroke="#4F8CCF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path opacity="0.6" d="M10.9946 14.6667H11.0029" stroke="#4F8CCF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
</svg>',
      'success' => '<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M20.0933 13.7757V8.29768C20.0933 3.73268 18.2673 1.90668 13.7023 1.90668H8.22433C3.65933 1.90668 1.83333 3.73268 1.83333 8.29768V13.7757C1.83333 18.3407 3.65933 20.1667 8.22433 20.1667H13.7023C18.2673 20.1667 20.0933 18.3407 20.0933 13.7757Z" stroke="#44B05C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path opacity="0.6" d="M7.18667 11.0367L9.70875 13.5667L14.74 8.50665" stroke="#44B05C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>',
      'warning' => '<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M10.9994 19.6257H5.44449C2.26365 19.6257 0.934486 17.3523 2.47449 14.5748L5.33449 9.42317L8.02949 4.58316C9.66112 1.64066 12.3378 1.64066 13.9694 4.58316L16.6644 9.43233L19.5244 14.584C21.0644 17.3615 19.7261 19.6348 16.5544 19.6348H10.9994V19.6257Z" stroke="#AB873A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path opacity="0.6" d="M11 8.25V12.8333" stroke="#AB873A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path opacity="0.6" d="M10.9946 15.5833H11.0028" stroke="#AB873A" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
</svg>',
      'error'   => '<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M19.3235 7.86501V14.135C19.3235 15.1616 18.7735 16.115 17.8844 16.6375L12.4394 19.7816C11.5502 20.295 10.4502 20.295 9.55185 19.7816L4.10681 16.6375C3.21765 16.1241 2.66765 15.1708 2.66765 14.135V7.86501C2.66765 6.83834 3.21765 5.88497 4.10681 5.36247L9.55185 2.21831C10.441 1.70498 11.541 1.70498 12.4394 2.21831L17.8844 5.36247C18.7735 5.88497 19.3235 6.82917 19.3235 7.86501Z" stroke="#BC5660" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path opacity="0.6" d="M11 7.10419V11.9167" stroke="#BC5660" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path opacity="0.6" d="M11 14.8502V14.9418" stroke="#BC5660" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
</svg>',
    );

    return $icons[ $type ] ?? $icons['default'];
  }

  /**
   * Get type of notice
   *
   * @param  string  $type  Notice type
   *
   * @return string Notice type
   */
  private static function getType( $type ): string {
    $types = array( 'default', 'info', 'success', 'warning', 'error' );
    $type  = strtolower( $type );

    return in_array( $type, $types, true ) ? $type : 'default';
  }
}
