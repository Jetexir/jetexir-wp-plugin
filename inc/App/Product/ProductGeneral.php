<?php

namespace AssistantForWooCommerce\App\Product;

defined( 'ABSPATH' ) || exit;

class ProductGeneral {
	private const sectionID = 'general';

	public function __construct() {
		add_filter( 'assistant_for_woocommerce_product_settings_sections', [ $this, 'addSectionSettings' ] );
	}

	public function addSectionSettings( $sections ) {
		$settings = apply_filters( 'assistant_for_woocommerce_product_general_settings', [] );

		if ( empty( $settings ) ) {
			return $sections;
		}

		$sections[ self::sectionID ] = array(
			'title'    => __( 'General', 'assistant-for-woocommerce' ),
			'desc'     => __( 'Product general settings', 'assistant-for-woocommerce' ),
			'settings' => $settings
		);

		return $sections;
	}
}