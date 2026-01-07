<?php

namespace Jetexir\Helper;

class Cookie {

  /**
   * Get cookie
   *
   * @param string $key Cookie key
   * @param mixed $default Cookie default value
   *
   * @return mixed|null
   */
  public static function get( $key, $default = null ) {
    return sanitize_text_field( wp_unslash( $_COOKIE[ $key ] ?? $default ) );
  }

  /**
   * Set cookie
   *
   * @param string $key Cookie key
   * @param string|numeric $value Cookie value
   * @param int $expire Expire time in seconds
   *
   * @return void
   */
  public static function set( $key, $value, $expire = 0 ): void {
    $options = array(
      'expires'  => $expire,
      'secure'   => is_ssl(),
      'path'     => COOKIEPATH ?: '/',
      'domain'   => COOKIE_DOMAIN,
      'httponly' => true,
    );

    /**
     * Controls whether the cookie should only be accessible via the HTTP protocol, or if it should also be
     * accessible to Javascript.
     *
     * @see   https://www.php.net/manual/en/function.setcookie.php
     * @since 3.3.0
     *
     * @param bool $httponly If the cookie should only be accessible via the HTTP protocol.
     * @param string $key Cookie key.
     * @param string $value Cookie value.
     * @param int $expire When the cookie should expire.
     * @param bool $secure If the cookie should only be served over HTTPS.
     */
    setcookie( $key, $value, $options );
  }
}
