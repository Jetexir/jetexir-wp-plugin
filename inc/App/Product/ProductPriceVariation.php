<?php

namespace AssistantForWooCommerce\App\Product;

defined( 'ABSPATH' ) || exit;

use AssistantForWooCommerce\Addons\Addon;
use AssistantForWooCommerce\Interfaces\AddonInterface;
use AssistantForWooCommerce\Settings\Settings;

class ProductPriceVariation extends Addon implements AddonInterface {
  public string $addonID = 'product-price-variation';
  public string $currentTab = 'product';
  public string $currentSection = 'general';

  public function __construct() {
    parent::__construct();

    add_filter( 'assistant_for_woocommerce_product_general_settings', [ $this, 'addProductGeneralSettings' ] );
  }

  public function initAction(): void {
    add_filter( 'woocommerce_variable_price_html', [ $this, 'getVariablePriceHtml' ], 10, 2 );
    add_filter( 'woocommerce_reset_variations_link', [ $this, 'removeResetLink' ] );
  }

  public function removeResetLink( $link ): string {
    if ( Settings::get( 'variation_hide_reset_link', false ) ) {
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

    $type = Settings::get( 'variation_price_type', 'min' );
    if ( empty( $type ) ) {
      return $price;
    }

    if ( $product->is_type( 'variable' ) ) {
      $addFrom  = Settings::get( 'variation_price_add_from', true );
      $addUpTo  = Settings::get( 'variation_price_add_up_to', true );
      $minPrice = current( $prices['price'] );
      $maxPrice = end( $prices['price'] );
      //$minRegPrice = current( $prices['regular_price'] );
      //$maxRegPrice = end( $prices['regular_price'] );

      if ( $minPrice !== $maxPrice ) {
        if ( $type === 'min' ) {
          $price = ( $addFrom ? esc_html__( 'From', 'assistant-for-woocommerce' ) : '' ) . ' ' . wc_price( $minPrice );

        } elseif ( $type === 'max' ) {
          $price = ( $addUpTo ? esc_html__( 'Up To', 'assistant-for-woocommerce' ) : '' ) . ' ' . wc_price( $maxPrice );

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
        'title' => esc_html__( 'Variation Prices', 'assistant-for-woocommerce' ),
        'type'  => 'startgrid',
      ),

      'variation_price_type'      => array(
        'id'                => 'variation_price_type',
        'title'             => esc_html__( 'Variation price type', 'assistant-for-woocommerce' ),
        'type'              => 'select',
        'options'           => array(
          'min'        => esc_html__( 'Minimum Price', 'assistant-for-woocommerce' ),
          'max'        => esc_html__( 'Maximum Price', 'assistant-for-woocommerce' ),
          'min_to_max' => esc_html__( 'Minimum to Maximum Price', 'assistant-for-woocommerce' ),
          'max_to_min' => esc_html__( 'Maximum to Minimum Price', 'assistant-for-woocommerce' ),
        ),
        'option_none'       => '---',
        'option_none_value' => '',
        'default'           => 'min',
        'sanitize'          => 'text'
      ),
      'variation_price_add_from'  => [
        'id'       => 'variation_price_add_from',
        'title'    => esc_html__( 'Add From', 'assistant-for-woocommerce' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => true,
        'desc'     => esc_html__( 'Activate this feature to show "From" prior to the Minimum Price.', 'assistant-for-woocommerce' ),
        'sanitize' => 'bool'
      ],
      'variation_price_add_up_to' => [
        'id'       => 'variation_price_add_up_to',
        'title'    => esc_html__( 'Add Up To', 'assistant-for-woocommerce' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => true,
        'desc'     => esc_html__( 'Activate this option to present "Up To" before the Maximum Price.', 'assistant-for-woocommerce' ),
        'sanitize' => 'bool'
      ],
      'variation_hide_reset_link' => array(
        'id'       => 'variation_hide_reset_link',
        'title'    => esc_html__( 'Hide Reset Link', 'assistant-for-woocommerce' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => false,
        'desc'     => esc_html__( 'Remove "Clear" link on single product page.', 'assistant-for-woocommerce' ),
        'sanitize' => 'bool'
      ),

      'end_grid_product_variation_price' => array(
        'type' => 'endgrid',
      ),
    );

    return array_merge( $settings, $addonSettings );
  }

  public function info(): array {
    $icon = '<svg viewBox="-6.08 -6.08 44.16 44.16" id="Lager_101" data-name="Lager 101" xmlns="http://www.w3.org/2000/svg" fill="#ffffff" stroke="#ffffff"><g id="SVGRepo_bgCarrier" stroke-width="0" transform="translate(0,0), scale(1)"><path transform="translate(-6.08, -6.08), scale(1.38)" d="M16,31.9156032204628C18.565905123714455,31.860634153439904,19.797392506512512,28.630294451314118,21.762411822309577,26.979358416581732C23.31522056946961,25.67474634731345,24.99728650061206,24.64351663700817,26.399548437151275,23.17828999315048C27.997663860423508,21.50841560155324,29.95679923217185,19.99671457926298,30.576148780677617,17.769864892692613C31.216224359106707,15.468495488864106,31.02289724536637,12.82973745403207,29.857749474733772,10.744448852526466C28.71765881373149,8.704005468439421,26.012429340919017,8.219713757790444,24.332181956680646,6.594910801512569C22.555776444251567,4.8771227214844615,22.074708992096994,1.6627019696719967,19.713261391734083,0.9347063218722909C17.37904102930416,0.21510438592703396,15.130289367486064,2.290410184689948,12.806038661883333,3.0415894602985336C10.643596515960187,3.740473500993611,8.338925030622594,3.956475339284764,6.450694330097075,5.221062132824809C4.42863342954942,6.575277753752161,1.9917145643647003,8.13848541930616,1.607343229436026,10.541585273738432C1.2098201715320087,13.026910051560067,3.7776055935309865,14.985932795772532,4.503344268815267,17.39594673930401C5.085067682010951,19.32771858063678,4.446178245898823,21.53273527681291,5.445003817937604,23.285587824253124C6.467502005113549,25.079983765163984,8.507662628535865,25.921785458793067,10.106534238446823,27.229060835504573C12.078272634169846,28.841200946109765,13.453674730633587,31.970152830695962,16,31.9156032204628" fill="#fff" strokewidth="0"></path></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path id="Path_99" data-name="Path 99" d="M14.764,4,26.49,15.76a1,1,0,0,1,0,1.413l-9.325,9.316a1,1,0,0,1-1.413,0L4,14.75V5A1,1,0,0,1,5,4h9.764m.808-4H2.053A2.053,2.053,0,0,0,0,2.053v13.5A2.056,2.056,0,0,0,.6,17.01L15.006,31.4a2.052,2.052,0,0,0,2.9,0L31.4,17.922a2.053,2.053,0,0,0,0-2.9L17.026.6A2.053,2.053,0,0,0,15.572,0Z" fill="#873eff"></path> <circle id="Ellipse_10" data-name="Ellipse 10" cx="3" cy="3" r="3" transform="translate(8 8)" fill="#873eff"></circle> </g></svg>';

    return array(
      'id'             => $this->addonID,
      'title'          => esc_html__( 'Product Price Variation', 'assistant-for-woocommerce' ),
      'desc'           => esc_html__( 'Add advanced settings for WooCommerce variable product pricing.', 'assistant-for-woocommerce' ),
      'tags'           => [ esc_html__( 'Product', 'assistant-for-woocommerce' ) ],
      'cat'            => 'product',
      'icon'           => $icon,
      'more_info_link' => 'https://parsa.ws'
    );
  }
}
