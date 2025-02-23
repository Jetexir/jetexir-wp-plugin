<?php

namespace WooAssistant\App\Product;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Admin\AdminAssets;
use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Helper;
use WooAssistant\Helper\Notice;
use WooAssistant\Helper\Param;
use WooAssistant\Helper\PostMeta;
use WooAssistant\Helper\Sanitizing;
use WooAssistant\Settings\Settings;

defined( 'ABSPATH' ) || exit;

class ProductQuantity {
	private static $printed = false;

	public function __construct() {
		add_filter( 'woo_assistant_product_settings_sections', [ $this, 'addSectionSettings' ] );
		add_filter( 'woo_assistant_settings_before_save', [ $this, 'checkSettingsBeforeSave' ], 10, 2 );

		add_action( 'woocommerce_before_quantity_input_field', [ $this, 'beforeQuantityInputField' ] );
		add_action( 'woocommerce_after_quantity_input_field', [ $this, 'afterQuantityInputField' ] );
		add_action( 'wp_footer', [ $this, 'enqueueScripts' ] );
		add_action( 'wp_footer', [ $this, 'printStyle' ] );
		//add_filter( 'woo_assistant_settings_header_image', [ $this, 'addHeaderImage' ], 10, 4 );

		add_filter( 'woocommerce_product_data_tabs', [ $this, 'productTab' ] );
		add_filter( 'woocommerce_product_data_panels', [ $this, 'productSettings' ] );
		add_action( 'woocommerce_process_product_meta', [ $this, 'productSaveMeta' ] );

		add_filter( 'woocommerce_quantity_input_args', [ $this, 'changeQuantityInputArgs' ], 10, 2 );
		add_filter( 'woocommerce_blocks_product_grid_add_to_cart_attributes',
			[ $this, 'changeQuantityAddToCart' ], 10, 2 );
		add_filter( 'woocommerce_loop_add_to_cart_link', [ $this, 'changeQuantityAddToCartLink' ], 10, 3 );
	}

