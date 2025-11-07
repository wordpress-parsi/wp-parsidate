<?php

namespace WPParsidate\App\Core;

use WPParsidate\Core\Months;
use WPParsidate\Helper\Number;
use WPParsidate\Settings\Settings;

class fixTitle {
  public function __construct() {
    add_filter( 'wp_title', [ $this, 'fixWpTitle' ], PHP_INT_MAX, 3 );
    add_filter( 'pre_get_document_title', [ $this, 'fixWpTitle' ], PHP_INT_MAX ); // WP 4.4+
  }

  /**
   * Fixes titles for archives
   *
   * @param  string  $title  Archive title
   * @param  string  $sep  Separator
   * @param  string  $seplocation  Separator location
   *
   * @return                  string New archive title
   */
  public function fixWpTitle( $title, $sep = '-', $seplocation = 'right' ): string {
    global $wp_query;

    $query = $wp_query->query;

    if ( ! is_archive() || ! Settings::get( 'persian_date', false ) ) {
      return $title;
    }

    if ( $seplocation === 'right' ) {
      $query = array_reverse( $query );
    }

    if ( isset( $query['monthnum'] ) ) {
      $monthsName        = Months::getNames();
      $query['monthnum'] = $monthsName[ (int) $query['monthnum'] ];
      $title             = implode( " ", $query ) . " $sep " . get_bloginfo( "name" );
    }

    if ( Settings::get( 'conv_page_title', false ) ) {
      $title = Number::fixNumber( $title );
    }

    return $title;
  }
}
