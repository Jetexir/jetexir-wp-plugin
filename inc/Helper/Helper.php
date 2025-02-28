<?php

namespace WooAssistant\Helper;

class Helper {
	/**
	 * Inserts any number of scalars or arrays at the point
	 * in the haystack immediately after the search key ($needle) was found,
	 * or at the end if the needle is not found or not supplied.
	 * Modifies $haystack in place.
	 * https://stackoverflow.com/a/7257599/3224296
	 *
	 * @param array &$haystack the associative array to search. This will be modified by the function
	 * @param string $needle the key to search for
	 * @param array $stuff one or more arrays or scalars to be inserted into $haystack
	 *
	 * @return array the index at which $needle was found
	 */
	public static function arrayInsertAfter( array $haystack, string $needle = '', array $stuff = [] ) {
		return array_merge( array_slice( $haystack, 0, $needle, true ), $stuff, array_slice( $haystack, $needle, count( $haystack ) - 1, true ) );
	}

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