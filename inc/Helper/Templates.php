<?php

namespace WPParsidate\Helper;

class Templates {
  public static function load( $file, $args = [], $loadOnce = false, $echo = true ) {
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

  public static function getPath( $template, $dir = 'plugin' ): string {
    $path = self::pathCorrection( WP_PARSI_DIR . '/inc/Templates/' . $dir . '/' . $template );

    return apply_filters( 'woo_assistant_template_path', $path, $template, $dir );
  }

  public static function pathCorrection( $path ): string {
    return str_replace( [ '/', '\\' ], DIRECTORY_SEPARATOR, $path );
  }
}
