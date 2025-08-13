<?php

namespace WooAssistant\App\Product;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Addons\Addon;
use WooAssistant\Interfaces\AddonInterface;

class ProductCall extends Addon implements AddonInterface {
	public string $addonID = 'product-call';
	public string $currentTab = 'product';
	public string $currentSection = 'call';

	public function initAction(): void {
		if ( $this->getSetting( 'product_call_empty_price', true ) ) {
			add_filter( 'woocommerce_empty_price_html', [ $this, 'emptyPriceText' ], 999999, 2 );
			add_filter( 'woocommerce_variable_empty_price_html', [ $this, 'emptyPriceText' ], 999999, 2 );
			add_filter( 'woocommerce_grouped_empty_price_html', [ $this, 'emptyPriceText' ], 999999, 2 );
			add_filter( 'woocommerce_variation_empty_price_html', [ $this, 'emptyPriceText' ], 999999, 2 );
		}

		if ( $this->getSetting( 'product_call_zero_price', true ) ) {
			add_filter( 'woocommerce_get_price_html', [ $this, 'zeroPriceText' ], 10, 2 );
		}

		if ( $this->getSetting( 'product_call_out_of_stock_price', false ) ) {
			add_filter( 'woocommerce_get_price_html', [ $this, 'outOfStockPriceText' ], 10, 2 );
		}

		if ( $this->getSetting( 'product_call_sale_tag', false ) ) {
			add_filter( 'woocommerce_sale_flash', [ $this, 'hideSaleTag' ], 10, 3 );
		}

		if ( $this->getSetting( 'product_call_read_more', true ) ) {
			add_filter( 'woocommerce_product_add_to_cart_text', [ $this, 'changeReadMore' ], 10, 2 );
		}

		add_filter( 'woocommerce_variation_is_visible', [ $this, 'variationIsVisible' ], 999999, 4 );
	}

