<?php

namespace Jetexir\Helper;

defined( 'ABSPATH' ) || exit;

class User {
  public static function can( $capability, ...$args ): bool {
    return current_user_can( $capability, ...$args );
  }

  public static function getData( $field = null, $userID = 0 ) {
    if ( $userID === 0 ) {
      $userID = get_current_user_id();
    }

    if ( $userID === 0 ) {
      return false;
    }

    $user = get_userdata( $userID );

    if ( is_null( $field ) ) {
      return $user;
    }

    return $user->$field ?? '';
  }
}
