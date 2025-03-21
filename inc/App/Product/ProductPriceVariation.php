<?php

namespace WooAssistant\App\Product;

use WooAssistant\Addons\Addon;
use WooAssistant\Interfaces\AddonInterface;
use WooAssistant\Settings\Settings;

class ProductPriceVariation extends Addon implements AddonInterface {
	public string $addonID = 'product-price-variation';

	public function initAction(): void {
		add_filter( 'woo_assistant_product_global_settings', [ $this, 'addSectionSettings' ] );
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
					$price = ( $addFrom ? __( 'From', 'variation-price-display' ) : '' ) . ' ' . wc_price( $minPrice );

				} elseif ( $type === 'max' ) {
					$price = ( $addUpTo ? __( 'Up To', 'variation-price-display' ) : '' ) . ' ' . wc_price( $maxPrice );

				} elseif ( $type === 'max_to_min' ) {
					$price = wc_format_price_range( $maxPrice, $minPrice );

				} else {
					$price = wc_format_price_range( $minPrice, $maxPrice );
				}
			}
		}

		return $price;
	}

	public function addSectionSettings( $settings ): array {
		$addonSettings = array(
			'start_grid_product_variation_price' => array(
				'title' => __( 'Product Variation Prices', 'woo-assistant' ),
				'type'  => 'startgrid',
			),

			'variation_price_type'      => array(
				'id'                => 'variation_price_type',
				'title'             => __( 'Variation price type', 'woo-assistant' ),
				'type'              => 'select',
				'options'           => array(
					'min'        => __( 'Minimum Price', 'woo-assistant' ),
					'max'        => __( 'Maximum Price', 'woo-assistant' ),
					'min_to_max' => __( 'Minimum to Maximum Price', 'woo-assistant' ),
					'max_to_min' => __( 'Maximum to Minimum Price', 'woo-assistant' ),
				),
				'option_none'       => '---',
				'option_none_value' => '',
				'default'           => 'min',
				'sanitize'          => 'text'
			),
			'variation_price_add_from'  => [
				'id'       => 'variation_price_add_from',
				'title'    => __( 'Add From', 'woo-assistant' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => true,
				'desc'     => __( 'Enable it to display "From" before Minimum Price.', 'woo-assistant' ),
				'sanitize' => 'bool'
			],
			'variation_price_add_up_to' => [
				'id'       => 'variation_price_add_up_to',
				'title'    => __( 'Add Up To', 'woo-assistant' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => true,
				'desc'     => __( 'Enable it to display "Up To" before Maximum Price.', 'woo-assistant' ),
				'sanitize' => 'bool'
			],
			'variation_hide_reset_link' => array(
				'id'       => 'variation_hide_reset_link',
				'title'    => __( 'Hide Reset Link', 'woo-assistant' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => false,
				'desc'     => __( 'Remove "Clear" link on single product page. ', 'woo-assistant' ),
				'sanitize' => 'bool'
			),

			'end_grid_product_variation_price' => array(
				'type' => 'endgrid',
			),
		);

		return array_merge( $settings, $addonSettings );
	}

	public function info(): array {
		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Product Price Variation', 'woo-assistant' ),
			'desc'           => __( 'Add advanced settings for WooCommerce variable product prices.', 'woo-assistant' ),
			'tags'           => [ __( 'Product', 'woo-assistant' ) ],
			'cat'            => 'product',
			'more_info_link' => 'https://parsa.ws'
		);
	}
}