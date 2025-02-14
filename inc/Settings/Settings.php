<?php

namespace WooAssistant\Settings;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Helper\Cache;
use WooAssistant\Helper\Validating;

class Settings {
	public static function save( $key, $value, $optionsName = null ): bool {
		return self::saves( [ $key => $value ], $optionsName );
	}

	public static function saves( $options, $optionsName = null ): bool {
		if ( ! is_array( $options ) || empty( $options ) ) {
			return false;
		}

		$optionsName  = is_string( $optionsName ) ? WOOASSISTANT_PLUGIN_KEY . '_' . $optionsName : WOOASSISTANT_PLUGIN_KEY;
		$savedOptions = get_option( $optionsName, [] );
		$savedOptions = is_array( $savedOptions ) ? $savedOptions : [];
		$now          = current_time( 'timestamp' );

		// Prevent from save option error
		$savedOptions['save_options_time_123456'] = Validating::isTimeStamp( $now ) ? $now : time();

		$newOptions = wp_parse_args( $options, $savedOptions );

		Cache::delete( 'options_' . $optionsName );

		return update_option( $optionsName, $newOptions, false );
	}

	public static function get( string $key = null, $default = null, $optionsName = null, bool $useCache = true ) {
		$optionsName = is_string( $optionsName ) ? WOOASSISTANT_PLUGIN_KEY . '_' . $optionsName : WOOASSISTANT_PLUGIN_KEY;
		$options     = Cache::get( 'options_' . $optionsName, false );

		if ( ! $useCache || $options === false ) {
			$options = get_option( $optionsName, [] );
			$options = is_array( $options ) ? $options : [];
			Cache::set( 'options_' . $optionsName, $options, DAY_IN_SECONDS, false );
		}

		if ( $key !== null ) {
			return $options[ $key ] ?? $default;
		}

		return $options ?: $default;
	}
}