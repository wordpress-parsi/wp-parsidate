<?php

namespace WPParsidate\Helper;

defined( 'ABSPATH' ) || exit;

class FeedReader {
  /**
   * @var array Args
   */
  private array $args;

  /**
   * @var array Default Args
   */
  private array $defaultArgs;

  /**
   * @var array Feed items
   */
  private array $feedItems = [];

  /**
   * @var \WP_Error Error
   */
  private \WP_Error $error;

  /**
   * @var array Replace desc text
   */
  private array $replaceDescText = [];

  /**
   * @param  array  $args  Feed arguments
   */
  public function __construct( array $args ) {
    $this->args = $this->defaultArgs = array(
      'url'          => '',
      'cache_key'    => '',
      'cache_time'   => DAY_IN_SECONDS,
      'items_number' => 10,
      'fields'       => [ 'link', 'title', 'description', 'author', 'datetime' ],
    );
    $this->setArgs( $args );
  }

  /**
   * Set feed arguments
   *
   * @param  array  $args  Feed Arguments
   *
   * @return void
   */
  public function setArgs( array $args ): void {
    $this->args                 = wp_parse_args( $args, $this->args );
    $this->args['url']          = Validating::isUrl( $this->args['url'] ) ? $this->args['url'] : '';
    $this->args['cache_key']    = empty( $this->args['cache_key'] ) ? 'feed_' . Helper::urlToKey( $this->args['url'] ) : $this->args['cache_key'];
    $this->args['cache_time']   = is_numeric( $this->args['cache_time'] ) ? (int) $this->args['cache_time'] : DAY_IN_SECONDS;
    $this->args['items_number'] = (int) $this->args['items_number'];
    $this->args['fields']       = is_array( $this->args['fields'] ) && ! empty( $this->args['fields'] ) ? $this->args['fields'] : $this->defaultArgs['fields'];
  }

  /**
   * Get Error
   *
   * @return \WP_Error
   */
  public function getError(): \WP_Error {
    return $this->error;
  }

  /**
   * Get feed Items
   *
   * @return array Feed items
   */
  public function getFeedItems(): array {
    return $this->feedItems;
  }

  /**
   * Set replace text value
   *
   * @param  array  $replaceTexts  Replace text's
   *
   * @return $this
   */
  public function replaceDescText( $replaceTexts ): self {
    $this->replaceDescText = $replaceTexts;

    return $this;
  }

  /**
   * Get HTML feed links
   *
   * @param  array  $fields  Print fields
   *
   * @return array Array of HTML feed links
   */
  public function getFeedLinks( $fields = null ): array {
    $fields = $printFields = is_null( $fields ) ? $this->defaultArgs['fields'] : $fields;
    if ( empty( $this->feedItems ) ) {
      return [];
    }

    if ( empty( $printFields ) ) {
      return [];
    }

    $dateFormat = get_option( 'date_format', 'F j, Y' );
    $timeFormat = get_option( 'time_format', 'g:i a' );

    $links = [];
    if ( ( $key = array_search( 'link', $printFields, true ) ) !== false ) {
      unset( $printFields[ $key ] );
    }

    foreach ( $this->feedItems as $item ) {
      $title = '';
      foreach ( $printFields as $field ) {
        if ( ! empty( $item[ $field ] ) && in_array( $field, $this->defaultArgs['fields'], true ) ) {
          $value = $item[ $field ];
          if ( $field === 'datetime' ) {
            $value = wp_date( $dateFormat . ', ' . $timeFormat, strtotime( $value ) );
          }
          $title .= Templates::load( Templates::getPath( 'feed-reader/feed_data_row.php' ), array(
            'field' => $field,
            'value' => $value
          ), false, false );
        }
      }

      if ( ! empty( $title ) ) {
        $links[] = Templates::load( Templates::getPath( 'feed-reader/feed_item.php' ), array(
          'link'  => in_array( 'link', $fields, true ) ? ( $item['link'] ?? '' ) : '',
          'title' => $title
        ), false, false );
      }
    }

    return $links;
  }