	/**
	 * @param string $text Button text
	 * @param \WC_Product $product WC Product
	 */
	public function changeReadMore( $text, $product ): string {
		if ( empty( $product->get_price() ) || ( ! $product->is_in_stock() && $this->getSetting( 'product_call_out_of_stock_price', false ) ) ) {
			return $this->getSetting( 'product_call_text', __( 'Call for Price', 'woo-assistant' ) );
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
			return $this->getSetting( 'product_call_text', __( 'Call for Price', 'woo-assistant' ) );
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
			return $this->getSetting( 'product_call_text', __( 'Call for Price', 'woo-assistant' ) );
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
		$_text = $this->getSetting( 'product_call_text', __( 'Call for Price', 'woo-assistant' ) );

		if ( ! empty( $_text ) ) {
			$text = $_text;
		}

		return $text;
	}

	public function addSectionSettings( $sections ): array {
		$sections[ $this->currentSection ] = array(
			'title'        => __( 'Call', 'woo-assistant' ),
			'desc'         => __( 'Call for product price', 'woo-assistant' ),
			'settings_key' => $this->addonID,
			'settings'     => [
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
					'id'          => 'product_call_text',
					'title'       => __( 'Text', 'woo-assistant' ),
					'type'        => 'text',
					'default'     => __( 'Call for Price', 'woo-assistant' ),
					'placeholder' => __( 'Call for Price', 'woo-assistant' ),
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
		$icon = '<svg viewBox="-3.2 -3.2 38.40 38.40" id="svg5" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg" fill="#873eff"><g id="SVGRepo_bgCarrier" stroke-width="0"><path transform="translate(-3.2, -3.2), scale(1.2)" d="M16,29.51841513160616C18.292037399584483,29.30524879721258,20.099097715630815,27.69293662603993,22.16663125064224,26.68091863765437C24.397302858252488,25.58904769343288,27.135195514085062,25.267251537249667,28.67156103870919,23.315929176751524C30.257444444071957,21.3017146552532,31.125262101756807,18.504121785097716,30.576208006590605,16C30.038787993966583,13.548938851003511,27.359582947658968,12.31265179423253,25.823867020736564,10.328187731094662C24.526135153144857,8.651248520087403,23.829690902681797,6.602248873283932,22.228134537348527,5.212554544937875C20.40963785946428,3.6346178740383746,18.40070253661824,1.6210013558488259,16,1.8038718989118934C13.581238614599307,1.98811805241964,12.361541329050851,4.848483825622085,10.296601653564725,6.121424288169774C8.30979601430753,7.3461987062568355,5.3305114816969965,7.14593317948527,4.1084967928247895,9.134437422268086C2.8893382317556298,11.118294075863956,4.251909357872484,13.6716197822013,4.225825255271047,15.999999999999998C4.199385176577204,18.360156188942167,3.053322610762618,20.77311528653661,3.9536699674860856,22.95495188701897C4.879403297073551,25.1983070162335,7.002488743062932,26.731919489468943,9.133553714025759,27.893033834749986C11.234743800210598,29.037870798650363,13.61744846912172,29.739999550967386,16,29.51841513160616" fill="#fff" strokewidth="0"></path></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <defs id="defs2"></defs> <g id="layer1" transform="translate(-156,-292)"> <path d="m 173.85547,301.89648 a 1,1 0 0 0 -0.92578,0.79297 1,1 0 0 0 0.77343,1.1836 c 1.63286,0.34401 2.66043,1.92184 2.31641,3.55468 a 1,1 0 0 0 0.77344,1.1836 1,1 0 0 0 1.18359,-0.77149 c 0.56686,-2.6905 -1.17081,-5.35698 -3.86133,-5.92382 a 1,1 0 0 0 -0.25976,-0.0195 z" id="path453461" style="color:#873eff;fill:#873eff;fill-rule:evenodd;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:4.1;-inkscape-stroke:none"></path> <path d="m 174.62695,297.98633 a 1,1 0 0 0 -0.87109,0.78906 1,1 0 0 0 0.77148,1.18359 c 3.79454,0.79946 6.20571,4.49845 5.40625,8.29297 a 1,1 0 0 0 0.77344,1.18555 1,1 0 0 0 1.18359,-0.77344 c 1.0223,-4.85218 -2.09897,-9.63982 -6.95117,-10.66211 a 1,1 0 0 0 -0.3125,-0.0156 z" id="path453441" style="color:#873eff;fill:#873eff;fill-rule:evenodd;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:4.1;-inkscape-stroke:none"></path> <path d="m 175.37305,294.08398 a 0.99999499,0.99999499 0 0 0 -0.79297,0.77735 0.99999499,0.99999499 0 0 0 0.77148,1.18359 c 5.95621,1.2549 9.75098,7.07505 8.4961,13.03125 a 0.99999499,0.99999499 0 0 0 0.77343,1.18555 0.99999499,0.99999499 0 0 0 1.1836,-0.77344 c 1.47772,-7.01386 -3.02716,-13.92266 -10.04102,-15.40039 a 0.99999499,0.99999499 0 0 0 -0.39062,-0.004 z" id="path453433" style="color:#873eff;fill:#873eff;fill-rule:evenodd;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:4.1;-inkscape-stroke:none"></path> <path d="m 162.98047,294.01172 c -0.98492,-0.0178 -1.90172,0.43314 -2.45117,1.34375 -3.28962,5.4519 -3.39002,12.29826 -0.18164,17.87305 a 1.0001,1.0001 0 0 0 0.002,0.006 c 3.23676,5.55933 9.22998,8.89168 15.60742,8.77539 1.21528,-0.0221 2.15131,-0.75945 2.57227,-1.83399 l 1.4375,-3.66797 c 0.51477,-1.31399 0.12221,-2.82162 -0.96875,-3.71679 l -2.58008,-2.11524 c -1.32752,-1.08929 -3.35959,-0.72448 -4.22656,0.75781 l -0.9668,1.65039 c -1.64502,-0.85898 -3.11472,-2.085 -4.08984,-3.76562 v -0.002 c -1.01959,-1.76568 -1.33095,-3.74687 -1.19727,-5.6836 l 1.83008,0.11719 c 1.70931,0.1098 3.11184,-1.44331 2.83008,-3.13281 l -0.54688,-3.2793 c -0.2321,-1.39165 -1.3395,-2.48347 -2.73437,-2.69531 l -3.91016,-0.59375 c -0.14259,-0.0217 -0.28508,-0.0346 -0.42578,-0.0371 z m 0.125,2.01367 3.91015,0.5957 c 0.54734,0.0831 0.97144,0.50081 1.0625,1.04688 l 0.54688,3.2793 c 0.0774,0.46424 -0.26078,0.83681 -0.73047,0.80664 l -2.88867,-0.18555 a 1.0001,1.0001 0 0 0 -1.05078,0.83984 c -0.42446,2.65227 0.0322,5.45735 1.44922,7.91016 a 1.0001,1.0001 0 0 0 0,0.002 c 1.35385,2.33418 3.42087,4.07747 5.78711,5.06641 a 1.0001,1.0001 0 0 0 1.24804,-0.41797 l 1.47852,-2.52734 c 0.26508,-0.45323 0.82652,-0.55377 1.23242,-0.22071 l 2.57813,2.11719 c 0.42703,0.3504 0.57649,0.92512 0.375,1.43945 l -1.43555,3.66797 c -0.0693,0.177 -0.61768,0.56212 -0.74609,0.56446 -5.65845,0.10318 -10.97213,-2.85101 -13.84376,-7.78321 -2.84321,-4.94487 -2.75158,-11.00379 0.16407,-15.83594 0.0663,-0.1099 0.67509,-0.39381 0.86328,-0.36523 z" id="path453419" style="color:#873eff;fill:#873eff;fill-rule:evenodd;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:4.1;-inkscape-stroke:none"></path> </g> </g></svg>';

		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Call for price', 'woo-assistant' ),
			'desc'           => __( 'Add a Call button for products with no price set.', 'woo-assistant' ),
			'tags'           => [ __( 'Product', 'woo-assistant' ) ],
			'cat'            => 'product',
			'icon'           => $icon,
			'more_info_link' => 'https://parsa.ws',
			'settings_key'   => $this->addonID,
		);
	}
}