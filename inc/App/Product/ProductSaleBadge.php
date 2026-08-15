<?php

namespace Jetexir\App\Product;

defined( 'ABSPATH' ) || exit;

use Jetexir\Addons\Addon;
use Jetexir\Interfaces\AddonInterface;
use Jetexir\Settings\Settings;

class ProductSaleBadge extends Addon implements AddonInterface {
  public string $addonID = 'product-sale-badge';
  public string $currentTab = 'product';
  public string $currentSection = 'global';

  public function initAction(): void {
    add_filter( 'jetexir_product_general_settings', [ $this, 'addProductGeneralSettings' ] );
    add_filter( 'woocommerce_sale_badge_text', [ $this, 'changeSaleBadgeText' ], 10, 2 );
    add_filter( 'woocommerce_sale_flash', [ $this, 'changeSaleBadgeTag' ], 10, 3 );
  }

  /**
   * Return sale badge text base on settings
   *
   * @copyright Percentage code from https://github.com/woocommerce/woocommerce/pull/57914
   */
  private function getSaleText( $product ) {
    if ( $this->getSetting( 'product_sale_badge_percentage', false ) ) {
      $regularPrice = (float) $product->get_regular_price();
      $salePrice    = (float) $product->get_sale_price();

      if ( $salePrice !== 0.0 && $regularPrice !== 0.0 ) {
        $percentage = $regularPrice
          ? round( ( ( $regularPrice - $salePrice ) / $regularPrice ) * 100 )
          : 0;

        /*if ( $percentage < 10 ) {
          return '%';
        }*/

        return '-' . $percentage . '%';
      }
    }

    if ( $text = $this->getSetting( 'product_sale_badge_text', false ) ) {
      return $text;
    }

    return false;
  }

  /**
   * Change the product sale badge tag.
   *
   * @param string $saleTag The sale badge tag.
   * @param \WP_Post $post The post object.
   * @param \WC_Product $product The product object.
   *
   * @return string The filtered sale badge tag.
   *
   */
  public function changeSaleBadgeTag( $saleTag, $post, $product ): string {
    if ( $text = $this->getSaleText( $product ) ) {
      return '<span class="onsale">' . esc_html( $text ) . '</span>';
    }

    return $saleTag;
  }

  /**
   * Change the product sale badge text.
   *
   * @param string $saleText The sale badge text.
   * @param \WC_Product $product The product object.
   *
   * @return string The filtered sale badge text.
   *
   */
  public function changeSaleBadgeText( $saleText, $product ): string {
    if ( $text = $this->getSaleText( $product ) ) {
      return esc_html( $text );
    }

    return $saleText;
  }

  public function addProductGeneralSettings( $settings ): array {
    $addonSettings = array(
      'start_grid_product_sale_badge' => array(
        'title' => esc_html__( 'Sale Badge', 'jetexir' ),
        'type'  => 'startgrid',
      ),
      'product_sale_badge_text'       => array(
        'id'          => 'product_sale_badge_text',
        'title'       => esc_html__( 'Sale Badge text', 'jetexir' ),
        'type'        => 'text',
        'default'     => esc_html__( 'Sale', 'jetexir' ),
        'placeholder' => esc_html__( 'Sale', 'jetexir' ),
      ),
      'product_sale_badge_percentage' => [
        'id'       => 'product_sale_badge_percentage',
        'title'    => esc_html__( 'Discount percentage', 'jetexir' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => false,
        'desc'     => esc_html__( 'Display discount percentage as sale badge', 'jetexir' ),
        'sanitize' => 'bool'
      ],
      'end_grid_product_sale_badge'   => array(
        'type' => 'endgrid',
      ),
    );

    return array_merge( $settings, $addonSettings );
  }

  public function info(): array {
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="#873eff" stroke-linecap="round" stroke-width="1.5" d="m9 15 6-6"/><path fill="#873eff" d="M15.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0M10.5 9.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/><path stroke="#873eff" stroke-width="1.5" d="M2 12c0-4.714 0-7.071 1.464-8.536C4.93 2 7.286 2 12 2s7.071 0 8.535 1.464C22 4.93 22 7.286 22 12s0 7.071-1.465 8.535C19.072 22 16.714 22 12 22s-7.071 0-8.536-1.465C2 19.072 2 16.714 2 12Z"/></svg>';

    return array(
      'id'             => $this->addonID,
      'title'          => esc_html__( 'Product Sale Badge', 'jetexir' ),
      'desc'           => esc_html__( 'Customize the product sale badge.', 'jetexir' ),
      'tags'           => [ esc_html__( 'Sale', 'jetexir' ) ],
      'cat'            => 'product',
      'icon'           => $icon,
      'more_info_link' => '{jetexir_website}/addons/sale-badges',
      'settings_key'   => $this->addonID
    );
  }
}
