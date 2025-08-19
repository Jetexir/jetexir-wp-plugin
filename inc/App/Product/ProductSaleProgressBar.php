<?php

namespace WooAssistant\App\Product;

use WooAssistant\Addons\Addon;
use WooAssistant\Admin\AdminPages;
use WooAssistant\Enums\Colors;
use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Notice;
use WooAssistant\Helper\Param;
use WooAssistant\Helper\PostMeta;
use WooAssistant\Helper\Sanitizing;
use WooAssistant\Helper\Templates;
use WooAssistant\Helper\WooCommerce;
use WooAssistant\Interfaces\AddonInterface;

class ProductSaleProgressBar extends Addon implements AddonInterface {
	public string $addonID = 'product-sale-progress-bar';
	public string $currentTab = 'product';
	public string $currentSection = 'sale-progress-bar';

	public function adminInitAction(): void {
		if ( AdminPages::isSettingPage() && Param::get( 'section' ) === $this->currentSection ) {
			Notice::add( $this->currentTab, __( 'At present, this functionality is exclusively available for simple products', 'wc-assistant' ), 'warning' );
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
		$saleProgressBarStock = Sanitizing::int( PostMeta::get( $productID, WOOASSISTANT_PLUGIN_KEY . '_sale_progress_bar_stock' ) );

		if ( $stock > $saleProgressBarStock ) {
			return;
		}

		$sold        = $saleProgressBarStock - $stock;
		$soldPercent = (int) ( 100 / $saleProgressBarStock ) * $sold;

		Templates::load( Templates::getPath( 'sale-progress-bar/progress_bar.php' ), array(
			'sold_title'            => $this->getSetting( 'product_sale_progress_bar_sold_title', __( 'Sold', 'wc-assistant' ) ),
			'sold'                  => $sold,
			'remaining_title'       => $this->getSetting( 'product_sale_progress_bar_remaining_title', __( 'Remaining', 'wc-assistant' ) ),
			'stock'                 => $stock,
			'sold_percent'          => $soldPercent,
			'progress_bar_bg_color' => $this->getSetting( 'product_sale_progress_bar_bg_color', Colors::primary ),
			'progress_bar_height'   => $this->getSetting( 'product_sale_progress_bar_height', 10 ),
		) );
	}

	public function adminProductSaveMeta( $productID ): void {
		$stock = Sanitizing::int( Param::post( WOOASSISTANT_INPUT_PREFIX . 'sale_progress_bar_stock' ) );

		if ( $stock ) {
			PostMeta::update( $productID, WOOASSISTANT_PLUGIN_KEY . '_sale_progress_bar_stock', $stock );
		} else {
			PostMeta::delete( $productID, WOOASSISTANT_PLUGIN_KEY . '_sale_progress_bar_stock' );
		}
	}

	public function addStockToProduct(): void {
		$product = wc_get_product();
		$value   = (int) $product->get_meta( WOOASSISTANT_PLUGIN_KEY . '_sale_progress_bar_stock' );
		if ( ! $value ) {
			$value = wc_stock_amount( $product->get_stock_quantity( 'edit' ) ?? 1 );
		}

		woocommerce_wp_text_input(
			array(
				'id'                => WOOASSISTANT_INPUT_PREFIX . 'sale_progress_bar_stock',
				'value'             => wc_stock_amount( $value ),
				'label'             => __( 'Sale progress bar quantity', 'wc-assistant' ),
				'desc_tip'          => true,
				'description'       => __( 'Please enter the starting quantity of product, The entered value must be greater than the quantity value.', 'wc-assistant' ),
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
			'title'        => __( 'Sale progress bar', 'wc-assistant' ),
			'desc'         => __( 'Product sale progress bar', 'wc-assistant' ),
			'settings_key' => $this->addonID,
			'settings'     => [
				'product_sale_progress_bar_start_grid'      => array(
					'id'    => 'product_sale_progress_bar_start_grid',
					'title' => __( 'Sale progress bar', 'wc-assistant' ),
					'type'  => 'startgrid',
				),
				'product_sale_progress_bar_sold_title'      => array(
					'id'          => 'product_sale_progress_bar_sold_title',
					'title'       => __( 'Sold title', 'wc-assistant' ),
					'type'        => 'text',
					'default'     => __( 'Sold', 'wc-assistant' ),
					'placeholder' => __( 'Sold', 'wc-assistant' ),
				),
				'product_sale_progress_bar_remaining_title' => array(
					'id'          => 'product_sale_progress_bar_remaining_title',
					'title'       => __( 'Remaining product title', 'wc-assistant' ),
					'type'        => 'text',
					'default'     => __( 'Remaining', 'wc-assistant' ),
					'placeholder' => __( 'Remaining', 'wc-assistant' ),
				),
				'product_sale_progress_bar_position'        => array(
					'id'          => 'product_sale_progress_bar_position',
					'title'       => __( 'Position on single page', 'wc-assistant' ),
					'type'        => 'select',
					'options'     => array(
						'before_title'       => __( 'Before title', 'wc-assistant' ),
						'after_title'        => __( 'After title', 'wc-assistant' ),
						'after_rating'       => __( 'After rating', 'wc-assistant' ),
						'after_price'        => __( 'After price', 'wc-assistant' ),
						'after_excerpt'      => __( 'After excerpt', 'wc-assistant' ),
						'before_add_to_cart' => __( 'Before add to cart button', 'wc-assistant' ),
						'after_add_to_cart'  => __( 'After add to cart button', 'wc-assistant' ),
						'after_meta'         => __( 'After meta', 'wc-assistant' ),
						'after_sharing'      => __( 'After sharing', 'wc-assistant' ),
					),
					'option_none' => __( 'Hide', 'wc-assistant' ),
					'default'     => 'after_title',
					'sanitize'    => 'text',
				),
				'product_sale_progress_bar_end_grid'        => array(
					'type' => 'endgrid',
				),
				'product_sale_progress_bar_start_grid_2'    => array(
					'id'    => 'product_sale_progress_bar_start_grid_2',
					'title' => __( 'Style', 'wc-assistant' ),
					'type'  => 'startgrid',
				),
				'product_sale_progress_bar_bg_color'        => array(
					'id'       => 'product_sale_progress_bar_bg_color',
					'title'    => __( 'Progress bar background color', 'wc-assistant' ),
					'type'     => 'wpColorPicker',
					'default'  => Colors::primary,
					'sanitize' => 'color'
				),
				'product_sale_progress_bar_height'          => array(
					'id'         => 'product_sale_progress_bar_height',
					'title'      => __( 'Progress bar height', 'wc-assistant' ),
					'desc'       => __( 'Pixel', 'wc-assistant' ),
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
		$debugName     = WOOASSISTANT_DEBUG_MODE ? '' : '.min';

		wp_enqueue_style( WOOASSISTANT_PLUGIN_KEY . '-product-sale-progress-bar-style',
			Assets::url( 'css/product-sale-progress-bar' . $debugName . '.css' ),
			false, $pluginVersion );
	}

	public function info(): array {
		$icon = '';

		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Sale progress bar', 'wc-assistant' ),
			'desc'           => __( 'Sales progress bar for products', 'wc-assistant' ),
			'tags'           => [ __( 'Product', 'wc-assistant' ) ],
			'cat'            => 'product',
			'icon'           => $icon,
			'more_info_link' => 'https://parsa.ws',
			'settings_key'   => $this->addonID,
		);
	}
}