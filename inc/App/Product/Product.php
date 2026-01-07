<?php

namespace Jetexir\App\Product;

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
      'price'      => esc_html__( 'Price', 'jetexir' ),
      'stock'      => esc_html__( 'Stock', 'jetexir' ),
      'rating'     => esc_html__( 'Rating', 'jetexir' ),
      'brand'      => esc_html__( 'Brand', 'jetexir' ),
      'dimensions' => esc_html__( 'Dimensions', 'jetexir' ),
      'weight'     => esc_html__( 'Weight', 'jetexir' ),
    );
  }
}
