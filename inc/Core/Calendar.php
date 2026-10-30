<?php
/**
 * Calendar class
 *
 * Print Jalali Calendar
 */

namespace WPParsidate\Core;

use WPParsidate\Helper\Cache;
use WPParsidate\Helper\Number;

defined( 'ABSPATH' ) || exit;

class Calendar {
  /**
   * Create Persian Calendar
   *
   * @param string $postType Post type
   * @param string $theme Theme style
   *
   * @return          void
   * @author          Parsa Kafi
   * @author          Mobin Ghasempoor
   */
  public static function printCalendar( string $postType = 'post', string $theme = 'none' ): void {
    global $wpdb, $m, $monthnum, $year, $day, $posts;

    // Both arguments can come from a Gutenberg block attribute, which anyone
    // who can edit a post is able to set, so treat them as untrusted input.
    // They are only ever used as a post type slug and as a CSS class suffix.
    $postType = sanitize_key( $postType );

    if ( '' === $postType ) {
      $postType = 'post';
    }

    $theme = sanitize_html_class( $theme, 'none' );

    $monthsName = Names::getMonths();
    $jy         = 0;
    $pd         = WPP_ParsiDate::getInstance();
    $jm         = $monthnum;
    // Get the site's timezone offset in seconds
    $tzOffset = (int) ( (float) get_option( 'gmt_offset' ) ) * HOUR_IN_SECONDS;

    if ( $m != '' ) {
      $m  = preg_replace( "/[^0-9]/", "", $m );
      $jy = substr( $m, 0, 4 );
    } elseif ( $year !== '' ) {
      $jy = $year;
    }

    if ( $jy > 1500 ) {
      // A Gregorian archive: take the date parts from the query vars, or from ?m=YYYYMMDD.
      $gy = (int) ( '' !== (string) $year ? $year : substr( $m, 0, 4 ) );
      $gm = (int) ( '' !== (string) $monthnum ? $monthnum : substr( $m, 4, 2 ) );
      $gd = (int) ( '' !== (string) $day ? $day : substr( $m, 6, 2 ) );

      list( $jy, $jm, $jd ) = $pd->gregorian_to_persian( $gy, max( 1, $gm ), max( 1, $gd ) );
    }

    // Cache group and key suffix, invalidated whenever a post changes.
    $lastChanged = wp_cache_get_last_changed( 'posts' );

    if ( ! $posts ) {
      $cacheKey = 'gotsome_' . md5( $postType ) . ':' . $lastChanged;
      $gotSome  = Cache::get( $cacheKey );

      if ( false === $gotSome ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Same query as core get_calendar(), there is no WordPress API for it. Cached per posts change.
        $gotSome = (int) $wpdb->get_var(
          $wpdb->prepare(
            "SELECT 1 AS test
					FROM $wpdb->posts
					WHERE post_type = %s
					  	AND post_status = 'publish'
					LIMIT 1",
            $postType )
        );

        Cache::set( $cacheKey, $gotSome, DAY_IN_SECONDS * 3 );
      }

      if ( ! $gotSome ) {
        return;
      }
    }

    $week_begins = (int) get_option( 'start_of_week' );
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public read-only query var, same as core get_calendar().
    $w            = (int) sanitize_text_field( wp_unslash( $_GET['w'] ?? '' ) );
    $is_gregorian = false;

    if ( ! empty( $jm ) && ! empty( $jy ) ) {
      $thisMonth = zeroise( (int) $jm, 2 );
      $thisYear  = '' . (int) $jy;
    } elseif ( ! empty( $w ) ) {
      $thisYear = '' . (int) substr( $m, 0, 4 );
      $d        = ( ( $w - 1 ) * 7 ) + 6; //it seems MySQL's weeks disagree with PHP's
      // Month of the day $d days after January 1st (was a MySQL DATE_ADD() query).
      $thisMonth = gmdate( 'm', gmmktime( 0, 0, 0, 1, 1 + $d, (int) $thisYear ) );
    } elseif ( ! empty( $m ) ) {
      $thisYear = '' . (int) substr( $m, 0, 4 );

      if ( strlen( $m ) < 6 ) {
        $thisMonth = '01';
      } else {
        $thisMonth = '' . zeroise( (int) substr( $m, 4, 2 ), 2 );
      }
    } else {
      $is_gregorian = true;
      $nowTimestamp = current_time( 'timestamp' );
      $thisYear     = gmdate( 'Y', $nowTimestamp + $tzOffset );
      $thisMonth    = gmdate( 'm', $nowTimestamp + $tzOffset );
      $thisDay      = gmdate( 'd', $nowTimestamp + $tzOffset );
    }

    if ( $is_gregorian ) {
      list( $jthisyear,
        $jthismonth,
        $jthisday ) = $pd->gregorian_to_persian( $thisYear, $thisMonth, $thisDay );

      $unixmonth = $pd->gregorian_date( 'Y-m-d 00:00:00', "$jthisyear-$jthismonth-01" );
    } else {
      $gdate      = $pd->persian_to_gregorian( $thisYear, $thisMonth, 1 );
      $unixmonth  = mktime( 0, 0, 0, $gdate[1], $gdate[2], $gdate[0] );
      $jthisyear  = $thisYear;
      $jthismonth = $thisMonth;
    }

    $jnextmonth = $jthismonth + 1;
    $jnextyear  = $jthisyear;

    if ( $jnextmonth > 12 ) {
      $jnextmonth = 1;
      $jnextyear ++;
    }

    $start = $pd->gregorian_date( 'Y-m-d 00:00:00', "$jthisyear-$jthismonth-01" );
    $end   = $pd->gregorian_date( 'Y-m-d 23:59:59',
      "$jnextyear-$jthismonth-" . $pd->j_days_in_month[ $jthismonth - 1 ] );

    $cacheKey = 'adjacent_' . md5( $start . '|' . $end . '|' . $postType ) . ':' . $lastChanged;
    $adjacent = Cache::get( $cacheKey );

    if ( false === $adjacent ) {
      $adjacent = [];

      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Same query as core get_calendar(), there is no WordPress API for it. Cached per posts change.
      $adjacent['previous'] = $wpdb->get_row(
        $wpdb->prepare(
          "SELECT MONTH(post_date) AS month,
				  	YEAR(post_date) AS year
                  FROM $wpdb->posts
                  WHERE post_date < %s
                  	AND post_type = %s
                      AND post_status = 'publish'
                  ORDER BY post_date DESC
                  LIMIT 1",
          $start, $postType )
      );

      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Same query as core get_calendar(), there is no WordPress API for it. Cached per posts change.
      $adjacent['next'] = $wpdb->get_row(
        $wpdb->prepare(
          "SELECT MONTH(post_date) AS month,
				  	YEAR(post_date) AS year
                  FROM $wpdb->posts
                  WHERE post_date >= %s
                  	AND post_type = %s
                      AND post_status = 'publish'
                  ORDER BY post_date ASC
                  LIMIT 1",
          $end, $postType )
      );

      Cache::set( $cacheKey, $adjacent, DAY_IN_SECONDS * 3 );
    }

    $previous = $adjacent['previous'] ?? null;
    $next     = $adjacent['next'] ?? null;

    $calendar_output = '<table id="wp-calendar" class="widget_calendar ' . WP_PARSI_CLASS_PREFIX . 'calendar-widget-table ' . WP_PARSI_CLASS_PREFIX . 'calendar-widget-theme-' . esc_attr( $theme ) . '">' .
                       '<caption>' . esc_html( $monthsName[ (int) $jthismonth ] ) . ' ' .
                       esc_html( $pd->persian_date( 'Y', $unixmonth ) ) . '</caption><thead><tr>';
    $myweek          = array();

    for ( $wdcount = 0; $wdcount <= 6; $wdcount ++ ) {
      $myweek[] = $pd->persian_day_small[ ( $wdcount + $week_begins ) % 7 ];
    }

    foreach ( $myweek as $wd ) {
      $calendar_output .= "\n\t\t<th scope=\"col\" title=\"" . esc_attr( $wd ) . '">' . esc_html( $wd ) . '</th>';
    }

    $calendar_output .= '</tr></thead><tfoot><tr>';

    if ( $previous ) {
      $previous_month = $jthismonth - 1;
      $previous_year  = $jthisyear;

      if ( $previous_month == 0 ) {
        $previous_month = 12;
        $previous_year --;
      }

      $url = get_month_link( $previous_year, $previous_month );
      if ( 'post' !== $postType ) {
        $url = add_query_arg( 'post_type', $postType, $url );
      }
      $calendar_output .= "\n\t\t" . '<td colspan="3" id="prev"><a href="' . esc_url( $url ) . '">&laquo; ' . esc_html( $monthsName[ $previous_month ] ) . '</a></td>';
    } else {
      $calendar_output .= "\n\t\t" . '<td colspan="3" id="prev" class="pad">&nbsp;</td>';
    }

    $calendar_output .= "\n\t\t" . '<td class="pad">&nbsp;</td>';

    if ( $next ) {
      $next_month = $jthismonth + 1;
      $next_year  = $jthisyear;

      if ( $next_month == 13 ) {
        $next_month = 1;
        $next_year ++;
      }

      $url = get_month_link( $next_year, $next_month );
      if ( 'post' !== $postType ) {
        $url = add_query_arg( 'post_type', $postType, $url );
      }
      $calendar_output .= "\n\t\t" . '<td colspan="3" id="next"><a href="' . esc_url( $url ) . '">' . esc_html( $monthsName[ $next_month ] ) . ' &raquo;</a></td>';
    } else {
      $calendar_output .= "\n\t\t" . '<td colspan="3" id="next" class="pad">&nbsp;</td>';
    }

    $calendar_output .= '</tr></tfoot><tbody><tr>';

    //____________________________________________________________________________________________________________________________________

    $cacheKey      = 'days_' . md5( $start . '|' . $end . '|' . $postType ) . ':' . $lastChanged;
    $daysWithPosts = Cache::get( $cacheKey );

    if ( false === $daysWithPosts ) {
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Same query as core get_calendar(), there is no WordPress API for it. Cached per posts change.
      $daysWithPosts = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT DISTINCT DAYOFMONTH ( post_date ),
			  	MONTH ( post_date ),
			  	YEAR ( post_date )
          	FROM $wpdb->posts
         		WHERE post_date > %s
         		    AND post_date < %s
          		AND post_type = %s
          		AND post_status = 'publish'
          	", $start, $end, $postType ),
        ARRAY_N
      );

      Cache::set( $cacheKey, $daysWithPosts, DAY_IN_SECONDS * 3 );
    }

