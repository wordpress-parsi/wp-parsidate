<?php
/**
 * Fix Arabic
 *
 * Fix Arabic alphabet in Farsi content
 * Replace Arabic alphabet and numbers with Persian characters
 */

namespace WPParsidate\App\Convert;

use WPParsidate\Settings\Settings;

class FixArabic {
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
   * @param  string  $content
   *
   * @return string Fixed string
   */
  public function fixArabic( $content ): string {
    return str_replace(
      array( 'ي', 'ك', 'ة', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' ),
      array( 'ی', 'ک', 'ه', '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ),
      $content,
    );
  }
}
