<?php

namespace WooAssistant\App\Cart;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Addons\Addon;
use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Cache;
use WooAssistant\Helper\Nonce;
use WooAssistant\Helper\Param;
use WooAssistant\Helper\Sanitizing;
use WooAssistant\Helper\Templates;
use WooAssistant\Helper\WooCommerce;
use WooAssistant\Helper\WordPress;
use WooAssistant\Interfaces\AddonInterface;

class FlyCart extends Addon implements AddonInterface {
	public string $addonID = 'fly-cart';
	public string $currentTab = 'cart';

	public function initAction(): void {
		add_action( 'wp_ajax_woo_assistant_fly_cart_update', [ $this, 'updateCart' ] );
		add_action( 'wp_ajax_nopriv_woo_assistant_fly_cart_update', [ $this, 'updateCart' ] );
		add_action( 'wp_ajax_woo_assistant_fly_cart_items_count', [ $this, 'getCartItemCount' ] );
		add_action( 'wp_ajax_nopriv_woo_assistant_fly_cart_items_count', [ $this, 'getCartItemCount' ] );
	}

	public function templateRedirectAction(): void {
		if ( ! $this->checkHide() ) {
			add_filter( 'woo_assistant_site_fly_icons', [ $this, 'addFlyIcon' ] );
			add_action( 'woo_assistant_site_modals', [ $this, 'printCart' ] );
			add_action( 'woo_assistant_fly_cart_modal_body', [ $this, 'printCartBody' ] );

			if ( $this->getSetting( 'fly_cart_overlay_layer', true ) ) {
				add_filter( 'woo_assistant_site_modal_overlay', '__return_true' );
			}
		}
	}

	public function getCartItemCount(): void {
		if ( Nonce::verify() ) {
			wp_send_json_success( [
				'cart_items_count' => WooCommerce::getCartItemsCount()
			] );
		}

		wp_send_json_error( [
			'error'   => 'nonce-invalid',
			'message' => __( 'Security code is not valid, page will be refreshed.', 'woo-assistant' ),
			'refresh' => true
		], 403 );
	}

	public function updateCart(): void {
		if ( Nonce::verify() ) {
			$action   = Sanitizing::text( Param::post( 'cart_action' ) );
			$itemKey  = Sanitizing::text( Param::post( 'item_key' ) );
			$quantity = max( Sanitizing::int( Param::post( 'item_qty' ) ), 1 );

			if ( $action === 'remove' ) {
				WooCommerce::removeCartItem( $itemKey );

			} elseif ( $action === 'quantity' ) {
				WC()->cart->set_quantity( $itemKey, $quantity );
				WC()->cart->calculate_totals();
			}

			wp_send_json_success( [
				'cart'             => $this->printCartBody( false ),
				'cart_items_count' => WooCommerce::getCartItemsCount()
			] );
		}

		wp_send_json_error( [
			'error'   => 'nonce-invalid',
			'message' => __( 'Security code is not valid, page will be refreshed.', 'woo-assistant' ),
			'refresh' => true
		], 403 );
	}

