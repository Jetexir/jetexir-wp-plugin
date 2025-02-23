<?php

namespace WooAssistant\App;

use WooAssistant\App\Product\Product;

defined( 'ABSPATH' ) || exit;

class App {
	public function __construct() {
		new AppAssets();
		new Product();

		add_action( 'init', [ $this, 'init' ] );
	}

	public function init() {
		do_action( 'woo_assistant_addons_load' );
		do_action( 'woo_assistant_init' );
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
	public static function addShortcode( $tag, $callback ) {
		if ( apply_filters( 'woo_assistant_add_shortcode', true, $tag ) ) {
			add_shortcode( $tag, $callback );
		}
	}
}