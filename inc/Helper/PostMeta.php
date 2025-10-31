<?php

namespace AssistantForWooCommerce\Helper;

class PostMeta {
  /**
   * Retrieves a post meta field for the given post ID.
   *
   * @param int $postId Post ID.
   * @param string $metaKey Optional. The meta key to retrieve. By default,
   *                        returns data for all keys. Default empty.
   * @param bool $single Optional. Whether to return a single value.
   *                        This parameter has no effect if `$key` is not specified.
   *                        Default true.
   *
   * @return mixed An array of values if `$single` is false.
   *               The value of the meta field if `$single` is true.
   *               False for an invalid `$post_id` (non-numeric, zero, or negative value).
   *               An empty array if a valid but non-existing post ID is passed and `$single` is false.
   *               An empty string if a valid but non-existing post ID is passed and `$single` is true.
   *
   */
  public static function get( $postId, $metaKey = '', $single = true ) {
    $metaValue = get_post_meta( $postId, $metaKey, $single );

    return apply_filters( 'assistant_for_woocommerce_get_post_meta', $metaValue, $postId, $metaKey, $single );
  }

  /**
   * Updates a post meta field based on the given post ID.
   *
   * Use the `$prev_value` parameter to differentiate between meta fields with the
   * same key and post ID.
   *
   * If the meta field for the post does not exist, it will be added and its ID returned.
   *
   * Can be used in place of add_post_meta().
   *
   * @param int $postId Post ID.
   * @param string $metaKey Metadata key.
   * @param mixed $metaValue Metadata value. Must be serializable if non-scalar.
   * @param mixed $prevValue Optional. Previous value to check before updating.
   *                           If specified, only update existing metadata entries with
   *                           this value. Otherwise, update all entries. Default empty.
   *
   * @return int|bool Meta ID if the key didn't exist, true on successful update,
   *                  false on failure or if the value passed to the function
   *                  is the same as the one that is already in the database.
   */
  public static function update( $postId, $metaKey, $metaValue, $prevValue = '' ) {
    if ( ( $metaValue = apply_filters( 'assistant_for_woocommerce_update_post_meta', $metaValue, $postId, $metaKey, $prevValue ) ) !== false ) {
      return update_post_meta( $postId, $metaKey, $metaValue, $prevValue );
    }

    return false;
  }

  /**
   * Deletes a post meta field for the given post ID.
   *
   * You can match based on the key, or key and value. Removing based on key and
   * value, will keep from removing duplicate metadata with the same key. It also
   * allows removing all metadata matching the key, if needed.
   *
   * @param int $postId Post ID.
   * @param string $metaKey Metadata name.
   * @param mixed $metaValue Optional. Metadata value. If provided,
   *                           rows will only be removed that match the value.
   *                           Must be serializable if non-scalar. Default empty.
   *
   * @return bool True on success, false on failure.
   *
   */
  public static function delete( $postId, $metaKey = '', $metaValue = '' ): bool {
    if ( apply_filters( 'assistant_for_woocommerce_delete_post_meta', true, $postId, $metaKey, $metaValue ) ) {
      return delete_post_meta( $postId, $metaKey, $metaValue );
    }

    return false;
  }
}