	public function printCartBody( $echo = true ) {
		$echo = ! is_bool( $echo ) || $echo;

		ob_start();
		echo '<div class="wa-loader-wrap" style="display: none"><div class="wa-loader"></div></div>';

		if ( ! WordPress::isAjax() && $this->getSetting( 'fly_cart_reload_page_load', true ) ) {
			echo '<p>' . __( 'Loading...', 'woo-assistant' ) . '</p>';

		} else {
			$cart = WooCommerce::getCart();

			if ( $cart->is_empty() ) {
				echo '<p>' . $this->getSetting( 'fly_cart_empty_message', __( 'Your cart is currently empty!', 'woo-assistant' ) ) . '</p>';

			} else {
				$itemPrice       = $this->getSetting( 'fly_cart_item_price', 'price' );
				$quantityButtons = $this->getSetting( 'fly_cart_quantity_buttons', true );

				echo '<div class="wa-fly-cart-items wa-product-list-wrap">';
				$items = $cart->get_cart();

				foreach ( $items as $itemKey => $item ) {
					$item        = (object) $item;
					$productID   = $item->data->get_id();
					$_product    = wc_get_product( $productID );
					$productLink = $_product->get_permalink();
					$name        = wp_strip_all_tags( $_product->get_name() );
					$minValue    = apply_filters( 'woocommerce_quantity_input_min', $_product->get_min_purchase_quantity(), $_product );
					$maxValue    = apply_filters( 'woocommerce_quantity_input_max', $_product->get_max_purchase_quantity(), $_product );
					$buttons     = $quantityButtons && ! ( ( $maxValue && $minValue === $maxValue ) || $_product->is_sold_individually() );

					echo '<div class="wa-fly-cart-item wa-product-item-wrap" data-item-key="' . $itemKey . '" data-product-id="' . $productID . '">';
					echo '<a href="' . $productLink . '" class="wa-fly-cart-item-image wa-product-item-image">' . $_product->get_image() . '</a>';

					echo '<div class="wa-fly-cart-item-info wa-product-item-info">';
					echo '<a href="' . $productLink . '" class="wa-fly-cart-item-title wa-product-item-title">' . $name . '</a>';
					if ( $itemPrice === 'price' ) {
						echo sprintf( '<div class="wa-fly-cart-item-price wa-product-item-price">%s</div>', $_product->get_price_html() );
					} elseif ( $itemPrice === 'subtotal' ) {
						echo sprintf( '<div class="wa-fly-cart-item-price wa-product-item-price">%s</div>', WC()->cart->get_product_subtotal( $_product, $item->quantity ) );
					}
					echo '</div>';

					echo '<div class="wa-fly-cart-item-actions wa-product-item-actions">';

					echo '<div class="wa-fly-cart-item-quantity ' . ( $buttons ? 'wa-fly-cart-item-quantity-buttons wa-appearance-text-field' : '' ) . '">';
					if ( $buttons ) {
						echo '<button type="button" data-action="minus" aria-label="' . __( 'Reduce quantity', 'woo-assistant' ) . '">-</button>';
					}

					if ( ( $maxValue && $minValue === $maxValue ) || $_product->is_sold_individually() ) {
						echo '<span class="wa-fly-cart-item-quantity-value">' . $item->quantity . '</span>';
					} else {
						add_filter( 'woo_assistant_quantity_input_display_plus_minus', '__return_false' );
						$quantity = isset( $item->quantity ) ? wc_stock_amount( $item->quantity ) : $_product->get_min_purchase_quantity();
						woocommerce_quantity_input( [
							'input_name'  => WOOASSISTANT_INPUT_PREFIX . 'quantity_' . $productID,
							'input_value' => $quantity,
							'min_value'   => $minValue,
							'max_value'   => $maxValue,
						], $_product );
					}

					if ( $buttons ) {
						echo '<button type="button" data-action="plus" aria-label="' . __( 'Increase quantity', 'woo-assistant' ) . '">+</button>';
					}

					echo '</div>';

					echo '<a href="#" class="wa-fly-cart-item-remove wa-flex wa-product-item-remove" ><i class="wa-icon-cross"></i> ' . __( 'Remove', 'woo-assistant' ) . '</a>';
					echo '</div>';

					echo '</div>';
				}

				echo '</div>';

				if ( $this->getSetting( 'fly_cart_subtotal', true ) ) {
					echo '<div class="wa-fly-cart-subtotal wa-fly-cart-meta wa-flex"><span>' . __( 'Subtotal', 'woo-assistant' ) . '</span>' . $cart->get_cart_subtotal() . '</div>';
				}
				if ( $this->getSetting( 'fly_cart_total', true ) ) {
					echo '<div class="wa-fly-cart-total wa-fly-cart-meta wa-flex"><span>' . __( 'Total', 'woo-assistant' ) . '</span>' . $cart->get_cart_total() . '</div>';
				}
			}
		}

		if ( $echo ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo ob_get_clean();
		} else {
			return ob_get_clean();
		}
	}

