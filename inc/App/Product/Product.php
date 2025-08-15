<?php

namespace WooAssistant\App\Product;

defined( 'ABSPATH' ) || exit;

class Product {
	public function __construct() {
		new ProductGeneral();
		new ProductSaleBadge();
		new ProductPriceVariation();
		new ProductCompare();
		new ProductQuantity();
		new ProductWishList();
		new ProductSocialShare();
		new ProductFAQ();
		new ProductRelated();
		new ProductCall();
	}

	/**
	 * Get product fields
	 *
	 * @return array
	 */
	public static function getFields(): array {
		return array(
			'price'      => __( 'Price', 'wc-assistant' ),
			'stock'      => __( 'Stock', 'wc-assistant' ),
			'rating'     => __( 'Rating', 'wc-assistant' ),
			'brand'      => __( 'Brand', 'wc-assistant' ),
			'dimensions' => __( 'Dimensions', 'wc-assistant' ),
			'weight'     => __( 'Weight', 'wc-assistant' ),
		);
	}
}