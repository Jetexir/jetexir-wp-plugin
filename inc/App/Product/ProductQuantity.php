<?php

namespace WooAssistant\App\Product;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Addons\Addon;
use WooAssistant\Admin\AdminAssets;
use WooAssistant\Helper\Assets;
use WooAssistant\Helper\DebugTrait;
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
	private static bool $printed = false;
	private static bool $printStyle = false;

	public function initAction(): void {
		add_filter( 'woo_assistant_product_settings_sections', [ $this, 'addSectionSettings' ] );
		add_filter( 'woo_assistant_settings_before_save', [ $this, 'checkSettingsBeforeSave' ], 10, 2 );

		if ( Settings::get( 'product_quantity_tools_enable', false ) ) {
			add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'addToCartValidation' ], 10, 5 );
		}
	}

	public function adminInitAction(): void {
		// Control min/max/step per product
		if ( Settings::get( 'product_quantity_tools_enable', false ) && Settings::get( 'product_single_quantity_tools_enable', false ) ) {
			add_action( 'woocommerce_process_product_meta', [ $this, 'adminProductSaveMeta' ] );
			add_action( 'woocommerce_product_options_stock_fields', [ $this, 'addInventoryFields' ] );
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

		if ( Settings::get( 'product_quantity_tools_enable', false ) ) {
			add_filter( 'woocommerce_quantity_input_args', [ $this, 'changeQuantityInputArgs' ], 10, 2 );
			add_filter( 'woocommerce_blocks_product_grid_add_to_cart_attributes',
				[ $this, 'changeQuantityAddToCart' ], 10, 2 );
			add_filter( 'woocommerce_loop_add_to_cart_link', [ $this, 'changeQuantityAddToCartLink' ], 10, 3 );
			add_filter( 'woocommerce_quantity_input_min', [ $this, 'changeQuantityInputMin' ], 10, 2 );
			add_filter( 'woocommerce_quantity_input_max', [ $this, 'changeQuantityInputMax' ], 10, 2 );
		}
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
				'description'       => __( 'Enter Minimum Quantity for this Product', 'woo-assistant' ),
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
				'description'       => __( 'Enter Maximum Quantity for this Product', 'woo-assistant' ),
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
				'description'       => __( 'Enter quantity Step', 'woo-assistant' ),
				'data_type'         => 'decimal',
				'placeholder'       => 'eg: 1',
				'custom_attributes' => array(
					'step' => 1,
					'min'  => 1
				)
			)
		);

		$inputs = apply_filters( 'woo_assistant_product_quantity_settings', $inputs );

		foreach ( $inputs as $input ) {
			woocommerce_wp_text_input( $input );
		}
	}

	/**
	 * Set maximum quantity value allowed for the product.
	 *
	 * @param int $max Minimum quantity value.
	 * @param \WC_Product $product Product object.
	 */
	public function changeQuantityInputMax( $max, $product ) {
		$globalMax = $productMax = Sanitizing::int( Settings::get( 'quantity_maximum_value', false ) );

		if ( Settings::get( 'product_single_quantity_tools_enable', false ) ) {
			$_productMax = Sanitizing::int( PostMeta::get( $product->get_id(), WOOASSISTANT_PLUGIN_KEY . '_product_quantity_max' ) );
			if ( $_productMax ) {
				$productMax = $_productMax;
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
		$globalMin = $productMin = Sanitizing::int( Settings::get( 'quantity_minimum_value', false ) );

		if ( Settings::get( 'product_single_quantity_tools_enable', false ) ) {
			$_productMin = Sanitizing::int( PostMeta::get( $product->get_id(), WOOASSISTANT_PLUGIN_KEY . '_product_quantity_min' ) );
			if ( $_productMin ) {
				$productMin = $_productMin;
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
			$productMin = Sanitizing::int( PostMeta::get( $productID, WOOASSISTANT_PLUGIN_KEY . '_product_quantity_min' ) );
			if ( $productMin ) {
				$min = $productMin;
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
	public function addToCartValidation( $passed, $productId, $quantity, $variationId = 0, $variations = [] ): bool {
		if ( ! $passed ) {
			return $passed;
		}

		$quantities    = WC()->cart->get_cart_item_quantities();
		$cartProductId = $variationId ?: $productId;
		$stockQuantity = $globalMax = $productMax = Sanitizing::int( Settings::get( 'quantity_maximum_value', false ) );

		$cartProduct = WooCommerce::getCartProduct( $productId, $variationId );
		if ( $cartProduct && $cartProduct['manage_stock'] && $cartProduct['stock_quantity'] > 0 ) {
			$stockQuantity = $cartProduct['stock_quantity'];
		}

		if ( Settings::get( 'product_single_quantity_tools_enable', false ) ) {
			$_productMax = Sanitizing::int( PostMeta::get( $productId, WOOASSISTANT_PLUGIN_KEY . '_product_quantity_max' ) );
			if ( $_productMax ) {
				$productMax = $_productMax;
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
			$productMin = Sanitizing::int( PostMeta::get( $productID, WOOASSISTANT_PLUGIN_KEY . '_product_quantity_min' ) );
			if ( $productMin ) {
				$min = $productMin;
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
			$min         = Sanitizing::int( Settings::get( 'quantity_minimum_value', false ) );
			$max         = Sanitizing::int( Settings::get( 'quantity_maximum_value', false ) );
			$step        = Sanitizing::int( Settings::get( 'quantity_step_value', false ) );

			if ( Settings::get( 'product_single_quantity_tools_enable', false ) ) {
				$productMin  = Sanitizing::int( PostMeta::get( $productID, WOOASSISTANT_PLUGIN_KEY . '_product_quantity_min' ) );
				$productMax  = Sanitizing::int( PostMeta::get( $productID, WOOASSISTANT_PLUGIN_KEY . '_product_quantity_max' ) );
				$productStep = Sanitizing::int( PostMeta::get( $productID, WOOASSISTANT_PLUGIN_KEY . '_product_quantity_step' ) );
				if ( $productMin ) {
					$min = $productMin;
				}
				if ( $productMax ) {
					$max = $productMax;
				}
				if ( $productStep ) {
					$step = $productStep;
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

			$args['input_value'] = $maxPurchase > 0 ? min( $min, $maxPurchase ) : $min;
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
		if ( ! Settings::get( 'quantity_input_plus_minus_button', false ) ) {
			return;
		}
		if ( WooCommerce::isProduct() ) {
			$productID = WooCommerce::getCurrentId();
			$product   = WooCommerce::getProduct( $productID );
			if ( is_bool( $product ) || is_null( $product ) || ( $product->is_sold_individually() || ( $product->managing_stock() && ! is_null( $product->get_stock_quantity() ) && $product->get_stock_quantity() <= 1 ) ) ) {
				return;
			}
		}

		self::$printStyle = true;

		if (
			( WooCommerce::isProduct() && Settings::get( 'product_quantity_disabled', false ) ) ||
			( WooCommerce::isCart() && Settings::get( 'product_cart_quantity_disabled', false ) )
		) {
			return;
		}

		self::$printed = true;

		$displayButton = apply_filters( 'woo_assistant_quantity_input_display_plus_minus', true, $productID );

		if ( $displayButton ) {
			echo '<button type="button" class="wa-button wa-button-change-quantity" data-action="minus" aria-label="' . __( 'Reduce quantity', 'woo-assistant' ) . '">-</button>';
		}
	}

	public function afterQuantityInputField(): void {
		if ( ! self::$printed || ! Settings::get( 'quantity_input_plus_minus_button', false ) ) {
			return;
		}

		$productID     = WooCommerce::getCurrentId();
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
			[ 'jquery' ], $pluginVersion, [ 'in_footer' => true ] );

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
		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Product Quantity', 'woo-assistant' ),
			'desc'           => __( 'Add plus minus button to quantity field, Control Min/Max/Step of quantity field.', 'woo-assistant' ),
			'tags'           => [ __( 'Product', 'woo-assistant' ) ],
			'cat'            => 'product',
			'more_info_link' => 'https://parsa.ws'
		);
	}
}