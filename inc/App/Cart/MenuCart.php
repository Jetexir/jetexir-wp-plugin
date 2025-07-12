<?php

namespace WooAssistant\App\Cart;

use WooAssistant\Addons\Addon;
use WooAssistant\Helper\WooCommerce;
use WooAssistant\Interfaces\AddonInterface;
use WooAssistant\Settings\Settings;

class MenuCart extends Addon implements AddonInterface {
	public string $addonID = 'menu-cart';
	public string $currentTab = 'cart';
	private static $cart = false;

	public function initAction(): void {
		add_filter( 'wp_nav_menu_items', [ $this, 'addCartToMenu' ], 10, 2 );
	}

	public function wpEnqueueScriptsAction(): void {
		wp_register_style( WOOASSISTANT_PLUGIN_SLUG . '-menu-cart', false );
		wp_enqueue_style( WOOASSISTANT_PLUGIN_SLUG . '-menu-cart' );
		wp_add_inline_style( WOOASSISTANT_PLUGIN_SLUG . '-menu-cart', '.wa-menu-cart a{display: flex !important;column-gap: 5px;align-items: center;}' );
	}

	public function addCartToMenu( $items, $args ) {
		$menus              = Settings::get( 'menu_cart_menus', [] );
		$hideOnCartCheckout = Settings::get( 'menu_cart_cart_checkout_hide', true );
		$HideEmpty          = Settings::get( 'menu_cart_display_empty', true );

		if ( $hideOnCartCheckout && ( WooCommerce::isCart() || WooCommerce::isCheckout() ) ) {
			return $items;
		}

		if ( $HideEmpty && WooCommerce::getCartItemsCount() <= 0 ) {
			return $items;
		}

		if ( in_array( $args->menu->term_taxonomy_id, $menus, true ) ) {
			if ( ! self::$cart ) {
				self::$cart = $this->getMenuCart( $args->menu->slug );
			}

			$items .= self::$cart;
		}

		return $items;
	}

	private function getMenuCart( $menuSlug ): string {
		$icon       = Settings::get( 'menu_cart_icon', 'wa-icon-shopping-cart' );
		$icon       = $icon === 'none' ? '' : FlyCart::getBasketIcons( $icon, true );
		$content    = Settings::get( 'menu_cart_content', 'items-count-price' );
		$priceType  = Settings::get( 'menu_cart_price_type', 'total' );
		$link       = Settings::get( 'menu_cart_link', 'cart' );
		$itemsCount = WooCommerce::getCartItemsCount();
		$count      = '<span class="wa-menu-cart-count">' . $itemsCount . ' ' . __( 'items', 'woo-assistant' ) . '</span>';

		if ( $priceType === 'subtotal' ) {
			$price = WooCommerce::getCartSubTotal();
		} else {
			$price = WooCommerce::getCartTotal();
		}
		$price = '<span class="wa-menu-cart-amount">' . $price . '</span>';

		$attr = '';
		if ( $link === 'fly-cart-modal' ) {
			$url  = '#';
			$attr = 'data-wa-toggle="modal" data-wa-target="#wa-fly-cart-modal"';
		} elseif ( $link === 'checkout' ) {
			$url = WooCommerce::url( 'checkout' );
		} else {
			$url = WooCommerce::url( 'cart' );
		}

		if ( $itemsCount === 0 || $content === 'count' ) {
			$content = $count;
		} elseif ( $content === 'price' ) {
			$content = $price;
		} else {
			$content = $count . ' - ' . $price;
		}

		$output = '<li id="wa-menu-cart-' . $menuSlug . '" class="menu-item wa-menu-cart" >';
		$output .= '<a href="' . $url . '" aria-label="' . __( 'Menu Cart', 'woo-assistant' ) . '" ' . $attr . '>';
		$output .= $icon . $content;
		$output .= '</a></li>';

		return $output;
	}

