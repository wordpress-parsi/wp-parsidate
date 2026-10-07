<?php
/**
 * Text protection helper
 *
 * Protects fragile parts of HTML content (tags, attributes, script/style/
 * code blocks, ...) with random placeholders so converters can safely work
 * on the visible text only, then restores everything byte-for-byte.
 */

namespace WPParsidate\Helper;

final class TextProtector {
  /**
   * Blocks whose content must never be converted (scripts, styles, code, ...)
   */
  public const PROTECTED_BLOCKS = array(
    'script',
    'style',
    'pre',
    'code',
    'kbd',
    'samp',
    'textarea',
    'noscript',
    'svg'
  );

  /**
   * Replace fragile blocks (script/style/...) with placeholders so their
   * content is never converted and restored byte-for-byte afterward.
   *
   * @param string $html
   * @param array<string,string> $protected Placeholder => original fragment
   *
   * @return string|null Content with placeholders, or null on regex failure
   */
  public static function protectFragileBlocks( string $html, array &$protected ): ?string {
    $callback = static function ( array $matches ) use ( &$protected ): string {
      return self::protectFragment( $matches[0], $protected );
    };

    // Well-formed fragile blocks: <tag ...> ... </tag>
    $tagsPattern = implode( '|', array_map( 'preg_quote', self::PROTECTED_BLOCKS ) );

    $result = preg_replace_callback(
      '~<(' . $tagsPattern . ')\b[^>]*>.*?</\1>~isu',
      $callback,
      $html
    );

    if ( $result === null ) {
      return null;
    }

    // Unclosed blocks: browsers treat the rest of the document as the block's
    // raw content, so it is not visible text and must not be converted either
    foreach ( self::PROTECTED_BLOCKS as $tag ) {
      $result = preg_replace_callback(
        '~<' . $tag . '\b[^>]*>(?!.*?</' . $tag . '>).*~isu',
        $callback,
        (string) $result
      );

      if ( $result === null ) {
        return null;
      }
    }

    return (string) $result;
  }

  /**
   * Replace fragile blocks (script/style/...) and every remaining tag (and
   * its attributes) with placeholders so only visible text is left for
   * conversion. Protected parts are restored byte-for-byte afterward, which
   * keeps media URLs containing Arabic characters or Persian digits working.
   *
   * @param string $html
   * @param array<string,string> $protected Placeholder => original fragment
   *
   * @return string|null Content with placeholders, or null on regex failure
   */
  public static function protect( string $html, array &$protected ): ?string {
    $result = self::protectFragileBlocks( $html, $protected );

    if ( $result === null ) {
      return null;
    }

    // Protect every remaining tag (and its attributes) so only text is converted
    $result = preg_replace_callback(
      '~<[^>]*>~us',
      static function ( array $matches ) use ( &$protected ): string {
        return self::protectFragment( $matches[0], $protected );
      },
      (string) $result
    );

    return $result === null ? null : (string) $result;
  }

  /**
   * Restore protected placeholders byte-for-byte
   *
   * @param string $html
   * @param array<string,string> $protected Placeholder => original fragment
   *
   * @return string
   */
  public static function restore( string $html, array $protected ): string {
    if ( empty( $protected ) ) {
      return $html;
    }

    return strtr( $html, $protected );
  }

  /**
   * Replace a single fragment with a placeholder and register it
   *
   * @param string $fragment
   * @param array<string,string> $protected
   *
   * @return string Placeholder
   */
  public static function protectFragment( string $fragment, array &$protected ): string {
    $token = self::makeToken( count( $protected ) );
    $protected[ $token ] = $fragment;

    return $token;
  }

  /**
   * Random, conversion-safe placeholder token
   *
   * @param int $index
   *
   * @return string
   */
  public static function makeToken( int $index ): string {
    try {
      $suffix = bin2hex( random_bytes( 8 ) );
    } catch ( \Exception $e ) {
      $suffix = md5( uniqid( (string) $index, true ) );
    }

    return '%%WPPTXT_' . $index . '_' . $suffix . '%%';
  }
}
