<?php

namespace WPParsidate\Helper;

class Number {
  /**
   * Fix numbers and convert them to Persian digits style
   *
   * Converts only the visible text of HTML content: tags, attributes (img
   * src, srcset, href, ...) and script/style/code blocks are protected and
   * restored byte-for-byte by TextProtector, so media URLs and inline code
   * keep working. Plain text is converted directly.
   *
   * @param string $content
   *
   * @return string Fixed number
   */
  public static function fixNumber( string $content ): string {
    if ( $content === '' ) {
      return $content;
    }

    // Fast bailout: no ASCII digits, nothing to convert
    if ( ! preg_match( '/[0-9]/', $content ) ) {
      return $content;
    }

    // Plain text (no tags): convert directly
    if ( strpos( $content, '<' ) === false ) {
      return self::convertNumberTokens( $content );
    }

    $protected = [];
    $working   = TextProtector::protect( $content, $protected );

    // Fail-safe: on protection failure use the legacy single-pass conversion
    if ( $working === null ) {
      return self::legacyFixNumber( $content );
    }

    return TextProtector::restore( self::convertNumberTokens( $working ), $protected );
  }

  /**
   * Converts standalone number tokens in plain text to Persian digits
   *
   * Unlike NumberConverter (used for post contents), no Persian/Arabic
   * context is required: prices and counters are converted in any language.
   * Boundaries are ASCII-based on purpose, so digits next to non-Latin
   * letters still convert (e.g. "12الف" => "۱۲الف"), matching the legacy
   * behavior of this method.
   *
   * @param string $text
   *
   * @return string
   */
  private static function convertNumberTokens( string $text ): string {
    return (string) preg_replace_callback(
      '~&#\d{1,5};|(?<![A-Za-z0-9_])[+-]?\d+(?:\.\d+)?(?![A-Za-z0-9_])~u',
      static function ( array $matches ): string {
        // Keep numeric HTML entities (e.g. &#8220;) untouched
        if ( $matches[0][0] === '&' ) {
          return $matches[0];
        }

        return self::toPersian( $matches[0] );
      },
      $text
    );
  }

  /**
   * Legacy single-pass conversion, kept as fail-safe when the HTML
   * protection pass cannot run
   *
   * @param string $content
   *
   * @return string
   */
  private static function legacyFixNumber( string $content ): string {
    return (string) preg_replace_callback(
      '~<(script|style|textarea|pre|code)\b[^>]*>.*?</\1>(*SKIP)(*F)|(?:&#\d{2,4};)|(?:[0]?[a-z][\x20-\x3B=\x3F-\x7F]*)|(\d+(?:\.\d+)?)|<\s*[^>]+>~isu',
      static function ( $matches ) {
        return isset( $matches[2] ) ? self::toPersian( $matches[2] ) : $matches[0];
      },
      $content
    );
  }

  /**
   * Converts English numbers to Persian numbers in post contents
   *
   * @param string $content Post content
   *
   * @return  string Formatted content
   */
  public static function persianNumber( string $content ): string {
    return isset( $content[1] ) ? self::toPersian( $content[1] ) : $content[0];
  }

  /**
   * Converts English digits to Persian digits
   *
   * @param string $number Numbers
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
   * @param string $number Numbers
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
