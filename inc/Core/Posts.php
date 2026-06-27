<?php
/**
 * Posts class
 *
 * Fix post query based on Jalali date
 */

namespace WPParsidate\Core;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Helper\{Cache, Number, Param};

class Posts {
  public function __construct() {
    add_action( 'load-edit.php', [ $this, 'editPostHooks' ] );
  }

  /**
   * Converts post date pointer to Jalali pointer
   *
   * @param string $where
   * @param \WP_Query|string $wp_query
   *
   * @return string
   */
  public static function getPostsWhere( $where, $wp_query = '' ): string {
    global $wpdb;

    if ( empty( $wp_query ) ) {
      global $wp_query;
    }

    if ( empty( $wp_query->query_vars ) || ! $wp_query->is_main_query() ) {
      return $where;
    }

    $pd = WPP_ParsiDate::getInstance();

    $m      = $wp_query->query_vars['m'] ?? '';
    $hour   = $wp_query->query_vars['hour'] ?? '';
    $minute = $wp_query->query_vars['minute'] ?? '';
    $second = $wp_query->query_vars['second'] ?? '';
    $year   = $wp_query->query_vars['year'] ?? '';
    $month  = $wp_query->query_vars['monthnum'] ?? '';
    $day    = $wp_query->query_vars['day'] ?? '';

    if ( ! empty( $m ) ) {
      $len  = strlen( $m );
      $year = substr( $m, 0, 4 );

      if ( $len > 5 ) {
        $month = substr( $m, 4, 2 );
      }

      if ( $len > 7 ) {
        $day = substr( $m, 6, 2 );
      }

      if ( $len > 9 ) {
        $hour = substr( $m, 8, 2 );
      }
      if ( $len > 11 ) {
        $minute = substr( $m, 10, 2 );
      }

      if ( $len > 13 ) {
        $second = substr( $m, 12, 2 );
      }
    }

    if ( empty( $year ) || $year > 1700 ) {
      return $where;
    }

    $stamon  = 1;
    $staday  = 1;
    $stahou  = '00';
    $stamin  = '00';
    $stasec  = '00';
    $endmon  = 1;
    $endday  = 1;
    $endhou  = '00';
    $endmin  = '00';
    $endsec  = '00';
    $stayear = $year;
    $endyear = $year + 1;

    if ( ! empty( $month ) ) {
      $stamon  = $month;
      $endmon  = ( $month == 12 ? 1 : $month + 1 );
      $endyear = ( $endmon == 1 ? $stayear + 1 : $stayear );
    }

    if ( ! empty( $day ) ) {
      $staday = $day;
      $endday = ( $day == $pd->j_days_in_month[ (int) $month - 1 ] ? 1 : $day + 1 );
      $endmon = ( $endday == 1 ? $stamon + 1 : $stamon );
    }

    if ( ! empty( $hour ) ) {
      $stahou = $hour;
      $endhou = ( $hour == 24 ? '00' : $hour + 1 );
      $endday = ( $endhou == '00' ? $staday + 1 : $staday );
    }

    if ( ! empty( $minute ) ) {
      $stamin = $minute;
      $endmin = ( $minute == 59 ? '00' : $minute + 1 );
      $endhou = ( $endmin == '00' ? $stahou + 1 : $stahou );
    }

    if ( ! empty( $second ) ) {
      $stasec = $second;
      $endsec = ( $second == 59 ? '00' : $second + 1 );
      $endmin = ( $endsec == '00' ? $stamin + 1 : $stamin );
    }

    $stadate = "$stayear-$stamon-$staday";
    $enddate = "$endyear-$endmon-$endday";
    $stadate = gregdate( 'Y-m-d', $stadate );
    $enddate = gregdate( 'Y-m-d', $enddate );
    $stadate .= " $stahou:$stamin:$stasec";
    $enddate .= " $endhou:$endmin:$endsec";

    $patterns = array(
      '/YEAR\((.*?)post_date\s*\)\s*=\s*[0-9\']*/',
      '/DAYOFMONTH\((.*?)post_date\s*\)\s*=\s*[0-9\']*/',
      '/MONTH\((.*?)post_date\s*\)\s*=\s*[0-9\']*/',
      '/HOUR\((.*?)post_date\s*\)\s*=\s*[0-9\']*/',
      '/MINUTE\((.*?)post_date\s*\)\s*=\s*[0-9\']*/',
      '/SECOND\((.*?)post_date\s*\)\s*=\s*[0-9\']*/'
    );

    foreach ( $patterns as $pattern ) {
      $where = preg_replace( $pattern, '1 = 1', $where );
    }

    $prefixp = "$wpdb->posts.";
    $prefixp = ( strpos( $where, $prefixp ) === false ) ? '' : $prefixp;
    $where   .= " AND {$prefixp}post_date >= '$stadate' AND {$prefixp}post_date < '$enddate' ";

    return $where;
  }

