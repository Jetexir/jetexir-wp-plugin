<?php

namespace Jetexir\App\Product;

use Jetexir\Addons\Addon;
use Jetexir\Admin\AdminPages;
use Jetexir\Enums\Colors;
use Jetexir\Helper\Assets;
use Jetexir\Helper\Notice;
use Jetexir\Helper\Param;
use Jetexir\Helper\PostMeta;
use Jetexir\Helper\Sanitizing;
use Jetexir\Helper\Templates;
use Jetexir\Helper\WooCommerce;
use Jetexir\Interfaces\AddonInterface;

class ProductSaleProgressBar extends Addon implements AddonInterface {
  public string $addonID = 'product-sale-progress-bar';
  public string $currentTab = 'product';
  public string $currentSection = 'sale-progress-bar';

  public function adminInitAction(): void {
    if ( AdminPages::isSettingPage() && Param::get( 'section' ) === $this->currentSection ) {
      Notice::add( $this->currentTab, esc_html__( 'At present, this functionality is exclusively available for simple products.', 'jetexir' ), 'warning' );
    }
  }

  public function initAction(): void {
    if ( $position = $this->getSetting( 'product_sale_progress_bar_position', 'after_title' ) ) {
      add_action( 'woocommerce_single_product_summary', [
        $this,
        'addProgressBar'
      ], WooCommerce::getProductPositionPriority( $position ) );
    }

    add_action( 'woocommerce_product_options_stock_fields', [ $this, 'addStockToProduct' ] );
    add_action( 'woocommerce_process_product_meta', [ $this, 'adminProductSaveMeta' ] );
  }

  public function addProgressBar(): void {
    global $product;

    if ( ! $product->managing_stock() ) {
      return;
    }

    $productID            = $product->get_id();
    $stock                = Sanitizing::int( $product->get_stock_quantity() );
    $saleProgressBarStock = Sanitizing::int( PostMeta::get( $productID, JETEXIR_PLUGIN_KEY . '_sale_progress_bar_stock' ) );

    if ( $stock > $saleProgressBarStock ) {
      return;
    }

    $sold        = $saleProgressBarStock - $stock;
    $soldPercent = (int) ( 100 / $saleProgressBarStock ) * $sold;

    Templates::load( Templates::getPath( 'sale-progress-bar/progress_bar.php' ), array(
      'sold_title'            => $this->getSetting( 'product_sale_progress_bar_sold_title', esc_html__( 'Sold', 'jetexir' ) ),
      'sold'                  => $sold,
      'remaining_title'       => $this->getSetting( 'product_sale_progress_bar_remaining_title', esc_html__( 'Remaining', 'jetexir' ) ),
      'stock'                 => $stock,
      'sold_percent'          => $soldPercent,
      'progress_bar_bg_color' => $this->getSetting( 'product_sale_progress_bar_bg_color', Colors::primary ),
      'progress_bar_height'   => $this->getSetting( 'product_sale_progress_bar_height', 10 ),
    ) );
  }

  public function adminProductSaveMeta( $productID ): void {
    $stock = Sanitizing::int( Param::post( JETEXIR_INPUT_PREFIX . 'sale_progress_bar_stock' ) );

    if ( $stock ) {
      PostMeta::update( $productID, JETEXIR_PLUGIN_KEY . '_sale_progress_bar_stock', $stock );
    } else {
      PostMeta::delete( $productID, JETEXIR_PLUGIN_KEY . '_sale_progress_bar_stock' );
    }
  }

  public function addStockToProduct(): void {
    $product = wc_get_product();
    $value   = (int) $product->get_meta( JETEXIR_PLUGIN_KEY . '_sale_progress_bar_stock' );
    if ( ! $value ) {
      $value = wc_stock_amount( $product->get_stock_quantity( 'edit' ) ?? 1 );
    }

    woocommerce_wp_text_input(
      array(
        'id'                => JETEXIR_INPUT_PREFIX . 'sale_progress_bar_stock',
        'value'             => wc_stock_amount( $value ),
        'label'             => esc_html__( 'Sale progress bar quantity', 'jetexir' ),
        'desc_tip'          => true,
        'description'       => esc_html__( 'Please enter the starting quantity of product, The entered value must be greater than the quantity value.', 'jetexir' ),
        'type'              => 'number',
        'custom_attributes' => array(
          'step' => 'any',
        ),
        'data_type'         => 'stock',
      )
    );
  }

