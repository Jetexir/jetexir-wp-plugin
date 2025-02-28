<?php

namespace WooAssistant\Helper;

defined( 'ABSPATH' ) || exit;

trait DebugTrait {
	public static function log( $value ): void {
		error_log( print_r( $value, true ) );
	}

	public static function dd( $var, $exit = false ): void {
		echo '<pre style="background: #520b0b; color: white; direction: ltr; text-align: left; border-radius: 10px; padding: 20px; font-size: 1.3em">';
		var_dump( $var );
		echo '</pre>';

		if ( $exit ) {
			exit();
		}
	}
}