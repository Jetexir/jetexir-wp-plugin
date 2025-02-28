<?php

namespace WooAssistant\App\Product;

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

	public static function getCurrentId(): int {
		global $product;

		$productID = 0;
		if ( is_a( $product, 'WC_Product' ) && method_exists( $product, 'get_id' ) ) {
			$productID = $product->get_id();
		}

		return $productID;
	}
}