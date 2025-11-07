<?php

namespace WPParsidate\Helper;

class Strip {
  public static function kses( $content ) {
    return wp_kses( stripslashes_deep( $content ), wp_kses_allowed_html( 'post' ) );
  }

  public static function removeHtmlComments( $html ) {
    return preg_replace( '~<!--(.*?)-->~s', '', $html );
  }

  public static function removeHtmlDoctype( $html ) {
    return preg_replace( '/^<!DOCTYPE.+?>/', '', $html );
  }
}
