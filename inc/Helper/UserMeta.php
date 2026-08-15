<?php

namespace Jetexir\Helper;

class UserMeta {
  /**
   * Retrieves a user meta field for the given user ID.
   *
   * @param int $userId user ID.
   * @param string $metaKey Optional. The meta key to retrieve. By default,
   *                        returns data for all keys. Default empty.
   * @param bool $single Optional. Whether to return a single value.
   *                        This parameter has no effect if `$key` is not specified.
   *                        Default true.
   *
   * @return mixed An array of values if `$single` is false.
   *               The value of the meta field if `$single` is true.
   *               False for an invalid `$user_id` (non-numeric, zero, or negative value).
   *               An empty array if a valid but non-existing user ID is passed and `$single` is false.
   *               An empty string if a valid but non-existing user ID is passed and `$single` is true.
   *
   */
  public static function get( $userId, $metaKey = '', $single = true ) {
    $metaValue = get_user_meta( $userId, $metaKey, $single );

    /**
     * Filters the retrieved user meta value.
     *
     * @param mixed $metaValue Meta value.
     * @param int $userId User ID.
     * @param string $metaKey Meta key.
     * @param bool $single Whether to return a single value.
     *
     * @return mixed Meta value.
     *
     * @since 1.0
     *
     */
    return apply_filters( 'jetexir_get_user_meta', $metaValue, $userId, $metaKey, $single );
  }

  /**
   * Updates a user meta field based on the given user ID.
   *
   * Use the `$prev_value` parameter to differentiate between meta fields with the
   * same key and user ID.
   *
   * If the meta field for the user does not exist, it will be added and its ID returned.
   *
   * Can be used in place of add_user_meta().
   *
   * @param int $userId user ID.
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
  public static function update( $userId, $metaKey, $metaValue, $prevValue = '' ) {
    /**
     * Filters the user meta value before it is updated.
     *
     * Return false to prevent the meta value from being updated.
     *
     * @param mixed $metaValue Meta value.
     * @param int $userId User ID.
     * @param string $metaKey Meta key.
     * @param mixed $prevValue Previous value.
     *
     * @return mixed|false Meta value, or false to prevent the update.
     *
     * @since 1.0
     *
     */
    $metaValue = apply_filters( 'jetexir_update_user_meta', $metaValue, $userId, $metaKey, $prevValue );

    if ( $metaValue !== false ) {
      return update_user_meta( $userId, $metaKey, $metaValue, $prevValue );
    }

    return false;
  }

  /**
   * Deletes a user meta field for the given user ID.
   *
   * You can match based on the key, or key and value. Removing based on key and
   * value, will keep from removing duplicate metadata with the same key. It also
   * allows removing all metadata matching the key, if needed.
   *
   * @param int $userId user ID.
   * @param string $metaKey Metadata name.
   * @param mixed $metaValue Optional. Metadata value. If provided,
   *                           rows will only be removed that match the value.
   *                           Must be serializable if non-scalar. Default empty.
   *
   * @return bool True on success, false on failure.
   *
   */
  public static function delete( $userId, $metaKey = '', $metaValue = '' ): bool {
    /**
     * Filters whether to delete a user meta field.
     *
     * @param bool $delete Whether to delete the meta value.
     * @param int $userId User ID.
     * @param string $metaKey Meta key.
     * @param mixed $metaValue Meta value.
     *
     * @return bool Whether to delete the meta value.
     *
     * @since 1.0
     *
     */
    $deleteMeta = (bool) apply_filters( 'jetexir_delete_user_meta', true, $userId, $metaKey, $metaValue );

    if ( $deleteMeta ) {
      return delete_user_meta( $userId, $metaKey, $metaValue );
    }

    return false;
  }
}
