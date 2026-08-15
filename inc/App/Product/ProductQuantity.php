<?php

namespace Jetexir\App\Product;

defined( 'ABSPATH' ) || exit;

use Jetexir\Addons\Addon;
use Jetexir\Admin\AdminAssets;
use Jetexir\Helper\Assets;
use Jetexir\Helper\Helper;
use Jetexir\Helper\Notice;
use Jetexir\Helper\Param;
use Jetexir\Helper\PostMeta;
use Jetexir\Helper\Sanitizing;
use Jetexir\Helper\WooCommerce;
use Jetexir\Interfaces\AddonInterface;
use Jetexir\Settings\Settings;

class ProductQuantity extends Addon implements AddonInterface {
  public string $addonID = 'product-quantity';
  public string $currentTab = 'product';
  public string $currentSection = 'quantity';
  private static bool $printed = false;
  private static bool $printStyle = false;

  public function initAction(): void {
    add_filter( 'jetexir_settings_before_save', [ $this, 'checkSettingsBeforeSave' ], 10, 2 );

    if ( $this->getSetting( 'product_quantity_tools_enable', false ) ) {
      add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'addToCartValidation' ], 10, 5 );
      add_filter( 'woocommerce_quantity_input_args', [ $this, 'changeQuantityInputArgs' ], 10, 2 );
      add_filter( 'woocommerce_blocks_product_grid_add_to_cart_attributes',
        [ $this, 'changeQuantityAddToCart' ], 10, 2 );
      add_filter( 'woocommerce_loop_add_to_cart_link', [ $this, 'changeQuantityAddToCartLink' ], 10, 3 );
      add_filter( 'woocommerce_quantity_input_min', [ $this, 'changeQuantityInputMin' ], 10, 2 );
      add_filter( 'woocommerce_quantity_input_max', [ $this, 'changeQuantityInputMax' ], 10, 2 );
      add_filter( 'woocommerce_quantity_input_step', [ $this, 'changeQuantityInputStep' ], 10, 2 );
      add_filter( 'woocommerce_available_variation', [ $this, 'changeAvailableVariation' ], 10, 3 );
    }
  }

  public function adminInitAction(): void {
    // Control min/max/step per product
    if ( $this->getSetting( 'product_quantity_tools_enable', false ) && $this->getSetting( 'product_single_quantity_tools_enable', false ) ) {
      add_action( 'woocommerce_process_product_meta', [ $this, 'adminProductSaveMeta' ] );
      add_action( 'woocommerce_product_options_stock_fields', [ $this, 'addInventoryFields' ] );

      add_action( 'woocommerce_save_product_variation', [ $this, 'adminVariationSaveMeta' ], 10, 2 );
      add_action( 'woocommerce_variation_options_inventory', [ $this, 'addVariationInventoryFields' ], 10, 3 );
    }
  }

  public function templateRedirectAction(): void {
    if ( $this->getSetting( 'products_sold_individually', false ) ) {
      add_filter( 'woocommerce_is_sold_individually', '__return_true', 999 );
    }

    // Add plus/minus buttons
    add_action( 'woocommerce_before_quantity_input_field', [ $this, 'beforeQuantityInputField' ] );
    add_action( 'woocommerce_after_quantity_input_field', [ $this, 'afterQuantityInputField' ] );

    if ( WooCommerce::isWoocommerce() || WooCommerce::isCart() ) {
      add_action( 'wp_footer', [ $this, 'enqueueScripts' ], 0 );
      add_action( 'wp_footer', [ $this, 'enqueueStyle' ], 0 );
      add_action( 'wp_footer', [ $this, 'printStyleFooter' ] );
    }
    //add_filter( 'jetexir_settings_header_image', [ $this, 'addHeaderImage' ], 10, 4 );
  }

  public function adminProductSaveMeta( $productID ): void {
    $min  = Sanitizing::int( Param::post( JETEXIR_INPUT_PREFIX . 'product_quantity_min' ) );
    $max  = Sanitizing::int( Param::post( JETEXIR_INPUT_PREFIX . 'product_quantity_max' ) );
    $step = Sanitizing::int( Param::post( JETEXIR_INPUT_PREFIX . 'product_quantity_step' ) );

    if ( $min && $max && $min > $max ) {
      $max = $min + 10;
    }

    if ( $min ) {
      PostMeta::update( $productID, JETEXIR_PLUGIN_KEY . '_product_quantity_min', $min );
    } else {
      PostMeta::delete( $productID, JETEXIR_PLUGIN_KEY . '_product_quantity_min' );
    }
    if ( $max ) {
      PostMeta::update( $productID, JETEXIR_PLUGIN_KEY . '_product_quantity_max', $max );
    } else {
      PostMeta::delete( $productID, JETEXIR_PLUGIN_KEY . '_product_quantity_max' );
    }
    if ( $step ) {
      PostMeta::update( $productID, JETEXIR_PLUGIN_KEY . '_product_quantity_step', $step );
    } else {
      PostMeta::delete( $productID, JETEXIR_PLUGIN_KEY . '_product_quantity_step' );
    }
  }

  public function addInventoryFields(): void {
    $inputs = array(
      array(
        'id'                => JETEXIR_INPUT_PREFIX . 'product_quantity_min',
        'name'              => JETEXIR_INPUT_PREFIX . 'product_quantity_min',
        'label'             => esc_html__( 'Minimum Quantity', 'jetexir' ),
        'type'              => 'number',
        'desc_tip'          => true,
        'description'       => esc_html__( 'Enter minimum quantity for this product', 'jetexir' ),
        'data_type'         => 'decimal',
        'placeholder'       => 'eg: 1',
        'custom_attributes' => array(
          'step' => 1,
          'min'  => 1
        )
      ),
      array(
        'id'                => JETEXIR_INPUT_PREFIX . 'product_quantity_max',
        'name'              => JETEXIR_INPUT_PREFIX . 'product_quantity_max',
        'label'             => esc_html__( 'Maximum Quantity', 'jetexir' ),
        'type'              => 'number',
        'desc_tip'          => true,
        'description'       => esc_html__( 'Enter maximum quantity for this product', 'jetexir' ),
        'data_type'         => 'decimal',
        'placeholder'       => 'eg: 10',
        'custom_attributes' => array(
          'step' => 1,
          'min'  => 1
        )
      ),
      array(
        'id'                => JETEXIR_INPUT_PREFIX . 'product_quantity_step',
        'name'              => JETEXIR_INPUT_PREFIX . 'product_quantity_step',
        'label'             => esc_html__( 'Quantity Step', 'jetexir' ),
        'type'              => 'number',
        'desc_tip'          => true,
        'description'       => esc_html__( 'Enter quantity step for this product', 'jetexir' ),
        'data_type'         => 'decimal',
        'placeholder'       => 'eg: 1',
        'custom_attributes' => array(
          'step' => 1,
          'min'  => 1
        )
      )
    );

    /**
     * Filters the product quantity admin fields.
     *
     * @param array $inputs Admin fields.
     *
     * @return array Admin fields.
     *
     * @since 1.0
     *
     */
    $inputs = (array) apply_filters( 'jetexir_product_quantity_settings', $inputs );
    if ( ! empty( $inputs ) ) {
      foreach ( $inputs as $input ) {
        woocommerce_wp_text_input( $input );
      }
    }
  }

  public function adminVariationSaveMeta( $variationID, $i ): void {
    // PHPCS ignore reason: Nonce check is already happening before this logic in `AdminPages` class.
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
    if ( ! isset( $_POST[ JETEXIR_INPUT_PREFIX . 'variation_quantity_min' ][ $i ] ) ) {
      return;
    }

    $min  = Sanitizing::int( Param::post( JETEXIR_INPUT_PREFIX . 'variation_quantity_min' )[ $i ] );
    $max  = Sanitizing::int( Param::post( JETEXIR_INPUT_PREFIX . 'variation_quantity_max' )[ $i ] );
    $step = Sanitizing::int( Param::post( JETEXIR_INPUT_PREFIX . 'variation_quantity_step' )[ $i ] );

    if ( $min && $max && $min > $max ) {
      $max = $min + 10;
    }

    if ( $min ) {
      PostMeta::update( $variationID, JETEXIR_PLUGIN_KEY . '_variation_quantity_min', $min );
    } else {
      PostMeta::delete( $variationID, JETEXIR_PLUGIN_KEY . '_variation_quantity_min' );
    }
    if ( $max ) {
      PostMeta::update( $variationID, JETEXIR_PLUGIN_KEY . '_variation_quantity_max', $max );
    } else {
      PostMeta::delete( $variationID, JETEXIR_PLUGIN_KEY . '_variation_quantity_max' );
    }
    if ( $step ) {
      PostMeta::update( $variationID, JETEXIR_PLUGIN_KEY . '_variation_quantity_step', $step );
    } else {
      PostMeta::delete( $variationID, JETEXIR_PLUGIN_KEY . '_variation_quantity_step' );
    }
  }

  /**
   * Variation options inventory.
   *
   * @param int $loop Position in the loop.
   * @param array $variationData Variation data.
   * @param \WP_Post $variation Post data.
   *
   */
  public function addVariationInventoryFields( $loop, $variationData, $variation ): void {
    $inputs = array(
      array(
        'id'                => JETEXIR_INPUT_PREFIX . 'variation_quantity_min[' . $loop . ']',
        'name'              => JETEXIR_INPUT_PREFIX . 'variation_quantity_min[' . $loop . ']',
        'label'             => esc_html__( 'Minimum Quantity', 'jetexir' ),
        'type'              => 'number',
        'desc_tip'          => true,
        'description'       => esc_html__( 'Enter minimum quantity for this product variation', 'jetexir' ),
        'data_type'         => 'decimal',
        'placeholder'       => 'eg: 1',
        'custom_attributes' => array(
          'step' => 1,
          'min'  => 1
        ),
        'wrapper_class'     => 'form-row form-row-first',
        'value'             => get_post_meta( $variation->ID, JETEXIR_PLUGIN_KEY . '_variation_quantity_min', true )
      ),
      array(
        'id'                => JETEXIR_INPUT_PREFIX . 'variation_quantity_max[' . $loop . ']',
        'name'              => JETEXIR_INPUT_PREFIX . 'variation_quantity_max[' . $loop . ']',
        'label'             => esc_html__( 'Maximum Quantity', 'jetexir' ),
        'type'              => 'number',
        'desc_tip'          => true,
        'description'       => esc_html__( 'Enter maximum quantity for this product variation', 'jetexir' ),
        'data_type'         => 'decimal',
        'placeholder'       => 'eg: 10',
        'custom_attributes' => array(
          'step' => 1,
          'min'  => 1
        ),
        'wrapper_class'     => 'form-row form-row-last',
        'value'             => get_post_meta( $variation->ID, JETEXIR_PLUGIN_KEY . '_variation_quantity_max', true )
      ),
      array(
        'id'                => JETEXIR_INPUT_PREFIX . 'variation_quantity_step[' . $loop . ']',
        'name'              => JETEXIR_INPUT_PREFIX . 'variation_quantity_step[' . $loop . ']',
        'label'             => esc_html__( 'Quantity Step', 'jetexir' ),
        'type'              => 'number',
        'desc_tip'          => true,
        'description'       => esc_html__( 'Enter quantity step for this product variation', 'jetexir' ),
        'data_type'         => 'decimal',
        'placeholder'       => 'eg: 1',
        'custom_attributes' => array(
          'step' => 1,
          'min'  => 1
        ),
        'wrapper_class'     => 'form-row',
        'value'             => get_post_meta( $variation->ID, JETEXIR_PLUGIN_KEY . '_variation_quantity_step', true )
      )
    );

    /**
     * Filters the product variation quantity admin fields.
     *
     * @param array $inputs Admin fields.
     *
     * @return array Admin fields.
     *
     * @since 1.0
     *
     */
    $inputs = (array) apply_filters( 'jetexir_product_variation_quantity_settings', $inputs );
    if ( ! empty( $inputs ) ) {
      foreach ( $inputs as $input ) {
        woocommerce_wp_text_input( $input );
      }
    }
  }

  /**
   * @param array $data
   * @param \WC_Product_Variable $variable
   * @param \WC_Product_Variation $variation
   *
   * @return array
   */
  public function changeAvailableVariation( $data, $variable, $variation ): array {
    $variationID = $variation->get_id();
    $productID   = $variation->get_parent_id();
    $maxQty      = $productMax = $variationMax = (int) $data['max_qty'];
    $minQty      = $productMin = $variationMin = (int) $data['min_qty'];
    $min         = Sanitizing::int( $this->getSetting( 'quantity_minimum_value', false ) );
    $max         = Sanitizing::int( $this->getSetting( 'quantity_maximum_value', false ) );

    if ( ! $variation->is_sold_individually() && $this->getSetting( 'product_single_quantity_tools_enable', false ) ) {
      $_productMin = Sanitizing::int( PostMeta::get( $productID, JETEXIR_PLUGIN_KEY . '_product_quantity_min' ) );
      if ( $_productMin ) {
        $productMin = $_productMin;
      }
      $_productMax = Sanitizing::int( PostMeta::get( $productID, JETEXIR_PLUGIN_KEY . '_product_quantity_max' ) );
      if ( $_productMax ) {
        $variationMax = $_productMax;
      }
      $_variationMin = Sanitizing::int( PostMeta::get( $variationID, JETEXIR_PLUGIN_KEY . '_variation_quantity_min' ) );
      if ( $_variationMin ) {
        $variationMin = $_variationMin;
      }
      $_variationMax = Sanitizing::int( PostMeta::get( $variationID, JETEXIR_PLUGIN_KEY . '_variation_quantity_max' ) );
      if ( $_variationMax ) {
        $variationMax = $_variationMax;
      }
    }

    $data['max_qty'] = min( $maxQty, $max, $productMax, $variationMax );
    $data['min_qty'] = max( $minQty, $min, $productMin, $variationMin );

    return $data;
  }

  /**
   * Set step quantity value allowed for the product.
   *
   * @param int $step Step quantity value.
   * @param \WC_Product $product Product object.
   */
  public function changeQuantityInputStep( $step, $product ) {
    $step       = (int) $step;
    $globalStep = $productStep = Sanitizing::int( $this->getSetting( 'quantity_step_value', false ) );

    if ( $this->getSetting( 'product_single_quantity_tools_enable', false ) ) {
      $_productStep = Sanitizing::int( PostMeta::get( $product->get_id(), JETEXIR_PLUGIN_KEY . '_product_quantity_step' ) );
      if ( $_productStep ) {
        $productStep = $_productStep;
      }
    }

    return min( $step, $productStep, $globalStep );
  }

  /**
   * Set maximum quantity value allowed for the product.
   *
   * @param int $max Minimum quantity value.
   * @param \WC_Product $product Product object.
   */
  public function changeQuantityInputMax( $max, $product ) {
    $max       = (int) $max;
    $globalMax = $productMax = Sanitizing::int( $this->getSetting( 'quantity_maximum_value', false ) );

    if ( $this->getSetting( 'product_single_quantity_tools_enable', false ) ) {
      $_variationMax = Sanitizing::int( PostMeta::get( $product->get_id(), JETEXIR_PLUGIN_KEY . '_variation_quantity_max' ) );
      if ( $_variationMax ) {
        $productMax = $_variationMax;
      } else {
        $_productMax = Sanitizing::int( PostMeta::get( $product->get_id(), JETEXIR_PLUGIN_KEY . '_product_quantity_max' ) );
        if ( $_productMax ) {
          $productMax = $_productMax;
        }
      }
    }

    return min( $max, $productMax, $globalMax );
  }

  /**
   * Set minimum quantity value allowed for the product.
   *
   * @param int $min Minimum quantity value.
   * @param \WC_Product $product Product object.
   */
  public function changeQuantityInputMin( $min, $product ) {
    $min       = (int) $min;
    $globalMin = $productMin = Sanitizing::int( $this->getSetting( 'quantity_minimum_value', false ) );

    if ( $this->getSetting( 'product_single_quantity_tools_enable', false ) ) {
      $variationMin = Sanitizing::int( PostMeta::get( $product->get_id(), JETEXIR_PLUGIN_KEY . '_variation_quantity_min' ) );
      if ( $variationMin ) {
        $productMin = $variationMin;
      } else {
        $_productMin = Sanitizing::int( PostMeta::get( $product->get_id(), JETEXIR_PLUGIN_KEY . '_product_quantity_min' ) );
        if ( $_productMin ) {
          $productMin = $_productMin;
        }
      }
    }

    return max( $min, $productMin, $globalMin );
  }

  /**
   * Change the quantity attr for add to cart link.
   *
   * @param string $link Args
   * @param \WC_Product $product The WC_Product instance of the product that will be added to the cart once the button is pressed.
   * @param array $args Args
   *
   * @return string Returns an associative array derived from the default array passed as an argument and added the extra HTML attributes.
   */
  public function changeQuantityAddToCartLink( $link, $product, $args ): string {
    $productID = $product->get_id();
    $min       = Sanitizing::int( $this->getSetting( 'quantity_minimum_value', false ) );
    $quantity  = Sanitizing::int( $args['quantity'] );

    if ( $this->getSetting( 'product_single_quantity_tools_enable', false ) ) {
      $variationMin = Sanitizing::int( PostMeta::get( $productID, JETEXIR_PLUGIN_KEY . '_variation_quantity_min' ) );
      if ( $variationMin ) {
        $min = $variationMin;
      } else {
        $productMin = Sanitizing::int( PostMeta::get( $productID, JETEXIR_PLUGIN_KEY . '_product_quantity_min' ) );
        if ( $productMin ) {
          $min = $productMin;
        }
      }
    }

    if ( $min !== $quantity ) {
      $min  = max( $min, $quantity );
      $link = str_replace( 'data-quantity="' . $quantity . '"', 'data-quantity="' . $min . '"', $link );
    }

    return $link;
  }

  /**
   * Check quantity in add to cart action
   *
   * @param boolean $passed True if the item passed validation.
   * @param integer $productId Product ID being validated.
   * @param integer $quantity Quantity added to the cart.
   * @param integer $variationId Variation ID being added to the cart.
   * @param array $variations Variation data.
   *
   * @return boolean
   * @deprecated
   */
  public function addToCartValidation( $passed, $productId, $quantity, $variationID = 0, $variations = [] ): bool {
    if ( ! $passed ) {
      return $passed;
    }

    $quantities    = WC()->cart->get_cart_item_quantities();
    $cartProductId = $variationID ?: $productId;
    $stockQuantity = $globalMax = $productMax = Sanitizing::int( $this->getSetting( 'quantity_maximum_value', false ) );

    $cartProduct = WooCommerce::getCartProduct( $productId, $variationID );
    if ( $cartProduct && $cartProduct['manage_stock'] && $cartProduct['stock_quantity'] > 0 ) {
      $stockQuantity = $cartProduct['stock_quantity'];
    }

    if ( $this->getSetting( 'product_single_quantity_tools_enable', false ) ) {
      $_productMax = Sanitizing::int( PostMeta::get( $productId, JETEXIR_PLUGIN_KEY . '_product_quantity_max' ) );
      if ( $_productMax ) {
        $productMax = $_productMax;
      }

      if ( $variationID ) {
        $_variationMax = Sanitizing::int( PostMeta::get( $variationID, JETEXIR_PLUGIN_KEY . '_variation_quantity_max' ) );
        if ( $_variationMax ) {
          $productMax = $_variationMax;
        }
      }
    }

    $quantities[ $cartProductId ] = isset( $quantities[ $cartProductId ] ) ? $quantities[ $cartProductId ] + $quantity : $quantity;
    $max                          = min( $stockQuantity, $globalMax, $productMax );
    if ( $quantities[ $cartProductId ] > $max ) {
      wc_add_notice( esc_html__( 'You have reached the maximum number of items in your cart for this product.', 'jetexir' ), 'error' );

      return false;
    }

    return $passed;
  }

  /**
   * Change the quantity attr for add to cart link.
   *
   * @param array $attributes An associative array containing default HTML attributes of the add to cart button.
   * @param \WC_Product $product The WC_Product instance of the product that will be added to the cart once the button is pressed.
   *
   * @return array Returns an associative array derived from the default array passed as an argument and added the extra HTML attributes.
   */
  public function changeQuantityAddToCart( $attributes, $product ): array {
    $productID = $product->get_id();
    $min       = Sanitizing::int( $this->getSetting( 'quantity_minimum_value', false ) );
    $quantity  = Sanitizing::int( $attributes['data-quantity'] );

    if ( $this->getSetting( 'product_single_quantity_tools_enable', false ) ) {
      $variationMin = Sanitizing::int( PostMeta::get( $productID, JETEXIR_PLUGIN_KEY . '_variation_quantity_min' ) );
      if ( $variationMin ) {
        $min = $variationMin;
      } else {
        $productMin = Sanitizing::int( PostMeta::get( $productID, JETEXIR_PLUGIN_KEY . '_product_quantity_min' ) );
        if ( $productMin ) {
          $min = $productMin;
        }
      }
    }

    $attributes['data-quantity'] = max( $min, $quantity );

    return $attributes;
  }

  /**
   * Change the quantity input for add to cart forms.
   *
   * @param array $args Args for the input.
   * @param \WC_Product|null $product Product.
   *
   * @return array
   */
  public function changeQuantityInputArgs( $args, $product ): array {
    if ( $product instanceof \WC_Product ) {
      $productID   = $product->get_id();
      $maxPurchase = $product->get_max_purchase_quantity();
      $currentVal  = Sanitizing::int( $args['input_value'] );
      $min         = Sanitizing::int( $this->getSetting( 'quantity_minimum_value', false ) );
      $max         = Sanitizing::int( $this->getSetting( 'quantity_maximum_value', false ) );
      $step        = Sanitizing::int( $this->getSetting( 'quantity_step_value', false ) );

      if ( $this->getSetting( 'product_single_quantity_tools_enable', false ) ) {
        $productStep = Sanitizing::int( PostMeta::get( $productID, JETEXIR_PLUGIN_KEY . '_product_quantity_step' ) );
        if ( $productStep ) {
          $step = $productStep;
        }

        $_variationMin = Sanitizing::int( PostMeta::get( $productID, JETEXIR_PLUGIN_KEY . '_variation_quantity_min' ) );
        if ( $_variationMin ) {
          $min = $_variationMin;
        } else {
          $productMin = Sanitizing::int( PostMeta::get( $productID, JETEXIR_PLUGIN_KEY . '_product_quantity_min' ) );
          if ( $productMin ) {
            $min = $productMin;
          }
        }

        $_variationMax = Sanitizing::int( PostMeta::get( $productID, JETEXIR_PLUGIN_KEY . '_variation_quantity_max' ) );
        if ( $_variationMax ) {
          $max = $_variationMax;
        } else {
          $productMax = Sanitizing::int( PostMeta::get( $productID, JETEXIR_PLUGIN_KEY . '_product_quantity_max' ) );
          if ( $productMax ) {
            $max = $productMax;
          }
        }
      }

      if ( $min ) {
        $args['min_value'] = $maxPurchase > 0 ? min( $min, $maxPurchase ) : $min;
      }
      if ( $max ) {
        $args['max_value'] = $maxPurchase > 0 ? min( $max, $maxPurchase ) : $max;
      }
      if ( $step ) {
        $args['step'] = $step;
      }

      if (
        ( WooCommerce::isProduct() && $this->getSetting( 'product_quantity_disabled', false ) ) ||
        ( WooCommerce::isCart() && $this->getSetting( 'product_cart_quantity_disabled', false ) )
      ) {
        $args['readonly'] = 'readonly';
        $args['disabled'] = 'disabled';
      }

      $value               = min( $currentVal, $max );
      $args['input_value'] = $maxPurchase > 0 ? min( $value, $maxPurchase ) : $value;
    }

    return $args;
  }

  public function checkSettingsBeforeSave( $options, $tab ) {
    $min = Sanitizing::int( Param::post( JETEXIR_INPUT_PREFIX . 'quantity_minimum_value', 0 ) );
    if ( $min ) {
      $max = $_max = Sanitizing::int( Param::post( JETEXIR_INPUT_PREFIX . 'quantity_maximum_value', 0 ) );
      //$step = Sanitizing::float( Param::post( JETEXIR_INPUT_PREFIX . 'quantity_step_value', 0 ) );

      if ( $max && $min > $max ) {
        $max = $min + 10;
      }

      /*if ( $remainder = fmod( $max, $step ) ) {
        $max -= $remainder;
      }*/

      if ( $_max != $max ) {
        Notice::add( $tab, esc_html__( 'The maximum value changes based on the minimum and step.', 'jetexir' ), 'warning' );
      }

      $options['quantity_maximum_value'] = $max;
    }

    return $options;
  }

  public function addHeaderImage( $image, $tab, $section, $settings ) {
    if ( $tab === 'product' && $section === 'quantity' ) {
      return AdminAssets::imageUrl( 'header/product-quantity-header.png' );
    }

    return $image;
  }

  public function enqueueStyle(): void {
    if ( ! self::$printed && ! self::$printStyle ) {
      return;
    }

    $enableStyle = Settings::get( 'enable_styles', false );
    $style       = '';
    $buttonStyle = $buttonHoverStyle = $inputStyle = [];

    // Button default style
    if ( $value = $this->getSetting( 'quantity_button_width_height', false ) ) {
      $buttonStyle['display']         = 'inline-flex';
      $buttonStyle['align-items']     = 'center';
      $buttonStyle['justify-content'] = 'center';
      $buttonStyle['width']           = $value;
      $buttonStyle['height']          = $value;
    }

    if ( $enableStyle ) {
      $buttonStyle['border']           = 'var(--jetexir-button-border-width, 0) solid transparent';
      $buttonStyle['border-radius']    = 'var(--jetexir-button-border-radius, 0)';
      $buttonStyle['color']            = 'var(--jetexir-button-color, initial)';
      $buttonStyle['background-color'] = 'var(--jetexir-button-bg-color, initial)';
      $buttonStyle['border-color']     = 'var(--jetexir-button-border-color, initial)';

      // Button hover style
      $buttonHoverStyle['color']            = 'var(--jetexir-button-hover-color, initial)';
      $buttonHoverStyle['background-color'] = 'var(--jetexir-button-hover-bg-color, initial)';
      $buttonHoverStyle['border-color']     = 'var(--jetexir-button-hover-border-color, initial)';
    }

    // Input style
    if ( $this->getSetting( 'quantity_input_style', false ) ) {
      if ( $enableStyle ) {
        $inputStyle['border']           = 'var(--jetexir-input-border-width, 0) solid transparent';
        $inputStyle['border-radius']    = 'var(--jetexir-input-border-radius, 0)';
        $inputStyle['color']            = 'var(--jetexir-input-color, initial)';
        $inputStyle['background-color'] = 'var(--jetexir-input-bg-color, initial)';
        $inputStyle['border-color']     = 'var(--jetexir-input-border-color, initial)';
      }

      if ( $value = $this->getSetting( 'quantity_input_width', false ) ) {
        $inputStyle['width'] = $value;
      }
      if ( $value = $this->getSetting( 'quantity_input_height', false ) ) {
        $inputStyle['height'] = $value;
      }
    }

    $buttonStyle = Helper::combineStyles( $buttonStyle );
    if ( ! empty( $buttonStyle ) ) {
      $style .= "\n" . '.jetexir-quantity-input-plus-minus .jetexir-button-change-quantity{' . $buttonStyle . "\n}\n";
    }

    $buttonHoverStyle = Helper::combineStyles( $buttonHoverStyle );
    if ( ! empty( $buttonHoverStyle ) ) {
      $style .= "\n" . '.jetexir-quantity-input-plus-minus .jetexir-button-change-quantity:hover{' . $buttonHoverStyle . "\n}\n";
    }

    $inputStyle = Helper::combineStyles( $inputStyle );
    if ( ! empty( $inputStyle ) ) {
      $style .= "\n" . '.jetexir-quantity-input-plus-minus input[name="quantity"]{' . $inputStyle . "\n}\n";
    }
    if ( ! empty( $style ) ) {
      wp_register_style( JETEXIR_PLUGIN_SLUG . '-product-quantity-inline-style', false, [], Assets::getVersion() );
      wp_enqueue_style( JETEXIR_PLUGIN_SLUG . '-product-quantity-inline-style' );
      wp_add_inline_style( JETEXIR_PLUGIN_SLUG . '-product-quantity-inline-style', esc_html( $style ) );
    }
  }

  public function printStyleFooter(): void {
    wp_print_styles( JETEXIR_PLUGIN_SLUG . '-product-quantity-inline-style' );
  }

  public function beforeQuantityInputField(): void {
    if ( ! WooCommerce::isProduct() || ! $this->getSetting( 'quantity_input_plus_minus_button', false ) ) {
      return;
    }

    $productID = WooCommerce::getCurrentProductId();
    $product   = WooCommerce::getProduct( $productID );
    if ( is_bool( $product ) || is_null( $product ) || ( $product->is_sold_individually() || ( $product->managing_stock() && ! is_null( $product->get_stock_quantity() ) && $product->get_stock_quantity() <= 1 ) ) ) {
      return;
    }

    self::$printStyle = true;

    if (
      ( WooCommerce::isProduct() && $this->getSetting( 'product_quantity_disabled', false ) ) ||
      ( WooCommerce::isCart() && $this->getSetting( 'product_cart_quantity_disabled', false ) )
    ) {
      return;
    }

    /**
     * Filters whether to display the minus quantity button.
     *
     * @param bool $display Whether to display the button.
     * @param int $productID Current product ID.
     *
     * @return bool Whether to display the button.
     *
     * @since 1.0
     *
     */
    $displayButton = (bool) apply_filters( 'jetexir_quantity_input_display_plus_minus', true, $productID );

    if ( $displayButton ) {
      self::$printed = true;
      echo '<button type="button" class="jetexir-button jetexir-button-change-quantity" data-action="minus" aria-label="' . esc_html__( 'Reduce quantity', 'jetexir' ) . '">-</button>';
    }
  }

  public function afterQuantityInputField(): void {
    if ( ! self::$printed || ! WooCommerce::isProduct() || ! $this->getSetting( 'quantity_input_plus_minus_button', false ) ) {
      return;
    }

    $productID = WooCommerce::getCurrentProductId();
    /**
     * Filters whether to display the plus quantity button.
     *
     * @param bool $display Whether to display the button.
     * @param int $productID Current product ID.
     *
     * @return bool Whether to display the button.
     *
     * @since 1.0
     *
     */
    $displayButton = (bool) apply_filters( 'jetexir_quantity_input_display_plus_minus', true, $productID );

    if ( $displayButton ) {
      echo '<button type="button" class="jetexir-button jetexir-button-change-quantity" data-action="plus" aria-label="' . esc_html__( 'Increase quantity', 'jetexir' ) . '">+</button>';
    }
  }

  public function enqueueScripts(): void {
    $productQuantityDisabled = ( WooCommerce::isProduct() && $this->getSetting( 'product_quantity_disabled', false ) ) || ( WooCommerce::isCart() && $this->getSetting( 'product_cart_quantity_disabled', false ) );
    if ( ! self::$printed && ! $productQuantityDisabled ) {
      return;
    }

    $pluginVersion = Assets::getVersion();
    wp_enqueue_script( JETEXIR_PLUGIN_SLUG . '-product-quantity-script',
      Assets::url( 'js/product-quantity.min.js' ),
      [ JETEXIR_PLUGIN_SLUG . '-global' ], $pluginVersion, [ 'in_footer' => true ] );

    wp_localize_script( JETEXIR_PLUGIN_SLUG . '-product-quantity-script', JETEXIR_PLUGIN_KEYCAP . 'ProductQuantity', array(
      'plusMinusButtons' => Sanitizing::int( self::$printed && $this->getSetting( 'quantity_input_plus_minus_button', false ) ),
      'quantityDisabled' => Sanitizing::int( $productQuantityDisabled )
    ) );
  }

  public function addSectionSettings( $sections ): array {
    $sections[ $this->currentSection ] = array(
      'title'        => esc_html__( 'Quantity', 'jetexir' ),
      'desc'         => esc_html__( 'Quantity Customization', 'jetexir' ),
      'settings_key' => $this->addonID,
      'settings'     => array(
        'start_grid_quantity_control'          => array(
          'title' => esc_html__( 'Quantity Control', 'jetexir' ),
          'type'  => 'startGrid',
        ),
        'product_quantity_disabled'            => array(
          'id'       => 'product_quantity_disabled',
          'title'    => esc_html__( 'Disable on Single Product', 'jetexir' ),
          'type'     => 'toggle',
          'value'    => 1,
          'default'  => false,
          'desc'     => esc_html__( 'Disable Quantity Field for All Products', 'jetexir' ),
          'sanitize' => 'bool'
        ),
        'product_cart_quantity_disabled'       => array(
          'id'       => 'product_cart_quantity_disabled',
          'title'    => esc_html__( 'Disable on Cart Page', 'jetexir' ),
          'type'     => 'toggle',
          'value'    => 1,
          'default'  => false,
          'sanitize' => 'bool'
        ),
        'products_sold_individually'           => array(
          'id'       => 'products_sold_individually',
          'title'    => esc_html__( 'Set "Sold individually" for All Products', 'jetexir' ),
          'type'     => 'toggle',
          'value'    => 1,
          'default'  => false,
          'sanitize' => 'bool'
        ),
        'end_grid_quantity_control'            => array(
          'type' => 'endGrid',
        ),
        'start_grid_quantity_min_max'          => array(
          'title' => esc_html__( 'Min/Max/Step', 'jetexir' ),
          'type'  => 'startGrid',
        ),
        'product_quantity_tools_enable'        => array(
          'id'       => 'product_quantity_tools_enable',
          'title'    => esc_html__( 'Enable quantity manager', 'jetexir' ),
          'type'     => 'toggle',
          'value'    => 1,
          'default'  => false,
          'desc'     => esc_html__( 'Enable Minimum/Maximum/Step Quantity for all Products', 'jetexir' ),
          'sanitize' => 'bool'
        ),
        'quantity_minimum_value'               => array(
          'id'         => 'quantity_minimum_value',
          'title'      => esc_html__( 'Minimum', 'jetexir' ),
          'type'       => 'number',
          'default'    => 1,
          'attributes' => array(
            'placeholder' => 'eg: 2',
            'step'        => 1,
            'min'         => 1,
          ),
          'sanitize'   => 'int'
        ),
        'quantity_maximum_value'               => array(
          'id'         => 'quantity_maximum_value',
          'title'      => esc_html__( 'Maximum', 'jetexir' ),
          'type'       => 'number',
          'default'    => 1000,
          'attributes' => array(
            'placeholder' => 'eg: 10',
            'step'        => 1,
            'min'         => 1,
          ),
          'sanitize'   => 'int'
        ),
        'quantity_step_value'                  => array(
          'id'         => 'quantity_step_value',
          'title'      => esc_html__( 'Step', 'jetexir' ),
          'type'       => 'number',
          'default'    => 1,
          'attributes' => array(
            'placeholder' => 'eg: 1',
            'step'        => 1,
            'min'         => 1,
          ),
          'sanitize'   => 'int'
        ),
        'product_single_quantity_tools_enable' => array(
          'id'       => 'product_single_quantity_tools_enable',
          'title'    => esc_html__( 'Enable quantity manager per Product', 'jetexir' ),
          'type'     => 'toggle',
          'value'    => 1,
          'default'  => false,
          'desc'     => esc_html__( 'Manage Minimum/Maximum/Step per Product', 'jetexir' ),
          'sanitize' => 'bool'
        ),
        'end_grid_quantity_min_max'            => array(
          'type' => 'endGrid',
        ),
        'quantity_min_max_sep'                 => array(
          'type' => 'hr',
        ),
        'start_grid_quantity_input1'           => array(
          'title' => esc_html__( 'Plus/Minus button', 'jetexir' ),
          'type'  => 'startGrid',
        ),
        'quantity_input_plus_minus_button'     => array(
          'id'       => 'quantity_input_plus_minus_button',
          'title'    => esc_html__( 'Enable Plus/Minus', 'jetexir' ),
          'type'     => 'toggle',
          'value'    => 1,
          'default'  => false,
          'desc'     => esc_html__( 'Add Plus/Minus buttons to Quantity input', 'jetexir' ),
          'sanitize' => 'bool'
        ),
        'quantity_button_width_height'         => array(
          'id'          => 'quantity_button_width_height',
          'title'       => esc_html__( 'Button width/height', 'jetexir' ),
          'type'        => 'text',
          'default'     => '40px',
          'placeholder' => '40px'
        ),

        'end_grid_quantity_input1' => array(
          'type' => 'endGrid',
        ),

        'start_grid_quantity_input5' => array(
          'title' => esc_html__( 'Input Box', 'jetexir' ),
          'type'  => 'startGrid',
        ),
        'quantity_input_style'       => array(
          'id'       => 'quantity_input_style',
          'title'    => esc_html__( 'Enable quantity input style', 'jetexir' ),
          'type'     => 'toggle',
          'value'    => 1,
          'default'  => false,
          'sanitize' => 'bool'
        ),
        'quantity_input_width'       => array(
          'id'          => 'quantity_input_width',
          'title'       => esc_html__( 'Width', 'jetexir' ),
          'type'        => 'text',
          'default'     => '40px',
          'placeholder' => '40px'
        ),
        'quantity_input_height'      => array(
          'id'          => 'quantity_input_height',
          'title'       => esc_html__( 'Height', 'jetexir' ),
          'type'        => 'text',
          'default'     => '40px',
          'placeholder' => '40px'
        ),
        'end_grid_quantity_input5'   => array(
          'type' => 'endGrid',
        )
      )
    );

    return $sections;
  }

  public function info(): array {
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="#873eff" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 4 4 20M4 7h3m3 0H7m0 0V4m0 3v3m7 7h6"/></svg>';

    return array(
      'id'             => $this->addonID,
      'title'          => esc_html__( 'Product Quantity', 'jetexir' ),
      'desc'           => esc_html__( 'Add plus and minus buttons to the quantity field. Control the minimum, maximum, and step values of the quantity field.', 'jetexir' ),
      'tags'           => [ esc_html__( 'Product', 'jetexir' ) ],
      'cat'            => 'product',
      'icon'           => $icon,
      'more_info_link' => '{jetexir_website}/addons/quantity-fields',
      'settings_key'   => $this->addonID
    );
  }
}
