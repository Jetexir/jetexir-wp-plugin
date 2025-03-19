<?php

namespace WooAssistant\App\Cart;

use WooAssistant\Addons\Addon;
use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Cache;
use WooAssistant\Helper\Nonce;
use WooAssistant\Helper\Param;
use WooAssistant\Helper\Sanitizing;
use WooAssistant\Helper\WooCommerce;
use WooAssistant\Helper\WordPress;
use WooAssistant\Interfaces\AddonInterface;
use WooAssistant\Settings\Settings;

class FlyCart extends Addon implements AddonInterface {
	public string $addonID = 'fly-cart';

	public function initAction(): void {
		add_filter( 'woo_assistant_cart_settings_sections', [ $this, 'addSectionSettings' ] );

		add_action( 'wp_ajax_woo_assistant_fly_cart_update', [ $this, 'updateCart' ] );
		add_action( 'wp_ajax_nopriv_woo_assistant_fly_cart_update', [ $this, 'updateCart' ] );
		add_action( 'wp_ajax_woo_assistant_fly_cart_items_count', [ $this, 'getCartItemCount' ] );
		add_action( 'wp_ajax_nopriv_woo_assistant_fly_cart_items_count', [ $this, 'getCartItemCount' ] );
	}

	public function templateRedirectAction(): void {
		$hide = $this->checkHide();
		if ( ! $hide ) {
			add_filter( 'woo_assistant_site_fly_icons', [ $this, 'addFlyIcon' ] );
			add_action( 'woo_assistant_site_modals', [ $this, 'printCart' ] );
			add_action( 'woo_assistant_fly_cart_modal_body', [ $this, 'printCartBody' ] );

			if ( Settings::get( 'fly_cart_overlay_layer', true ) ) {
				add_filter( 'woo_assistant_modal_overlay', '__return_true' );
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

		if ( ! WordPress::isAjax() && Settings::get( 'fly_cart_reload_page_load', true ) ) {
			echo '<p>' . __( 'Loading...', 'woo-assistant' ) . '</p>';

		} else {
			$cart = WooCommerce::getCart();

			if ( $cart->is_empty() ) {
				echo '<p>' . Settings::get( 'fly_cart_empty_message', __( 'Your cart is currently empty!', 'woo-assistant' ) ) . '</p>';

			} else {
				$itemPrice       = Settings::get( 'fly_cart_item_price', 'price' );
				$quantityButtons = Settings::get( 'fly_cart_quantity_buttons', true );

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

				if ( Settings::get( 'fly_cart_subtotal', true ) ) {
					echo '<div class="wa-fly-cart-subtotal wa-fly-cart-meta wa-flex"><span>' . __( 'Subtotal', 'woo-assistant' ) . '</span>' . $cart->get_cart_subtotal() . '</div>';
				}
				if ( Settings::get( 'fly_cart_total', true ) ) {
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
		?>
        <div id="wa-fly-cart-modal" class="wa-modal wa-fade" tabindex="-1"
             aria-labelledby="flyCartModalLabel" aria-hidden="true" style="--wa-modal-border-width:0">
            <div class="wa-modal-dialog">
                <div class="wa-modal-content">
                    <div class="wa-modal-header">
                        <span class="wa-modal-title"
                              id="flyCartModalLabel"><?php _e( 'Cart', 'woo-assistant' ) ?></span>
                        <button type="button" class="wa-button wa-button-close" data-wa-dismiss="modal"
                                aria-label="<?php _e( 'Close', 'woo-assistant' ) ?>"></button>
                    </div>
                    <div class="wa-modal-body">
						<?php do_action( 'woo_assistant_fly_cart_modal_body' ); ?>
                    </div>

					<?php
					$cart     = Settings::get( 'fly_cart_cart_button_enable', true ) && ! WooCommerce::isCart();
					$checkout = Settings::get( 'fly_cart_checkout_button_enable', true ) && ! WooCommerce::isCheckout();

					if ( $cart || $checkout ) {
						echo '<div class="wa-modal-footer">';

						if ( $cart ) {
							echo '<a href="' . wc_get_cart_url() . '" type="button" class="wa-button wa-button-secondary">' .
							     Settings::get( 'fly_cart_cart_button', __( 'Cart', 'woo-assistant' ) )
							     . '</a>';
						}

						if ( $checkout ) {
							echo '<a href="' . wc_get_checkout_url() . '" type="button" class="wa-button wa-button-primary">' .
							     Settings::get( 'fly_cart_checkout_button', __( 'Checkout', 'woo-assistant' ) )
							     . '</a>';
						}

						echo '</div>';
					}
					?>
                </div>
            </div>
        </div>
		<?php
	}

	public function addFlyIcon( $icons ) {
		$icons[] = array(
			'id'          => $this->addonID,
			'tag'         => 'a',
			'title'       => Settings::get( 'fly_cart_title', __( 'Cart', 'woo-assistant' ) ),
			'icon'        => $this->getBasketIcons( Settings::get( 'fly_cart_icon', 'wa-icon-shopping-cart' ), true ),
			'count_badge' => WooCommerce::getCartItemsCount(),
			'attributes'  => array(
				'class'          => 'wa-fly-cart',
				'href'           => '#',
				'data-wa-toggle' => 'modal',
				'data-wa-target' => '#wa-fly-cart-modal'
			),
			'position'    => Settings::get( 'fly_cart_position', 'bottom-left' ),
		);

		return $icons;
	}

	private function checkHide(): bool {
		if ( Cache::get( 'fly_cart_hide' ) ) {
			return true;
		}

		$hide = false;

		if ( Settings::get( 'fly_cart_hide_on_home', false ) && WordPress::isHome() ) {
			$hide = true;
		}

		if ( ! $hide && Settings::get( 'fly_cart_hide_on_blog', false ) && WordPress::isBlog() ) {
			$hide = true;
		}

		if ( ! $hide && Settings::get( 'fly_cart_hide_on_posts', false ) && WordPress::isSingular( 'post' ) ) {
			$hide = true;
		}

		if ( ! $hide && Settings::get( 'fly_cart_hide_on_cart', false ) && WooCommerce::isCart() ) {
			$hide = true;
		}

		if ( ! $hide && Settings::get( 'fly_cart_hide_on_checkout', false ) && WooCommerce::isCheckout() ) {
			$hide = true;
		}

		$pages = Settings::get( 'fly_cart_hide_on_pages', [] );
		if ( ! $hide && ! empty( $pages ) && WordPress::isPage( $pages ) ) {
			$hide = true;
		}

		$hide = apply_filters( 'woo_assistant_fly_cart_hide', $hide );


		Cache::set( 'fly_cart_hide', $hide );

		return $hide;
	}

	private function getBasketIcons( $icon = null, $tag = false ) {
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
		$icons       = $this->getBasketIcons();
		$basketIcons = [];
		foreach ( $icons as $icon ) {
			$basketIcons[ $icon ] = '<i class="' . $icon . '"></i>';
		}
		$sections[ $this->addonID ] = array(
			'title'    => __( 'Fly Cart', 'woo-assistant' ),
			'desc'     => __( 'Fly Cart', 'woo-assistant' ),
			'settings' => [
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
					'title'    => __( 'Reload the cart', 'woo-assistant' ),
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
		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Fly Cart', 'woo-assistant' ),
			'desc'           => __( 'Float Cart for WooCommerce', 'woo-assistant' ),
			'tags'           => [ __( 'Cart', 'woo-assistant' ) ],
			'cat'            => 'cart',
			'more_info_link' => 'https://parsa.ws'
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
			'reloadOnLoad' => Sanitizing::int( Settings::get( 'fly_cart_reload_page_load', true ) )
		) );
	}
}