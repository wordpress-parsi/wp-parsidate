<?php

namespace WPParsidate\Helper;

defined( 'ABSPATH' ) || exit;

class Assets {
  /**
   * Get assets version, if plugin or WP debug mode activated add timestamp to version string.
   *
   * @return string Assets Version string
   */
  public static function getVersion(): string {
    return WP_PARSI_VER . ( WP_PARSI_DEBUG_MODE || wp_is_development_mode( 'plugin' ) || ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? time() : '' );
  }

  /**
   * Get asset url
   *
   * @param  string  $path  Assets path
   *
   * @return string Asset url
   */
  public static function url( string $path ): string {
    return WP_PARSI_URL . 'assets/' . $path;
  }

  /**
   * Determines whether the string is HTML image string
   *
   * @param  string  $string  HTML image string
   *
   * @return bool
   */
  public static function isImageString( string $string ): bool {
    return str_starts_with( trim( $string ), '<img' ) !== false;
  }

  /**
   * Determines whether the string is HTML image string
   *
   * @param  string  $string  HTML image string
   * @param  bool  $clean  Clean HTML string before check
   *
   * @return bool
   */
  public static function isSvgImageString( string $string, bool $clean = true ): bool {
    if ( $clean ) {
      $string = self::cleanSvgImageString( $string );
    }

    return str_starts_with( trim( $string ), '<svg' ) !== false;
  }

  /**
   * Clean HTML SVG string
   *
   * @param  string  $svg  HTML SVG string
   *
   * @return string
   */
  public static function cleanSvgImageString( string $svg ): string {
    $svg = Sanitizing::svg( $svg );
    $svg = Strip::removeHtmlComments( $svg );
    $svg = Strip::removeHtmlDoctype( $svg );

    $svg = trim( $svg );
    $svg = str_replace( "\n", '', $svg );

    return trim( $svg );
  }

  /**
   * Set HTML SVG image string width and height
   *
   * @param  string  $svg
   * @param  int  $width  SVG width
   * @param  int|null  $height  SVG height, If not set equal to width
   * @param  bool  $clean  Clean HTML string before check
   *
   * @return string HTML SVG string
   */
  public static function setSvgDimensions( string $svg, int $width, int $height = null, bool $clean = true ): string {
    if ( is_null( $height ) ) {
      $height = $width;
    }

    if ( $clean ) {
      $svg = self::cleanSvgImageString( $svg );
    }

    if ( ! empty( $svg ) && self::isSvgImageString( $svg ) ) {
      $openingTag = $openTag = substr( $svg, 0, mb_strpos( $svg, '>' ) + 1 );
      $svgWidth   = $svgHeight = null;
      if ( $openingTag ) {
        preg_match( '/width="(.+?)"/', $openingTag, $matches );
        if ( ! empty( $matches ) ) {
          $svgWidth = $matches[0];
        }

        preg_match( '/height="(.+?)"/', $openingTag, $matches );
        if ( ! empty( $matches ) ) {
          $svgHeight = $matches[0];
        }

        if ( is_null( $svgWidth ) ) {
          $openTag = substr_replace( $openTag, ' width="' . $width . '"', mb_strlen( $openTag ) - 1, 0 );
        } else {
          $openTag = str_replace( $svgWidth, 'width="' . $width . '"', $openTag );
        }

        if ( is_null( $svgHeight ) ) {
          $openTag = substr_replace( $openTag, ' height="' . $height . '"', mb_strlen( $openTag ) - 1, 0 );
        } else {
          $openTag = str_replace( $svgHeight, 'height="' . $height . '"', $openTag );
        }

        $svg = str_replace( $openingTag, $openTag, $svg );
      }
    }

    return $svg;
  }
}
