<?php
/**
 * Fix title settings
 *
 * Fix page title
 */

namespace WPParsidate\App\Core;

use WPParsidate\Core\Names;
use WPParsidate\Helper\Number;
use WPParsidate\Settings\Settings;

class FixTitle {
  public function __construct() {
    add_filter( 'wp_title', [ $this, 'fixWpTitle' ], PHP_INT_MAX, 3 );
    add_filter( 'pre_get_document_title', [ $this, 'fixWpTitle' ], PHP_INT_MAX ); // WP 4.4+
  }

  /**
   * Fixes titles for archives
   *
   * The title is built only from local date parts (validated plugin data) and
   * never from the raw request query vars stored in $wp_query->query, which
   * hold unsanitized user input and used to be reflected into <title>
   * (unauthenticated reflected XSS). The value is escaped at the single
   * return point, because wp_get_document_title() and wp_title() both echo
   * the filtered value without escaping it.
   *
   * @param string|null $title Archive title.
   * @param string $sep Separator.
   * @param string $seplocation Separator location.
   *
   * @return string
   */
  public function fixWpTitle( ?string $title, $sep = '-', $seplocation = 'right' ): string {
    global $wp_query;

    // pre_get_document_title may return null.
    $title ??= '';

    /**
     * Filters the separator for the document title.
     *
     * @since WP 4.4.0
     *
     * @param string $sep Document title separator. Default '-'.
     */
    $sep = apply_filters( 'document_title_separator', $sep );

    // Sanitized copy of the request vars. $wp_query->query is deliberately not
    // read: it keeps the raw values from the request (wp_magic_quotes()) and
    // reflecting them here allowed query strings such as
    // "?monthnum=9&order=</title><script>..." to inject markup into <title>.
    $query_vars = $wp_query->query_vars ?? [];

    $localized = '';

    if ( is_archive() && Settings::get( 'persian_date', false ) ) {
      $year  = isset( $query_vars['year'] ) ? absint( $query_vars['year'] ) : 0;
      $month = isset( $query_vars['monthnum'] ) ? absint( $query_vars['monthnum'] ) : 0;
      $day   = isset( $query_vars['day'] ) ? absint( $query_vars['day'] ) : 0;

      $monthsName = Names::getMonths();

      if ( $month > 0 && $month <= 12 && isset( $monthsName[ $month ] ) ) {
        $month = $monthsName[ $month ];
      } else {
        $month = 0;
      }

      // Only these validated date parts are ever copied into the title: no
      // other query var (order, orderby, cat, ...) can reach the output.
      // Order matches the previous output for pretty permalinks:
      // right separator (default) => "day month year", otherwise reversed.
      $parts = array();

      if ( $day > 0 ) {
        $parts[] = $day;
      }

      if ( ! empty( $month ) ) {
        $parts[] = $month;
      }

      if ( $year > 0 ) {
        $parts[] = $year;
      }

      if ( ! empty( $parts ) ) {
        if ( $seplocation !== 'right' ) {
          $parts = array_reverse( $parts );
        }

        $localized = implode( ' ', $parts ) . " $sep " . get_bloginfo( 'name' );
      }
    }

    if ( $localized !== '' ) {
      $title = $localized;
    }

    if ( Settings::get( 'conv_page_title', false ) ) {
      $title = Number::fixNumber( $title );
    }

    // Single output point, escaped: wp_get_document_title() and wp_title()
    // both echo the filtered value without escaping it. WP's esc_html() does
    // not double encode, so separators/titles that already contain entities
    // (SEO plugins use e.g. "&ndash;") keep rendering as before.
    return esc_html( (string) $title );
  }
}
