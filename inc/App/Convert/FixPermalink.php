<?php
/**
 * Fix Permalink
 *
 * Fix Permalink in post, archive, etc.
 * Fix DB query
 */

namespace WPParsidate\App\Convert;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Core\Posts;
use WPParsidate\Core\WPP_ParsiDate;
use WPParsidate\Settings\Settings;

class FixPermalink {
  public function __construct() {
    if ( Settings::get( 'conv_permalinks' ) ) {
      add_filter( 'posts_where', [ $this, 'postsWhere' ], 10, 2 );
      add_action( 'pre_get_posts', [ $this, 'changeQuery' ] );
      add_filter( 'post_link', [ $this, 'getPostLink' ], 10, 3 );
      add_filter( 'day_link', [ $this, 'getDayLink' ], 10, 4 );
    }
  }

  /**
   * Filters the day archive permalink.
   *
   * @param  string  $link  Permalink for the day archive.
   * @param  int  $year  Year for the archive.
   * @param  int  $month  Month for the archive.
   * @param  int  $day  The day for the archive.
   *
   * @return string Archive link
   */
  public function getDayLink( $link, $year, $month, $day ): string {
    global $wp_rewrite;

    $jDate = parsidate( "Y-m-d", "$year-$month-$day", false );
    [ $jYear, $jMonth, $jDay ] = explode( '-', $jDate );

    $dayLink = $wp_rewrite->get_day_permastruct();
    if ( ! empty( $dayLink ) ) {
      $dayLink = str_replace(
        array( '%year%', '%monthnum%', '%day%' ),
        array( $jYear, zeroise( (int) $jMonth, 2 ), zeroise( (int) $jDay, 2 ) ),
        $dayLink
      );
      $dayLink = home_url( user_trailingslashit( $dayLink, 'day' ) );
    } else {
      $dayLink = home_url( '?m=' . $jYear . zeroise( $jMonth, 2 ) . zeroise( $jDay, 2 ) );
    }

    return $dayLink;
  }

  /**
   * Converts post date pointer to Jalali pointer
   *
   * @param  string  $where
   * @param  \WP_Query|string  $wp_query
   *
   * @return string
   */
  public function postsWhere( $where, $wp_query = '' ): string {
    return Posts::getPostsWhere( $where, $wp_query );
  }

  /**
   * Converts post dates to Georgian dates for preventing errors
   *
   * @param  \WP_Query  $query
   */
  public function changeQuery( $query ): void {
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
      return;
    }

    $out = false;
    $pd  = WPP_ParsiDate::getInstance();

    if ( isset( $permalink['name'] ) ) {
      $var = $wpdb->get_var(
        $wpdb->prepare(
          "
				SELECT post_date FROM $wpdb->posts
				WHERE post_name = %s
				  AND post_type != 'attachment'
				ORDER BY ID
				",
          $permalink['name'],
        ),
      );

      if ( empty( $var ) ) {
        return;
      }

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
      $var = $wpdb->get_var(
        $wpdb->prepare(
          "
					SELECT post_date FROM $wpdb->posts
					WHERE ID = %d
				",
          absint( $permalink['post_id'] )
        ),
      );

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
    }

    if ( $out ) {
      if ( ! isset( $var ) ) {
        return;
      }

      preg_match_all( '!\d+!', $var, $matches );

      $var = $matches[0];

      if ( ! empty( $var ) ) {
        $query->set( 'year', $var[0] );
        $query->set( 'monthnum', $var[1] );
        $query->set( 'day', $var[2] );
      }

      $query->is_404              = false;
      $query->query_vars['error'] = '';
    }
  }

  /**
   * Convert permalink structure to Jalali format
   *
   * @param  mixed  $perma
   * @param  \WP_Post  $post
   * @param  bool  $leavename
   *
   * @return string New permalink
   */
  public function getPostLink( $perma, $post, $leavename = false ) {
    if ( empty( $post->ID ) ) {
      return false;
    }

    if ( $post->post_type === 'page' || $post->post_status === 'static' ) {
      return get_page_link( $post->ID );
    } elseif ( 'attachment' === $post->post_type ) {
      return get_attachment_link( $post->ID );
    } elseif ( in_array( $post->post_type, get_post_types( array( '_builtin' => false ) ), true ) ) {
      return get_post_permalink( $post->ID );
    }

    $permalink = get_option( 'permalink_structure' );

    preg_match_all( '%\%([^\%]*)\%%', $permalink, $rewriteCode );

    $rewriteCode = $rewriteCode[0];

    if ( ! empty( $permalink ) && ! in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ) ) ) {
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
               * @param  \WP_Term  $cat  The category to use in the permalink.
               * @param  array  $cats  Array of all categories (WP_Term objects) associated with the post.
               * @param  \WP_Post  $post  The post in question.
               *
               * @since 3.5.0
               *
               */
              $category_object = apply_filters( 'post_link_category', $cats[0], $cats, $post );
              $category_object = get_term( $category_object, 'category' );
              $category        = $category_object->slug;

              if ( $category_object->parent ) {
                $category = get_category_parents( $category_object->parent, false, '/',
                    true ) . $category;
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
}
