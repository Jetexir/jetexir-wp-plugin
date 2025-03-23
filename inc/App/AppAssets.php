<?php

namespace WooAssistant\App;

use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Nonce;
use WooAssistant\Helper\Sanitizing;
use WooAssistant\Helper\WordPress;

defined( 'ABSPATH' ) || exit;

class AppAssets {
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueueScripts' ) );
	}

	public function enqueueScripts(): void {
		$pluginVersion = Assets::getVersion();
		$debugName     = WOOASSISTANT_DEBUG_MODE ? '' : '.min';

		wp_enqueue_style( WOOASSISTANT_PLUGIN_SLUG . '-global-style',
			Assets::url( 'css/style' . $debugName . '.css' ), false, $pluginVersion );

		wp_enqueue_script( WOOASSISTANT_PLUGIN_SLUG . '-global',
			Assets::url( 'js/global.min.js' ),
			[ 'jquery' ], $pluginVersion, [ 'in_footer' => true ] );

		wp_add_inline_script( WOOASSISTANT_PLUGIN_SLUG . '-global', 'var wooAssistantAjax, wooAssistantModalCloseEvent;', 'before' );

		wp_localize_script( WOOASSISTANT_PLUGIN_SLUG . '-global', WOOASSISTANT_PLUGIN_KEYCAP, array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'ajaxNonce' => Nonce::create(),
			'sslError'  => __( 'Your site does not have SSL support, For example: https://example.com', 'woo-assistant' ),
			'pageName'  => WordPress::getPageName(),
			'direction' => WordPress::isRTL() ? 'rtl' : 'ltr',
		) );

		// Owl Carousel
		wp_register_style( WOOASSISTANT_PLUGIN_SLUG . '-owl-carousel',
			Assets::url( 'css/owl-carousel' . $debugName . '.css' ), false, '2.3.4' );
		/*wp_register_style( WOOASSISTANT_PLUGIN_SLUG . '-owl-carousel-theme',
			Assets::url( 'css/owl.theme.default' . $debugName . '.css' ), false, '2.3.4' );*/
		wp_register_script( WOOASSISTANT_PLUGIN_SLUG . '-owl-carousel',
			Assets::url( 'js/owl.carousel.min.js' ),
			[ 'jquery' ], '2.3.4', [ 'in_footer' => true ] );

	}
}