<?php

namespace WPParsidate\Helper;

defined( 'ABSPATH' ) || exit;

class Assets {
	public static function getVersion(): string {
		return WP_PARSI_VER . ( WP_PARSI_DEBUG_MODE || wp_is_development_mode( 'plugin' ) || ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? time() : '' );
	}

	public static function url( $path ): string {
		return WP_PARSI_URL . 'assets/' . $path;
	}

	public static function isImageString( $string ): bool {
		return str_starts_with( trim( $string ), '<img' ) !== false;
	}

	public static function isSvgImageString( $string, $cleaner = true ): bool {
		if ( $cleaner ) {
			$string = self::cleanSvgImageString( $string );
		}

		return str_starts_with( trim( $string ), '<svg' ) !== false;
	}

	public static function cleanSvgImageString( $svg ): string {
		$svg = Sanitizing::svg( $svg );
		$svg = Strip::removeHtmlComments( $svg );
		$svg = Strip::removeHtmlDoctype( $svg );

		$svg = trim( $svg );
		$svg = str_replace( "\n", '', $svg );

		return trim( $svg );
	}

	public static function setSvgDimensions( $svg, $width, $height = null, $cleaner = true ): string {
		if ( is_null( $height ) ) {
			$height = $width;
		}

		if ( $cleaner ) {
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