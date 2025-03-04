<?php

namespace WooAssistant\Helper;

class WooCommerce {
	public static function isWoocommerce(): bool {
		return is_woocommerce();
	}

	public static function isShop() {
		return is_shop();
	}

	public static function isProduct() {
		return is_product();
	}

	public static function isProductTaxonomy() {
		return is_product_taxonomy();
	}

	public static function isProductCategory( $term = '' ) {
		return is_product_category( $term );
	}

	public static function isProductTag( $term = '' ) {
		return is_product_tag( $term );
	}

	public static function isCart() {
		return is_cart();
	}

	public static function isCheckout() {
		return is_checkout();
	}

	public static function getProducts( $args ) {
		return wc_get_products( $args );
	}

	public static function getCurrentId(): int {
		global $product;

		$productID = 0;
		if ( is_a( $product, 'WC_Product' ) && method_exists( $product, 'get_id' ) ) {
			$productID = $product->get_id();
		}

		return $productID;
	}

	/**
	 * Get product add to card button
	 *
	 * @param $product
	 *
	 * @return string
	 */
	public static function getAddToCardButton( $product ): string {
		ob_start();
		$GLOBALS['product'] = $product;
		woocommerce_template_loop_add_to_cart();
		wc_setup_product_data( $GLOBALS['post'] );

		return ob_get_clean();
	}

	/**
	 * Get Woocommerce attributes
	 *
	 * @return array Woocommerce attributes
	 */
	public static function getAttributeTaxonomies(): array {
		$attributes   = wc_get_attribute_taxonomies();
		$wcAttributes = [];

		foreach ( $attributes as $attribute ) {
			$wcAttributes[ md5( $attribute->attribute_name ) ] = [
				'label' => $attribute->attribute_label,
				'name'  => $attribute->attribute_name
			];
		}

		return apply_filters( 'woo_assistant_attribute_taxonomies', $wcAttributes );
	}
}