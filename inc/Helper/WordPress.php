<?php

namespace WPParsidate\Helper;

class WordPress {
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

  public static function getCurrentAction(): string {
    return current_action();
  }

  public static function isAction( $actions ): bool {
    $currentAction = self::getCurrentAction();

    if ( is_array( $actions ) ) {
      return in_array( $currentAction, $actions, true );
    }

    return $currentAction === $actions;
  }

  public static function isRTL(): bool {
    return is_rtl();
  }

  public static function isUserLoggedIn(): bool {
    return is_user_logged_in();
  }

  public static function getCurrentUserID(): int {
    return get_current_user_id();
  }

  public static function blogInfo( $show = '', $filter = 'raw' ) {
    return get_bloginfo( $show, $filter );
  }

  public static function isAjax(): bool {
    return wp_doing_ajax();
  }

  public static function isHome(): bool {
    return ( is_front_page() && is_home() ) || is_front_page();
  }

  public static function isBlog(): bool {
    return ! is_front_page() && is_home();
  }

  public static function isSinglePost(): bool {
    return is_singular( 'post' );
  }

  public static function isSingle( $post = '' ): bool {
    return is_single( $post );
  }

  public static function isSingular( $postTypes = '' ): bool {
    return is_singular( $postTypes );
  }

  public static function is404(): bool {
    return is_404();
  }

  public static function isPage( $page = '' ): bool {
    return is_page( $page );
  }

  public static function isCategory( $category = '' ): bool {
    return is_category( $category );
  }

  public static function isTag( $tag = '' ): bool {
    return is_tag( $tag );
  }

  /**
   * Detects current page is feed or not
   *
   * @return              bool True when page is feed, false when page isn't feed
   * @since               1.0
   */
  public static function isFeed() {
    global $wp_query;

    if ( ! isset( $wp_query ) ) {
      return false;
    }

    if ( $wp_query->is_feed() ) {
      return true;
    }

    $path = $_SERVER['REQUEST_URI'];
    $ext  = pathinfo( $path, PATHINFO_EXTENSION );

    return in_array( $ext, array( 'xml', 'gz', 'xsl' ) );
  }

  /**
   * Checks is WordPress sitemap
   *
   * @return boolean
   */
  public static function isSitemap(): bool {
    return ( isset( $_SERVER['REQUEST_URI'] ) and strpos( $_SERVER['REQUEST_URI'], 'wp-sitemap' ) !== false );
  }

  /**
   * Converts a plugin filepath to a slug.
   *
   * @param  string  $pluginFile  The plugin's filepath, relative to the plugins directory.
   *
   * @return string The plugin's slug.
   */
  public static function pluginPathToSlug( $pluginFile ): string {
    if ( 'hello.php' === $pluginFile ) {
      return 'hello-dolly';
    }

    return str_contains( $pluginFile, '/' ) ? dirname( $pluginFile ) : str_replace( '.php', '', $pluginFile );
  }

  /**
   * Check the given plugin is installed and activated
   *
   * Since 5.0.1
   */
  public static function isPluginActivated( $plugin_file ): bool {
    if ( ! function_exists( 'is_plugin_active' ) ) {
      include_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    return is_plugin_active( $plugin_file );
  }

  /**
   * Checks WPML or PolyLang plugins is active
   *
   * Since 4.0.1
   */
  public static function isMultilingualActive(): bool {
    return self::isPluginActivated( 'polylang/polylang.php' ) || self::isPluginActivated( 'sitepress-multilingual-cms/sitepress.php' );
  }
}
