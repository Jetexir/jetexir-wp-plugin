<?php

namespace AssistantForWooCommerce\App\Product;

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
		new ProductSaleProgressBar();
	}

	/**
	 * Get product fields
	 *
	 * @return array
	 */
	public static function getFields(): array {
		return array(
			'price'      => __( 'Price', 'assistant-for-woocommerce' ),
			'stock'      => __( 'Stock', 'assistant-for-woocommerce' ),
			'rating'     => __( 'Rating', 'assistant-for-woocommerce' ),
			'brand'      => __( 'Brand', 'assistant-for-woocommerce' ),
			'dimensions' => __( 'Dimensions', 'assistant-for-woocommerce' ),
			'weight'     => __( 'Weight', 'assistant-for-woocommerce' ),
		);
	}
}