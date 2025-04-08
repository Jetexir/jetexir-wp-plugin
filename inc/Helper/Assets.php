<?php

namespace WooAssistant\Helper;

defined( 'ABSPATH' ) || exit;

class Assets {
	public static function cssGradient( $value, $default = [] ): string {
		$function   = $value['function'] = $value['function'] ?? 'linear-gradient';
		$rotate     = $value['rotate'] = $value['rotate'] ?? 90;
		$shape      = $value['shape'] = $value['shape'] ?? 'ellipse';
		$colors     = $value['colors'] = isset( $value['colors'] ) && is_array( $value['colors'] ) && count( $value['colors'] ) >= 2 ? $value['colors'] : $default['colors'];
		$firstParam = $function === 'linear-gradient' ? $rotate . 'deg' : $shape;

		$steps = [];
		foreach ( $colors as $position => $color ) {
			$color   = Sanitizing::color( $color );
			$steps[] = "$color $position%";
		}

		$steps      = implode( ', ', $steps );
		$firstParam = ! empty( $firstParam ) ? $firstParam . ', ' : '';

		return "$function($firstParam $steps)";
	}

	/**
	 * Generate CSS property
	 * https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_cascading_variables/Using_CSS_custom_properties
	 * https://developer.mozilla.org/en-US/docs/Web/CSS/@property/syntax#syntax
	 *
	 * @param string $var
	 * @param string|array $syntax
	 * @param bool $inherits
	 * @param string $initialValue
	 *
	 * @return string
	 */
	public static function generateCssProperty( string $var, $syntax = '*', bool $inherits = false, string $initialValue = '' ): string {
		$property    = '@property --' . $var . ' {';
		$syntaxUnits = [
			'angle',
			'color',
			'custom-ident',
			'image',
			'url',
			'resolution',
			'string',
			'time',
			'transform-function',
			'transform-list',
			'integer',
			'number',
			'length-percentage',
			'length',
			'percentage'
		];

		if ( is_array( $syntax ) ) {
			$syntax   = array_map( 'strtolower', $syntax );
			$syntaxes = [];
			foreach ( $syntax as $value ) {
				$syntaxes[] = in_array( $value, $syntaxUnits, true ) ? '<' . $value . '>' : $value;
			}
			$syntax = implode( ' | ', $syntaxes );

		} else {
			$syntax = strtolower( $syntax );
			$syntax = in_array( $syntax, $syntaxUnits, true ) ? '<' . $syntax . '>' : $syntax;
		}

		$property .= 'syntax: ' . $syntax . ';';
		$property .= 'inherits: ' . ( $inherits ? 'true' : 'false' ) . ';';

		if ( ! empty( $initialValue ) ) {
			$property .= 'initial-value: ' . $initialValue . ';';
		}

		$property .= '}';

		return $property;
	}

	public static function getVersion(): string {
		return WOOASSISTANT_PLUGIN_VERSION . ( WOOASSISTANT_DEBUG_MODE && defined( 'WP_DEVELOPMENT_MODE' ) && WP_DEVELOPMENT_MODE === 'plugin' ? time() : '' );
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