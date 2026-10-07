<?php
/**
 * Fix Arabic
 *
 * Fix Arabic alphabet in Farsi content
 * Replace Arabic alphabet and numbers with Persian characters
 */

namespace WPParsidate\App\Convert;

use WPParsidate\Helper\TextProtector;
use WPParsidate\Settings\Settings;

class FixArabic {
  /**
   * Arabic characters replaced with Persian equivalents
   */
  private const SEARCH_CHARS  = array( 'ي', 'ك', 'ة', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' );
  private const REPLACE_CHARS = array( 'ی', 'ک', 'ه', '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );

  public function __construct() {
    if ( Settings::get( 'conv_arabic', false ) ) {
      add_filter( 'the_content', [ $this, 'fixArabic' ], 1000 );
      add_filter( 'the_title', [ $this, 'fixArabic' ], 1000 );
      add_filter( 'comment_text', [ $this, 'fixArabic' ], 1000 );
      add_filter( 'wp_list_categories', [ $this, 'fixArabic' ], 1000 );
      add_filter( 'the_excerpt', [ $this, 'fixArabic' ], 1000 );
      add_filter( 'wp_title', [ $this, 'fixArabic' ], 1000 );
    }
  }

  /**
   * Fix arabic foreign characters
   *
   * Converts only the visible text of HTML content: tags, attributes (img
   * src, srcset, href, ...) and script/style/code blocks are protected and
   * restored byte-for-byte by TextProtector, so media URLs containing Arabic
   * characters keep working (they were rewritten before, producing 404s on
   * product archives and loops).
   *
   * @param string $content
   *
   * @return string Fixed string
   */
  public function fixArabic( $content ): string {
    $content = (string) $content;

    if ( $content === '' ) {
      return $content;
    }

    $converted = str_replace( self::SEARCH_CHARS, self::REPLACE_CHARS, $content );

    // Nothing to convert: the common case, bail out without any HTML work
    if ( $converted === $content ) {
      return $content;
    }

    // Plain text (no tags): the full conversion is safe
    if ( strpos( $content, '<' ) === false ) {
      return $converted;
    }

    $protected = [];
    $working   = TextProtector::protect( $content, $protected );

    // Fail-safe: fall back to the legacy full-string conversion
    if ( $working === null ) {
      return $converted;
    }

    $working = str_replace( self::SEARCH_CHARS, self::REPLACE_CHARS, $working );

    // Restore protected tags and blocks byte-for-byte
    return TextProtector::restore( $working, $protected );
  }
}
