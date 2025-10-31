<?php

namespace AssistantForWooCommerce\App\Product;

defined( 'ABSPATH' ) || exit;

use AssistantForWooCommerce\Addons\Addon;
use AssistantForWooCommerce\Interfaces\AddonInterface;
use AssistantForWooCommerce\Settings\Settings;

class ProductSaleBadge extends Addon implements AddonInterface {
  public string $addonID = 'product-sale-badge';
  public string $currentTab = 'product';
  public string $currentSection = 'global';

  public function __construct() {
    parent::__construct();

    add_filter( 'assistant_for_woocommerce_product_general_settings', [ $this, 'addProductGeneralSettings' ] );
  }

  public function initAction(): void {
    add_filter( 'woocommerce_sale_badge_text', [ $this, 'changeSaleBadgeText' ], 10, 2 );
    add_filter( 'woocommerce_sale_flash', [ $this, 'changeSaleBadgeTag' ], 10, 3 );
  }

  /**
   * Return sale badge text base on settings
   *
   * @copyright Percentage code from https://github.com/woocommerce/woocommerce/pull/57914
   */
  private function getSaleText( $product ) {
    if ( Settings::get( 'product_sale_badge_percentage', false ) ) {
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

    if ( $text = Settings::get( 'product_sale_badge_text', false ) ) {
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
        'title' => esc_html__( 'Sale Badge', 'assistant-for-woocommerce' ),
        'type'  => 'startgrid',
      ),
      'product_sale_badge_text'       => array(
        'id'          => 'product_sale_badge_text',
        'title'       => esc_html__( 'Sale Badge text', 'assistant-for-woocommerce' ),
        'type'        => 'text',
        'default'     => esc_html__( 'Sale', 'assistant-for-woocommerce' ),
        'placeholder' => esc_html__( 'Sale', 'assistant-for-woocommerce' ),
      ),
      'product_sale_badge_percentage' => [
        'id'       => 'product_sale_badge_percentage',
        'title'    => esc_html__( 'Discount percentage', 'assistant-for-woocommerce' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => false,
        'desc'     => esc_html__( 'Display discount percentage as sale badge', 'assistant-for-woocommerce' ),
        'sanitize' => 'bool'
      ],
      'end_grid_product_sale_badge'   => array(
        'type' => 'endgrid',
      ),
    );

    return array_merge( $settings, $addonSettings );
  }

  public function info(): array {
    $icon = '<svg viewBox="-2.4 -2.4 28.80 28.80" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"><path transform="translate(-2.4, -2.4), scale(0.8999999999999999)" d="M16,27.330527491867542C18.47261903065169,27.195429219894862,21.049516902786518,27.16278823595093,23.067683443322156,25.727831711002068C25.09682975393443,24.285068351286373,25.981674719611974,21.852814119309006,26.93744054882061,19.553789860688266C27.98161447831989,17.04210643282828,29.565475031227795,14.449227783136969,28.847170645366106,11.825701216455336C28.119917484605704,9.169490248636627,25.469744321178393,7.651330868375313,23.23715512951034,6.038910524096753C21.0112934417752,4.431348917040484,18.732104308490552,2.8198369950891253,16,2.547218908866247C13.079613936694546,2.255813468328671,9.898180744020381,2.6174143952310627,7.63148477053285,4.48172693697374C5.4127150716958,6.3066210670921965,5.200426519717348,9.470611019365325,4.275420611948478,12.190453226775702C3.334092606985781,14.958288131841709,1.3892677057115304,17.675494955057395,2.187882593559925,20.487828992033744C2.993426064777225,23.32456213693169,5.705595921606244,25.223004134631697,8.347994528435088,26.532081986408436C10.703615024499678,27.699086317481044,13.375066168940837,27.473947897608436,16,27.330527491867542" fill="#fff" strokewidth="0"></path></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M9 15L15 9" stroke="#873eff" stroke-width="1.5" stroke-linecap="round"></path> <path d="M15.5 14.5C15.5 15.0523 15.0523 15.5 14.5 15.5C13.9477 15.5 13.5 15.0523 13.5 14.5C13.5 13.9477 13.9477 13.5 14.5 13.5C15.0523 13.5 15.5 13.9477 15.5 14.5Z" fill="#873eff"></path> <path d="M10.5 9.5C10.5 10.0523 10.0523 10.5 9.5 10.5C8.94772 10.5 8.5 10.0523 8.5 9.5C8.5 8.94772 8.94772 8.5 9.5 8.5C10.0523 8.5 10.5 8.94772 10.5 9.5Z" fill="#873eff"></path> <path d="M2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12Z" stroke="#873eff" stroke-width="1.5"></path> </g></svg>';

    return array(
      'id'             => $this->addonID,
      'title'          => esc_html__( 'Product Sale Badge', 'assistant-for-woocommerce' ),
      'desc'           => esc_html__( 'Customize the product sale badge.', 'assistant-for-woocommerce' ),
      'tags'           => [ esc_html__( 'Sale', 'assistant-for-woocommerce' ) ],
      'cat'            => 'product',
      'icon'           => $icon,
      'more_info_link' => 'https://parsa.ws'
    );
  }
}
