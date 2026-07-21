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
     * @param string|null $title Archive title.
     * @param string      $sep Separator.
     * @param string      $seplocation Separator location.
     *
     * @return string
     */
    public function fixWpTitle( ?string $title, $sep = '-', $seplocation = 'right' ): string {
        global $wp_query;

        // pre_get_document_title may return null.
        $title ??= '';

        $query = $wp_query->query ?? [];

        if ( ! is_archive() || ! Settings::get( 'persian_date', false ) ) {
            return $title;
        }

        if ( $seplocation === 'right' ) {
            $query = array_reverse( $query );
        }

        if ( isset( $query['monthnum'] ) ) {
            $monthsName = Names::getMonths();

            if ( isset( $monthsName[ (int) $query['monthnum'] ] ) ) {
                $query['monthnum'] = $monthsName[ (int) $query['monthnum'] ];
            }

            $title = implode( ' ', $query ) . " {$sep} " . get_bloginfo( 'name' );
        }

        if ( Settings::get( 'conv_page_title', false ) ) {
            $title = Number::fixNumber( $title );
        }

        return (string) $title;
    }
}
