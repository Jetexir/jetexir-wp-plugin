<?php

namespace Jetexir\App;

defined( 'ABSPATH' ) || exit;

use Jetexir\App\General\General;
use Jetexir\App\Order\Order;
use Jetexir\App\Product\Product;
use Jetexir\App\Cart\Cart;
use Jetexir\App\Checkout\Checkout;
use Jetexir\App\Tools\Tools;
use Jetexir\App\WordPress\WordPress;

class App {
  public function __construct() {
    new AppAssets();
    new Product();
    new Cart();
    new Checkout();
    new Order();
    new Tools();
    new General();
    new WordPress();
    new WooCommerce();

    add_action( 'init', [ $this, 'init' ], 0 );
  }

  public function init(): void {
    /**
     * Fires when all Jetexir addons are loaded.
     *
     * @since 1.0
     *
     */
    do_action( 'jetexir_addons_load' );

    /**
     * Fires when Jetexir is initialized.
     *
     * @since 1.0
     *
     */
    do_action( 'jetexir_init' );
  }

  /**
   * Adds a new shortcode.
   *
   * @param string $tag Shortcode tag to be searched in post content.
   * @param callable $callback The callback function to run when the shortcode is found.
   *                           Every shortcode callback is passed three parameters by default,
   *                           including an array of attributes (`$atts`), the shortcode content
   *                           or null if not set (`$content`), and finally the shortcode tag
   *                           itself (`$shortcode_tag`), in that order.
   */
  public static function addShortcode( $tag, $callback ): void {
    /**
     * Filters whether to register a shortcode.
     *
     * @param bool $allow Whether the shortcode should be registered.
     * @param string $tag Shortcode tag.
     *
     * @return bool Whether the shortcode should be registered.
     *
     * @since 1.0
     *
     */
    $allowAddShortcode = (bool) apply_filters( 'jetexir_add_shortcode', true, $tag );

    if ( $allowAddShortcode ) {
      add_shortcode( $tag, $callback );
    }
  }
}
