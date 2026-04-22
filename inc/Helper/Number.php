<?php

namespace WPParsidate\Helper;

class Number {
  /**
   * Fix numbers and convert them to Persian digits style
   *
   * @param  string  $content
   *
   * @return string Fixed number
   */
  public static function fixNumber( string $content ): string {
    return preg_replace_callback( '/(?:&#\d{2,4};)|(?:[0]?[a-z][\x20-\x3B=\x3F-\x7F]*)|(?<![>=<][\s*])(\b\d+\b)|<\s*[^>]+>/i',
      static function ( $content ) {
        return isset( $content[1] ) ? self::toPersian( $content[1] ) : $content[0];
      }, $content );
    //return preg_replace_callback( '/(?:&#\d{2,4};)|(?:[0]?[a-z][\x20-\x3B=\x3F-\x7F]*)|(\d+[\d]*)|<\s*[^>]+>/i', 'persian_number', $content );
  }

  /**
   * Converts English numbers to Persian numbers in post contents
   *
   * @param  string  $content  Post content
   *
   * @return  string Formatted content
   */
  public static function persianNumber( string $content ): string {
    return isset( $content[1] ) ? self::toPersian( $content[1] ) : $content[0];
  }

  /**
   * Converts English digits to Persian digits
   *
   * @param  string  $number  Numbers
   *
   * @return string Formatted numbers
   */
  public static function toPersian( string $number ): string {
    return str_replace(
      range( 0, 9 ),
      array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ),
      $number
    );
  }

  /**
   * Converts Persian digits to English digits
   *
   * @param  string  $number  Numbers
   *
   * @return              string Formatted numbers
   */
  public static function toEnglish( string $number ): string {
    return str_replace(
      array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ),
      range( 0, 9 ),
      $number
    );
  }
}
