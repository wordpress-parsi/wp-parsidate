<?php

namespace WPParsidate\Helper;

class Strip {
  /**
   * Filters text content and strips out disallowed HTML.
   *
   * @param  string  $content  Text content to filter.
   *
   * @return string Filtered content containing only the allowed HTML.
   */
  public static function kses( string $content ): string {
    return wp_kses( stripslashes_deep( $content ), wp_kses_allowed_html( 'post' ) );
  }

  /**
   * Remove HTML comments
   *
   * @param  string  $html
   *
   * @return string
   */
  public static function removeHtmlComments( string $html ): string {
    return preg_replace( '~<!--(.*?)-->~s', '', $html );
  }

  /**
   * Remove HTML Document type
   *
   * @param  string  $html
   *
   * @return string
   */
  public static function removeHtmlDoctype( string $html ): string {
    return preg_replace( '/^<!DOCTYPE.+?>/', '', $html );
  }
}
