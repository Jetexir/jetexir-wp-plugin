<?php

namespace Jetexir\Helper;

class WooCommerce {
  public static function isComingSoon(): bool {
    return wc_string_to_bool( get_option( 'woocommerce_coming_soon' ) );
  }

  public static function getProductPositionPriority( $position ): int {
    switch ( $position ) {
      case 'before_title':
        return 1;
      case 'after_title':
        return 6;
      case 'after_rating':
        return 11;
      case 'after_price':
        return 13;
      case 'after_excerpt':
        return 21;
      case 'before_add_to_cart':
        return 29;
      case 'after_meta':
        return 41;
      case 'after_sharing':
        return 51;
      default:
        return 31; // after_add_to_cart
    }
  }

  public static function url( $page, $endpoint = 'dashboard' ) {
    if ( $page === 'cart' ) {
      return wc_get_cart_url();
    } elseif ( $page === 'checkout' ) {
      return wc_get_checkout_url();
    } elseif ( in_array( $page, [ 'myaccount', 'dashboard' ] ) ) {
      return $endpoint ? wc_get_account_endpoint_url( $endpoint ) : wc_get_page_permalink( 'myaccount' );
    } elseif ( $page === 'terms' ) {
      return wc_get_page_permalink( 'terms' );
    }

    return false;
  }

  public static function hposEnabled(): bool {
    return class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
  }

  /**
   * Check page has block
   * https://stackoverflow.com/a/77950175/3224296
   *
   * Woocommerce blocks: woocommerce/checkout, woocommerce/cart
   *
   * @param int $page Page ID
   * @param string $block block name
   *
   * @return bool
   */
  public static function hasBlockInPage( $page, $block ): bool {
    if ( class_exists( 'WC_Blocks_Utils' ) && method_exists( 'WC_Blocks_Utils', 'has_block_in_page' ) ) {
      return \WC_Blocks_Utils::has_block_in_page( $page, $block );
    }

    return false;
  }

  public static function currencySymbol() {
    return get_woocommerce_currency_symbol();
  }

  public static function isWoo(): bool {
    return self::isWoocommerce() || self::isDashboard() || self::isCart() || self::isCheckout();
  }

  public static function isWoocommerce(): bool {
    return is_woocommerce();
  }

  public static function isDashboard(): bool {
    return is_account_page();
  }

  public static function isShop(): bool {
    return is_shop();
  }

  public static function isProduct(): bool {
    return is_product();
  }

  public static function isProductTaxonomy(): bool {
    return is_product_taxonomy();
  }

  public static function isProductCategory( $term = '' ): bool {
    return is_product_category( $term );
  }

  public static function isProductTag( $term = '' ): bool {
    return is_product_tag( $term );
  }

  public static function isCart(): bool {
    return is_cart();
  }

  public static function isCheckout(): bool {
    return is_checkout();
  }

  public static function getOrderStatuses(): array {
    $statuses = wc_get_order_statuses();

    return array_combine(
      array_map( static fn( $k ) => str_replace( 'wc-', '', $k ), array_keys( $statuses ) ),
      array_values( $statuses )
    );
  }

  public static function getOrderMeta( $orderID, $metaKey, $single = true ) {
    if ( self::hposEnabled() ) {
      $order = wc_get_order( $orderID );
      if ( $order ) {
        return $order->get_meta( $metaKey, $single );
      }
    }

    return PostMeta::get( $orderID, $metaKey, $single );
  }

  public static function updateOrderMeta( $orderID, $metaKey, $metaValue ): void {
    if ( self::hposEnabled() ) {
      $order = wc_get_order( $orderID );
      $order->update_meta_data( $metaKey, $metaValue );
      $order->save();
    } else {
      PostMeta::update( $orderID, $metaKey, $metaValue );
    }
  }

