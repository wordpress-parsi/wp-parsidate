<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

/**
 * Fixes and converts permalinks date to Jalali
 *
 * @author              Mobin Ghasempoor
 * @package             WP-Parsidate
 * @subpackage          Fixes/Permalinks
 */
global $wpp_settings;

if ( wpp_is_active( 'conv_permalinks' ) ) {
	add_filter( 'posts_where', 'wpp_posts_where', 10, 2 );
	add_action( 'pre_get_posts', 'wpp_pre_get_posts' );
	add_filter( 'post_link', 'wpp_permalink', 10, 3 );
}

/**
 * Converts post date pointer to Jalali pointer
 *
 * @param string $where
 * @param string $wp_query
 *
 * @return          string
 */
function wpp_posts_where( $where, $wp_query = '' ) {
	global $wpdb;

	if ( empty( $wp_query ) ) {
		global $wp_query;
	}

	if ( ! $wp_query->is_main_query() || empty( $wp_query->query_vars ) ) {
		return $where;
	}

	$pd = bn_parsidate::getInstance();

	$m      = ( isset( $wp_query->query_vars['m'] ) ) ? $wp_query->query_vars['m'] : '';
	$hour   = ( isset( $wp_query->query_vars['hour'] ) ) ? $wp_query->query_vars['hour'] : '';
	$minute = ( isset( $wp_query->query_vars['minute'] ) ) ? $wp_query->query_vars['minute'] : '';
	$second = ( isset( $wp_query->query_vars['second'] ) ) ? $wp_query->query_vars['second'] : '';
	$year   = ( isset( $wp_query->query_vars['year'] ) ) ? $wp_query->query_vars['year'] : '';
	$month  = ( isset( $wp_query->query_vars['monthnum'] ) ) ? $wp_query->query_vars['monthnum'] : '';
	$day    = ( isset( $wp_query->query_vars['day'] ) ) ? $wp_query->query_vars['day'] : '';

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

	if ( $month != '' ) {
		$stamon  = $month;
		$endmon  = ( $month == 12 ? 1 : $month + 1 );
		$endyear = ( $endmon == 1 ? $stayear + 1 : $stayear );
	}

	if ( $day != '' ) {
		$staday = $day;
		$endday = ( $day == $pd->j_days_in_month[ (int) $month - 1 ] ? 1 : $day + 1 );
		$endmon = ( $endday == 1 ? $stamon + 1 : $stamon );
	}

	if ( $hour != '' ) {
		$stahou = $hour;
		$endhou = ( $hour == 24 ? '00' : $hour + 1 );
		$endday = ( $endhou == '00' ? $staday + 1 : $staday );
	}

	if ( $minute != '' ) {
		$stamin = $minute;
		$endmin = ( $minute == 59 ? '00' : $minute + 1 );
		$endhou = ( $endmin == '00' ? $stahou + 1 : $stahou );
	}

	if ( $second != '' ) {
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

	$prefixp = "{$wpdb->posts}.";
	$prefixp = ( strpos( $where, $prefixp ) == false ) ? '' : $prefixp;
	$where   .= " AND {$prefixp}post_date >= '$stadate' AND {$prefixp}post_date < '$enddate' ";

	return $where;
}

/**
 * Converts post dates to Georgian dates for preventing errors
 *
 * @param WP_Query $query
 *
 * @return          WP_Query
 */
function wpp_pre_get_posts( $query ) {
	global $wpdb;

	$permalink = $query->query;
	$year      = '';
	$monthnum  = '';
	$day       = '';//start

	if ( isset( $permalink['year'] ) ) {
		$year = $permalink['year'];
	}

	if ( isset( $permalink['monthnum'] ) ) {
		$monthnum = $permalink['monthnum'];
	}

	if ( isset( $permalink['day'] ) ) {
		$day = $permalink['day'];
	}

	if ( $year > 1700 ) {
		return $query;
	}

	$out = false;
	$pd  = bn_parsidate::getInstance();

	if ( isset( $permalink['name'] ) ) {
		$var = $wpdb->get_var( "SELECT post_date FROM {$wpdb->prefix}posts WHERE post_name='{$permalink['name']}' AND post_type!='attachment' ORDER BY id" );
		$per = parsidate( 'Y-m-d', $var, 'eng' );
		$per = explode( '-', $per );
		$out = true;

		if ( ! empty( $year ) && $year != $per[0] ) {
			$out = false;
		}

		if ( $out && ! empty( $monthnum ) && $monthnum != $per[1] ) {
			$out = false;
		}

		if ( $out && ! empty( $day ) && $day != $per[2] ) {
			$out = false;
		}
	} elseif ( isset( $permalink['post_id'] ) ) {
		$out = true;
		$var = $wpdb->get_var( "SELECT post_date FROM {$wpdb->prefix}posts WHERE ID={$permalink['post_id']}" );
	} elseif ( ! empty( $year ) && ! empty( $monthnum ) && ! empty( $day ) ) {
		$out = true;
		$var = gregdate( 'Y-m-d', "$year-$monthnum-$day" );
	} elseif ( ! empty( $year ) && ! empty( $monthnum ) ) {
		$stadate    = $pd->persian_to_gregorian( $year, $monthnum, 1 );
		$enddate    = $pd->persian_to_gregorian( $year, $monthnum, $pd->j_days_in_month[ ( $monthnum - 1 ) ] );
		$date_query = array(
			array(
				'after'     => array(
					'year'  => $stadate[0],
					'month' => $stadate[1],
					'day'   => $stadate[2] - 1,
				),
				'before'    => array(
					'year'  => $enddate[0],
					'month' => $enddate[1],
					'day'   => $enddate[2] + 1,
				),
				'inclusive' => true,
			),
		);

		$query->set( 'date_query', $date_query );

		$out = false;
	} elseif ( ! empty( $year ) ) {
		$stadate    = $pd->persian_to_gregorian( $year, 1, 1 );
		$enddate    = $pd->persian_to_gregorian( ( $year + 1 ), 1, 1 );
		$date_query = array(
			array(
				'after'     => array(
					'year'  => $stadate[0],
					'month' => $stadate[1],
					'day'   => $stadate[2] - 1,
				),
				'before'    => array(
					'year'  => $enddate[0],
					'month' => $enddate[1],
					'day'   => $enddate[2],
				),
				'inclusive' => true,
			),
		);

		$query->set( 'date_query', $date_query );

		$out = false;
	}

	if ( $out ) {
		preg_match_all( '!\d+!', $var, $matches );

		$var = $matches[0];

		$query->set( 'year', $var[0] );
		$query->set( 'monthnum', $var[1] );
		$query->set( 'day', $var[2] );

		$query->is_404              = false;
		$query->query_vars['error'] = '';
	}

	return $query;
}

/**
 * Convert permalink structure to Jalali format
 *
 * @param mixed $perma
 * @param WP_Post $post
 * @param bool $leavename
 *
 * @return          string New permalink
 */
function wpp_permalink( $perma, $post, $leavename = false ) {
	if ( empty( $post->ID ) ) {
		return false;
	}

	if ( $post->post_type == 'page' || $post->post_status == 'static' ) {
		return get_page_link( $post->ID );
	} elseif ( $post->post_type == 'attachment' ) {
		return get_attachment_link( $post->ID );
	} elseif ( in_array( $post->post_type, get_post_types( array( '_builtin' => false ) ) ) ) {
		return get_post_permalink( $post->ID );
	}

	$permalink = get_option( 'permalink_structure' );

	preg_match_all( '%\%([^\%]*)\%%', $permalink, $rewriteCode );

	$rewriteCode = $rewriteCode[0];

	if ( '' != $permalink && ! in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ) ) ) {
		if ( $leavename ) {
			$rewriteCode = array_diff( $rewriteCode, array( '%postname%', '%pagename%' ) );
		}

		$date = explode( ' ', parsidate( 'Y m d H i s', $post->post_date, 'eng' ) );
		$out  = array();

		foreach ( $rewriteCode as $rewrite ) {
			switch ( $rewrite ) {
				case '%year%':
					$out[] = $date[0];
					break;
				case '%monthnum%':
					$out[] = $date[1];
					break;
				case '%day%':
					$out[] = $date[2];
					break;
				case '%hour%':
					$out[] = $date[3];
					break;
				case '%minute%':
					$out[] = $date[4];
					break;
				case '%second%':
					$out[] = $date[5];
					break;
				case '%post_id%':
					$out[] = $post->ID;
					break;
				case '%postname%':
					$out[] = $post->post_name;
					break;
				case '%category%':
					$category = '';
					/**
					 * This code from wp-includes/link-template.php:171
					 * */
					$cats = get_the_category( $post->ID );
					if ( $cats ) {
						$cats = wp_list_sort(
							$cats,
							array(
								'term_id' => 'ASC',
							)
						);

						/**
						 * Filters the category that gets used in the %category% permalink token.
						 *
						 * @param WP_Term $cat The category to use in the permalink.
						 * @param array $cats Array of all categories (WP_Term objects) associated with the post.
						 * @param WP_Post $post The post in question.
						 *
						 * @since 3.5.0
						 *
						 */
						$category_object = apply_filters( 'post_link_category', $cats[0], $cats, $post );
						$category_object = get_term( $category_object, 'category' );
						$category        = $category_object->slug;

						if ( $category_object->parent ) {
							$category = get_category_parents( $category_object->parent, false, '/', true ) . $category;
						}
					}

					// Show default category in permalinks,
					// without having to assign it explicitly.
					if ( empty( $category ) ) {
						$default_category = get_term( get_option( 'default_category' ), 'category' );

						if ( $default_category && ! is_wp_error( $default_category ) ) {
							$category = $default_category->slug;
						}
					}

					$out[] = $category;
					break;
				case '%author%':
					$authordata = get_userdata( $post->post_author );
					$out[]      = $authordata->user_nicename;
					break;
				default:
					unset( $rewriteCode[ array_search( $rewrite, $rewriteCode ) ] );
					break;
			}
		}

		$permalink = home_url( str_replace( $rewriteCode, $out, $permalink ) );

		return user_trailingslashit( $permalink, 'single' );
	}

	return home_url( "?p=$post->ID" );
}
