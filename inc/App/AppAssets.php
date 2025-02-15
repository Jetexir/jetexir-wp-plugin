<?php

namespace WooAssistant\App;

use WooAssistant\Helper\Assets;

defined( 'ABSPATH' ) || exit;

class AppAssets {
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueueScripts' ) );
	}

	public function enqueueScripts(): void {
		$pluginVersion = WOOASSISTANT_PLUGIN_VERSION . ( defined( 'DEVELOPMENT_MODE' ) && DEVELOPMENT_MODE ? time() : '' );

		wp_enqueue_style( WOOASSISTANT_PLUGIN_SLUG . '-global-style',
			Assets::url( 'css/style.min.css' ), false, $pluginVersion );
	}
}