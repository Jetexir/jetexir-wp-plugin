<?php

namespace WooAssistant\App\Product;

defined( 'ABSPATH' ) || exit;

class ProductGlobal {
	private const sectionID = 'global';

	public function __construct() {
		add_action( 'init', [ $this, 'initAction' ] );
	}

	public function initAction(): void {
		add_filter( 'woo_assistant_product_settings_sections', [ $this, 'addSectionSettings' ] );
	}

	public function addSectionSettings( $sections ) {
		$settings                    = [];
		$sections[ self::sectionID ] = array(
			'title'    => __( 'Global', 'woo-assistant' ),
			'desc'     => __( 'Product global settings', 'woo-assistant' ),
			'settings' => apply_filters( 'woo_assistant_product_global_settings', $settings ),
		);

		return $sections;
	}
}