<?php
/**
 * Archive class
 *
 * Print post, month, year archive links
 */

namespace WPParsidate\Core;

use WPParsidate\Helper\Number;

defined( 'ABSPATH' ) || exit;

class Archive {
  /**
   * Create Persian Archives
   *
   * @param string|array $args
   */
  public static function getPostTypeArchives( $args = '' ): void {
    global $wpdb;

    $defaults = array(
      'type'            => 'monthly',
      'limit'           => '',
      'format'          => 'html',
      'before'          => '',
      'after'           => '',
      'show_post_count' => false,
      'echo'            => 1,
      'order'           => 'DESC',
      'post_type'       => 'post'
    );

    $r                = wp_parse_args( $args, $defaults );
    $post_type_object = get_post_type_object( $r['post_type'] );

    if ( is_null( $post_type_object ) || ! is_post_type_viewable( $post_type_object ) ) {
      return;
    }

    $r['post_type'] = $post_type_object->name;

    /// TODO: This query need optimized base on input type (yearly,monthly,daily)
    $results = $wpdb->get_results(
      $wpdb->prepare(
        "
				SELECT date( post_date ) AS date,
				    COUNT( ID ) AS count
				FROM $wpdb->posts
				WHERE post_date < NOW()
					AND post_type = %s
					AND post_status = 'publish'
				group by date
				ORDER BY post_date DESC
			",
        $r['post_type']
      )
    );

    if ( ! empty( $results ) ) {
      self::printArchive( $results, $r );
    }
  }

  /**
   * @param string|array $args
   */
  public static function getPostArchives( $args = '' ): void {
    global $wpdb;

    $defaults = array(
      'type'            => 'monthly',
      'limit'           => '',
      'format'          => 'html',
      'before'          => '',
      'after'           => '',
      'show_post_count' => false,
      'echo'            => 1,
      'order'           => 'DESC',
      'post_type'       => 'post'
    );

    $r = wp_parse_args( $args, $defaults );

    $results = $wpdb->get_results(
      "
				SELECT date ( post_date ) AS date,
				  	COUNT( ID ) AS count
				FROM $wpdb->posts
				WHERE post_date < NOW()
					AND post_type = 'post'
					AND post_status = 'publish'
				GROUP BY date
				ORDER BY post_date DESC
				"
    );

    if ( ! empty( $results ) ) {
      self::printArchive( $results, $r );
    }
  }

  /**
   * Yearly archive
   *
   * @param $year
   * @param $format
   * @param $before
   * @param $count
   * @param $show_post_count
   * @param $args
   */
  private static function printYearArchive( $year, $format, $before, $count, $show_post_count, $args ): void {
    $count = $show_post_count ? '&nbsp;(' . Number::toPersian( $count ) . ')' : '';

    $url = get_year_link( $year );
    if ( 'post' !== $args['post_type'] ) {
      $url = add_query_arg( 'post_type', $args['post_type'], $url );
    }

    echo get_archives_link( $url, Number::toPersian( $year ), $format, $before, $count );
  }

  /**
   * Monthly archive
   *
   * @param $old_date
   * @param $format
   * @param $before
   * @param $count
   * @param $show_post_count
   * @param $args
   */
  private static function printMonthArchive( $old_date, $format, $before, $count, $show_post_count, $args ): void {
    $monthsName = Names::getMonths();
    $year       = substr( $old_date, 0, 4 );
    $month      = substr( $old_date, 4, 2 );
    $count      = $show_post_count ? '&nbsp;(' . Number::toPersian( $count ) . ')' : '';

    $url = get_month_link( $year, $month );
    if ( 'post' !== $args['post_type'] ) {
      $url = add_query_arg( 'post_type', $args['post_type'], $url );
    }

    echo get_archives_link( $url, $monthsName[ (int) $month ] . ' ' . Number::toPersian( $year ), $format, $before, $count );
  }

  /**
   * @param $results
   * @param $args
   */
  private static function printArchive( $results, $args ): void {
    if ( $args['type'] === 'yearly' ) {
      $year  = parsidate( 'Y', $results[0]->date, 'eng' );
      $count = $results[0]->count;
      $c     = count( $results );

      for ( $i = 1; $i < $c; $i ++ ) {
        $dt   = $results[ $i ];
        $date = parsidate( 'Y', $dt->date, 'eng' );

        if ( $date === $year ) {
          $count += $dt->count;
        } else {
          self::printYearArchive( $year, $args['format'], $args['before'], $count, $args['show_post_count'], $args );

          $year  = $date;
          $count = $dt->count;
        }
      }

      self::printYearArchive( $year, $args['format'], $args['before'], $count, $args['show_post_count'], $args );

    } elseif ( $args['type'] === 'monthly' ) {
      $yearMonth = parsidate( 'Ym', $results[0]->date, 'eng' );
      $count     = $results[0]->count;
      $c         = count( $results );

      for ( $i = 1; $i < $c; $i ++ ) {
        $dt   = $results[ $i ];
        $date = parsidate( 'Ym', $dt->date, 'eng' );

        if ( $date === $yearMonth ) {
          $count += $dt->count;
        } else {
          self::printMonthArchive( $yearMonth, $args['format'], $args['before'], $count, $args['show_post_count'], $args );
          $yearMonth = $date;
          $count     = $dt->count;
        }
      }

      self::printMonthArchive( $yearMonth, $args['format'], $args['before'], $count, $args['show_post_count'], $args );

    } elseif ( $args['type'] === 'daily' ) {
      $monthsName = Names::getMonths();

      foreach ( $results as $row ) {
        $jDate = parsidate( 'Y,m,d', $row->date, false );
        $jDate = explode( ',', $jDate );
        $date  = date( 'Y,m,d', strtotime( $row->date ) );
        $date  = explode( ',', $date );
        $count = $args['show_post_count'] ? '&nbsp;(' . Number::toPersian( $row->count ) . ')' : '';
        $text  = Number::toPersian( $jDate[2] ) . ' ' . $monthsName[ (int) $jDate[1] ] . ' ' . Number::toPersian( $jDate[0] );
        // get_day_link convert to Jalali in FixPermalink:getDayLink
        $url = get_day_link( $date[0], $date[1], $date[2] );
        if ( 'post' !== $args['post_type'] ) {
          $url = add_query_arg( 'post_type', $args['post_type'], $url );
        }
        echo get_archives_link( $url, $text, $args['format'], '', $count );
      }
    }
  }
}
