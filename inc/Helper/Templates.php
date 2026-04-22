<?php

namespace WPParsidate\Helper;

class Templates {
  /**
   * Load template file
   *
   * @param  string  $file  Path to template file
   * @param  array  $args  array of args pass to template file
   * @param  bool  $loadOnce  Load once
   * @param  bool  $echo  Print template output
   *
   * @return false|string|void
   */
  public static function load( string $file, array $args = [], bool $loadOnce = false, bool $echo = true ) {
    if ( ! $echo ) {
      ob_start();
    }

    if ( file_exists( $file ) ) {
      load_template( $file, $loadOnce, $args );
    }

    if ( ! $echo ) {
      return ob_get_clean();
    }
  }

  /**
   * Get path of template file
   *
   * @param string $template
   * @param string $dir
   *
   * @return string
   */
  public static function getPath( $template, $dir = 'plugin' ): string {
    $path = self::pathCorrection( WP_PARSI_DIR . '/inc/Templates/' . $dir . '/' . $template );

    return apply_filters( 'wp_parsidate_template_path', $path, $template, $dir );
  }

  /**
   * Fix template path
   *
   * @param string $path Path of template
   *
   * @return string Fixed path
   */
  public static function pathCorrection( $path ): string {
    return str_replace( [ '/', '\\' ], DIRECTORY_SEPARATOR, $path );
  }
}
