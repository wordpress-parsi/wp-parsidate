<?php
/**
 * Numbers converter for English numbers in HTML content
 */

declare( strict_types=1 );

namespace WPParsidate\Helper;

final class NumberConverter {
  /**
   * Public API.
   *
   * Converts ASCII digits to Persian digits in eligible text nodes only.
   * Returns original content unchanged if conversion is unnecessary or parsing fails.
   *
   * The markup is never parsed and written back: TextProtector swaps fragile
   * blocks and every tag with placeholders, so only the text between tags is
   * rewritten and the markup is restored byte-for-byte. Passing the content
   * through DOMDocument::saveHTML() is unsafe, because libxml copies text
   * after "&{" in an attribute value without escaping it and can turn an
   * escaped attribute (for example inside <title>) into live markup.
   */
  public static function convertContent( string $content ): string {
    if ( $content === '' ) {
      return $content;
    }

    // Fast bailouts.
    if ( ! self::containsAsciiDigit( $content ) ) {
      return $content;
    }

    // If your site is Persian-only and you want ALL visible numbers converted,
    // you can remove this check. Keeping it improves performance and avoids
    // converting English-only text blocks.
    if ( ! self::containsPersianOrArabic( $content ) ) {
      return $content;
    }

    $protected = [];
    $working   = TextProtector::protect( $content, $protected );

    // Fail-safe: on protection failure, return original content untouched.
    if ( $working === null ) {
      return $content;
    }

    return TextProtector::restore( self::convertTextSegments( $working, $protected ), $protected );
  }

  /**
   * Quick ASCII digit test.
   */
  private static function containsAsciiDigit( string $content ): bool {
    return (bool) preg_match( '/[0-9]/', $content );
  }

  /**
   * Check whether the content contains Persian/Arabic script characters.
   *
   * This reduces unnecessary work on English-only content and avoids converting
   * English sections in multilingual pages unless they contain Persian/Arabic text nearby.
   */
  private static function containsPersianOrArabic( string $content ): bool {
    return (bool) preg_match( '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}]/u', $content );
  }

  /**
   * Convert digits in the unprotected text segments of a protected document.
   *
   * The segments between placeholders are the former text nodes. One without
   * Persian/Arabic text is left alone, matching the previous per-text-node
   * behavior; this is the check to remove if you want all visible numbers
   * converted everywhere.
   *
   * @param string $text Content with tags replaced by placeholders.
   * @param array<string,string> $protected Placeholder => original fragment.
   *
   * @return string
   */
  private static function convertTextSegments( string $text, array $protected ): string {
    if ( empty( $protected ) ) {
      return self::convertNumbersInText( $text );
    }

    // One fixed pattern for the placeholder format (TextProtector::makeToken()).
    // Joining every placeholder into one alternation made the pattern grow with
    // the number of tags, and long posts went past the PCRE pattern size limit
    // ("regular expression is too large").
    $parts = preg_split( TextProtector::TOKEN_PATTERN, $text, - 1, PREG_SPLIT_DELIM_CAPTURE );

    if ( ! is_array( $parts ) ) {
      // Fail-safe: convert nothing rather than risk rewriting protected bytes.
      return $text;
    }

    $result = '';

    foreach ( $parts as $index => $part ) {
      // Odd entries are the captured placeholders.
      if ( $index % 2 === 1 ) {
        $result .= $part;
        continue;
      }

      $result .= self::containsPersianOrArabic( $part ) ? self::convertNumbersInText( $part ) : $part;
    }

    return $result;
  }

  /**
   * Convert only standalone numeric tokens, not digits inside identifiers.
   *
   * Converts:
   *   875254
   *   12.5
   *   +15
   *   -7.2
   *
   * Does not convert:
   *   abc123
   *   SR7_1_1
   *   item_25
   *   v2ray
   *
   * Numeric character references (&#8220;) are kept intact.
   */
  private static function convertNumbersInText( string $text ): string {
    return (string) preg_replace_callback(
      '~&#\d+;|&#x[0-9a-f]+;|(?<![\pL\pN_])([+-]?\d+(?:\.\d+)?)(?![\pL\pN_])~iu',
      static function ( array $matches ): string {
        if ( $matches[0][0] === '&' ) {
          return $matches[0];
        }

        return self::toPersianNumber( $matches[1] );
      },
      $text
    );
  }

  private static function toPersianNumber( string $value ): string {
    return strtr(
      $value,
      [
        '0' => '۰',
        '1' => '۱',
        '2' => '۲',
        '3' => '۳',
        '4' => '۴',
        '5' => '۵',
        '6' => '۶',
        '7' => '۷',
        '8' => '۸',
        '9' => '۹',
        // If you want Persian decimal separator instead of ".", uncomment:
        // '.' => '٫',
      ]
    );
  }
}
