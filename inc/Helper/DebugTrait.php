<?php

namespace WooAssistant\Helper;

defined( 'ABSPATH' ) || exit;

trait DebugTrait {
	public static function log( $value ): void {
		error_log( print_r( $value, true ) );
	}

	public static function dd( $var, $echo = true, $exit = false ) {
		ob_start();
		echo '<pre style="background: #520b0b; color: white; direction: ltr; text-align: left; border-radius: 10px; padding: 20px; font-size: 1.3em">';
		var_dump( $var );
		echo '</pre>';

		if ( $echo ) {
			echo ob_get_clean();
		} else {
			$output = ob_get_contents();
			ob_get_clean();

			return $output;
		}

		if ( $exit ) {
			exit();
		}
	}
}