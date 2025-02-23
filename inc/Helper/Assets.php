<?php

namespace WooAssistant\Helper;

defined( 'ABSPATH' ) || exit;

class Assets {
	public static function getVersion(): string {
		return WOOASSISTANT_PLUGIN_VERSION . ( defined( 'WP_DEVELOPMENT_MODE' ) && WP_DEVELOPMENT_MODE === 'plugin' ? time() : '' );
	}

	public static function url( $path ): string {
		return WOOASSISTANT_PLUGIN_URL . 'assets/' . $path;
	}

	/**
	 *  Get WP image sizes
	 * https://developer.wordpress.org/reference/functions/get_intermediate_image_sizes/
	 *
	 * @return array WP image sizes
	 */
	public static function getImageSizes(): array {
		$sizes      = get_intermediate_image_sizes();
		$imageSizes = [];

		foreach ( $sizes as $value ) {
			$imageSizes[ $value ] = ucwords( str_replace( '_', ' ', $value ) );
		}

		return $imageSizes;
	}

	public static function setSvgDimensions( $svg, $width, $height = null ): string {
		if ( is_null( $height ) ) {
			$height = $width;
		}

		$svg = trim( $svg );
		if ( ! empty( $svg ) && str_starts_with( $svg, '<svg' ) !== false ) {
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