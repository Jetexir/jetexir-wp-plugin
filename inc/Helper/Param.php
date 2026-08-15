<?php
/**
 * Param is helper class for get inputs
 * All data sanitized with 'Jetexir\Helper\Sanitizing' helper class
 */

namespace Jetexir\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Param class.
 */
class Param {

  /**
   * Get field from query string.
   *
   * @param string $id Field id to get.
   * @param mixed $default Default value to return if field is not found.
   * @param int $filter The ID of the filter to apply.
   * @param int $flag The ID of the flag to apply.
   *
   * @return mixed
   */
  public static function get( $id, $default = false, $filter = FILTER_DEFAULT, $flag = [] ) {
    // PHPCS ignore reason: Nonce check is already happening before this logic in `AdminPages` class.
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
    return isset( $_GET[ $id ] ) ? self::sanitize( wp_unslash( $_GET[ $id ] ), $filter, $flag ) : $default;
  }

  /**
   * Get field from FORM post.
   *
   * @param string $id Field id to get.
   * @param mixed $default Default value to return if field is not found.
   * @param int $filter The ID of the filter to apply.
   * @param int $flag The ID of the flag to apply.
   *
   * @return mixed
   */
  public static function post( $id, $default = false, $filter = FILTER_DEFAULT, $flag = [] ) {
    // PHPCS ignore reason: Nonce check is already happening before this logic in `AdminPages` class.
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
    if ( ! isset( $_POST[ $id ] ) ) {
      return $default;
    }

    return self::sanitize( wp_unslash( $_POST[ $id ] ), $filter, $flag );
  }

  /**
   * Get field from request.
   *
   * @param string $id Field id to get.
   * @param mixed $default Default value to return if field is not found.
   * @param int $filter The ID of the filter to apply.
   * @param int $flag The ID of the flag to apply.
   *
   * @return mixed
   */
  public static function request( $id, $default = false, $filter = FILTER_DEFAULT, $flag = [] ) {
    // PHPCS ignore reason: Nonce check is already happening before this logic in `AdminPages` class.
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
    return isset( $_REQUEST[ $id ] ) ? self::sanitize( wp_unslash( $_REQUEST[ $id ] ), $filter, $flag ) : $default;
  }

  /**
   * Get field from FORM server.
   *
   * @param string $id Field id to get.
   * @param mixed $default Default value to return if field is not found.
   * @param int $filter The ID of the filter to apply.
   * @param int $flag The ID of the flag to apply.
   *
   * @return mixed
   */
  public static function server( $id, $default = false, $filter = FILTER_DEFAULT, $flag = [] ) {
    return isset( $_SERVER[ $id ] ) ? self::sanitize( wp_unslash( $_SERVER[ $id ] ), $filter, $flag ) : $default;
  }

  /**
   * Sanitize a retrieved input value.
   *
   * By default the value is sanitized with `sanitize_textarea_field()`
   * recursively, because PHP's `FILTER_DEFAULT` does not sanitize anything.
   * When a real filter is explicitly provided, it is applied with `filter_var()`.
   *
   * @param mixed $value Value to sanitize.
   * @param int $filter The ID of the filter to apply.
   * @param int $flag The ID of the flag to apply.
   *
   * @return mixed
   */
  private static function sanitize( $value, $filter, $flag ) {
    if ( $filter === FILTER_DEFAULT ) {
      return self::sanitizeDefault( $value );
    }

    if ( is_array( $value ) ) {
      return filter_var_array( $value, $filter );
    }

    return filter_var( $value, $filter, $flag );
  }

  /**
   * Recursively sanitize the input with `sanitize_textarea_field()`.
   *
   * @param mixed $value Value to sanitize.
   *
   * @return mixed
   */
  private static function sanitizeDefault( $value ) {
    if ( is_array( $value ) ) {
      return array_map( [ self::class, 'sanitizeDefault' ], $value );
    }

    return sanitize_textarea_field( $value );
  }

  /**
   * Decode form.serelize() jQuery Post String
   * Return like $_POST['Form_Input_Name or ID']
   * Data is NOT escaped here - will be sanitized using WordPress sanitization
   * functions after decoding per WordPress security guidelines.
   *
   * @source https://stackoverflow.com/a/5788352/3224296
   */
  public static function decodeSerialize( $queryString ): array {
    $a     = explode( '&', $queryString );
    $i     = 0;
    $store = array();
    while ( $i < count( $a ) ) {
      $b              = explode( '=', $a[ $i ] );
      $arrayName      = urldecode( $b[0] );
      $cleanArrayName = str_replace( '[]', '', $arrayName );
      $arrayValue     = urldecode( $b[1] );

      if ( strpos( $arrayName, '[]' ) !== false ) {
        $store[ $cleanArrayName ][] = $arrayValue;
      } else {
        $store[ $cleanArrayName ] = $arrayValue;
      }
      $i ++;
    }

    return $store;
  }
}
