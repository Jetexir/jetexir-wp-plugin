<?php

namespace WooAssistant\Admin;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Param;

class AdminAssets {
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );
	}

	public function enqueueScripts(): void {
		$pluginVersion = WOOASSISTANT_PLUGIN_VERSION . ( defined( 'DEVELOPMENT_MODE' ) && DEVELOPMENT_MODE ? time() : '' );

		if ( Param::get( 'page' ) === WOOASSISTANT_PLUGIN_SLUG ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );

			wp_enqueue_style( WOOASSISTANT_PLUGIN_SLUG . '-admin-style',
				Assets::url( 'css-admin/admin-style.min.css' ), false, $pluginVersion );

			wp_enqueue_script( WOOASSISTANT_PLUGIN_SLUG . '-admin',
				Assets::url( 'js-admin/script.min.js' ),
				[ 'jquery' ], $pluginVersion, [ 'in_footer' => true ] );

			wp_localize_script( WOOASSISTANT_PLUGIN_SLUG . '-admin', WOOASSISTANT_PLUGIN_KEYCAP, array(
				'ajaxurl'   => admin_url( 'admin-ajax.php' ),
				'ajaxnonce' => wp_create_nonce( WOOASSISTANT_PLUGIN_SLUG . current_time( 'd' ) )
			) );
		}
	}

	public static function imageUrl( $path ): string {
		return WOOASSISTANT_PLUGIN_URL . 'assets/images/' . $path;
	}
}