	public function printCart(): void {
		Templates::load( Templates::getPath( 'fly-cart/fly_cart_modal.php' ) );
	}

	public function addFlyIcon( $icons ) {
		$icons[] = array(
			'id'          => $this->addonID,
			'tag'         => 'a',
			'title'       => $this->getSetting( 'fly_cart_title', __( 'Cart', 'woo-assistant' ) ),
			'icon'        => self::getBasketIcons( $this->getSetting( 'fly_cart_icon', 'wa-icon-shopping-cart' ), true ),
			'count_badge' => WooCommerce::getCartItemsCount(),
			'attributes'  => array(
				'class'          => 'wa-fly-cart',
				'href'           => '#',
				'data-wa-toggle' => 'modal',
				'data-wa-target' => '#wa-fly-cart-modal'
			),
			'position'    => $this->getSetting( 'fly_cart_position', 'bottom-left' ),
		);

		return $icons;
	}

	private function checkHide(): bool {
		if ( Cache::get( 'fly_cart_hide', false ) ) {
			return true;
		}

		$hide = false;

		if ( $this->getSetting( 'fly_cart_hide_on_home', false ) && WordPress::isHome() ) {
			$hide = true;
		}

		if ( ! $hide && $this->getSetting( 'fly_cart_hide_on_blog', false ) && WordPress::isBlog() ) {
			$hide = true;
		}

		if ( ! $hide && $this->getSetting( 'fly_cart_hide_on_posts', false ) && WordPress::isSingular( 'post' ) ) {
			$hide = true;
		}

		if ( ! $hide && $this->getSetting( 'fly_cart_hide_on_cart', false ) && WooCommerce::isCart() ) {
			$hide = true;
		}

		if ( ! $hide && $this->getSetting( 'fly_cart_hide_on_checkout', false ) && WooCommerce::isCheckout() ) {
			$hide = true;
		}

		$pages = $this->getSetting( 'fly_cart_hide_on_pages', [] );
		if ( ! $hide && ! empty( $pages ) && WordPress::isPage( $pages ) ) {
			$hide = true;
		}

		$hide = apply_filters( 'woo_assistant_fly_cart_hide', $hide );

		Cache::set( 'fly_cart_hide', $hide );

		return $hide;
	}

	public static function getBasketIcons( $icon = null, $tag = false ) {
		$icons = array(
			'wa-icon-shopping-cart',
			'wa-icon-shopping-cart1',
			'wa-icon-shopping-cart2',
			'wa-icon-shopping-cart3',
			'wa-icon-shopping-cart4',
			'wa-icon-shopping-cart5',
			'wa-icon-shopping-cart6',
			'wa-icon-shopping-cart7',
			'wa-icon-shopping-bag',
			'wa-icon-shopping-bag1',
			'wa-icon-shopping-basket',
			'wa-icon-shopping-basket1',
			'wa-icon-shopping-basket2',
			'wa-icon-shopping-basket3',
		);

		if ( is_null( $icon ) ) {
			return $icons;
		}

		$icon = in_array( $icon, $icons, true ) ? $icon : 'wa-icon-shopping-cart';

		return $tag ? '<i class="' . $icon . '"></i>' : $icon;
	}

