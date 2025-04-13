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
		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Menu Cart', 'woo-assistant' ),
			'desc'           => __( 'Add cart icon to menu bar', 'woo-assistant' ),
			'tags'           => [ __( 'Cart', 'woo-assistant' ) ],
			'cat'            => 'cart',
			'more_info_link' => 'https://parsa.ws'
		);
	}
}