<?php
/**
 * Fix dates settings
 *
 * Fix dates and time in WP Hooks.
 */

namespace WPParsidate\App\Core;

use WPParsidate\App\Integration\HookDeactivator;
use WPParsidate\Helper\WordPress;
use WPParsidate\Settings\Settings;

class FixDates {
  public function __construct() {
    // @TODO: locale non-farsi is a problem
    if ( get_locale() === 'fa_IR' && Settings::get( 'persian_date' ) ) {
      add_filter( 'the_time', [ $this, 'fixPostTime' ], 10, 2 );
      add_filter( 'get_the_time', [ $this, 'fixPostTime' ], 10, 3 );

      add_filter( 'the_date', [ $this, 'fixPostDate' ], 10, 2 );
      add_filter( 'get_the_date', [ $this, 'fixPostDate' ], 100, 3 );

      add_filter( 'get_the_modified_date', [ $this, 'fixModifiedDate' ], 10, 3 );

      add_filter( 'get_comment_time', [ $this, 'fixCommentTime' ], 10, 2 );
      add_filter( 'get_comment_date', [ $this, 'fixCommentDate' ], 10, 3 );
      add_filter( 'media_view_settings', [ $this, 'fixMediaViewSettings' ], 10, 2 );

      add_filter( 'date_i18n', [ $this, 'fixDateI18n' ], 10, 4 );

      if ( ! WordPress::isSitemap() ) {
        add_filter( 'wp_date', [ $this, 'fixDateI18n' ], 10, 4 );
      }
    }
  }

  /**
   * Fixes i18n date formatting and convert them to Jalali
   *
   * @param  string  $date  Formatted date string.
   * @param  string  $format  Format to display the date.
   * @param  int  $timestamp  A sum of Unix timestamp and timezone offset in seconds.
   *                          Might be without offset if input omitted timestamp but requested GMT.
   * @param  bool  $gmt  Whether to use GMT timezone. Only applies if timestamp was not provided.
   *                          Default false.
   *
   * @return string Formatted time
   */
  public function fixDateI18n( $date, $format, $timestamp, $gmt ): string {
    if ( ( function_exists( 'pll_current_language' ) && ( pll_current_language() !== false && pll_current_language() !== "fa" ) ) ) {
      return $date;
    }

    if ( HookDeactivator::checkDisable() ) {
      return $date;
    }

    return parsidate( $format, $timestamp, Settings::get( 'conv_dates' ) );
  }

  /**
   * Fixes Media view Select box and returns in Jalali Format Date
   *
   * @param  array  $settings  List of media view settings.
   * @param  \WP_Post  $post  Post object.
   *
   * @return  array _wpMediaViewsL10n localize script in WordPress
   * @author  Mehrshad Darzi
   */
  public function fixMediaViewSettings( $settings, $post ): array {
    if ( ! empty( $settings['months'] ) ) {
      $convDates = Settings::get( 'conv_dates' );

      for ( $i = 0, $iMax = count( $settings['months'] ); $i < $iMax; $i ++ ) {
        if ( isset( $settings['months'][ $i ]->year, $settings['months'][ $i ]->month ) ) {
          $settings['months'][ $i ]->text = parsidate(
            "F Y",
            $settings['months'][ $i ]->year . '-' . $settings['months'][ $i ]->month,
            $convDates
          );
        }
      }
    }

    return $settings;
  }

  /**
   * Fixes comment date and returns in Jalali format
   *
   * @param  string|int  $comment_date  Formatted date string or Unix timestamp.
   * @param  string  $format  PHP date format.
   * @param  \WP_Comment  $comment  The comment object.
   */
  public function fixCommentDate( $comment_date, $format, $comment ) {
    if ( $comment === null ) {
      return $comment_date;
    }

    if ( empty( $format ) ) {
      $format = (string) get_option( 'date_format' );
    }
    if ( 'c' === $format || HookDeactivator::checkDisable() ) {
      return date( $format, strtotime( $comment->comment_date ) );
    }

    return parsidate( $format, $comment->comment_date, Settings::get( 'conv_dates' ) );
  }

  /**
   * Fixes comment time and returns to Jalali format
   *
   * @param  string  $time  Comment time
   * @param  string  $format  Date format
   *
   * @return          string Formatted date
   */
  public function fixCommentTime( $time, $format = '' ): string {
    global $comment;

    if ( empty( $comment ) ) {
      return $time;
    }

    if ( empty( $format ) ) {
      $format = (string) get_option( 'time_format' );
    }
    if ( HookDeactivator::checkDisable() ) {
      return date( $format, strtotime( $comment->comment_date ) );
    }

    return parsidate( $format, $comment->comment_date, Settings::get( 'conv_dates' ) );
  }

  /**
   * Fixes post modified date and returns to Jalali format
   *
   * @param  string  $time  Post modified time
   * @param  string  $format  Date format
   * @param  \WP_Post|null  $post  WP_Post object or null if no post is found.
   *
   * @return string Formatted date
   * @author Yousef Mahmoudi
   */
  public function fixModifiedDate( $time, $format, $post ): string {
    if ( $post === null ) {
      return $time;
    }

    if ( 'c' === $format ) {
      return date( $format, strtotime( $post->post_modified ) );
    }

    return $time;
  }

  /**
   * Fixes post time and returns to Jalali format
   *
   * @param  string  $time  Post time
   * @param  string  $format  Date format
   *
   * @return          string Formatted date
   */
  public function fixPostTime( $time, $format = '', $post = null ): string {
    $post = get_post( $post );

    if ( ! $post ) {
      global $post;
    }

    if ( empty( $post ) ) {
      return $time;
    }

    if ( empty( $format ) ) {
      $format = (string) get_option( 'time_format' );
    }

    if ( HookDeactivator::checkDisable() ) {
      return date( $format, strtotime( $post->post_date ) );
    }

    return parsidate( $format, $post->post_date, Settings::get( 'conv_dates' ) );
  }

  /**
   * Fixes post date and returns to Jalali format
   *
   * @param  string  $time  Post time
   * @param  string  $format  Date format
   *
   * @return string Formatted date
   */
  public function fixPostDate( $time, $format = '', $post = null ): string {
    if ( null === $post ) {
      global $post;
    } else {
      $post = get_post( $post );
    }

    // It seems some plugin like acf does not exist $post.
    if ( ! $post ) {
      return $time;
    }
    if ( function_exists( 'pll_current_language' ) && pll_current_language() !== "fa" ) {
      return $time;
    }
    if ( empty( $format ) ) {
      $format = (string) get_option( 'date_format', 'F j, Y' );
    }

    if ( 'c' === $format || HookDeactivator::checkDisable() ) {
      return date( $format, strtotime( $post->post_date ) );
    }

    return parsidate( $format, date( 'Y-m-d H:i:s', strtotime( $post->post_date ) ), Settings::get( 'conv_dates' ) );
  }
}