    $dayWithPost = array();
    if ( $daysWithPosts ) {
      foreach ( $daysWithPosts as $dayWith ) {
        $dayWithPost[] = $pd->persian_date( 'j', "$dayWith[2]-$dayWith[1]-$dayWith[0]", 'eng' );
      }
    }

    $userAgent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );

    if ( strpos( $userAgent, 'MSIE' ) !== false
         || stripos( $userAgent, 'camino' ) !== false
         || stripos( $userAgent, 'safari' ) !== false ) {
      $ak_title_separator = "\n";
    } else {
      $ak_title_separator = ', ';
    }

    $ak_titles_for_day = array();
    $cacheKey          = 'titles_' . md5( $start . '|' . $end . '|' . $postType ) . ':' . $lastChanged;
    $ak_post_titles    = Cache::get( $cacheKey );

    if ( false === $ak_post_titles ) {
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Same query as core get_calendar(), there is no WordPress API for it. Cached per posts change.
      $ak_post_titles = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT ID,
				  	post_title,
				  	DAYOFMONTH ( post_date ) AS dom,
				  	MONTH ( post_date ) AS month,
				  	YEAR ( post_date ) AS year
				FROM $wpdb->posts
				WHERE post_date >= %s
				    AND post_date <= %s
				    AND post_type = %s
				    AND post_status = 'publish'",
          $start, $end, $postType )
      );

      Cache::set( $cacheKey, $ak_post_titles, DAY_IN_SECONDS * 3 );
    }

    if ( $ak_post_titles ) {
      foreach ( $ak_post_titles as $ak_post_title ) {
        /** This filter is documented in wp-includes/post-template.php */
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core hook.
        $post_title         = apply_filters( 'the_title', $ak_post_title->post_title, $ak_post_title->ID );
        $ak_post_title->dom = $pd->persian_date( 'j',
          "$ak_post_title->year-$ak_post_title->month-$ak_post_title->dom", 'eng' );

        if ( empty( $ak_titles_for_day[ 'day_' . $ak_post_title->dom ] ) ) {
          $ak_titles_for_day[ 'day_' . $ak_post_title->dom ] = '';
        }

        if ( empty( $ak_titles_for_day[ $ak_post_title->dom ] ) ) { // first one
          $ak_titles_for_day[ $ak_post_title->dom ] = $post_title;
        } else {
          $ak_titles_for_day[ $ak_post_title->dom ] .= $ak_title_separator . $post_title;
        }
      }
    }

    $pd  = WPP_ParsiDate::getInstance();
    $pad = $pd->persian_date( "w", $pd->gregorian_date( "Y-m-d", $jthisyear . "-" . $jthismonth . "-01" ), "eng" );

    if ( 0 != $pad ) {
      $calendar_output .= "\n\t\t" . '<td colspan="' . (int) $pad . '" class="pad">&nbsp;</td>';
    }

    $daysInMonth  = (int) $pd->persian_date( 't', $unixmonth, 'eng' );
    $currentDay   = gmdate( 'j', time() + $tzOffset );
    $currentMonth = gmdate( 'm', time() + $tzOffset );
    $currentYear  = gmdate( 'Y', time() + $tzOffset );

    for ( $calendarDay = 1; $calendarDay <= $daysInMonth; ++ $calendarDay ) {
      list( $thisYear, $thisMonth, $thisDay ) = $pd->persian_to_gregorian( $jthisyear, $jthismonth, $calendarDay );

      if ( isset( $newrow ) && $newrow ) {
        $calendar_output .= "\n\t</tr>\n\t<tr>\n\t\t";
      }

      $newrow = false;

      if ( $thisDay == $currentDay && $thisMonth == $currentMonth && $thisYear == $currentYear ) {
        $calendar_output .= '<td id="today" title="' . esc_html__( 'Today', 'wp-parsidate' ) . '">';
      } else {
        $calendar_output .= '<td>';
      }

      $p_day = Number::toPersian( $calendarDay );

      if ( in_array( $calendarDay, $dayWithPost ) ) {
        $dayLinkDate = $pd->gregorian_date( 'Y,m,d', "$jthisyear-$jthismonth-$calendarDay" );
        $dayLinkDate = explode( ',', $dayLinkDate );
        $url         = get_day_link( $dayLinkDate[0], $dayLinkDate[1], $dayLinkDate[2] );
        if ( 'post' !== $postType ) {
          $url = add_query_arg( 'post_type', $postType, $url );
        }
        $calendar_output .= '<a href="' . esc_url( $url ) . '" title="' . esc_attr( $ak_titles_for_day[ $calendarDay ] ?? '' ) . '">' . esc_html( $p_day ) . '</a>';
      } else {
        $calendar_output .= esc_html( $p_day );
      }

      $calendar_output .= '</td>';

      if ( 6 == calendar_week_mod( $pd->gregorian_date( 'w', "$jthisyear-$jthismonth-$calendarDay" ) - $week_begins ) ) {
        $newrow = true;
      }
    }

    $pad = 6 - calendar_week_mod( $pd->gregorian_date( 'w', "$jthisyear-$jthismonth-" . ( $calendarDay - 1 ), 'eng' ) - $week_begins );

    if ( $pad != 0 && $pad != 7 ) {
      $calendar_output .= "\n\t\t" . '<td class="pad" colspan="' . (int) $pad . '">&nbsp;</td>';
    }

    // Every dynamic value was escaped with esc_attr()/esc_html()/esc_url()
    // while the markup above was assembled.
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $calendar_output . "\n\t</tr>\n\t</tbody>\n\t</table>";
  }
}
