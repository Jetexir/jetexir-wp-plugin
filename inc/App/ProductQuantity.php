<?php

namespace WooAssistant\App;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Helper\Assets;
use WooAssistant\Helper\DebugTrait;
use WooAssistant\Helper\Templates;
use WooAssistant\Settings\Settings;

defined( 'ABSPATH' ) || exit;

class ProductQuantity {
	public function __construct() {
		//add_filter( 'woocommerce_locate_template', [ $this, 'changeWcTemplate' ], 10, 3 );
		add_action( 'woocommerce_before_quantity_input_field', [ $this, 'beforeQuantityInputField' ] );
		add_action( 'woocommerce_after_quantity_input_field', [ $this, 'afterQuantityInputField' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueScripts' ] );
	}

	public function beforeQuantityInputField(): void {
		if ( ! Settings::get( 'quantity_input_plus_minus_button', false ) ) {
			return;
		}

		$productID     = Product::getCurrentId();
		$displayButton = apply_filters( 'woo_assistant_quantity_input_display_plus_minus', true, $productID );

		if ( $displayButton ) {
			echo '<button type="button" class="wa-button wa-button-change-quantity" data-action="minus" aria-label="' . __( 'Reduce quantity', 'woo-assistant' ) . '">-</button>';
		}
	}

	public function afterQuantityInputField(): void {
		if ( ! Settings::get( 'quantity_input_plus_minus_button', false ) ) {
			return;
		}

		$productID     = Product::getCurrentId();
		$displayButton = apply_filters( 'woo_assistant_quantity_input_display_plus_minus', true, $productID );

		if ( $displayButton ) {
			echo '<button type="button" class="wa-button wa-button-change-quantity" data-action="plus" aria-label="' . __( 'Increase quantity', 'woo-assistant' ) . '">+</button>';
		}
	}

	public function enqueueScripts(): void {
		$pluginVersion = WOOASSISTANT_PLUGIN_VERSION . ( defined( 'DEVELOPMENT_MODE' ) && DEVELOPMENT_MODE ? time() : '' );

		//wp_enqueue_style( WOOASSISTANT_PLUGIN_SLUG . '-admin-style',
		//	Assets::url( 'css-admin/admin-style.min.css' ), false, WOOASSISTANT_PLUGIN_VERSION );

		if ( is_product() && Settings::get( 'quantity_input_plus_minus_button', false ) ) {
			wp_enqueue_script( WOOASSISTANT_PLUGIN_SLUG . '-product-quantity-style',
				Assets::url( 'js/product-quantity.min.js' ),
				[ 'jquery' ], $pluginVersion, [ 'in_footer' => true ] );
		}
	}

	public function changeWcTemplate( $template, $templateName, $templatePath ) {
		if ( $templateName === 'global/quantity-input.php' && Settings::get( 'quantity_input_style', false ) ) {
			$quantityTemplate = Templates::getPath( $templateName );

			if ( file_exists( $quantityTemplate ) ) {
				$template = $quantityTemplate;
			}
		}

		return $template;
	}
}