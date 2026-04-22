<?php

namespace WPParsidate\Helper;

class WordPress {
  /**
   * Get current page name
   *
   * @return string Page name, Return empty string if isn't standard WP pages.
   */
  public static function getPageName(): string {
    if ( self::isHome() ) {
      return 'home';
    } elseif ( self::isBlog() ) {
      return 'blog';
    } elseif ( self::isCategory() ) {
      return 'category';
    } elseif ( self::isTag() ) {
      return 'tag';
    } elseif ( self::isSinglePost() ) {
      return 'post';
    } elseif ( self::isSingle() ) {
      return 'single';
    } elseif ( self::isPage() ) {
      return 'page';
    } elseif ( self::isSingular() ) {
      return 'singular';
    } elseif ( self::is404() ) {
      return '404';
    }

    return '';
  }


  /**
   * Retrieves the name of the current action hook.
   *
   * @return string|false Hook name of the current action, false if no action is running.
   * @since 6.0
   *
   */
  public static function getCurrentAction(): string {
    return current_action();
  }

  /**
   * Check action
   *
   * @param $actions
   *
   * @return bool
   */
  public static function isAction( $actions ): bool {
    $currentAction = self::getCurrentAction();

    if ( is_array( $actions ) ) {
      return in_array( $currentAction, $actions, true );
    }

    return $currentAction === $actions;
  }

  /**
   * Determines whether the current locale is right-to-left (RTL).
   * @return bool Whether locale is RTL.
   *
   * @since 6.0
   */
  public static function isRTL(): bool {
    return is_rtl();
  }

  /**
   * Determines whether the current visitor is a logged in user.
   *
   * @return bool True if user is logged in, false if not logged in.
   */
  public static function isUserLoggedIn(): bool {
    return is_user_logged_in();
  }

  /**
   * Gets the current user's ID.
   *
   * @return int The current user's ID, or 0 if no user is logged in.
   */
  public static function getCurrentUserID(): int {
    return get_current_user_id();
  }

  /**
   * Retrieves information about the current site.
   *
   * @param  string  $show  Optional. Site info to retrieve. Default empty (site name).
   * @param  string  $filter  Optional. How to filter what is retrieved. Default 'raw'.
   *
   * @return string Mostly string values, might be empty.
   */
  public static function blogInfo( $show = '', $filter = 'raw' ) {
    return get_bloginfo( $show, $filter );
  }

  /**
   * Determines whether the current request is a WordPress Ajax request.
   *
   * @return bool True if it's a WordPress Ajax request, false otherwise.
   * @since 6.0
   *
   */
  public static function isAjax(): bool {
    return wp_doing_ajax();
  }

  /**
   * Check current page is Home page
   *
   * @return bool
   */
  public static function isHome(): bool {
    return ( is_front_page() && is_home() ) || is_front_page();
  }

  /**
   * Check current page is Blog page
   *
   * @return bool
   */
  public static function isBlog(): bool {
    return ! is_front_page() && is_home();
  }

  /**
   * Check current page is Single Post page
   *
   * @return bool True, if is current page is Single post page
   */
  public static function isSinglePost(): bool {
    return is_singular( 'post' );
  }


  /**
   * Determines whether the query is for an existing single post.
   *
   * @param  int|string|int[]|string[]  $post  Optional. Post ID, title, slug, or array of such
   * *                                        to check against. Default empty.
   * * @return bool Whether the query is for an existing single post.
   */
  public static function isSingle( $post = '' ): bool {
    return is_single( $post );
  }

  /**
   * Determines whether the query is for an existing single post of any post type
   * * (post, attachment, page, custom post types).
   *
   * @param  string|string[]  $postTypes  Optional. Post type or array of post types
   * *                                    to check against. Default empty.
   * * @return bool Whether the query is for an existing single post
   * *              or any of the given post types.
   */
  public static function isSingular( $postTypes = '' ): bool {
    return is_singular( $postTypes );
  }

  /**
   * Determines whether the query has resulted in a 404 (returns no results).
   *
   * @return bool Whether the query is a 404 error.
   */
  public static function is404(): bool {
    return is_404();
  }

  /**
   * Determines whether the query is for an existing single page.
   *
   * @param  int|string|int[]|string[]  $page  Optional. Page ID, title, slug, or array of such
   *                                         to check against. Default empty.
   *
   * @return bool Whether the query is for an existing single page.
   */
  public static function isPage( $page = '' ): bool {
    return is_page( $page );
  }

  /**
   * Determines whether the query is for an existing category archive page.
   *
   * @param  int|string|int[]|string[]  $category  Optional. Category ID, name, slug, or array of such
   * *                                            to check against. Default empty.
   * * @return bool Whether the query is for an existing category archive page.
   */
  public static function isCategory( $category = '' ): bool {
    return is_category( $category );
  }

  /**
   * Determines whether the query is for an existing tag archive page.
   *
   * @param  int|string|int[]|string[]  $tag  Optional. Tag ID, name, slug, or array of such
   * *                                       to check against. Default empty.
   * * @return bool Whether the query is for an existing tag archive page.
   */
  public static function isTag( $tag = '' ): bool {
    return is_tag( $tag );
  }

  /**
   * Detects current page is feed or not
   *
   * @return              bool True when page is feed, false when page isn't feed
   * @since               1.0
   */
  public static function isFeed(): bool {
    global $wp_query;

    if ( ! isset( $wp_query ) ) {
      return false;
    }

    if ( $wp_query->is_feed() ) {
      return true;
    }

    $path = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
    $ext  = pathinfo( $path, PATHINFO_EXTENSION );

    return in_array( $ext, array( 'xml', 'gz', 'xsl' ) );
  }

  /**
   * Checks is WordPress sitemap
   *
   * @return bool
   */
  public static function isSitemap(): bool {
    $path = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );

    return ! empty( $path ) and strpos( $path, 'wp-sitemap' ) !== false;
  }

  /**
   * Converts a plugin filepath to a slug.
   *
   * @param  string  $pluginFile  The plugin's filepath, relative to the plugins directory.
   *
   * @return string The plugin's slug.
   */
  public static function pluginPathToSlug( string $pluginFile ): string {
    if ( 'hello.php' === $pluginFile ) {
      return 'hello-dolly';
    }

    return str_contains( $pluginFile, '/' ) ? dirname( $pluginFile ) : str_replace( '.php', '', $pluginFile );
  }

  /**
   * Determines whether a plugin is active.
   *
   * Since 5.0.1
   *
   * @param  string  $plugin  Path to the plugin file relative to the plugins' directory.
   *
   * @return bool  True, if in the active plugins list. False, not in the list.
   */
  public static function isPluginActivated( string $plugin ): bool {
    if ( ! function_exists( 'is_plugin_active' ) ) {
      include_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    return is_plugin_active( $plugin );
  }

  /**
   * Checks WPML or PolyLang plugins is active
   *
   * Since 4.0.1
   * @return bool True, if Multilingual plugins is active.
   */
  public static function isMultilingualActive(): bool {
    return self::isPluginActivated( 'polylang/polylang.php' ) || self::isPluginActivated( 'sitepress-multilingual-cms/sitepress.php' );
  }
}
