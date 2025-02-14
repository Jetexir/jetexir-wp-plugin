<?php

namespace WooAssistant\App;

class Product {
	public static function getCurrentId(): int {
		global $product;

		$productID = 0;
		if ( is_a( $product, 'WC_Product' ) && method_exists( $product, 'get_id' ) ) {
			$productID = $product->get_id();
		}

		return $productID;
	}
}