  /**
   * Hooks admin functions for restrict posts in edit pages
   *
   * @return              void
   */
  public function editPostHooks(): void {
    add_action( 'restrict_manage_posts', [ $this, 'addMonthYearSelectPostFilter' ] );
    add_filter( 'posts_where', [ $this, 'adminPostsWhere' ] );
  }

  /**
   * Limits posts to a certain date, if date set
   *
   * @param string $where Query pointer
   *
   * @return              string New Pointer
   */
  public function adminPostsWhere( $where ): string {
    global $wp_query;

    if ( isset( $_GET['mfa'] ) && Param::get( 'mfa' ) !== '0' ) {
      $wp_query->query_vars['m'] = Param::get( 'mfa' );
      $where                     = self::getPostsWhere( $where, $wp_query );
    }

    return $where;
  }

  /**
   * Restrict posts to given date
   * @return            void
   */
  public function addMonthYearSelectPostFilter(): void {
    global $post_type, $post_status, $wpdb;

    if ( apply_filters( 'disable_months_dropdown', false, $post_type ) ) {
      return;
    }

    $cacheKey = 'post_select_months_list_' . md5( $post_type . $post_status );
    $list     = Cache::get( $cacheKey );

    if ( false === $list ) {
      $post_status_w = " AND post_status IN ('publish', 'future', 'draft', 'pending', 'private')";

      if ( $post_status !== "" && is_string( $post_status ) ) {
        $post_status_w = $wpdb->prepare( " AND post_status = %s", $post_status );
      }

      $query = $wpdb->prepare( "
            SELECT post_date
            FROM {$wpdb->posts}
            WHERE post_type = %s
            {$post_status_w}
            AND post_date > '1000-01-01 00:00:00'
            GROUP BY YEAR(post_date), MONTH(post_date)
            ORDER BY post_date DESC
        ", $post_type );

      $list = $wpdb->get_col( $query );

      if ( ! empty( $list ) ) {
        Cache::set( $cacheKey, $list, 2 * HOUR_IN_SECONDS );
      }
    }

    if ( empty( $list ) ) {
      return;
    }

    $m          = (int) Param::get( 'mfa', 0 );
    $predate    = '';
    $monthsName = Names::getMonths();

    echo '<select name="mfa">';
    echo '<option ' . selected( $m, 0, false ) . ' value="0">' . esc_html__( 'Show All Dates', 'wp-parsidate' ) . '</option>' . PHP_EOL;

    foreach ( $list as $date ) {
      $date      = parsidate( 'Ym', $date, 'eng' );
      $year      = substr( $date, 0, 4 );
      $month     = substr( $date, 4, 2 );
      $monthName = $monthsName[ (int) $month ];

      if ( $predate !== $date ) {
        echo sprintf( '<option %s value="%s">%s</option>',
          selected( $m, $date, false ),
          esc_html( $date ),
          esc_html( $monthName . ' ' . Number::toPersian( $year ) ) );
      }
      $predate = $date;
    }
    echo '</select>';
  }
}
