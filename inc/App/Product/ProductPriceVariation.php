<?php

namespace Jetexir\App\Product;

defined( 'ABSPATH' ) || exit;

use Jetexir\Addons\Addon;
use Jetexir\Interfaces\AddonInterface;
use Jetexir\Settings\Settings;

class ProductPriceVariation extends Addon implements AddonInterface {
  public string $addonID = 'product-price-variation';
  public string $currentTab = 'product';
  public string $currentSection = 'general';

  public function initAction(): void {
    add_filter( 'jetexir_product_general_settings', [ $this, 'addProductGeneralSettings' ] );
    add_filter( 'woocommerce_variable_price_html', [ $this, 'getVariablePriceHtml' ], 10, 2 );
    add_filter( 'woocommerce_reset_variations_link', [ $this, 'removeResetLink' ] );
  }

  public function removeResetLink( $link ): string {
    if ( $this->getSetting( 'variation_hide_reset_link', false ) ) {
      return false;
    }

    return $link;
  }

  /**
   * @param string $price
   * @param \WC_Product_Variable $product
   *
   * @return string
   */
  public function getVariablePriceHtml( $price, $product ): string {
    $prices = $product->get_variation_prices( true );

    if ( ! isset( $prices['price'] ) || ! is_array( $prices['price'] ) || count( $prices['price'] ) === 1 ) {
      return $price;
    }

    $type = $this->getSetting( 'variation_price_type', 'min' );
    if ( empty( $type ) ) {
      return $price;
    }

    if ( $product->is_type( 'variable' ) ) {
      $addFrom  = $this->getSetting( 'variation_price_add_from', true );
      $addUpTo  = $this->getSetting( 'variation_price_add_up_to', true );
      $minPrice = current( $prices['price'] );
      $maxPrice = end( $prices['price'] );
      //$minRegPrice = current( $prices['regular_price'] );
      //$maxRegPrice = end( $prices['regular_price'] );

      if ( $minPrice !== $maxPrice ) {
        if ( $type === 'min' ) {
          $price = ( $addFrom ? esc_html__( 'From', 'jetexir' ) : '' ) . ' ' . wc_price( $minPrice );

        } elseif ( $type === 'max' ) {
          $price = ( $addUpTo ? esc_html__( 'Up To', 'jetexir' ) : '' ) . ' ' . wc_price( $maxPrice );

        } elseif ( $type === 'max_to_min' ) {
          $price = wc_format_price_range( $maxPrice, $minPrice );

        } else {
          $price = wc_format_price_range( $minPrice, $maxPrice );
        }
      }
    }

    return $price;
  }

  public function addProductGeneralSettings( $settings ): array {
    $addonSettings = array(
      'start_grid_product_variation_price' => array(
        'title' => esc_html__( 'Variation Prices', 'jetexir' ),
        'type'  => 'startgrid',
      ),

      'variation_price_type'      => array(
        'id'                => 'variation_price_type',
        'title'             => esc_html__( 'Variation price type', 'jetexir' ),
        'type'              => 'select',
        'options'           => array(
          'min'        => esc_html__( 'Minimum Price', 'jetexir' ),
          'max'        => esc_html__( 'Maximum Price', 'jetexir' ),
          'min_to_max' => esc_html__( 'Minimum to Maximum Price', 'jetexir' ),
          'max_to_min' => esc_html__( 'Maximum to Minimum Price', 'jetexir' ),
        ),
        'option_none'       => '---',
        'option_none_value' => '',
        'default'           => 'min',
        'sanitize'          => 'text'
      ),
      'variation_price_add_from'  => [
        'id'       => 'variation_price_add_from',
        'title'    => esc_html__( 'Add From', 'jetexir' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => true,
        'desc'     => esc_html__( 'Activate this feature to show "From" prior to the Minimum Price.', 'jetexir' ),
        'sanitize' => 'bool'
      ],
      'variation_price_add_up_to' => [
        'id'       => 'variation_price_add_up_to',
        'title'    => esc_html__( 'Add Up To', 'jetexir' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => true,
        'desc'     => esc_html__( 'Activate this option to present "Up To" before the Maximum Price.', 'jetexir' ),
        'sanitize' => 'bool'
      ],
      'variation_hide_reset_link' => array(
        'id'       => 'variation_hide_reset_link',
        'title'    => esc_html__( 'Hide Reset Link', 'jetexir' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => false,
        'desc'     => esc_html__( 'Remove "Clear" link on single product page.', 'jetexir' ),
        'sanitize' => 'bool'
      ),

      'end_grid_product_variation_price' => array(
        'type' => 'endgrid',
      ),
    );

    return array_merge( $settings, $addonSettings );
  }

  public function info(): array {
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><g stroke="#873eff" stroke-width="1.5"><path d="M4.728 16.137c-1.545-1.546-2.318-2.318-2.605-3.321-.288-1.003-.042-2.068.45-4.197l.283-1.228c.413-1.792.62-2.688 1.233-3.302s1.51-.82 3.302-1.233l1.228-.284c2.13-.491 3.194-.737 4.197-.45 1.003.288 1.775 1.061 3.32 2.606l1.83 1.83C20.657 9.248 22 10.592 22 12.262c0 1.671-1.344 3.015-4.033 5.704-2.69 2.69-4.034 4.034-5.705 4.034-1.67 0-3.015-1.344-5.704-4.033z"/><path stroke-linecap="round" d="M15.39 15.39c.585-.587.664-1.457.176-1.946s-1.359-.409-1.945.177c-.585.586-1.456.665-1.944.177s-.409-1.359.177-1.944m3.535 3.535.354.354m-.354-.354c-.4.401-.935.565-1.389.471m-2.5-4.36.354.354m0 0c.331-.332.753-.5 1.146-.497"/><circle cx="8.607" cy="8.879" r="2" transform="rotate(-45 8.607 8.879)"/></g></svg>';

    return array(
      'id'             => $this->addonID,
      'title'          => esc_html__( 'Product Price Variation', 'jetexir' ),
      'desc'           => esc_html__( 'Add advanced settings for WooCommerce variable product pricing.', 'jetexir' ),
      'tags'           => [ esc_html__( 'Product', 'jetexir' ) ],
      'cat'            => 'product',
      'icon'           => $icon,
      'more_info_link' => '{jetexir_website}/addons/price-variations',
      'settings_key'   => $this->addonID
    );
  }
}
