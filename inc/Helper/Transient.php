<?php

namespace AssistantForWooCommerce\Helper;

class Transient {
  public static function get( $key ) {
    return get_transient( $key );
  }

  public static function set( $key, $value, $expiration = 0 ): bool {
    return set_transient( $key, $value, $expiration );
  }

  public static function delete( $key ): bool {
    return delete_transient( $key );
  }

  /**
   * Deletes all expired transients.
   *
   * Note that this function won't do anything if an external object cache is in use.
   *
   * @param bool $forceDB Optional. Force cleanup to run against the database even when an external object cache is used.
   */
  public static function deleteExpired( $forceDB = false ) {
    delete_expired_transients( $forceDB );
  }

  /**
   * Deletes all transients by key.
   *
   * Note that this function won't do anything if an external object cache is in use.
   *
   * @param string $key Transient key
   * @param bool $deleteExpired Delete transients expired or all
   * @param bool $forceDB Optional. Force cleanup to run against the database even when an external object cache is used.
   *
   * @global \wpdb $wpdb WordPress database abstraction object.
   *
   * @copyright This method by delete_expired_transients WP function
   */
  public static function deleteLike( $key, $deleteExpired = false, $forceDB = false ): void {
    global $wpdb;

    if ( ! $forceDB && wp_using_ext_object_cache() ) {
      return;
    }

    $time = $deleteExpired ? time() : strtotime( '+100 years' );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query(
      $wpdb->prepare(
        "DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
			WHERE a.option_name LIKE %s
			AND a.option_name NOT LIKE %s
			AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
			AND b.option_value < %d",
        $wpdb->esc_like( '_transient_' . $key ) . '%',
        $wpdb->esc_like( '_transient_timeout_' ) . '%',
        $time
      )
    );

    if ( ! is_multisite() ) {
      // Single site stores site transients in the options table.
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
      $wpdb->query(
        $wpdb->prepare(
          "DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
				WHERE a.option_name LIKE %s
				AND a.option_name NOT LIKE %s
				AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )
				AND b.option_value < %d",
          $wpdb->esc_like( '_site_transient_' . $key ) . '%',
          $wpdb->esc_like( '_site_transient_timeout_' ) . '%',
          $time
        )
      );
    } elseif ( is_multisite() && is_main_site() && is_main_network() ) {
      // Multisite stores site transients in the sitemeta table.
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
      $wpdb->query(
        $wpdb->prepare(
          "DELETE a, b FROM {$wpdb->sitemeta} a, {$wpdb->sitemeta} b
				WHERE a.meta_key LIKE %s
				AND a.meta_key NOT LIKE %s
				AND b.meta_key = CONCAT( '_site_transient_timeout_', SUBSTRING( a.meta_key, 17 ) )
				AND b.meta_value < %d",
          $wpdb->esc_like( '_site_transient_' . $key ) . '%',
          $wpdb->esc_like( '_site_transient_timeout_' ) . '%',
          $time
        )
      );
    }
  }
}
