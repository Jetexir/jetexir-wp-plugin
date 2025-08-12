<?php

namespace WooAssistant\Helper;

use enshrined\svgSanitize\Sanitizer;

defined( 'ABSPATH' ) || exit;

class Sanitizing {
	public static function svg( $svg ) {
		$Sanitizer = new Sanitizer();
		$Sanitizer->removeXMLTag( true );

		return $Sanitizer->sanitize( $svg );
	}

	public static function clean( $value ) {
		return wc_clean( $value );
	}

	public static function jsonArray( $value ) {
		$value = wp_unslash( htmlspecialchars_decode( $value ) );
		$value = str_replace( "'", '"', $value );
		if ( ! JSON::validate( $value ) ) {
			return [];
		}

		return self::array( JSON::decode( $value, true ) );
	}

	public static function array( $value ): array {
		return (array) $value;
	}

	public static function bool( $value ): bool {
		return wc_string_to_bool( $value );
	}

	public static function absint( $value ): int {
		return absint( $value );
	}

	public static function int( $value ): int {
		return (int) $value;
	}

	public static function float( $value ): float {
		return (float) $value;
	}

	public static function title( $value ): string {
		return sanitize_title( $value );
	}

	public static function color( $value ): string {
		return sanitize_hex_color( $value );
	}

	public static function colorNoHash( $value ): string {
		return sanitize_hex_color_no_hash( $value );
	}

	public static function text( $value ): string {
		return sanitize_text_field( $value );
	}

	public static function email( $value ): string {
		return sanitize_email( wp_unslash( $value ) );
	}

	public static function filename( $value ): string {
		return sanitize_file_name( $value );
	}

	public static function url( $value ): string {
		return sanitize_url( $value, array( 'http', 'https' ) );
	}

	public static function textarea( $value ): string {
		return sanitize_textarea_field( wp_unslash( $value ) );
	}

	public static function class( $value ): string {
		return sanitize_html_class( $value );
	}

	public static function mimeType( $value ): string {
		return sanitize_mime_type( $value );
	}

	public static function sqlOrderBy( $value ): string {
		return sanitize_sql_orderby( $value );
	}

	public static function username( $value ): string {
		return sanitize_user( $value );
	}
}