  public static function changeOrdersStatus( $oldStatus, $newStatus ): int {
    $ordersChanged = 0;
    $limit         = 1000;
    while ( true ) {
      $orders = wc_get_orders( array(
        'type'   => 'shop_order',
        'status' => $oldStatus,
        'limit'  => $limit,
        'return' => 'ids',
      ) );
      if ( empty( $orders ) ) {
        break;
      }

      foreach ( $orders as $orderID ) {
        $order = wc_get_order( $orderID );
        $order->update_status( $newStatus );
        $ordersChanged ++;
      }
    }

    return $ordersChanged;
  }

  public static function getPaymentGateways(): array {
    $list     = [];
    $gateways = WC()->payment_gateways->get_available_payment_gateways();
    foreach ( $gateways as $gateway ) {
      $list[ $gateway->id ] = $gateway->settings['title'];
    }

    return $list;
  }

  public static function getProducts( $args ) {
    return wc_get_products( $args );
  }

  /**
   * Get product
   *
   * This function should only be called after 'init' action is finished, as there might be taxonomies that are getting
   * registered during the init action.
   *
   *
   * @param mixed $productID Post object or post ID of the product.
   *
   * @return \WC_Product|null|false
   */
  public static function getProduct( $productID ) {
    return wc_get_product( $productID );
  }

  /**
   * @return \WC_Cart|null
   */
  public static function getCart(): ?\WC_Cart {
    return WC()->cart;
  }

  public static function getCartItemsCount(): int {
    return WC()->cart->get_cart_contents_count();
  }

  public static function getCartTotal() {
    return WC()->cart->get_cart_total();
  }

  public static function getCartSubTotal() {
    return WC()->cart->get_cart_subtotal();
  }

  public static function getCartItems(): array {
    return WC()->cart->get_cart();
  }

  public static function getCartProduct( $productID, $variationId = 0 ) {
    if ( WC()->cart->is_empty() ) {
      return false;
    }

    $items         = self::getCartItems();
    $cartProductId = $variationId ?: $productID;
    $key           = $variationId ? 'variation_id' : 'product_id';
    foreach ( $items as $item ) {
      if ( $item[ $key ] === $cartProductId ) {
        return $item;
      }
    }

    return false;
  }

  public static function removeCartItem( $itemKey ): bool {
    return WC()->cart->remove_cart_item( $itemKey );
  }

  public static function getCurrentProductId(): int {
    global $product;

    $productID = 0;
    if ( is_a( $product, 'WC_Product' ) && method_exists( $product, 'get_id' ) ) {
      $productID = $product->get_id();
    }

    return $productID;
  }

  /**
   * Get product add to cart button
   *
   * @param $product
   *
   * @return string
   */
  public static function getAddToCartButton( $product ): string {
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

    /**
     * Filters the WooCommerce attribute taxonomies.
     *
     * @param array $wcAttributes Attribute taxonomies.
     *
     * @return array Attribute taxonomies.
     *
     * @since 1.0
     *
     */
    return (array) apply_filters( 'jetexir_attribute_taxonomies', $wcAttributes );
  }

  public static function getCheckoutFields( $type = '' ): array {
    if ( is_null( WC()->countries ) ) {
      WC()->countries = new \WC_Countries();
    }

    return WC()->checkout()->get_checkout_fields( $type );

    //$wcCountries = new \WC_Countries();
    //return $wcCountries->get_address_fields( $wcCountries->get_base_country(), $type . '_' );
  }

  public static function getVersion() {
    if ( class_exists( '\Automattic\Jetpack\Constants' ) && method_exists( '\Automattic\Jetpack\Constants', 'get_constant' ) ) {
      $wc_version = \Automattic\Jetpack\Constants::get_constant( 'WC_VERSION' );
      if ( $wc_version ) {
        return $wc_version;
      }
    }

    if ( function_exists( 'WC' ) && method_exists( WC(), 'version' ) ) {
      return WC()->version();
    }

    if ( defined( 'WC_VERSION' ) ) {
      return WC_VERSION;
    }

    // Return null since none of the above worked.
    return null;
  }
}
