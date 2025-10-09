<?php

namespace AssistantForWooCommerce\Admin;

defined( 'ABSPATH' ) || exit;

use AssistantForWooCommerce\Helper\Assets;
use AssistantForWooCommerce\Helper\Nonce;

class AdminAssets {
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );
	}

	public function enqueueScripts(): void {
		if ( ! AdminPages::isSettingPage() ) {
			return;
		}

		$pluginVersion = Assets::getVersion();
		$debugName     = ASSISTANTFORWOOCOMMERCE_DEBUG_MODE ? '' : '.min';

		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		wp_enqueue_style( ASSISTANTFORWOOCOMMERCE_PLUGIN_SLUG . '-admin-style',
			Assets::url( 'css-admin/admin-style' . $debugName . '.css' ), false, $pluginVersion );

		wp_enqueue_script( ASSISTANTFORWOOCOMMERCE_PLUGIN_SLUG . '-dom-drag',
			Assets::url( 'js-admin/dom-drag.js' ),
			[], $pluginVersion, [ 'in_footer' => true ] );

		/*wp_enqueue_script( ASSISTANTFORWOOCOMMERCE_PLUGIN_SLUG . '-modal',
			Assets::url( 'js-admin/modal.min.js' ),
			[], $pluginVersion, [ 'in_footer' => true ] );*/

		wp_enqueue_script( ASSISTANTFORWOOCOMMERCE_PLUGIN_SLUG . '-admin',
			Assets::url( 'js-admin/script.min.js' ),
			[
				'jquery',
				'jquery-ui-sortable',
				ASSISTANTFORWOOCOMMERCE_PLUGIN_SLUG . '-dom-drag',
				//ASSISTANTFORWOOCOMMERCE_PLUGIN_SLUG . '-modal'
			], $pluginVersion, [ 'in_footer' => true ] );

		wp_add_inline_script( ASSISTANTFORWOOCOMMERCE_PLUGIN_SLUG . '-admin', 'var assistantForWooCommerceAjax = false, assistantForWooCommerceModalCloseEvent;', 'before' );

		wp_localize_script( ASSISTANTFORWOOCOMMERCE_PLUGIN_SLUG . '-admin', ASSISTANTFORWOOCOMMERCE_PLUGIN_KEYCAP, array(
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'ajaxNonce'        => Nonce::create(),
			'removeText'       => esc_html__( 'Remove', 'assistant-for-woocommerce' ),
			'dtuConfirmDelete' => esc_html__( 'Are you sure you want to delete this item(s)?', 'assistant-for-woocommerce' ),
			'copyText'         => esc_html__( 'Click to copy this text.', 'assistant-for-woocommerce' ),
		) );
	}

	public static function imageUrl( $path ): string {
		return ASSISTANTFORWOOCOMMERCE_PLUGIN_URL . 'assets/images/' . $path;
	}
}