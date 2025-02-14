<?php

namespace WooAssistant\Helper;

defined( 'ABSPATH' ) || exit;

trait DebugTrait {
	public static function log( $value ): void {
		error_log( print_r( $value, true ) );
	}

	public static function dd( $value ): void {
		echo '<pre>';
		var_dump( $value );
		echo '</pre>';
	}
}