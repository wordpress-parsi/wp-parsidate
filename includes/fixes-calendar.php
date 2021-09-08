<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

/**
 * Create Persian Calendar
 *
 * @return          void
 * @author          Parsa Kafi
 * @author          Mobin Ghasempoor
 */
function wpp_get_calendar() {
	global $wpdb, $m, $monthnum, $year, $day, $posts;

	$jy = 0;
	$pd = bn_parsidate::getInstance();
	$jm = $monthnum;

	if ( $m != '' ) {
		$m  = preg_replace( "/[^0-9]/", "", $m );
		$jy = substr( $m, 0, 4 );
	} elseif ( $year !== '' ) {
		$jy = $year;
	}

	if ( $jy > 1500 ) {
		list( $jy, $jm, $jd ) = $pd->gregorian_to_persian( $year, $monthnum, $day );
	}

	if ( ! $posts ) {
		$gotsome = $wpdb->get_var(
			"
					SELECT 1 AS test
					FROM $wpdb->posts
					WHERE post_type = 'post'
					  	AND post_status = 'publish'
					LIMIT 1
				"
		);

		if ( ! $gotsome ) {
			return;
		}
	}

	$week_begins  = intval( get_option( 'start_of_week' ) );
	$w            = isset( $_GET['w'] ) ? intval( $_GET['w'] ) : '';
	$is_gregorian = false;

	if ( ! empty( $jm ) && ! empty( $jy ) ) {
		$thismonth = '' . zeroise( (int) $jm, 2 );
		$thisyear  = '' . (int) $jy;
	} elseif ( ! empty( $w ) ) {
		$thisyear  = '' . (int) substr( $m, 0, 4 );
		$d         = ( ( $w - 1 ) * 7 ) + 6; //it seems MySQL's weeks disagree with PHP's
		$thismonth = $wpdb->get_var(
			"
			SELECT DATE_FORMAT ( ( DATE_ADD( '{$thisyear}0101', INTERVAL $d DAY ) ), '%m')
			" );
	} elseif ( ! empty( $m ) ) {
		$thisyear = '' . (int) substr( $m, 0, 4 );

		if ( strlen( $m ) < 6 ) {
			$thismonth = '01';
		} else {
			$thismonth = '' . zeroise( (int) substr( $m, 4, 2 ), 2 );
		}
	} else {
		$is_gregorian = true;
		$thisyear     = gmdate( 'Y', current_time( 'timestamp' ) + get_option( 'gmt_offset' ) * 3600 );
		$thismonth    = gmdate( 'm', current_time( 'timestamp' ) + get_option( 'gmt_offset' ) * 3600 );
		$thisday      = gmdate( 'd', current_time( 'timestamp' ) + get_option( 'gmt_offset' ) * 3600 );
	}

	//print_r($wp_query->query_vars);

	if ( $is_gregorian ) {
		list( $jthisyear,
			$jthismonth,
			$jthisday ) = $pd->gregorian_to_persian( $thisyear, $thismonth, $thisday );

		$unixmonth = $pd->gregorian_date( 'Y-m-d 00:00:00', "$jthisyear-$jthismonth-01" );
	} else {
		$gdate      = $pd->persian_to_gregorian( $thisyear, $thismonth, 1 );
		$unixmonth  = mktime( 0, 0, 0, $gdate[1], 1, $gdate[0] );
		$jthisyear  = $thisyear;
		$jthismonth = $thismonth;
	}

	$jnextmonth = $jthismonth + 1;
	$jnextyear  = $jthisyear;

	if ( $jnextmonth > 12 ) {
		$jnextmonth = 1;
		$jnextyear ++;
	}

	$start = $pd->gregorian_date( 'Y-m-d 00:00:00', "$jthisyear-$jthismonth-01" );
	$end   = $pd->gregorian_date( 'Y-m-d 23:59:59', "$jnextyear-$jthismonth-" . $pd->j_days_in_month[ $jthismonth - 1 ] );

	//echo "Start Date: ".$start.", End Date: ".$end."<br>";

	$previous = $wpdb->get_row( $wpdb->prepare(
		"
				SELECT MONTH(post_date) AS month,
				  	YEAR(post_date) AS year
                FROM $wpdb->posts
                WHERE post_date < '%s'
                	AND post_type = 'post'
                    AND post_status = 'publish'
                ORDER BY post_date DESC
                LIMIT 1
               ",
		$start
	) );

	$next = $wpdb->get_row( $wpdb->prepare(
		"
				SELECT MONTH(post_date) AS month,
				  	YEAR(post_date) AS year
                FROM $wpdb->posts
                WHERE post_date >= '%s'
                	AND post_type = 'post'
                    AND post_status = 'publish'
                ORDER BY post_date ASC
                LIMIT 1
                ",
		$end
	) );

	$calendar_output = '<table id="wp-calendar" style="direction: rtl" class="widget_calendar">' .
	                   '<caption>' . $pd->persian_month_names[ (int) $jthismonth ] . ' ' .
	                   $pd->persian_date( 'Y', $unixmonth ) . '</caption><thead><tr>';
	$myweek          = array();

	for ( $wdcount = 0; $wdcount <= 6; $wdcount ++ ) {
		$myweek[] = $pd->persian_day_small[ ( $wdcount + $week_begins ) % 7 ];
	}

	foreach ( $myweek as $wd ) {
		$calendar_output .= "\n\t\t<th scope=\"col\" title=\"$wd\">$wd</th>";
	}

	$calendar_output .= '</tr></thead><tfoot><tr>';

	if ( $previous ) {
		$previous_month = $jthismonth - 1;
		$previous_year  = $jthisyear;

		if ( $previous_month == 0 ) {
			$previous_month = 12;
			$previous_year --;
		}

		$calendar_output .= "\n\t\t" . '<td colspan="3" id="prev"><a href="' . get_month_link( $previous_year, $previous_month ) .
		                    '">&laquo; ' . $pd->persian_month_names[ $previous_month ] . '</a></td>';
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

		$calendar_output .= "\n\t\t" . '<td colspan="3" id="next"><a href="' . get_month_link( $next_year, $next_month ) .
		                    '">' . $pd->persian_month_names[ $next_month ] . ' &raquo;</a></td>';
	} else {
		$calendar_output .= "\n\t\t" . '<td colspan="3" id="next" class="pad">&nbsp;</td>';
	}

	$calendar_output .= '</tr></tfoot><tbody><tr>';

	//____________________________________________________________________________________________________________________________________

	$dayswithposts = $wpdb->get_results(
		$wpdb->prepare(
			"
			SELECT DISTINCT DAYOFMONTH ( post_date ),
			  	MONTH ( post_date ),
			  	YEAR ( post_date )
        	FROM $wpdb->posts
       		WHERE post_date > '%s'
       		    AND post_date < '%s'
        		AND post_type = 'post'
        		AND post_status = 'publish'
        	",
			$start,
			$end
		),
		ARRAY_N
	);

	if ( $dayswithposts ) {
		foreach ( $dayswithposts as $daywith ) {
			$daywithpost[] = $pd->persian_date( 'j', "$daywith[2]-$daywith[1]-$daywith[0]", 'eng' );
		}
	} else {
		$daywithpost = array();
	}

	if ( strpos( $_SERVER['HTTP_USER_AGENT'], 'MSIE' ) !== false
	     || stripos( $_SERVER['HTTP_USER_AGENT'], 'camino' ) !== false
	     || stripos( $_SERVER['HTTP_USER_AGENT'], 'safari' ) !== false ) {
		$ak_title_separator = "\n";
	} else {
		$ak_title_separator = ', ';
	}

	$ak_titles_for_day = array();
	$ak_post_titles    = $wpdb->get_results(
		$wpdb->prepare(
			"
				SELECT ID,
				  	post_title,
				  	DAYOFMONTH ( post_date ) AS dom,
				  	MONTH ( post_date ) AS month,
				  	YEAR ( post_date ) AS year
				FROM $wpdb->posts
				WHERE post_date >= '%s'
				    AND post_date <= '%s'
				    AND post_type = 'post'
				    AND post_status = 'publish'
				",
			$start,
			$end
		)
	);

	if ( $ak_post_titles ) {
		foreach ( $ak_post_titles as $ak_post_title ) {
			/** This filter is documented in wp-includes/post-template.php */
			$post_title         = esc_attr( apply_filters( 'the_title', $ak_post_title->post_title, $ak_post_title->ID ) );
			$ak_post_title->dom = $pd->persian_date( 'j', "$ak_post_title->year-$ak_post_title->month-$ak_post_title->dom", 'eng' );

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

	$pd  = bn_parsidate::getInstance();
	$pad = $pd->persian_date( "w", $pd->gregorian_date( "Y-m-d", $jthisyear . "-" . $jthismonth . "-01" ), "eng" );

	if ( 0 != $pad ) {
		$calendar_output .= "\n\t\t" . '<td colspan="' . $pad . '" class="pad">&nbsp;</td>';
	}

	$daysinmonth = (int) $pd->persian_date( 't', $unixmonth, 'eng' );

	for ( $day = 1; $day <= $daysinmonth; ++ $day ) {
		list( $thiyear,
			$thismonth,
			$thisday ) = $pd->persian_to_gregorian( $jthisyear, $jthismonth, $day );

		if ( isset( $newrow ) && $newrow ) {
			$calendar_output .= "\n\t</tr>\n\t<tr>\n\t\t";
		}

		$newrow = false;

		if ( $thisday == gmdate( 'j', ( time() + ( get_option( 'gmt_offset' ) * 3600 ) ) )
		     && $thismonth == gmdate( 'm', time() + ( get_option( 'gmt_offset' ) * 3600 ) )
		     && $thisyear == gmdate( 'Y', time() + ( get_option( 'gmt_offset' ) * 3600 ) ) ) {
			$calendar_output .= '<td id="today">';
		} else {
			$calendar_output .= '<td>';
		}

		$p_day = ( empty( $val['sep_datesnum'] ) ? $day : per_number( $day ) );

		if ( in_array( $day, $daywithpost ) ) {
			$calendar_output .= '<a href="' . get_day_link( $jthisyear, $jthismonth, $day ) .
			                    "\" title=\"$ak_titles_for_day[$day]\">$p_day</a>";
		} else {
			$calendar_output .= $p_day;
		}

		$calendar_output .= '</td>';

		if ( 6 == calendar_week_mod( $pd->gregorian_date( 'w', "$jthisyear-$jthismonth-$day" ) - $week_begins ) ) {
			$newrow = true;
		}
	}

	$pad = 7 - calendar_week_mod( $pd->gregorian_date( 'w', "$jthisyear-$jthismonth-$day", 'eng' ) - $week_begins );

	if ( $pad != 0 && $pad != 7 ) {
		$calendar_output .= "\n\t\t" . '<td class="pad" colspan="' . $pad . '">&nbsp;</td>';
	}

	echo $calendar_output . "\n\t</tr>\n\t</tbody>\n\t</table>";
}