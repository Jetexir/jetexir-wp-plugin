<?php

namespace WooAssistant\App\Product;

defined( 'ABSPATH' ) || exit;

class Product {
	public function __construct() {
		new ProductCompare();
		new ProductQuantity();
		//new ProductTest();
		new ProductSocialShare();
		new ProductFAQ();
	}

	/**
	 * Get product fields
	 *
	 * @return array
	 */
	public static function getFields(): array {
		return array(
			'price'      => __( 'Price', 'woo-assistant' ),
			'stock'      => __( 'Stock', 'woo-assistant' ),
			'rating'     => __( 'Rating', 'woo-assistant' ),
			'brand'      => __( 'Brand', 'woo-assistant' ),
			'dimensions' => __( 'Dimensions', 'woo-assistant' ),
			'weight'     => __( 'Weight', 'woo-assistant' ),
		);
	}
}