<?php
/**
 * Archive class
 *
 * Print post, month, year archive links
 */

namespace WPParsidate\Core;

defined( 'ABSPATH' ) || exit;

class Archive {
  /**
   * Create Persian Archives
   *
   * @param  string|array  $args
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
    $results        = $wpdb->get_results(
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
   * @param  string|array  $args
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
   * @param $year
   * @param $format
   * @param $before
   * @param $count
   * @param $show_post_count
   * @param $r
   */
  private static function printYearArchive( $year, $format, $before, $count, $show_post_count, $r ): void {
    if ( $show_post_count ) {
      $count = '&nbsp;(' . fix_number( $count ) . ')';
    } else {
      $count = '';
    }

    $url = get_year_link( $year );

    if ( 'post' !== $r['post_type'] ) {
      $url = add_query_arg( 'post_type', $r['post_type'], $url );
    }

    echo get_archives_link( $url, fix_number( $year ), $format, $before, $count );
  }

  /**
   * @param $old_date
   * @param $format
   * @param $before
   * @param $count
   * @param $show_post_count
   * @param $r
   */
  private static function printMonthArchive( $old_date, $format, $before, $count, $show_post_count, $r ): void {
    $wpp_months_name = Names::getMonths();
    $year            = substr( $old_date, 0, 4 );
    $month           = substr( $old_date, 4, 2 );

    if ( $show_post_count ) {
      $count = '&nbsp;(' . fix_number( $count ) . ')';
    } else {
      $count = '';
    }

    $url = get_month_link( $year, $month );

    if ( 'post' !== $r['post_type'] ) {
      $url = add_query_arg( 'post_type', $r['post_type'], $url );
    }

    echo get_archives_link( $url, $wpp_months_name[ (int) $month ] . ' ' . fix_number( $year ), $format, $before,
      $count );
  }

  /**
   * @param $results
   * @param $args
   */
  private static function printArchive( $results, $args ): void {
    global $wpp_months_name;

    if ( $args['type'] === 'yearly' ) {
      $old_date = parsidate( 'Y', $results[0]->date, 'eng' );
      $count    = $results[0]->count;
      $c        = count( $results );

      for ( $i = 1; $i < $c; $i ++ ) {
        $dt   = $results[ $i ];
        $date = parsidate( 'Y', $dt->date, 'eng' );

        if ( $date === $old_date ) {
          $count += $dt->count;
        } else {
          self::printYearArchive( $old_date, $args['format'], $args['before'], $count,
            $args['show_post_count'],
            $args );

          $old_date = $date;
          $count    = $dt->count;
        }
      }

      self::printYearArchive( $old_date, $args['format'], $args['before'], $count, $args['show_post_count'],
        $args );

    } elseif ( $args['type'] === 'monthly' ) {
      $old_date = parsidate( 'Ym', $results[0]->date, 'eng' );
      $count    = $results[0]->count;
      $c        = count( $results );

      for ( $i = 1; $i < $c; $i ++ ) {
        $dt   = $results[ $i ];
        $date = parsidate( 'Ym', $dt->date, 'eng' );

        if ( $date === $old_date ) {
          $count += $dt->count;
        } else {
          self::printMonthArchive( $old_date, $args['format'], $args['before'], $count,
            $args['show_post_count'],
            $args );
          $old_date = $date;
          $count    = $dt->count;
        }
      }

      self::printMonthArchive( $old_date, $args['format'], $args['before'], $count, $args['show_post_count'],
        $args );

    } elseif ( $args['type'] === 'daily' ) {
      foreach ( $results as $row ) {
        $date = parsidate( 'Y,m,d', $row->date, 'eng' );
        $date = explode( ',', $date );

        if ( $args['show_post_count'] ) {
          $count = '&nbsp;(' . fix_number( $row->count ) . ')';
        } else {
          $count = '';
        }

        $text = fix_number( $date[2] ) . ' ' . $wpp_months_name[ (int) $date[1] ] . ' ' . fix_number( $date[0] );

        echo get_archives_link( get_day_link( $date[0], $date[1], $date[2] ), $text, $args['format'],
          $args['before'], $count );
      }
    }
  }
}