	public function addSectionSettings( $sections ) {
		$icons       = FlyCart::getBasketIcons();
		$basketIcons = [ 'none' => '-' ];
		foreach ( $icons as $icon ) {
			$basketIcons[ $icon ] = '<i class="' . $icon . '"></i>';
		}
		$sections[ $this->addonID ] = array(
			'title'    => __( 'Menu Cart', 'woo-assistant' ),
			'desc'     => __( 'Menu Cart', 'woo-assistant' ),
			'settings' => [
				'menu_cart_display_start_grid' => array(
					'id'    => 'fly_cart_start_grid_icon',
					'title' => __( 'Menu Cart', 'woo-assistant' ),
					'type'  => 'startGrid',
				),
				'menu_cart_menus'              => array(
					'id'                => 'menu_cart_menus',
					'title'             => __( 'Select the menu(s) to display the Menu Cart', 'woo-assistant' ),
					'type'              => 'menuSelect',
					'multiple'          => true,
					'default'           => 0,
					'option_none'       => '---',
					'option_none_value' => '',
					'sanitize'          => 'array',
					'sanitize_options'  => 'int',
					'attributes'        => array(
						'size' => 5,
					)
				),
				'menu_cart_display_empty'      => array(
					'id'       => 'menu_cart_display_empty',
					'title'    => __( 'Hide empty cart', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => true,
					'sanitize' => 'bool'
				),
				'menu_cart_cart_checkout_hide' => array(
					'id'       => 'menu_cart_cart_checkout_hide',
					'title'    => __( 'Hide on cart & checkout page', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => true,
					'sanitize' => 'bool'
				),
				'menu_cart_display_end_grid'   => array(
					'type' => 'endGrid',
				),
				'menu_cart_content_start_grid' => array(
					'id'    => 'fly_cart_start_grid_icon',
					'title' => __( 'Menu Cart content', 'woo-assistant' ),
					'type'  => 'startGrid',
				),
				'menu_cart_icon'               => array(
					'id'       => 'menu_cart_icon',
					'title'    => __( 'Icon', 'woo-assistant' ),
					'type'     => 'radioInline',
					'default'  => 'wa-icon-shopping-cart',
					'options'  => $basketIcons,
					'sanitize' => 'text'
				),
				'menu_cart_content'            => array(
					'id'       => 'menu_cart_content',
					'title'    => __( 'Menu content', 'woo-assistant' ),
					'type'     => 'select',
					'options'  => array(
						'count'       => __( 'Items count', 'woo-assistant' ),
						'price'       => __( 'Price', 'woo-assistant' ),
						'count-price' => __( 'Items count and price', 'woo-assistant' ),
					),
					'default'  => 'count-price',
					'sanitize' => 'text'
				),
				'menu_cart_price_type'         => array(
					'id'       => 'menu_cart_price_type',
					'title'    => __( 'Price type', 'woo-assistant' ),
					'type'     => 'select',
					'options'  => array(
						'total'    => __( 'Total', 'woo-assistant' ),
						'subtotal' => __( 'Subtotal', 'woo-assistant' ),
					),
					'default'  => 'total',
					'sanitize' => 'text'
				),
				'menu_cart_link'               => array(
					'id'       => 'menu_cart_link',
					'title'    => __( 'Link to', 'woo-assistant' ),
					'type'     => 'select',
					'options'  => array(
						'fly-cart-modal' => __( 'Display Fly Cart modal', 'woo-assistant' ),
						'cart'           => __( 'Cart page', 'woo-assistant' ),
						'checkout'       => __( 'Checkout page', 'woo-assistant' ),
					),
					'default'  => 'cart',
					'sanitize' => 'text'
				),
				'menu_cart_content_end_grid'   => array(
					'type' => 'endGrid',
				),
			]
		);

		return $sections;
	}

	public function info(): array {
		$icon = '<svg fill="#873eff" version="1.1" id="Layer_1" xmlns:x="&amp;ns_extend;" xmlns:i="&amp;ns_ai;" xmlns:graph="&amp;ns_graphs;" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="-2.4 -2.4 28.80 28.80" enable-background="new 0 0 24 24" xml:space="preserve" transform="rotate(90)"><g id="SVGRepo_bgCarrier" stroke-width="0"><path transform="translate(-2.4, -2.4), scale(0.8999999999999999)" d="M16,31.364685096942328C19.972705723191634,31.287791043722077,23.549010166801654,29.165917028319917,26.275359660338694,26.275359660338697C28.91615599135494,23.47550831697567,30.92988568496685,19.823753492102156,30.49184226419996,16C30.084474223170172,12.444016972720618,26.72284702068782,10.338630774361551,24.163079893260466,7.8369201067395355C21.65239567325643,5.383179128150218,19.510518913641796,1.8620768977255568,16,1.836838047732325C12.480268481147702,1.8115329637814712,9.680889514140395,4.796547460009457,7.7186933099563255,7.718693309956324C6.101421191651408,10.127170606295117,6.853784455740706,13.121863435609042,6.489556630963788,15.999999999999998C6.022094776649552,19.69389421630223,3.359365957687479,23.515047852581304,5.319018035755056,26.68098196424494C7.446066852751814,30.117355431898616,11.959346266269309,31.442894323825737,16,31.364685096942328" fill="#fff" strokewidth="0"></path></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <metadata> <sfw xmlns="&amp;ns_sfw;"> <slices> </slices> <slicesourcebounds width="505" height="984" bottomleftorigin="true" x="0" y="-984"> </slicesourcebounds> </sfw> </metadata> <g> <g> <g> <path d="M12,7c-1.7,0-3-1.3-3-3s1.3-3,3-3s3,1.3,3,3S13.7,7,12,7z M12,3c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S12.6,3,12,3z"></path> </g> </g> <g> <g> <path d="M12,23c-1.7,0-3-1.3-3-3s1.3-3,3-3s3,1.3,3,3S13.7,23,12,23z M12,19c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S12.6,19,12,19 z"></path> </g> </g> <g> <g> <path d="M12,15c-1.7,0-3-1.3-3-3s1.3-3,3-3s3,1.3,3,3S13.7,15,12,15z M12,11c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S12.6,11,12,11 z"></path> </g> </g> </g> </g></svg>';

		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Menu Cart', 'woo-assistant' ),
			'desc'           => __( 'Add cart icon to menu bar', 'woo-assistant' ),
			'tags'           => [ __( 'Cart', 'woo-assistant' ) ],
			'cat'            => 'cart',
			'icon'           => $icon,
			'more_info_link' => 'https://parsa.ws'
		);
	}
}