	public function productSaveMeta( $productID ): void {
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

	public function productTab( $tabs ) {
		if ( Settings::get( 'quantity_manage_min_max_product', false ) ) {
			$tabs[ WOOASSISTANT_PLUGIN_KEY . '_quantity_control' ] = array(
				'label'  => __( 'Min/Max/Step', 'woo-assistant' ),
				'target' => WOOASSISTANT_PLUGIN_KEY . '_quantity_control',
				'class'  => array( 'hide_if_grouped' ),
			);
		}

		return $tabs;
	}

	public function productSettings(): void {
		if ( ! Settings::get( 'quantity_manage_min_max_product', false ) ) {
			return;
		}

		?>
        <div id="<?php echo WOOASSISTANT_PLUGIN_KEY . '_quantity_control' ?>" class="panel woocommerce_options_panel">
            <div class="options_group">
				<?php
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
				?>
            </div>
        </div>
		<?php
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

		if ( Settings::get( 'quantity_manage_min_max_product', false ) ) {
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

		if ( Settings::get( 'quantity_manage_min_max_product', false ) ) {
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

			if ( Settings::get( 'quantity_manage_min_max_product', false ) ) {
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
		if ( ! self::$printed ) {
			return;
		}

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

		if ( $value = Settings::get( 'quantity_button_border_width', false ) ) {
			$buttonStyle['border'] = $value . ' solid transparent';
		}
		if ( $value = Settings::get( 'quantity_button_border_radius', false ) ) {
			$buttonStyle['border-radius'] = $value;
		}
		if ( $value = Settings::get( 'quantity_button_font_color', false ) ) {
			$buttonStyle['color'] = $value;
		}
		if ( $value = Settings::get( 'quantity_button_bg_color', false ) ) {
			$buttonStyle['background-color'] = $value;
		}
		if ( $value = Settings::get( 'quantity_button_border_color', false ) ) {
			$buttonStyle['border-color'] = $value;
		}

		// Button hover style
		if ( $value = Settings::get( 'quantity_button_font_hover_color', false ) ) {
			$buttonHoverStyle['color'] = $value;
		}
		if ( $value = Settings::get( 'quantity_button_bg_hover_color', false ) ) {
			$buttonHoverStyle['background-color'] = $value;
		}
		if ( $value = Settings::get( 'quantity_button_border_hover_color', false ) ) {
			$buttonHoverStyle['border-color'] = $value;
		}

		// Input style
		if ( Settings::get( 'quantity_input_style', false ) ) {
			if ( $value = Settings::get( 'quantity_input_border_width', false ) ) {
				$inputStyle['border'] = $value . ' solid transparent';
			}
			if ( $value = Settings::get( 'quantity_input_border_radius', false ) ) {
				$inputStyle['border-radius'] = $value;
			}
			if ( $value = Settings::get( 'quantity_input_font_color', false ) ) {
				$inputStyle['color'] = $value;
			}
			if ( $value = Settings::get( 'quantity_input_bg_color', false ) ) {
				$inputStyle['background-color'] = $value;
			}
			if ( $value = Settings::get( 'quantity_input_border_color', false ) ) {
				$inputStyle['border-color'] = $value;
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

		self::$printed = true;
		$productID     = Product::getCurrentId();
		$displayButton = apply_filters( 'woo_assistant_quantity_input_display_plus_minus', true, $productID );

		if ( $displayButton ) {
			echo '<button type="button" class="wa-button wa-button-change-quantity" data-action="minus" aria-label="' . __( 'Reduce quantity', 'woo-assistant' ) . '">-</button>';
		}
	}

	public function afterQuantityInputField(): void {
		if ( ! Settings::get( 'quantity_input_plus_minus_button', false ) ) {
			return;
		}

		$productID     = Product::getCurrentId();
		$displayButton = apply_filters( 'woo_assistant_quantity_input_display_plus_minus', true, $productID );

		if ( $displayButton ) {
			echo '<button type="button" class="wa-button wa-button-change-quantity" data-action="plus" aria-label="' . __( 'Increase quantity', 'woo-assistant' ) . '">+</button>';
		}
	}

	public function enqueueScripts(): void {
		if ( self::$printed && Settings::get( 'quantity_input_plus_minus_button', false ) ) {
			$pluginVersion = Assets::getVersion();
			wp_enqueue_script( WOOASSISTANT_PLUGIN_SLUG . '-product-quantity-script',
				Assets::url( 'js/product-quantity.min.js' ),
				[ 'jquery' ], $pluginVersion, [ 'in_footer' => true ] );
		}
	}

	public function addSectionSettings( $sections ): array {
		$sections['quantity'] = array(
			'title'    => __( 'Quantity', 'woo-assistant' ),
			'desc'     => __( 'Quantity customization', 'woo-assistant' ),
			'settings' => self::getSettings()
		);

		return $sections;
	}

	public static function getSettings(): array {
		return array(
			'start_grid_quantity_min_max'      => array(
				'title' => __( 'Quantity Min/Max', 'woo-assistant' ),
				'type'  => 'startgrid',
			),
			'quantity_manage_min_max_product'  => array(
				'id'       => 'quantity_manage_min_max_product',
				'title'    => __( 'Enable Min/Max', 'woo-assistant' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => false,
				'desc'     => __( 'Manage Minimum/Maximum Quantity per Product', 'woo-assistant' ),
				'sanitize' => 'bool'
			),
			'quantity_minimum_value'           => array(
				'id'         => 'quantity_minimum_value',
				'title'      => __( 'Minimum', 'woo-assistant' ),
				'type'       => 'number',
				'attributes' => array(
					'placeholder' => 'eg: 2',
					'step'        => 1,
					'min'         => 1,
				),
				'sanitize'   => 'int'
			),
			'quantity_maximum_value'           => array(
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
			'quantity_step_value'              => array(
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
			'end_grid_quantity_min_max'        => array(
				'type' => 'endgrid',
			),
			'quantity_min_max_sep'             => array(
				'type' => 'hr',
			),
			'start_grid_quantity_input1'       => array(
				'title' => __( 'Quantity Plus/Minus button', 'woo-assistant' ),
				'type'  => 'startgrid',
			),
			'quantity_input_plus_minus_button' => array(
				'id'       => 'quantity_input_plus_minus_button',
				'title'    => __( 'Enable Plus/Minus', 'woo-assistant' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => false,
				'desc'     => __( 'Add Plus/Minus buttons to Quantity input', 'woo-assistant' ),
				'sanitize' => 'bool'
			),
			'quantity_button_width_height'     => array(
				'id'         => 'quantity_button_width_height',
				'title'      => __( 'Button width/height', 'woo-assistant' ),
				'type'       => 'text',
				'attributes' => array(
					'placeholder' => 'eg: 30px'
				)
			),
			'quantity_button_border_width'     => array(
				'id'         => 'quantity_button_border_width',
				'title'      => __( 'Border width', 'woo-assistant' ),
				'type'       => 'text',
				'attributes' => array(
					'placeholder' => 'eg: 1px'
				)
			),
			'quantity_button_border_radius'    => array(
				'id'         => 'quantity_button_border_radius',
				'title'      => __( 'Border radius', 'woo-assistant' ),
				'type'       => 'text',
				'attributes' => array(
					'placeholder' => 'eg: 4px'
				)
			),

			'end_grid_quantity_input1' => array(
				'type' => 'endgrid',
			),

			'start_grid_quantity_input2'   => array(
				'title' => __( 'Button Color', 'woo-assistant' ),
				'type'  => 'startgrid',
			),
			'quantity_button_font_color'   => array(
				'id'       => 'quantity_button_font_color',
				'title'    => __( 'Font', 'woo-assistant' ),
				'type'     => 'wpColorPicker',
				'sanitize' => 'color'
			),
			'quantity_button_bg_color'     => array(
				'id'       => 'quantity_button_bg_color',
				'title'    => __( 'Background', 'woo-assistant' ),
				'type'     => 'wpColorPicker',
				'sanitize' => 'color'
			),
			'quantity_button_border_color' => array(
				'id'       => 'quantity_button_border_color',
				'title'    => __( 'Border', 'woo-assistant' ),
				'type'     => 'wpColorPicker',
				'sanitize' => 'color'
			),
			'end_grid_quantity_input2'     => array(
				'type' => 'endgrid',
			),

			'start_grid_quantity_input3'         => array(
				'title' => __( 'Button Hover Color', 'woo-assistant' ),
				'type'  => 'startgrid',
			),
			'quantity_button_font_hover_color'   => array(
				'id'       => 'quantity_button_font_hover_color',
				'title'    => __( 'Font', 'woo-assistant' ),
				'type'     => 'wpColorPicker',
				'sanitize' => 'color'
			),
			'quantity_button_bg_hover_color'     => array(
				'id'       => 'quantity_button_bg_hover_color',
				'title'    => __( 'Background', 'woo-assistant' ),
				'type'     => 'wpColorPicker',
				'sanitize' => 'color'
			),
			'quantity_button_border_hover_color' => array(
				'id'       => 'quantity_button_border_hover_color',
				'title'    => __( 'Border', 'woo-assistant' ),
				'type'     => 'wpColorPicker',
				'sanitize' => 'color'
			),
			'end_grid_quantity_input3'           => array(
				'type' => 'endgrid',
			),

			'quantity_input_sep' => array(
				'type' => 'hr',
			),

			'start_grid_quantity_input5'   => array(
				'title' => __( 'Input Box', 'woo-assistant' ),
				'type'  => 'startgrid',
			),
			'quantity_input_style'         => array(
				'id'       => 'quantity_input_style',
				'title'    => __( 'Enable quantity input style', 'woo-assistant' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => false,
				'sanitize' => 'bool'
			),
			'quantity_input_font_color'    => array(
				'id'       => 'quantity_input_font_color',
				'title'    => __( 'Font', 'woo-assistant' ),
				'type'     => 'wpColorPicker',
				'sanitize' => 'color'
			),
			'quantity_input_bg_color'      => array(
				'id'       => 'quantity_input_bg_color',
				'title'    => __( 'Background', 'woo-assistant' ),
				'type'     => 'wpColorPicker',
				'sanitize' => 'color'
			),
			'quantity_input_border_color'  => array(
				'id'       => 'quantity_input_border_color',
				'title'    => __( 'Border', 'woo-assistant' ),
				'type'     => 'wpColorPicker',
				'sanitize' => 'color'
			),
			'quantity_input_width'         => array(
				'id'         => 'quantity_input_width',
				'title'      => __( 'Width', 'woo-assistant' ),
				'type'       => 'text',
				'attributes' => array(
					'placeholder' => 'eg: 50px'
				)
			),
			'quantity_input_height'        => array(
				'id'         => 'quantity_input_height',
				'title'      => __( 'Height', 'woo-assistant' ),
				'type'       => 'text',
				'attributes' => array(
					'placeholder' => 'eg: 30px'
				)
			),
			'quantity_input_border_width'  => array(
				'id'         => 'quantity_input_border_width',
				'title'      => __( 'Border width', 'woo-assistant' ),
				'type'       => 'text',
				'attributes' => array(
					'placeholder' => 'eg: 1px'
				)
			),
			'quantity_input_border_radius' => array(
				'id'         => 'quantity_input_border_radius',
				'title'      => __( 'Border radius', 'woo-assistant' ),
				'type'       => 'text',
				'attributes' => array(
					'placeholder' => 'eg: 4px'
				)
			),
			'end_grid_quantity_input5'     => array(
				'type' => 'endgrid',
			)
		);
	}
}