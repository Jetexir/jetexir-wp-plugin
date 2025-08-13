<?php

namespace WooAssistant\App\Product;

defined( 'ABSPATH' ) || exit;

class ProductGeneral {
	private const sectionID = 'general';

	public function __construct() {
		add_filter( 'woo_assistant_product_settings_sections', [ $this, 'addSectionSettings' ] );
	}

	public function addSectionSettings( $sections ) {
		$settings = apply_filters( 'woo_assistant_product_general_settings', [] );

		if ( empty( $settings ) ) {
			return $sections;
		}

		$sections[ self::sectionID ] = array(
			'title'    => __( 'General', 'woo-assistant' ),
			'desc'     => __( 'Product general settings', 'woo-assistant' ),
			'settings' => $settings
		);

		return $sections;
	}
}