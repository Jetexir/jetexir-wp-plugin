<?php

namespace Jetexir\Helper;

defined( 'ABSPATH' ) || exit;

trait DebugTrait {
  public static function log( $value ): void {
    // PHPCS ignore reason: Use on local system
    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
    error_log( print_r( $value, true ) );
  }

  public static function dd( $var, $echo = true, $exit = false ) {
    ob_start();
    echo '<pre style="background: #520b0b; color: white; direction: ltr; text-align: left; border-radius: 10px; padding: 20px; font-size: 1.3em">';
    // PHPCS ignore reason: Use on local system
    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump
    var_dump( $var );
    echo '</pre>';

    if ( $echo ) {
      echo wp_kses_post( ob_get_clean() );
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
