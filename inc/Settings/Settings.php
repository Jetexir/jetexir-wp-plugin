<?php

namespace Jetexir\Settings;

defined( 'ABSPATH' ) || exit;

use Jetexir\Helper\Cache;
use Jetexir\Helper\Validating;

class Settings {
  public static function addToArray( $key, $value, $optionsName = null, $reverse = false ): bool {
    $optionValue = self::get( $key, [], $optionsName );
    $optionValue = is_array( $optionValue ) ? $optionValue : [];
    if ( $reverse ) {
      $optionValue = array_values( array_reverse( $optionValue ) );
    }
    $optionValue[] = $value;

    if ( $reverse ) {
      $optionValue = array_values( array_reverse( $optionValue ) );
    }

    return self::save( $key, $optionValue, $optionsName );
  }

  public static function deleteFromArray( $key, $index, $optionsName = null ): bool {
    $optionValue = self::get( $key, [], $optionsName );
    $optionValue = is_array( $optionValue ) ? $optionValue : [];

    if ( isset( $optionValue[ $index ] ) ) {
      unset( $optionValue[ $index ] );
      $optionValue = array_values( $optionValue );
    } else {
      return false;
    }

    return self::save( $key, $optionValue, $optionsName );
  }

  public static function save( $key, $value, $optionsName = null ): bool {
    return self::saves( [ $key => $value ], $optionsName );
  }

  public static function saves( $options, $optionsName = null ): bool {
    if ( ! is_array( $options ) || empty( $options ) ) {
      return false;
    }

    $optionsName  = is_string( $optionsName ) ? JETEXIR_PLUGIN_KEY . '_' . $optionsName : JETEXIR_PLUGIN_KEY;
    $savedOptions = get_option( $optionsName, [] );
    $savedOptions = is_array( $savedOptions ) ? $savedOptions : [];
    $now          = current_time( 'timestamp' );

    // Prevent from save option error
    $savedOptions['save_options_time_123456'] = ( Validating::isTimeStamp( $now ) ? $now : time() ) + random_int( 999, 9999 );
    $newOptions                               = wp_parse_args( $options, $savedOptions );

    Cache::delete( 'options_' . $optionsName );

    return update_option( $optionsName, $newOptions, false );
  }

  public static function get( string $key = null, $default = null, $optionsName = null, bool $useCache = true ) {
    $optionsName = is_string( $optionsName ) ? JETEXIR_PLUGIN_KEY . '_' . $optionsName : JETEXIR_PLUGIN_KEY;
    $options     = Cache::get( 'options_' . $optionsName, false );

    if ( ! $useCache || ! is_array( $options ) ) {
      $options = get_option( $optionsName, [] );
      $options = is_array( $options ) ? $options : [];
      Cache::set( 'options_' . $optionsName, $options, DAY_IN_SECONDS, false );
    }

    if ( $key !== null ) {
      /**
       * Filters the value of a single setting.
       *
       * @param mixed $value Setting value.
       * @param string $key Setting key.
       * @param mixed $default Default value.
       * @param array $options Settings options.
       * @param string $optionsName Options name.
       *
       * @return mixed Setting value.
       *
       * @since 1.0
       *
       */
      return apply_filters( 'jetexir_get_setting', $options[ $key ] ?? $default, $key, $default, $options, $optionsName );
    }

    /**
     * Filters the settings options array.
     *
     * @param array $options Settings options.
     * @param string $optionsName Options name.
     *
     * @return array Settings options.
     *
     * @since 1.0
     *
     */
    return (array) apply_filters( 'jetexir_get_settings', $options ?: $default, $optionsName );
  }

  public static function delete( string $key, $optionsName = null ): bool {
    $optionsName  = is_string( $optionsName ) ? JETEXIR_PLUGIN_KEY . '_' . $optionsName : JETEXIR_PLUGIN_KEY;
    $savedOptions = get_option( $optionsName, [] );
    $savedOptions = is_array( $savedOptions ) ? $savedOptions : [];

    if ( isset( $savedOptions[ $key ] ) ) {
      unset( $savedOptions[ $key ] );
    }

    Cache::delete( 'options_' . $optionsName );

    return update_option( $optionsName, $savedOptions, false );
  }
}
