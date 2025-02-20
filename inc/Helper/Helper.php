<?php

namespace WooAssistant\Helper;

class Helper {
	public static function urlToKey( string $url, $hostOnly = false ) {
		if ( Validating::isUrl( $url ) ) {
			if ( $hostOnly ) {
				$url = parse_url( $url, PHP_URL_HOST );
			} else {
				$url = parse_url( $url, PHP_URL_HOST ) . parse_url( $url, PHP_URL_PATH );
				$url = trim( $url, '/' );
			}

			return Sanitizing::title( $url );
		}

		return false;
	}

	public static function combineStyles( $styles ): string {
		if ( ! is_array( $styles ) || empty( $styles ) ) {
			return '';
		}

		$style = [];

		foreach ( $styles as $key => $value ) {
			$style[] = $key . ': ' . $value . ';';
		}

		return "\n\t" . implode( "\n\t", $style );
	}
}