	public function addSectionSettings( $sections ) {
		$icons       = self::getBasketIcons();
		$basketIcons = [];
		foreach ( $icons as $icon ) {
			$basketIcons[ $icon ] = '<i class="' . $icon . '"></i>';
		}
		$sections[ $this->addonID ] = array(
			'title'        => __( 'Fly Cart', 'woo-assistant' ),
			'desc'         => __( 'Fly Cart', 'woo-assistant' ),
			'settings_key' => $this->addonID,
			'settings'     => [
				'fly_cart_start_grid_icon' => array(
					'id'    => 'fly_cart_start_grid_icon',
					'title' => __( 'Fly Cart Icon', 'woo-assistant' ),
					'type'  => 'startGrid',
				),
				'fly_cart_position'        => array(
					'id'       => 'fly_cart_position',
					'title'    => __( 'Position', 'woo-assistant' ),
					'type'     => 'select',
					'options'  => array(
						'top-left'     => __( 'Top Left', 'woo-assistant' ),
						'top-right'    => __( 'Top Right', 'woo-assistant' ),
						'bottom-left'  => __( 'Bottom Left', 'woo-assistant' ),
						'bottom-right' => __( 'Bottom Right', 'woo-assistant' ),
					),
					'default'  => 'bottom-left',
					'sanitize' => 'text'
				),
				'fly_cart_icon'            => array(
					'id'       => 'fly_cart_icon',
					'title'    => __( 'Icon', 'woo-assistant' ),
					'type'     => 'radioInline',
					'default'  => 'wa-icon-shopping-cart',
					'options'  => $basketIcons,
					'sanitize' => 'text'
				),
				'fly_cart_title'           => array(
					'id'      => 'fly_cart_title',
					'title'   => __( 'Title', 'woo-assistant' ),
					'type'    => 'text',
					'default' => __( 'Cart', 'woo-assistant' )
				),
				'fly_cart_empty_message'   => array(
					'id'      => 'fly_cart_empty_message',
					'title'   => __( 'Empty message', 'woo-assistant' ),
					'type'    => 'text',
					'default' => __( 'Your cart is currently empty!', 'woo-assistant' )
				),
				'fly_cart_end_grid_icon'   => array(
					'type' => 'endGrid',
				),

				'fly_cart_start_grid_modal'         => array(
					'id'    => 'fly_cart_start_grid_modal',
					'title' => __( 'Cart Modal', 'woo-assistant' ),
					'type'  => 'startGrid',
				),
				'fly_cart_item_price'               => array(
					'id'       => 'fly_cart_item_price',
					'title'    => __( 'Item price', 'woo-assistant' ),
					'type'     => 'select',
					'options'  => array(
						'price'    => __( 'Price', 'woo-assistant' ),
						'subtotal' => __( 'Subtotal', 'woo-assistant' ),
					),
					'default'  => 'price',
					'sanitize' => 'text'
				),
				'fly_cart_quantity_buttons'         => array(
					'id'       => 'fly_cart_quantity_buttons',
					'title'    => __( 'Quantity plus/minus buttons', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => true,
					'sanitize' => 'bool'
				),
				'fly_cart_subtotal'                 => array(
					'id'       => 'fly_cart_subtotal',
					'title'    => __( 'Subtotal', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => true,
					'sanitize' => 'bool'
				),
				'fly_cart_total'                    => array(
					'id'       => 'fly_cart_total',
					'title'    => __( 'Total', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => true,
					'sanitize' => 'bool'
				),
				'start_inline_elements_cart_button' => array(
					'title' => __( 'Cart button', 'woo-assistant' ),
					'type'  => 'startInlineElements',
				),
				'fly_cart_cart_button_enable'       => array(
					'id'       => 'fly_cart_cart_button_enable',
					'type'     => 'checkbox',
					'value'    => 1,
					'default'  => true,
					'sanitize' => 'bool'
				),
				'fly_cart_cart_button'              => array(
					'id'      => 'fly_cart_cart_button',
					'type'    => 'text',
					'default' => __( 'Cart', 'woo-assistant' )
				),
				'end_inline_elements_cart_button'   => array(
					'type' => 'endInlineElements',
				),

				'start_inline_elements_checkout_button' => array(
					'title' => __( 'Checkout button', 'woo-assistant' ),
					'type'  => 'startInlineElements',
				),
				'fly_cart_checkout_button_enable'       => array(
					'id'       => 'fly_cart_checkout_button_enable',
					'type'     => 'checkbox',
					'value'    => 1,
					'default'  => true,
					'sanitize' => 'bool'
				),
				'fly_cart_checkout_button'              => array(
					'id'      => 'fly_cart_checkout_button',
					'type'    => 'text',
					'default' => __( 'Checkout', 'woo-assistant' )
				),
				'end_inline_elements_checkout_button'   => array(
					'type' => 'endInlineElements',
				),
				'fly_cart_reload_page_load'             => array(
					'id'       => 'fly_cart_reload_page_load',
					'title'    => __( 'Reload cart', 'woo-assistant' ),
					'desc'     => __( 'Reload the shopping cart after the page opens.', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => true,
					'sanitize' => 'bool'
				),
				'fly_cart_overlay_layer'                => array(
					'id'       => 'fly_cart_overlay_layer',
					'title'    => __( 'Overlay layer', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => true,
					'sanitize' => 'bool'
				),
				'fly_cart_end_grid_modal'               => array(
					'type' => 'endGrid',
				),

				'fly_cart_start_grid_hide'  => array(
					'id'    => 'fly_cart_start_grid_icon',
					'title' => __( 'Hide on', 'woo-assistant' ),
					'type'  => 'startGrid',
				),
				'fly_cart_hide_on_home'     => array(
					'id'       => 'fly_cart_hide_on_home',
					'title'    => __( 'Home', 'woo-assistant' ),
					'desc'     => __( 'Hide on Home page', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => false,
					'sanitize' => 'bool'
				),
				'fly_cart_hide_on_blog'     => array(
					'id'       => 'fly_cart_hide_on_blog',
					'title'    => __( 'Blog', 'woo-assistant' ),
					'desc'     => __( 'Hide on Blog page', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => false,
					'sanitize' => 'bool'
				),
				'fly_cart_hide_on_posts'    => array(
					'id'       => 'fly_cart_hide_on_posts',
					'title'    => __( 'Posts', 'woo-assistant' ),
					'desc'     => __( 'Hide on Posts', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => false,
					'sanitize' => 'bool'
				),
				'fly_cart_hide_on_cart'     => array(
					'id'       => 'fly_cart_hide_on_cart',
					'title'    => __( 'Cart', 'woo-assistant' ),
					'desc'     => __( 'Hide on Cart page', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => false,
					'sanitize' => 'bool'
				),
				'fly_cart_hide_on_checkout' => array(
					'id'       => 'fly_cart_hide_on_checkout',
					'title'    => __( 'Checkout', 'woo-assistant' ),
					'desc'     => __( 'Hide on Checkout page', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => false,
					'sanitize' => 'bool'
				),
				'fly_cart_hide_on_pages'    => array(
					'id'                => 'fly_cart_hide_on_pages',
					'title'             => __( 'Hide on Pages', 'woo-assistant' ),
					'type'              => 'postSelect',
					'args'              => array(
						'post_type' => 'page'
					),
					'attributes'        => array( 'size' => 6 ),
					'multiple'          => true,
					'default'           => [],
					'option_none'       => '---',
					'option_none_value' => ''
				),
				'fly_cart_end_grid_hide'    => array(
					'type' => 'endGrid',
				),
			]
		);

		return $sections;
	}

	public function info(): array {
		$icon = '<svg width="256px" height="256px" viewBox="-2.5 -2.5 30.00 30.00" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"><path transform="translate(-2.5, -2.5), scale(0.9375)" d="M16,26.40257133357227C18.124473843070575,27.054614228404635,20.434420403807877,26.904077983108195,22.527190231152233,26.156506249861465C24.962415616630445,25.28660377015182,28.08133395502512,24.32639092330043,28.781124365402906,21.836943919681797C29.50303037686956,19.268822478620983,26.111123136204675,17.240923230409777,25.77737931798854,14.594225081917873C25.433628244647053,11.868166111843314,28.816087760725782,8.519134972922934,26.85863132825425,6.590942393401061C24.825567502492362,4.58827274645583,21.261926632628064,8.208073806462323,18.513696963111393,7.43913433029687C15.82786088450301,6.687652236111263,14.734956728086216,1.8087838289828604,12.0180199415162,2.438621329310239C9.375199724720447,3.0512772024204464,9.672971730868316,7.159781866747922,8.73607451873602,9.705772740859516C8.122429648342123,11.373334795412454,8.316042640128236,13.207701180560917,7.478444509368154,14.774785289373455C5.964246548538261,17.60773760250672,0.46714302752342607,19.566311883842708,1.9398149604552835,22.421071357252558C3.4514799688784654,25.351418508519917,8.45097574055869,21.911567887960345,11.623509724405986,22.809951795855948C13.477045554116078,23.334827737484197,14.158369766846198,25.837338717339716,16,26.40257133357227" fill="#ffffff" strokewidth="0"></path></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M9.76106 18.094C9.7639 18.4639 9.54326 18.7989 9.20233 18.9425C8.8614 19.086 8.46756 19.0096 8.20499 18.7491C7.94242 18.4885 7.86301 18.0953 8.00391 17.7532C8.1448 17.4112 8.47815 17.188 8.84806 17.188C9.35025 17.1863 9.75885 17.5918 9.76106 18.094V18.094Z" stroke="#873eff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path fill-rule="evenodd" clip-rule="evenodd" d="M15.239 18.094C15.2418 18.4639 15.0212 18.7989 14.6802 18.9425C14.3393 19.086 13.9455 19.0096 13.6829 18.7491C13.4203 18.4885 13.3409 18.0953 13.4818 17.7532C13.6227 17.4112 13.9561 17.188 14.326 17.188C14.8282 17.1863 15.2368 17.5918 15.239 18.094V18.094Z" stroke="#873eff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M7.5 6.86901V6.11901C7.08579 6.11901 6.75 6.45479 6.75 6.86901H7.5ZM20.182 7.74901L20.9259 7.65341C20.9244 7.64153 20.9225 7.62969 20.9205 7.6179L20.182 7.74901ZM20.103 8.38901L19.4047 8.11538C19.4004 8.12632 19.3964 8.13736 19.3926 8.14849L20.103 8.38901ZM19.456 10.3L20.1516 10.5803C20.1569 10.5672 20.1619 10.5539 20.1664 10.5405L19.456 10.3ZM18.283 13.211L18.9528 13.5485C18.9622 13.5298 18.9708 13.5107 18.9786 13.4913L18.283 13.211ZM9.935 15.325V14.575L9.9319 14.575L9.935 15.325ZM8.21698 14.6215L7.68873 15.1539L7.68873 15.1539L8.21698 14.6215ZM7.5 12.909H6.74999L6.75001 12.9118L7.5 12.909ZM6.75 6.86901C6.75 7.28322 7.08579 7.61901 7.5 7.61901C7.91421 7.61901 8.25 7.28322 8.25 6.86901H6.75ZM7.5 4.60001H8.25004L8.24996 4.59259L7.5 4.60001ZM6.891 4.00001V4.75002L6.89475 4.75L6.891 4.00001ZM5.5 3.25001C5.08579 3.25001 4.75 3.58579 4.75 4.00001C4.75 4.41422 5.08579 4.75001 5.5 4.75001V3.25001ZM7.5 7.61901H17.773V6.11901H7.5V7.61901ZM17.773 7.61901C18.4551 7.61901 18.8841 7.59978 19.2042 7.67274C19.3405 7.70383 19.3844 7.73889 19.3941 7.74792C19.3985 7.75199 19.4041 7.75803 19.4114 7.77217C19.4193 7.78768 19.433 7.82042 19.4435 7.88012L20.9205 7.6179C20.8533 7.23945 20.6912 6.90513 20.4142 6.64823C20.1471 6.40038 19.8289 6.27669 19.5376 6.21027C19.0024 6.08824 18.3139 6.11901 17.773 6.11901V7.61901ZM19.4381 7.84461C19.4499 7.93624 19.4384 8.02937 19.4047 8.11538L20.8013 8.66263C20.9269 8.34202 20.9698 7.99494 20.9259 7.65341L19.4381 7.84461ZM19.3926 8.14849L18.7456 10.0595L20.1664 10.5405L20.8134 8.62952L19.3926 8.14849ZM18.7604 10.0197L17.5874 12.9307L18.9786 13.4913L20.1516 10.5803L18.7604 10.0197ZM17.6132 12.8735C17.3822 13.3321 17.2111 13.6679 17.0595 13.9264C16.9075 14.1857 16.8017 14.3197 16.7182 14.3969C16.6109 14.4962 16.4818 14.575 15.848 14.575V16.075C16.5592 16.075 17.1911 16.0029 17.7368 15.4981C17.9798 15.2733 18.1735 14.9921 18.3535 14.6851C18.5339 14.3774 18.7273 13.9959 18.9528 13.5485L17.6132 12.8735ZM15.848 14.575H9.935V16.075H15.848V14.575ZM9.9319 14.575C9.48754 14.5769 9.06067 14.402 8.74523 14.0891L7.68873 15.1539C8.28665 15.7471 9.09581 16.0785 9.9381 16.075L9.9319 14.575ZM8.74523 14.0891C8.42979 13.7761 8.25164 13.3506 8.24999 12.9062L6.75001 12.9118C6.75312 13.7541 7.09081 14.5606 7.68873 15.1539L8.74523 14.0891ZM8.25 12.909V6.86901H6.75V12.909H8.25ZM8.25 6.86901V4.60001H6.75V6.86901H8.25ZM8.24996 4.59259C8.24258 3.84632 7.63355 3.24629 6.88725 3.25002L6.89475 4.75C6.8155 4.75039 6.75082 4.68667 6.75004 4.60742L8.24996 4.59259ZM6.891 3.25001H5.5V4.75001H6.891V3.25001Z" fill="#873eff"></path> </g></svg>';

		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Fly Cart', 'woo-assistant' ),
			'desc'           => __( 'Floating Cart for WooCommerce', 'woo-assistant' ),
			'tags'           => [ __( 'Cart', 'woo-assistant' ) ],
			'cat'            => 'cart',
			'icon'           => $icon,
			'more_info_link' => 'https://parsa.ws',
			'settings_key'   => $this->addonID,
		);
	}

	public function wpEnqueueScriptsAction(): void {
		$pluginVersion = Assets::getVersion();
		$debugName     = WOOASSISTANT_DEBUG_MODE ? '' : '.min';

		wp_enqueue_style( WOOASSISTANT_PLUGIN_KEY . '-fly-cart-style',
			Assets::url( 'css/fly-cart' . $debugName . '.css' ),
			false, $pluginVersion );

		wp_enqueue_script( WOOASSISTANT_PLUGIN_SLUG . '-fly-cart-script',
			Assets::url( 'js/fly-cart.min.js' ),
			[ WOOASSISTANT_PLUGIN_SLUG . '-global' ], $pluginVersion, [ 'in_footer' => true ] );

		wp_localize_script( WOOASSISTANT_PLUGIN_SLUG . '-fly-cart-script', WOOASSISTANT_PLUGIN_KEYCAP . 'FlyCart', array(
			'reloadOnLoad' => Sanitizing::int( $this->getSetting( 'fly_cart_reload_page_load', true ) )
		) );
	}
}