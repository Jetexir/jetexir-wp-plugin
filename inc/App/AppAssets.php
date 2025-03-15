<?php

namespace WooAssistant\App;

use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Nonce;
use WooAssistant\Helper\WordPress;

defined( 'ABSPATH' ) || exit;

class AppAssets {
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueueScripts' ) );
	}

	public function enqueueScripts(): void {
		$pluginVersion = Assets::getVersion();

		wp_enqueue_style( WOOASSISTANT_PLUGIN_SLUG . '-global-style',
			Assets::url( 'css/style.min.css' ), false, $pluginVersion );

		wp_enqueue_script( WOOASSISTANT_PLUGIN_SLUG . '-global',
			Assets::url( 'js/global.min.js' ),
			[ 'jquery' ], $pluginVersion, [ 'in_footer' => true ] );

		wp_localize_script( WOOASSISTANT_PLUGIN_SLUG . '-global', WOOASSISTANT_PLUGIN_KEYCAP, array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'ajaxNonce' => Nonce::create(),
			'sslError'  => __( 'Your site does not have SSL support, For example: https://example.com', 'woo-assistant' ),
			'pageName'  => WordPress::getPageName()
		) );
	}
}