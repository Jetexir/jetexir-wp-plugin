<?php

namespace WooAssistant\App\Product;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Addons\Addon;
use WooAssistant\Interfaces\AddonInterface;
use WooAssistant\Settings\Settings;

class ProductCall extends Addon implements AddonInterface {
	public string $addonID = 'product-call';
	public string $currentTab = 'product';
	private const sectionID = 'call';

	public function initAction(): void {
		if ( Settings::get( 'product_call_empty_price', true ) ) {
			add_filter( 'woocommerce_empty_price_html', [ $this, 'emptyPriceText' ], 999999, 2 );
			add_filter( 'woocommerce_variable_empty_price_html', [ $this, 'emptyPriceText' ], 999999, 2 );
			add_filter( 'woocommerce_grouped_empty_price_html', [ $this, 'emptyPriceText' ], 999999, 2 );
			add_filter( 'woocommerce_variation_empty_price_html', [ $this, 'emptyPriceText' ], 999999, 2 );
		}

		if ( Settings::get( 'product_call_zero_price', true ) ) {
			add_filter( 'woocommerce_get_price_html', [ $this, 'zeroPriceText' ], 10, 2 );
		}

		if ( Settings::get( 'product_call_out_of_stock_price', false ) ) {
			add_filter( 'woocommerce_get_price_html', [ $this, 'outOfStockPriceText' ], 10, 2 );
		}

		if ( Settings::get( 'product_call_sale_tag', false ) ) {
			add_filter( 'woocommerce_sale_flash', [ $this, 'hideSaleTag' ], 10, 3 );
		}

		if ( Settings::get( 'product_call_read_more', true ) ) {
			add_filter( 'woocommerce_product_add_to_cart_text', [ $this, 'changeReadMore' ], 10, 2 );
		}

		add_filter( 'woocommerce_variation_is_visible', [ $this, 'variationIsVisible' ], 999999, 4 );
	}

	/**
	 * @param string $text Button text
	 * @param \WC_Product $product WC Product
	 */
	public function changeReadMore( $text, $product ): string {
		if ( empty( $product->get_price() ) ) {
			return Settings::get( 'product_call_text', __( 'Call for Price', 'woo-assistant' ) );
		}

		return $text;
	}

	/**
	 * Hide "sales" tag for empty price products.
	 *
	 * @param string $tag On sale HTML.
	 * @param object $post Post Object.
	 * @param object $product Product Object.
	 */
	public function hideSaleTag( $tag, $post, $product ): string {
		if ( empty( $product->get_price() ) ) {
			return '';
		}

		return $tag;
	}

	/**
	 * Make variation visible for variable products.
	 *
	 * @param bool $visible If the variation is visible.
	 * @param int $variationId The variation ID.
	 * @param int $productId The product ID.
	 * @param \WC_Product_Variation $variation The variation object.
	 *
	 * @return bool Visible state
	 */
	public function variationIsVisible( $visible, $variationId, $productId, $variation ): bool {
		if ( '' === $variation->get_price() ) {
			$visible = true;
			if ( get_post_status( $variationId ) !== 'publish' ) {
				$visible = false;
			}
		}

		return $visible;
	}

	/**
	 * @param string $price
	 * @param \WC_Product $product
	 *
	 * @return string
	 */
	public function outOfStockPriceText( $price, $product ): string {
		if ( ! $product->is_in_stock() ) {
			return Settings::get( 'product_call_text', __( 'Call for Price', 'woo-assistant' ) );
		}

		return $price;
	}

	/**
	 * @param string $price
	 * @param \WC_Product $product
	 *
	 * @return string
	 */
	public function zeroPriceText( $price, $product ): string {
		if ( $product->get_price() === '0' ) {
			return Settings::get( 'product_call_text', __( 'Call for Price', 'woo-assistant' ) );
		}

		return $price;
	}

	/**
	 * @param string $text
	 * @param \WC_Product $product
	 *
	 * @return string
	 */
	public function emptyPriceText( $text, $product ): string {
		$_text = Settings::get( 'product_call_text', __( 'Call for Price', 'woo-assistant' ) );

		if ( ! empty( $_text ) ) {
			$text = $_text;
		}

		return $text;
	}

	public function addSectionSettings( $sections ): array {
		$sections[ self::sectionID ] = array(
			'title'    => __( 'Call', 'woo-assistant' ),
			'desc'     => __( 'Product Call for Price', 'woo-assistant' ),
			'settings' => [
				'product_call_start_grid'         => array(
					'id'    => 'product_social_share_start_grid_2',
					'title' => __( 'Call for Price', 'woo-assistant' ),
					'type'  => 'startgrid',
				),
				'product_call_empty_price'        => array(
					'id'       => 'product_call_empty_price',
					'title'    => __( 'Empty price', 'woo-assistant' ),
					'desc'     => __( 'Display custom text for product with empty price', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => true,
					'sanitize' => 'bool'
				),
				'product_call_zero_price'         => array(
					'id'       => 'product_call_zero_price',
					'title'    => __( 'Zero price', 'woo-assistant' ),
					'desc'     => __( 'Display custom text for product with zero price', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => true,
					'sanitize' => 'bool'
				),
				'product_call_out_of_stock_price' => array(
					'id'       => 'product_call_out_of_stock_price',
					'title'    => __( '"Out of stock" products', 'woo-assistant' ),
					'desc'     => __( 'Display custom text for out of stock products', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => false,
					'sanitize' => 'bool'
				),
				'product_call_text'               => array(
					'id'      => 'product_call_text',
					'title'   => __( 'Text', 'woo-assistant' ),
					'type'    => 'text',
					'default' => __( 'Call for Price', 'woo-assistant' )
				),
				'product_call_read_more'          => array(
					'id'       => 'product_call_read_more',
					'title'    => __( '"Read more" button', 'woo-assistant' ),
					'desc'     => __( 'Change "Read more" button text', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => true,
					'sanitize' => 'bool'
				),
				'product_call_sale_tag'           => array(
					'id'       => 'product_call_sale_tag',
					'title'    => __( 'Sale tag', 'woo-assistant' ),
					'desc'     => __( 'Hides sale tag for products with empty prices.', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => false,
					'sanitize' => 'bool'
				),
				'product_call_end_grid'           => array(
					'type' => 'endgrid',
				),
			]
		);

		return $sections;
	}

	public function info(): array {
		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Call for price', 'woo-assistant' ),
			'desc'           => __( 'Add a call button for products that have an empty price field.', 'woo-assistant' ),
			'tags'           => [ __( 'Product', 'woo-assistant' ) ],
			'cat'            => 'product',
			'more_info_link' => 'https://parsa.ws'
		);
	}
}