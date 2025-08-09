<?php

namespace WooAssistant\Admin;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Nonce;

class AdminAssets {
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );
	}

	public function enqueueScripts(): void {
		if ( AdminPages::isSettingPage() ) {
			$pluginVersion = Assets::getVersion();
			$debugName     = WOOASSISTANT_DEBUG_MODE ? '' : '.min';

			wp_enqueue_media();
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );

			wp_enqueue_style( WOOASSISTANT_PLUGIN_SLUG . '-admin-style',
				Assets::url( 'css-admin/admin-style' . $debugName . '.css' ), false, $pluginVersion );

			wp_enqueue_script( WOOASSISTANT_PLUGIN_SLUG . '-dom-drag',
				Assets::url( 'js-admin/dom-drag.js' ),
				[], $pluginVersion, [ 'in_footer' => true ] );

			/*wp_enqueue_script( WOOASSISTANT_PLUGIN_SLUG . '-modal',
				Assets::url( 'js-admin/modal.min.js' ),
				[], $pluginVersion, [ 'in_footer' => true ] );*/

			wp_enqueue_script( WOOASSISTANT_PLUGIN_SLUG . '-admin',
				Assets::url( 'js-admin/script.min.js' ),
				[
					'jquery',
					'jquery-ui-sortable',
					WOOASSISTANT_PLUGIN_SLUG . '-dom-drag',
					//WOOASSISTANT_PLUGIN_SLUG . '-modal'
				], $pluginVersion, [ 'in_footer' => true ] );

			wp_add_inline_script( WOOASSISTANT_PLUGIN_SLUG . '-admin', 'var wooAssistantAjax = false, wooAssistantModalCloseEvent;', 'before' );

			wp_localize_script( WOOASSISTANT_PLUGIN_SLUG . '-admin', WOOASSISTANT_PLUGIN_KEYCAP, array(
				'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
				'ajaxNonce'        => Nonce::create(),
				'removeText'       => __( 'Remove', 'woo-assistant' ),
				'dtuConfirmDelete' => __( 'Are you sure you want to delete this item(s)?', 'woo-assistant' ),
				'copyText'        => __( 'Click to copy this text.', 'woo-assistant' ),
			) );
		}
	}

	public static function imageUrl( $path ): string {
		return WOOASSISTANT_PLUGIN_URL . 'assets/images/' . $path;
	}
}