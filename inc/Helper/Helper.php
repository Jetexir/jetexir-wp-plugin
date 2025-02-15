<?php

namespace WooAssistant\Helper;

class Helper {
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