  public function addSectionSettings( $sections ): array {
    $sections[ $this->currentSection ] = array(
      'title'        => esc_html__( 'Sale progress bar', 'jetexir' ),
      'desc'         => esc_html__( 'Product sale progress bar', 'jetexir' ),
      'settings_key' => $this->addonID,
      'settings'     => [
        'product_sale_progress_bar_start_grid'      => array(
          'id'    => 'product_sale_progress_bar_start_grid',
          'title' => esc_html__( 'Sale progress bar', 'jetexir' ),
          'type'  => 'startgrid',
        ),
        'product_sale_progress_bar_sold_title'      => array(
          'id'          => 'product_sale_progress_bar_sold_title',
          'title'       => esc_html__( 'Sold title', 'jetexir' ),
          'type'        => 'text',
          'default'     => esc_html__( 'Sold', 'jetexir' ),
          'placeholder' => esc_html__( 'Sold', 'jetexir' ),
        ),
        'product_sale_progress_bar_remaining_title' => array(
          'id'          => 'product_sale_progress_bar_remaining_title',
          'title'       => esc_html__( 'Remaining product title', 'jetexir' ),
          'type'        => 'text',
          'default'     => esc_html__( 'Remaining', 'jetexir' ),
          'placeholder' => esc_html__( 'Remaining', 'jetexir' ),
        ),
        'product_sale_progress_bar_position'        => array(
          'id'          => 'product_sale_progress_bar_position',
          'title'       => esc_html__( 'Position on single page', 'jetexir' ),
          'type'        => 'select',
          'options'     => array(
            'before_title'       => esc_html__( 'Before title', 'jetexir' ),
            'after_title'        => esc_html__( 'After title', 'jetexir' ),
            'after_rating'       => esc_html__( 'After rating', 'jetexir' ),
            'after_price'        => esc_html__( 'After price', 'jetexir' ),
            'after_excerpt'      => esc_html__( 'After excerpt', 'jetexir' ),
            'before_add_to_cart' => esc_html__( 'Before add to cart button', 'jetexir' ),
            'after_add_to_cart'  => esc_html__( 'After add to cart button', 'jetexir' ),
            'after_meta'         => esc_html__( 'After meta', 'jetexir' ),
            'after_sharing'      => esc_html__( 'After sharing', 'jetexir' ),
          ),
          'option_none' => esc_html__( 'Hide', 'jetexir' ),
          'default'     => 'after_title',
          'sanitize'    => 'text',
        ),
        'product_sale_progress_bar_end_grid'        => array(
          'type' => 'endgrid',
        ),
        'product_sale_progress_bar_start_grid_2'    => array(
          'id'    => 'product_sale_progress_bar_start_grid_2',
          'title' => esc_html__( 'Style', 'jetexir' ),
          'type'  => 'startgrid',
        ),
        'product_sale_progress_bar_bg_color'        => array(
          'id'       => 'product_sale_progress_bar_bg_color',
          'title'    => esc_html__( 'Progress bar background color', 'jetexir' ),
          'type'     => 'wpColorPicker',
          'default'  => Colors::primary,
          'sanitize' => 'color'
        ),
        'product_sale_progress_bar_height'          => array(
          'id'         => 'product_sale_progress_bar_height',
          'title'      => esc_html__( 'Progress bar height', 'jetexir' ),
          'desc'       => esc_html__( 'Pixel', 'jetexir' ),
          'type'       => 'number',
          'default'    => 10,
          'attributes' => array(
            'placeholder' => 7,
            'step'        => 1,
            'min'         => 5,
          ),
          'sanitize'   => 'int'
        ),
        'product_sale_progress_bar_end_grid_2'      => array(
          'type' => 'endgrid',
        ),
        array(
          'type' => 'space',
          'size' => 30
        ),
      ]
    );

    return $sections;
  }

  /**
   * Enqueue style and script
   *
   * @return void
   */
  public function wpEnqueueScriptsAction(): void {
    if ( ! WooCommerce::isProduct() ) {
      return;
    }

    $pluginVersion = Assets::getVersion();
    $debugName     = JETEXIR_DEBUG_MODE ? '' : '.min';

    wp_enqueue_style( JETEXIR_PLUGIN_KEY . '-product-sale-progress-bar-style',
      Assets::url( 'css/product-sale-progress-bar' . $debugName . '.css' ),
      false, $pluginVersion );
  }

  public function info(): array {
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="-2.4 -2.4 28.8 28.8">
  <path fill="#fff" stroke-width="0" d="M12 20.834c1.654-.067 3.11-.935 4.519-1.803 1.434-.883 2.78-1.87 3.717-3.27 1.057-1.58 1.89-3.329 1.968-5.228.086-2.126.109-4.685-1.508-6.068-1.637-1.401-4.212-.093-6.303-.616-1.962-.49-3.456-2.515-5.47-2.326C6.84 1.72 5.026 3.191 3.75 4.852c-1.237 1.612-1.182 3.76-1.763 5.708-.7 2.351-3.252 4.817-2 6.926 1.303 2.195 4.793 1.237 7.252 1.922 1.629.454 3.072 1.494 4.761 1.426"/>
  <path fill="#873eff" fill-rule="evenodd" d="M0 10a5 5 0 0 1 5-5h14a5 5 0 0 1 5 5v4a5 5 0 0 1-5 5H5a5 5 0 0 1-5-5v-4Zm5-3a3 3 0 0 0-3 3v4a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-4a3 3 0 0 0-3-3H5Zm5 4a2 2 0 1 1 4 0v2a2 2 0 1 1-4 0v-2ZM6 9a2 2 0 0 0-2 2v2a2 2 0 1 0 4 0v-2a2 2 0 0 0-2-2Z" clip-rule="evenodd"/>
</svg>';

    return array(
      'id'             => $this->addonID,
      'title'          => esc_html__( 'Sale progress bar', 'jetexir' ),
      'desc'           => esc_html__( 'Sales progress bar for products', 'jetexir' ),
      'tags'           => [ esc_html__( 'Product', 'jetexir' ) ],
      'cat'            => 'product',
      'icon'           => $icon,
      'more_info_link' => 'https://parsa.ws',
      'settings_key'   => $this->addonID,
    );
  }
}
