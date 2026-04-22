<?php

/**
 * Fix Numbers
 *
 * Fix number in WP hooks
 */

namespace WPParsidate\App\Convert;

use WPParsidate\Helper\Number;
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
      add_filter( 'the_title', [ $this, 'changeNumbersToPersian' ], 1000 );
    }

    if ( Settings::get( 'conv_contents', false ) ) {
      add_filter( 'the_content', [ $this, 'fixNumbersToPersian' ], 1000 );
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
   * Fix numbers and convert them to Persian digits style
   *
   * @param  mixed  $content
   *
   * @return string
   */
  public function fixNumbersToPersian( $content ): string {
    return Number::fixNumber( $content );
  }

  /**
   * Converts English digits to Persian digits
   *
   * @param  mixed  $string
   *
   * @return string
   */
  public function changeNumbersToPersian( $string ): string {
    return Number::toPersian( $string );
  }
}
