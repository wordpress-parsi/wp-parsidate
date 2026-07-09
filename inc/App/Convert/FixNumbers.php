<?php

/**
 * Fix Numbers
 *
 * Fix number in WP hooks
 */

namespace WPParsidate\App\Convert;

use WPParsidate\Helper\{Number, NumberConverter, WordPress};
use WPParsidate\Settings\Settings;

class FixNumbers {
  public function __construct() {
    if ( Settings::get( 'conv_number_format_i18n', false ) ) {
      add_filter( 'number_format_i18n', [ $this, 'changeNumbersToPersian' ], 1000 );
    }

    if ( Settings::get( 'conv_page_title', false ) ) {
      add_filter( 'wp_title', [ $this, 'changeNumbersToPersian' ], 1000 );
    }

    if ( Settings::get( 'conv_title', false ) ) {
      add_filter( 'the_title', [ $this, 'changeNumbersToPersian' ], 0 );
    }

    if ( Settings::get( 'conv_contents', false ) ) {
      add_filter( 'the_content', [ $this, 'fixPostContentNumbers' ], 1000 );
    }

    if ( Settings::get( 'conv_excerpt', false ) ) {
      add_filter( 'the_excerpt', [ $this, 'fixNumbersToPersian' ], 1000 );
    }

    if ( Settings::get( 'conv_comments', false ) ) {
      add_filter( 'comment_text', [ $this, 'fixNumbersToPersian' ], 1000 );
    }

    if ( Settings::get( 'conv_comment_count', false ) ) {
      add_filter( 'comments_number', [ $this, 'changeNumbersToPersian' ], 1000 );
    }

    if ( Settings::get( 'conv_cats', false ) ) {
      add_filter( 'wp_list_categories', [ $this, 'fixNumbersToPersian' ], 1000 );
    }
  }

  /**
   * Fix numbers in content (Post content with HTML) and convert them to Persian digits
   *
   * @param $content
   *
   * @return string
   */
  public function fixPostContentNumbers( $content ): string {
    if ( ! self::shouldRunContentNumberConverter( $content ) ) {
      return $content;
    }

    $converted = NumberConverter::convertContent( $content );

    return apply_filters( 'wp_parsidate_fix_post_content_numbers', $converted, $content );
  }

  /**
   * Decide whether filter should run in current request context.
   *
   * @param string $content
   *
   * @return bool
   */
  private static function shouldRunContentNumberConverter( string $content ): bool {
    if ( $content === '' ) {
      return false;
    }

    // No digits => no work.
    if ( ! preg_match( '/[0-9]/', $content ) ) {
      return false;
    }

    // No Persian/Arabic chars => skip.
    // Remove if your site should convert numbers in all languages.
    if ( ! preg_match( '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}]/u', $content ) ) {
      return false;
    }

    // Avoid admin screens except AJAX if needed.
    if ( is_admin() && ! wp_doing_ajax() ) {
      return false;
    }

    // Usually don't touch feeds.
    if ( WordPress::isFeed() ) {
      return false;
    }

    // Optional: skip REST responses if you don't want transformed content there.
    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
      return false;
    }

    if ( is_preview() ) {
      return false;
    }

    return true;
  }

  /**
   * Fix numbers and convert them to Persian digits style
   *
   * @param mixed $content
   *
   * @return string
   */
  public function fixNumbersToPersian( $content ): string {
    return Number::fixNumber( $content );
  }

  /**
   * Converts English digits to Persian digits
   *
   * @param mixed $string
   *
   * @return string
   */
  public function changeNumbersToPersian( $string ): string {
    return Number::toPersian( $string );
  }
}
