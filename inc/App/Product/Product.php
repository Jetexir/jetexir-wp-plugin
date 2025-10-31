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
      'price'      => esc_html__( 'Price', 'assistant-for-woocommerce' ),
      'stock'      => esc_html__( 'Stock', 'assistant-for-woocommerce' ),
      'rating'     => esc_html__( 'Rating', 'assistant-for-woocommerce' ),
      'brand'      => esc_html__( 'Brand', 'assistant-for-woocommerce' ),
      'dimensions' => esc_html__( 'Dimensions', 'assistant-for-woocommerce' ),
      'weight'     => esc_html__( 'Weight', 'assistant-for-woocommerce' ),
    );
  }
}
