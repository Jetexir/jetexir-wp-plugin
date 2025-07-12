<?php

namespace WooAssistant\App\Product;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Addons\Addon;
use WooAssistant\Admin\AdminAssets;
use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Helper;
use WooAssistant\Helper\Notice;
use WooAssistant\Helper\Param;
use WooAssistant\Helper\PostMeta;
use WooAssistant\Helper\Sanitizing;
use WooAssistant\Helper\WooCommerce;
use WooAssistant\Interfaces\AddonInterface;
use WooAssistant\Settings\Settings;

class ProductQuantity extends Addon implements AddonInterface {
	public string $addonID = 'product-quantity';
	public string $currentTab = 'product';
	private static bool $printed = false;
	private static bool $printStyle = false;

	public function initAction(): void {
		add_filter( 'woo_assistant_settings_before_save', [ $this, 'checkSettingsBeforeSave' ], 10, 2 );

		if ( Settings::get( 'product_quantity_tools_enable', false ) ) {
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
		if ( Settings::get( 'product_quantity_tools_enable', false ) && Settings::get( 'product_single_quantity_tools_enable', false ) ) {
			add_action( 'woocommerce_process_product_meta', [ $this, 'adminProductSaveMeta' ] );
			add_action( 'woocommerce_product_options_stock_fields', [ $this, 'addInventoryFields' ] );

			add_action( 'woocommerce_save_product_variation', [ $this, 'adminVariationSaveMeta' ], 10, 2 );
			add_action( 'woocommerce_variation_options_inventory', [ $this, 'addVariationInventoryFields' ], 10, 3 );
		}
	}

	public function templateRedirectAction(): void {
		if ( Settings::get( 'products_sold_individually', false ) ) {
			add_filter( 'woocommerce_is_sold_individually', '__return_true', 999 );
		}

		// Add plus/minus buttons
		add_action( 'woocommerce_before_quantity_input_field', [ $this, 'beforeQuantityInputField' ] );
		add_action( 'woocommerce_after_quantity_input_field', [ $this, 'afterQuantityInputField' ] );

		if ( WooCommerce::isWoocommerce() || WooCommerce::isCart() ) {
			add_action( 'wp_footer', [ $this, 'enqueueScripts' ] );
			add_action( 'wp_footer', [ $this, 'printStyle' ] );
		}
		//add_filter( 'woo_assistant_settings_header_image', [ $this, 'addHeaderImage' ], 10, 4 );
	}

	public function adminProductSaveMeta( $productID ): void {
		$min  = Sanitizing::int( Param::post( WOOASSISTANT_INPUT_PREFIX . 'product_quantity_min' ) );
		$max  = Sanitizing::int( Param::post( WOOASSISTANT_INPUT_PREFIX . 'product_quantity_max' ) );
		$step = Sanitizing::int( Param::post( WOOASSISTANT_INPUT_PREFIX . 'product_quantity_step' ) );

		if ( $min && $max && $min > $max ) {
			$max = $min + 10;
		}

		if ( $min ) {
			PostMeta::update( $productID, WOOASSISTANT_PLUGIN_KEY . '_product_quantity_min', $min );
		} else {
			PostMeta::delete( $productID, WOOASSISTANT_PLUGIN_KEY . '_product_quantity_min' );
		}
		if ( $max ) {
			PostMeta::update( $productID, WOOASSISTANT_PLUGIN_KEY . '_product_quantity_max', $max );
		} else {
			PostMeta::delete( $productID, WOOASSISTANT_PLUGIN_KEY . '_product_quantity_max' );
		}
		if ( $step ) {
			PostMeta::update( $productID, WOOASSISTANT_PLUGIN_KEY . '_product_quantity_step', $step );
		} else {
			PostMeta::delete( $productID, WOOASSISTANT_PLUGIN_KEY . '_product_quantity_step' );
		}
	}

	public function addInventoryFields(): void {
		$inputs = array(
			array(
				'id'                => WOOASSISTANT_INPUT_PREFIX . 'product_quantity_min',
				'name'              => WOOASSISTANT_INPUT_PREFIX . 'product_quantity_min',
				'label'             => __( 'Minimum Quantity', 'woo-assistant' ),
				'type'              => 'number',
				'desc_tip'          => true,
				'description'       => __( 'Enter minimum quantity for this product', 'woo-assistant' ),
				'data_type'         => 'decimal',
				'placeholder'       => 'eg: 1',
				'custom_attributes' => array(
					'step' => 1,
					'min'  => 1
				)
			),
			array(
				'id'                => WOOASSISTANT_INPUT_PREFIX . 'product_quantity_max',
				'name'              => WOOASSISTANT_INPUT_PREFIX . 'product_quantity_max',
				'label'             => __( 'Maximum Quantity', 'woo-assistant' ),
				'type'              => 'number',
				'desc_tip'          => true,
				'description'       => __( 'Enter maximum quantity for this product', 'woo-assistant' ),
				'data_type'         => 'decimal',
				'placeholder'       => 'eg: 10',
				'custom_attributes' => array(
					'step' => 1,
					'min'  => 1
				)
			),
			array(
				'id'                => WOOASSISTANT_INPUT_PREFIX . 'product_quantity_step',
				'name'              => WOOASSISTANT_INPUT_PREFIX . 'product_quantity_step',
				'label'             => __( 'Quantity Step', 'woo-assistant' ),
				'type'              => 'number',
				'desc_tip'          => true,
				'description'       => __( 'Enter quantity step for this product', 'woo-assistant' ),
				'data_type'         => 'decimal',
				'placeholder'       => 'eg: 1',
				'custom_attributes' => array(
					'step' => 1,
					'min'  => 1
				)
			)
		);

		$inputs = apply_filters( 'woo_assistant_product_quantity_settings', $inputs );
		if ( ! empty( $inputs ) ) {
			foreach ( $inputs as $input ) {
				woocommerce_wp_text_input( $input );
			}
		}
	}

	public function adminVariationSaveMeta( $variationID, $i ): void {
		if ( ! isset( $_POST[ WOOASSISTANT_INPUT_PREFIX . 'variation_quantity_min' ][ $i ] ) ) {
			return;
		}

		$min  = Sanitizing::int( Param::post( WOOASSISTANT_INPUT_PREFIX . 'variation_quantity_min' )[ $i ] );
		$max  = Sanitizing::int( Param::post( WOOASSISTANT_INPUT_PREFIX . 'variation_quantity_max' )[ $i ] );
		$step = Sanitizing::int( Param::post( WOOASSISTANT_INPUT_PREFIX . 'variation_quantity_step' )[ $i ] );

		if ( $min && $max && $min > $max ) {
			$max = $min + 10;
		}

		if ( $min ) {
			PostMeta::update( $variationID, WOOASSISTANT_PLUGIN_KEY . '_variation_quantity_min', $min );
		} else {
			PostMeta::delete( $variationID, WOOASSISTANT_PLUGIN_KEY . '_variation_quantity_min' );
		}
		if ( $max ) {
			PostMeta::update( $variationID, WOOASSISTANT_PLUGIN_KEY . '_variation_quantity_max', $max );
		} else {
			PostMeta::delete( $variationID, WOOASSISTANT_PLUGIN_KEY . '_variation_quantity_max' );
		}
		if ( $step ) {
			PostMeta::update( $variationID, WOOASSISTANT_PLUGIN_KEY . '_variation_quantity_step', $step );
		} else {
			PostMeta::delete( $variationID, WOOASSISTANT_PLUGIN_KEY . '_variation_quantity_step' );
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
				'id'                => WOOASSISTANT_INPUT_PREFIX . 'variation_quantity_min[' . $loop . ']',
				'name'              => WOOASSISTANT_INPUT_PREFIX . 'variation_quantity_min[' . $loop . ']',
				'label'             => __( 'Minimum Quantity', 'woo-assistant' ),
				'type'              => 'number',
				'desc_tip'          => true,
				'description'       => __( 'Enter minimum quantity for this product variation', 'woo-assistant' ),
				'data_type'         => 'decimal',
				'placeholder'       => 'eg: 1',
				'custom_attributes' => array(
					'step' => 1,
					'min'  => 1
				),
				'wrapper_class'     => 'form-row form-row-first',
				'value'             => get_post_meta( $variation->ID, WOOASSISTANT_PLUGIN_KEY . '_variation_quantity_min', true )
			),
			array(
				'id'                => WOOASSISTANT_INPUT_PREFIX . 'variation_quantity_max[' . $loop . ']',
				'name'              => WOOASSISTANT_INPUT_PREFIX . 'variation_quantity_max[' . $loop . ']',
				'label'             => __( 'Maximum Quantity', 'woo-assistant' ),
				'type'              => 'number',
				'desc_tip'          => true,
				'description'       => __( 'Enter maximum quantity for this product variation', 'woo-assistant' ),
				'data_type'         => 'decimal',
				'placeholder'       => 'eg: 10',
				'custom_attributes' => array(
					'step' => 1,
					'min'  => 1
				),
				'wrapper_class'     => 'form-row form-row-last',
				'value'             => get_post_meta( $variation->ID, WOOASSISTANT_PLUGIN_KEY . '_variation_quantity_max', true )
			),
			array(
				'id'                => WOOASSISTANT_INPUT_PREFIX . 'variation_quantity_step[' . $loop . ']',
				'name'              => WOOASSISTANT_INPUT_PREFIX . 'variation_quantity_step[' . $loop . ']',
				'label'             => __( 'Quantity Step', 'woo-assistant' ),
				'type'              => 'number',
				'desc_tip'          => true,
				'description'       => __( 'Enter quantity step for this product variation', 'woo-assistant' ),
				'data_type'         => 'decimal',
				'placeholder'       => 'eg: 1',
				'custom_attributes' => array(
					'step' => 1,
					'min'  => 1
				),
				'wrapper_class'     => 'form-row',
				'value'             => get_post_meta( $variation->ID, WOOASSISTANT_PLUGIN_KEY . '_variation_quantity_step', true )
			)
		);

		$inputs = apply_filters( 'woo_assistant_product_variation_quantity_settings', $inputs );
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
		$min         = Sanitizing::int( Settings::get( 'quantity_minimum_value', false ) );
		$max         = Sanitizing::int( Settings::get( 'quantity_maximum_value', false ) );

		if ( ! $variation->is_sold_individually() && Settings::get( 'product_single_quantity_tools_enable', false ) ) {
			$_productMin = Sanitizing::int( PostMeta::get( $productID, WOOASSISTANT_PLUGIN_KEY . '_product_quantity_min' ) );
			if ( $_productMin ) {
				$productMin = $_productMin;
			}
			$_productMax = Sanitizing::int( PostMeta::get( $productID, WOOASSISTANT_PLUGIN_KEY . '_product_quantity_max' ) );
			if ( $_productMax ) {
				$variationMax = $_productMax;
			}
			$_variationMin = Sanitizing::int( PostMeta::get( $variationID, WOOASSISTANT_PLUGIN_KEY . '_variation_quantity_min' ) );
			if ( $_variationMin ) {
				$variationMin = $_variationMin;
			}
			$_variationMax = Sanitizing::int( PostMeta::get( $variationID, WOOASSISTANT_PLUGIN_KEY . '_variation_quantity_max' ) );
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
		$globalStep = $productStep = Sanitizing::int( Settings::get( 'quantity_step_value', false ) );

		if ( Settings::get( 'product_single_quantity_tools_enable', false ) ) {
			$_productStep = Sanitizing::int( PostMeta::get( $product->get_id(), WOOASSISTANT_PLUGIN_KEY . '_product_quantity_step' ) );
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
		$globalMax = $productMax = Sanitizing::int( Settings::get( 'quantity_maximum_value', false ) );

		if ( Settings::get( 'product_single_quantity_tools_enable', false ) ) {
			$_variationMax = Sanitizing::int( PostMeta::get( $product->get_id(), WOOASSISTANT_PLUGIN_KEY . '_variation_quantity_max' ) );
			if ( $_variationMax ) {
				$productMax = $_variationMax;
			} else {
				$_productMax = Sanitizing::int( PostMeta::get( $product->get_id(), WOOASSISTANT_PLUGIN_KEY . '_product_quantity_max' ) );
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
		$globalMin = $productMin = Sanitizing::int( Settings::get( 'quantity_minimum_value', false ) );

		if ( Settings::get( 'product_single_quantity_tools_enable', false ) ) {
			$variationMin = Sanitizing::int( PostMeta::get( $product->get_id(), WOOASSISTANT_PLUGIN_KEY . '_variation_quantity_min' ) );
			if ( $variationMin ) {
				$productMin = $variationMin;
			} else {
				$_productMin = Sanitizing::int( PostMeta::get( $product->get_id(), WOOASSISTANT_PLUGIN_KEY . '_product_quantity_min' ) );
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
		$min       = Sanitizing::int( Settings::get( 'quantity_minimum_value', false ) );
		$quantity  = Sanitizing::int( $args['quantity'] );

		if ( Settings::get( 'product_single_quantity_tools_enable', false ) ) {
			$variationMin = Sanitizing::int( PostMeta::get( $productID, WOOASSISTANT_PLUGIN_KEY . '_variation_quantity_min' ) );
			if ( $variationMin ) {
				$min = $variationMin;
			} else {
				$productMin = Sanitizing::int( PostMeta::get( $productID, WOOASSISTANT_PLUGIN_KEY . '_product_quantity_min' ) );
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
		$stockQuantity = $globalMax = $productMax = Sanitizing::int( Settings::get( 'quantity_maximum_value', false ) );

		$cartProduct = WooCommerce::getCartProduct( $productId, $variationID );
		if ( $cartProduct && $cartProduct['manage_stock'] && $cartProduct['stock_quantity'] > 0 ) {
			$stockQuantity = $cartProduct['stock_quantity'];
		}

		if ( Settings::get( 'product_single_quantity_tools_enable', false ) ) {
			$_productMax = Sanitizing::int( PostMeta::get( $productId, WOOASSISTANT_PLUGIN_KEY . '_product_quantity_max' ) );
			if ( $_productMax ) {
				$productMax = $_productMax;
			}

			if ( $variationID ) {
				$_variationMax = Sanitizing::int( PostMeta::get( $variationID, WOOASSISTANT_PLUGIN_KEY . '_variation_quantity_max' ) );
				if ( $_variationMax ) {
					$productMax = $_variationMax;
				}
			}
		}

		$quantities[ $cartProductId ] = isset( $quantities[ $cartProductId ] ) ? $quantities[ $cartProductId ] + $quantity : $quantity;
		$max                          = min( $stockQuantity, $globalMax, $productMax );
		if ( $quantities[ $cartProductId ] > $max ) {
			wc_add_notice( __( 'You have reached the maximum number of items in your cart for this product.', 'woocommerce' ), 'error' );

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
		$min       = Sanitizing::int( Settings::get( 'quantity_minimum_value', false ) );
		$quantity  = Sanitizing::int( $attributes['data-quantity'] );

		if ( Settings::get( 'product_single_quantity_tools_enable', false ) ) {
			$variationMin = Sanitizing::int( PostMeta::get( $productID, WOOASSISTANT_PLUGIN_KEY . '_variation_quantity_min' ) );
			if ( $variationMin ) {
				$min = $variationMin;
			} else {
				$productMin = Sanitizing::int( PostMeta::get( $productID, WOOASSISTANT_PLUGIN_KEY . '_product_quantity_min' ) );
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
			$min         = Sanitizing::int( Settings::get( 'quantity_minimum_value', false ) );
			$max         = Sanitizing::int( Settings::get( 'quantity_maximum_value', false ) );
			$step        = Sanitizing::int( Settings::get( 'quantity_step_value', false ) );

			if ( Settings::get( 'product_single_quantity_tools_enable', false ) ) {
				$productStep = Sanitizing::int( PostMeta::get( $productID, WOOASSISTANT_PLUGIN_KEY . '_product_quantity_step' ) );
				if ( $productStep ) {
					$step = $productStep;
				}

				$_variationMin = Sanitizing::int( PostMeta::get( $productID, WOOASSISTANT_PLUGIN_KEY . '_variation_quantity_min' ) );
				if ( $_variationMin ) {
					$min = $_variationMin;
				} else {
					$productMin = Sanitizing::int( PostMeta::get( $productID, WOOASSISTANT_PLUGIN_KEY . '_product_quantity_min' ) );
					if ( $productMin ) {
						$min = $productMin;
					}
				}

				$_variationMax = Sanitizing::int( PostMeta::get( $productID, WOOASSISTANT_PLUGIN_KEY . '_variation_quantity_max' ) );
				if ( $_variationMax ) {
					$max = $_variationMax;
				} else {
					$productMax = Sanitizing::int( PostMeta::get( $productID, WOOASSISTANT_PLUGIN_KEY . '_product_quantity_max' ) );
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
				( WooCommerce::isProduct() && Settings::get( 'product_quantity_disabled', false ) ) ||
				( WooCommerce::isCart() && Settings::get( 'product_cart_quantity_disabled', false ) )
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
		$min = Sanitizing::int( Param::post( WOOASSISTANT_INPUT_PREFIX . 'quantity_minimum_value', 0 ) );
		if ( $min ) {
			$max = $_max = Sanitizing::int( Param::post( WOOASSISTANT_INPUT_PREFIX . 'quantity_maximum_value', 0 ) );
			//$step = Sanitizing::float( Param::post( WOOASSISTANT_INPUT_PREFIX . 'quantity_step_value', 0 ) );

			if ( $max && $min > $max ) {
				$max = $min + 10;
			}

			/*if ( $remainder = fmod( $max, $step ) ) {
				$max -= $remainder;
			}*/

			if ( $_max != $max ) {
				Notice::add( $tab, __( 'The maximum value changed based on the minimum and step.', 'woo-assistant' ), 'warning' );
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

	public function printStyle(): void {
		if ( ! self::$printed && ! self::$printStyle ) {
			return;
		}

		$enableStyle = Settings::get( 'enable_styles', false );
		$style       = '';
		$buttonStyle = $buttonHoverStyle = $inputStyle = [];

		// Button default style
		if ( $value = Settings::get( 'quantity_button_width_height', false ) ) {
			$buttonStyle['display']         = 'inline-flex';
			$buttonStyle['align-items']     = 'center';
			$buttonStyle['justify-content'] = 'center';
			$buttonStyle['width']           = $value;
			$buttonStyle['height']          = $value;
		}

		if ( $enableStyle ) {
			$buttonStyle['border']           = 'var(--wa-button-border-width, 0) solid transparent';
			$buttonStyle['border-radius']    = 'var(--wa-button-border-radius, 0)';
			$buttonStyle['color']            = 'var(--wa-button-color, initial)';
			$buttonStyle['background-color'] = 'var(--wa-button-bg-color, initial)';
			$buttonStyle['border-color']     = 'var(--wa-button-border-color, initial)';

			// Button hover style
			$buttonHoverStyle['color']            = 'var(--wa-button-hover-color, initial)';
			$buttonHoverStyle['background-color'] = 'var(--wa-button-hover-bg-color, initial)';
			$buttonHoverStyle['border-color']     = 'var(--wa-button-hover-border-color, initial)';
		}

		// Input style
		if ( Settings::get( 'quantity_input_style', false ) ) {
			if ( $enableStyle ) {
				$inputStyle['border']           = 'var(--wa-input-border-width, 0) solid transparent';
				$inputStyle['border-radius']    = 'var(--wa-input-border-radius, 0)';
				$inputStyle['color']            = 'var(--wa-input-color, initial)';
				$inputStyle['background-color'] = 'var(--wa-input-bg-color, initial)';
				$inputStyle['border-color']     = 'var(--wa-input-border-color, initial)';
			}

			if ( $value = Settings::get( 'quantity_input_width', false ) ) {
				$inputStyle['width'] = $value;
			}
			if ( $value = Settings::get( 'quantity_input_height', false ) ) {
				$inputStyle['height'] = $value;
			}
		}

		$buttonStyle = Helper::combineStyles( $buttonStyle );
		if ( ! empty( $buttonStyle ) ) {
			$style .= "\n" . '.wa-quantity-input-plus-minus .wa-button-change-quantity{' . $buttonStyle . "\n}\n";
		}

		$buttonHoverStyle = Helper::combineStyles( $buttonHoverStyle );
		if ( ! empty( $buttonHoverStyle ) ) {
			$style .= "\n" . '.wa-quantity-input-plus-minus .wa-button-change-quantity:hover{' . $buttonHoverStyle . "\n}\n";
		}

		$inputStyle = Helper::combineStyles( $inputStyle );
		if ( ! empty( $inputStyle ) ) {
			$style .= "\n" . '.wa-quantity-input-plus-minus input[name="quantity"]{' . $inputStyle . "\n}\n";
		}

		if ( ! empty( $style ) ) {
			echo '<style>' . $style . '</style>' . "\n";
		}
	}

	public function beforeQuantityInputField(): void {
		if ( ! WooCommerce::isProduct() || ! Settings::get( 'quantity_input_plus_minus_button', false ) ) {
			return;
		}

		$productID = WooCommerce::getCurrentProductId();
		$product   = WooCommerce::getProduct( $productID );
		if ( is_bool( $product ) || is_null( $product ) || ( $product->is_sold_individually() || ( $product->managing_stock() && ! is_null( $product->get_stock_quantity() ) && $product->get_stock_quantity() <= 1 ) ) ) {
			return;
		}

		self::$printStyle = true;

		if (
			( WooCommerce::isProduct() && Settings::get( 'product_quantity_disabled', false ) ) ||
			( WooCommerce::isCart() && Settings::get( 'product_cart_quantity_disabled', false ) )
		) {
			return;
		}

		$displayButton = apply_filters( 'woo_assistant_quantity_input_display_plus_minus', true, $productID );

		if ( $displayButton ) {
			self::$printed = true;
			echo '<button type="button" class="wa-button wa-button-change-quantity" data-action="minus" aria-label="' . __( 'Reduce quantity', 'woo-assistant' ) . '">-</button>';
		}
	}

	public function afterQuantityInputField(): void {
		if ( ! self::$printed || ! WooCommerce::isProduct() || ! Settings::get( 'quantity_input_plus_minus_button', false ) ) {
			return;
		}

		$productID     = WooCommerce::getCurrentProductId();
		$displayButton = apply_filters( 'woo_assistant_quantity_input_display_plus_minus', true, $productID );

		if ( $displayButton ) {
			echo '<button type="button" class="wa-button wa-button-change-quantity" data-action="plus" aria-label="' . __( 'Increase quantity', 'woo-assistant' ) . '">+</button>';
		}
	}

	public function enqueueScripts(): void {
		$productQuantityDisabled = ( WooCommerce::isProduct() && Settings::get( 'product_quantity_disabled', false ) ) || ( WooCommerce::isCart() && Settings::get( 'product_cart_quantity_disabled', false ) );
		if ( ! self::$printed && ! $productQuantityDisabled ) {
			return;
		}

		$pluginVersion = Assets::getVersion();
		wp_enqueue_script( WOOASSISTANT_PLUGIN_SLUG . '-product-quantity-script',
			Assets::url( 'js/product-quantity.min.js' ),
			[ WOOASSISTANT_PLUGIN_SLUG . '-global' ], $pluginVersion, [ 'in_footer' => true ] );

		wp_localize_script( WOOASSISTANT_PLUGIN_SLUG . '-product-quantity-script', WOOASSISTANT_PLUGIN_KEYCAP . 'ProductQuantity', array(
			'plusMinusButtons' => Sanitizing::int( self::$printed && Settings::get( 'quantity_input_plus_minus_button', false ) ),
			'quantityDisabled' => Sanitizing::int( $productQuantityDisabled )
		) );
	}

	public function addSectionSettings( $sections ): array {
		$sections['quantity'] = array(
			'title'    => __( 'Quantity', 'woo-assistant' ),
			'desc'     => __( 'Quantity Customization', 'woo-assistant' ),
			'settings' => array(
				'start_grid_quantity_control'          => array(
					'title' => __( 'Quantity Control', 'woo-assistant' ),
					'type'  => 'startGrid',
				),
				'product_quantity_disabled'            => array(
					'id'       => 'product_quantity_disabled',
					'title'    => __( 'Disable on Single Product', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => false,
					'desc'     => __( 'Disable Quantity Field for All Products', 'woo-assistant' ),
					'sanitize' => 'bool'
				),
				'product_cart_quantity_disabled'       => array(
					'id'       => 'product_cart_quantity_disabled',
					'title'    => __( 'Disable on Cart Page', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => false,
					'sanitize' => 'bool'
				),
				'products_sold_individually'           => array(
					'id'       => 'products_sold_individually',
					'title'    => __( 'Set "Sold individually" for All Products', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => false,
					'sanitize' => 'bool'
				),
				'end_grid_quantity_control'            => array(
					'type' => 'endGrid',
				),
				'start_grid_quantity_min_max'          => array(
					'title' => __( 'Min/Max/Step', 'woo-assistant' ),
					'type'  => 'startGrid',
				),
				'product_quantity_tools_enable'        => array(
					'id'       => 'product_quantity_tools_enable',
					'title'    => __( 'Enable quantity manager', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => false,
					'desc'     => __( 'Enable Minimum/Maximum/Step Quantity for all Products', 'woo-assistant' ),
					'sanitize' => 'bool'
				),
				'quantity_minimum_value'               => array(
					'id'         => 'quantity_minimum_value',
					'title'      => __( 'Minimum', 'woo-assistant' ),
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
					'title'      => __( 'Maximum', 'woo-assistant' ),
					'type'       => 'number',
					'attributes' => array(
						'placeholder' => 'eg: 10',
						'step'        => 1,
						'min'         => 1,
					),
					'sanitize'   => 'int'
				),
				'quantity_step_value'                  => array(
					'id'         => 'quantity_step_value',
					'title'      => __( 'Step', 'woo-assistant' ),
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
					'title'    => __( 'Enable quantity manager per Product', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => false,
					'desc'     => __( 'Manage Minimum/Maximum/Step Quantity per Product', 'woo-assistant' ),
					'sanitize' => 'bool'
				),
				'end_grid_quantity_min_max'            => array(
					'type' => 'endGrid',
				),
				'quantity_min_max_sep'                 => array(
					'type' => 'hr',
				),
				'start_grid_quantity_input1'           => array(
					'title' => __( 'Plus/Minus button', 'woo-assistant' ),
					'type'  => 'startGrid',
				),
				'quantity_input_plus_minus_button'     => array(
					'id'       => 'quantity_input_plus_minus_button',
					'title'    => __( 'Enable Plus/Minus', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => false,
					'desc'     => __( 'Add Plus/Minus buttons to Quantity input', 'woo-assistant' ),
					'sanitize' => 'bool'
				),
				'quantity_button_width_height'         => array(
					'id'         => 'quantity_button_width_height',
					'title'      => __( 'Button width/height', 'woo-assistant' ),
					'type'       => 'text',
					'default'    => '30px',
					'attributes' => array(
						'placeholder' => 'eg: 30px'
					)
				),

				'end_grid_quantity_input1' => array(
					'type' => 'endGrid',
				),

				'start_grid_quantity_input5' => array(
					'title' => __( 'Input Box', 'woo-assistant' ),
					'type'  => 'startGrid',
				),
				'quantity_input_style'       => array(
					'id'       => 'quantity_input_style',
					'title'    => __( 'Enable quantity input style', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => false,
					'sanitize' => 'bool'
				),
				'quantity_input_width'       => array(
					'id'         => 'quantity_input_width',
					'title'      => __( 'Width', 'woo-assistant' ),
					'type'       => 'text',
					'attributes' => array(
						'placeholder' => 'eg: 50px'
					)
				),
				'quantity_input_height'      => array(
					'id'         => 'quantity_input_height',
					'title'      => __( 'Height', 'woo-assistant' ),
					'type'       => 'text',
					'attributes' => array(
						'placeholder' => 'eg: 30px'
					)
				),
				'end_grid_quantity_input5'   => array(
					'type' => 'endGrid',
				)
			)
		);

		return $sections;
	}

	public function info(): array {
		$icon = '<svg viewBox="-2.4 -2.4 28.80 28.80" xmlns="http://www.w3.org/2000/svg" fill="none"><g id="SVGRepo_bgCarrier" stroke-width="0"><path transform="translate(-2.4, -2.4), scale(0.8999999999999999)" d="M16,31.12033211439848C18.530283848748677,31.782735758387147,21.154156914770756,30.142637762271093,23.31114255574842,28.66327036793524C25.381255990223888,27.243484227795925,27.305765294873353,25.33157663310361,27.96986668242001,22.91080575125913C28.598915391158545,20.61780828742065,26.628751001415665,18.37257021379276,26.78511668741703,16C26.958087504373985,13.375476559710885,29.453261516102195,11.117401865184682,28.916820597537473,8.54247015093764C28.36255965227664,5.882001800128064,26.51450405862154,3.0984370486690587,23.92532746866346,2.2729301576536614C21.272101936979354,1.427002549502883,18.73979665523863,3.9254897736000043,16,4.424201642473539C13.8533018682126,4.81495466976993,11.408086628680854,3.6893828242977804,9.582040973628565,4.883768885429381C7.759144818607677,6.076094911026477,7.9802264979325095,8.88786942597975,6.599738249988281,10.572756348177787C4.840835722435482,12.719498177025658,1.0158092210312542,13.278087937777569,0.4741143745680638,15.999999999999996C-0.039928757954186045,18.582967536151354,2.2223065386100322,21.051764690301123,4.159132768187844,22.836327883725357C5.884355043554317,24.425922306848037,8.68657619672315,23.993130931017554,10.60875555935005,25.337909287228957C12.80547451857235,26.874758640873164,13.406452968550797,30.441366785928494,16,31.12033211439848" fill="#fff" strokewidth="0"></path></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path stroke="#873eff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 4 4 20M4 7h3m3 0H7m0 0V4m0 3v3m7 7h6"></path></g></svg>';

		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Product Quantity', 'woo-assistant' ),
			'desc'           => __( 'Add plus minus button to quantity field, Control Min/Max/Step of quantity field.', 'woo-assistant' ),
			'tags'           => [ __( 'Product', 'woo-assistant' ) ],
			'cat'            => 'product',
			'icon'           => $icon,
			'more_info_link' => 'https://parsa.ws'
		);
	}
}