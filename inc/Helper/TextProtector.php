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
   * Blocks whose content must never be converted.
   *
   * Besides scripts, styles and code, this covers the raw-text and RCDATA
   * elements a browser does not parse as markup (title, xmp, iframe,
   * noembed, noframes, plaintext). Their content is shown verbatim or hides
   * later markup, so leaving it untouched also keeps a converter from
   * treating that text as document structure.
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
    'svg',
    'title',
    'xmp',
    'iframe',
    'noembed',
    'noframes',
    'plaintext'
  );

  /**
   * Pattern matching any token produced by makeToken().
   *
   * Used to split text on placeholders without building a large alternation
   * of every registered token, which can exceed PCRE's pattern size limit.
   */
  public const TOKEN_PATTERN = '/(%%WPPTXT_\d+_[0-9a-f]+%%)/';

  /**
   * A tag body: ">" closes the tag only outside a quoted attribute value.
   */
  private const TAG_BODY = '(?:[^>"\']|"[^"]*"|\'[^\']*\')*';

  /**
   * Comments and tags, in that order.
   *
   * A comment is matched as a whole, because its text is not markup. A tag
   * ends at the first ">" outside a quoted attribute value, so
   * <a title="a > b"> stays one fragment instead of leaving a stray
   * b"> behind as text. "<" followed by a space or a digit is a comparison
   * in text ("12 < 13"), not a tag, so it no longer swallows what follows it.
   *
   * The last two alternatives are a quote-blind fallback for malformed markup
   * with an unbalanced quote, which a browser reads to the next ">" anyway.
   */
  private const TAG_PATTERN = '~<!--.*?-->|</?[a-zA-Z]' . self::TAG_BODY . '>|<[!?]' . self::TAG_BODY . '>|</?[a-zA-Z][^>]*>|<[!?][^>]*>~us';

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
      self::TAG_PATTERN,
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

    // A fragment can hold placeholders of fragments protected before it (an
    // unclosed <title> or <textarea> swallows an earlier <code> block), and
    // strtr() does not look inside the text it has just put back. Repeat until
    // no placeholder is left, so none can leak into the page. Each pass
    // unwraps one level; fragments are original content, so the depth is small.
    for ( $pass = 0; $pass < 32; $pass++ ) {
      $restored = strtr( $html, $protected );

      if ( $restored === $html ) {
        break;
      }

      $html = $restored;
    }

    return $html;
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