  /**
   * Read feed
   *
   * @param  bool  $useCache  Use Cache
   *
   * @return FeedReader
   */
  public function read( bool $useCache = true ): self {
    if ( empty( $this->args['url'] ) ) {
      return $this;
    }

    if ( $useCache ) {
      $feedItems = Cache::get( $this->args['cache_key'] );

      if ( is_array( $feedItems ) ) {
        $this->feedItems = $feedItems;

        return $this;
      }
    }

    $feed = fetch_feed( $this->args['url'] );

    if ( is_wp_error( $feed ) ) {
      $this->error = $feed;

      return $this;
    }

    if ( ! $feed->get_item_quantity() ) {
      $this->error = new \WP_Error( 'feed_empty', esc_html__( 'Feed is empty.', 'wp-parsidate' ), $this->args );
      $feed->__destruct();
      unset( $feed );

      return $this;
    }

    $itemsNumber = (int) $this->args['items_number'];
    if ( $itemsNumber < 1 || 20 < $itemsNumber ) {
      $itemsNumber = 10;
    }

    $feedItems = [];
    foreach ( $feed->get_items( 0, $itemsNumber ) as $item ) {
      $feedItem = [];
      if ( in_array( 'link', $this->args['fields'], true ) ) {
        $link = $item->get_link();
        while ( ! empty( $link ) && stristr( $link, 'http' ) !== $link ) {
          $link = substr( $link, 1 );
        }
        $feedItem['link'] = esc_url_raw( wp_strip_all_tags( $link ) );
      }

      if ( in_array( 'title', $this->args['fields'], true ) ) {
        $title = esc_html( trim( wp_strip_all_tags( $item->get_title() ) ) );
        if ( empty( $title ) ) {
          $title = esc_html__( 'Untitled', 'wp-parsidate' );
        }

        $feedItem['title'] = $title;
      }

      if ( in_array( 'description', $this->args['fields'], true ) ) {
        $desc                    = html_entity_decode( $item->get_description(), ENT_QUOTES,
          get_option( 'blog_charset' ) );
        $desc                    = esc_attr( wp_trim_words( $desc, 55, ' [&hellip;]' ) );
        $feedItem['description'] = $desc;
      }

      if ( in_array( 'datetime', $this->args['fields'], true ) ) {
        $feedItem['datetime']  = $item->get_date( 'Y-m-d H:i:s' );
        $feedItem['timestamp'] = $item->get_date( 'U' );
      }

      if ( in_array( 'author', $this->args['fields'], true ) ) {
        $author = $item->get_author();
        if ( is_object( $author ) ) {
          $author = $author->get_name();
          $author = esc_html( wp_strip_all_tags( $author ) );
        }
        $feedItem['author'] = $author;
      }

      if ( ! empty( $feedItem ) ) {
        if ( ! empty( $this->replaceDescText ) ) {
          $feedItem = $this->replaceText( $feedItem, $this->replaceDescText );
        }
        $feedItems[] = $feedItem;
      }
    }

    if ( ! empty( $feedItems ) ) {
      Cache::set( $this->args['cache_key'], $feedItems, $this->args['cache_time'] );
      $this->feedItems = $feedItems;
    }

    return $this;
  }

  public function setEmptyFeedDesc( $desc ) {
    return Templates::load( Templates::getPath( 'feed-reader/feed_none.php' ), array( 'desc' => $desc ), false, false );
  }

  /**
   * Replace text in feed fields
   *
   * @param  array  $feedItem  Feed item
   * @param  array  $replaceTexts  Replace text's
   * @param  string  $field  Field key
   *
   * @return array Feed item
   */
  private function replaceText( array $feedItem, array $replaceTexts, string $field = 'description' ): array {
    if ( isset( $feedItem[ $field ] ) ) {
      foreach ( $replaceTexts as $replaceText ) {
        if ( isset( $feedItem['title'] ) && str_contains( $replaceText[0], '%title%' ) ) {
          $replaceText[0] = str_replace( '%title%', $feedItem['title'], $replaceText[0] );
        }

        $feedItem[ $field ] = trim( str_replace( $replaceText[0], $replaceText[1], $feedItem[ $field ] ) );
      }
    }

    return $feedItem;
  }
}
