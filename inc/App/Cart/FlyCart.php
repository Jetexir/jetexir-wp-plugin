<?php

namespace Jetexir\App\Cart;

defined( 'ABSPATH' ) || exit;

use Jetexir\Addons\Addon;
use Jetexir\Helper\{Assets, Cache, Nonce, Param, Sanitizing, Templates, User, WooCommerce, WordPress};
use Jetexir\Interfaces\AddonInterface;

class FlyCart extends Addon implements AddonInterface {
  public string $addonID = 'fly-cart';
  public string $currentTab = 'cart';

  public function initAction(): void {
    add_action( 'wp_ajax_jetexir_fly_cart_update', [ $this, 'updateCart' ] );
    add_action( 'wp_ajax_nopriv_jetexir_fly_cart_update', [ $this, 'updateCart' ] );
    add_action( 'wp_ajax_jetexir_fly_cart_items_count', [ $this, 'getCartItemCount' ] );
    add_action( 'wp_ajax_nopriv_jetexir_fly_cart_items_count', [ $this, 'getCartItemCount' ] );
  }

  public function templateRedirectAction(): void {
    if ( ! $this->checkHide() && ( ! WooCommerce::isComingSoon() || User::can( 'manage_options' ) ) ) {
      add_filter( 'jetexir_site_fly_icons', [ $this, 'addFlyIcon' ] );
      add_action( 'jetexir_site_modals', [ $this, 'printCart' ] );
      add_action( 'jetexir_fly_cart_modal_body', [ $this, 'printCartBody' ] );

      if ( $this->getSetting( 'fly_cart_overlay_layer', true ) ) {
        add_filter( 'jetexir_site_modal_overlay', '__return_true' );
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
      'message' => esc_html__( 'Security code is not valid, page will be refreshed.', 'jetexir' ),
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
      'message' => esc_html__( 'Security code is not valid, page will be refreshed.', 'jetexir' ),
      'refresh' => true
    ], 403 );
  }

  public function printCartBody( $echo = true ) {
    $echo = ! is_bool( $echo ) || $echo;

    ob_start();
    echo '<div class="jetexir-loader-wrap" style="display: none"><div class="jetexir-loader"></div></div>';

    if ( ! WordPress::isAjax() && $this->getSetting( 'fly_cart_reload_page_load', true ) ) {
      echo '<p>' . esc_html__( 'Loading...', 'jetexir' ) . '</p>';

    } else {
      $cart = WooCommerce::getCart();

      if ( $cart->is_empty() ) {
        echo '<p>' . esc_html( $this->getSetting( 'fly_cart_empty_message', esc_html__( 'Your cart is currently empty!', 'jetexir' ) ) ) . '</p>';

      } else {
        $itemPrice       = $this->getSetting( 'fly_cart_item_price', 'price' );
        $quantityButtons = $this->getSetting( 'fly_cart_quantity_buttons', true );

        echo '<div class="jetexir-fly-cart-items jetexir-product-list-wrap">';
        $items = $cart->get_cart();

        foreach ( $items as $itemKey => $item ) {
          $item        = (object) $item;
          $productID   = $item->data->get_id();
          $_product    = wc_get_product( $productID );
          $productLink = $_product->get_permalink();
          $name        = wp_strip_all_tags( $_product->get_name() );
          $minValue    = $_product->get_min_purchase_quantity();
          $maxValue    = $_product->get_max_purchase_quantity();
          $buttons     = $quantityButtons && ! ( ( $maxValue && $minValue === $maxValue ) || $_product->is_sold_individually() );

          echo '<div class="jetexir-fly-cart-item jetexir-product-item-wrap" data-item-key="' . esc_html( $itemKey ) . '" data-product-id="' . esc_html( $productID ) . '">';
          echo '<a href="' . esc_url( $productLink ) . '" class="jetexir-fly-cart-item-image jetexir-product-item-image">' . wp_kses_post( $_product->get_image() ) . '</a>';

          echo '<div class="jetexir-fly-cart-item-info jetexir-product-item-info">';
          echo '<a href="' . esc_url( $productLink ) . '" class="jetexir-fly-cart-item-title jetexir-product-item-title">' . esc_html( $name ) . '</a>';
          if ( $itemPrice === 'price' ) {
            echo sprintf( '<div class="jetexir-fly-cart-item-price jetexir-product-item-price">%s</div>', wp_kses_post( $_product->get_price_html() ) );

          } elseif ( $itemPrice === 'subtotal' ) {
            echo sprintf( '<div class="jetexir-fly-cart-item-price jetexir-product-item-price">%s</div>', wp_kses_post( WC()->cart->get_product_subtotal( $_product, $item->quantity ) ) );
          }
          echo '</div>';

          echo '<div class="jetexir-fly-cart-item-actions jetexir-product-item-actions">';

          echo '<div class="jetexir-fly-cart-item-quantity ' . ( $buttons ? 'jetexir-fly-cart-item-quantity-buttons jetexir-appearance-text-field' : '' ) . '">';
          if ( $buttons ) {
            echo '<button type="button" data-action="minus" aria-label="' . esc_html__( 'Reduce quantity', 'jetexir' ) . '">-</button>';
          }

          if ( ( $maxValue && $minValue === $maxValue ) || $_product->is_sold_individually() ) {
            echo '<span class="jetexir-fly-cart-item-quantity-value">' . esc_html( $item->quantity ) . '</span>';
          } else {
            add_filter( 'jetexir_quantity_input_display_plus_minus', '__return_false' );
            $quantity = isset( $item->quantity ) ? wc_stock_amount( $item->quantity ) : $_product->get_min_purchase_quantity();
            woocommerce_quantity_input( [
              'input_name'  => JETEXIR_INPUT_PREFIX . 'quantity_' . $productID,
              'input_value' => $quantity,
              'min_value'   => $minValue,
              'max_value'   => $maxValue,
            ], $_product );
          }

          if ( $buttons ) {
            echo '<button type="button" data-action="plus" aria-label="' . esc_html__( 'Increase quantity', 'jetexir' ) . '">+</button>';
          }

          echo '</div>';

          echo '<a href="#" class="jetexir-fly-cart-item-remove jetexir-flex jetexir-product-item-remove" ><i class="jetexir-icon-cross"></i> ' . esc_html__( 'Remove', 'jetexir' ) . '</a>';
          echo '</div>';

          echo '</div>';
        }

        echo '</div>';

        if ( $this->getSetting( 'fly_cart_subtotal', true ) ) {
          echo '<div class="jetexir-fly-cart-subtotal jetexir-fly-cart-meta jetexir-flex"><span>' . esc_html__( 'Subtotal', 'jetexir' ) . '</span>' . wp_kses_post( $cart->get_cart_subtotal() ) . '</div>';
        }
        if ( $this->getSetting( 'fly_cart_total', true ) ) {
          echo '<div class="jetexir-fly-cart-total jetexir-fly-cart-meta jetexir-flex"><span>' . esc_html__( 'Total', 'jetexir' ) . '</span>' . wp_kses_post( $cart->get_cart_total() ) . '</div>';
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
      'title'       => $this->getSetting( 'fly_cart_title', esc_html__( 'Cart', 'jetexir' ) ),
      'icon'        => self::getBasketIcons( $this->getSetting( 'fly_cart_icon', 'jetexir-icon-shopping-cart' ), true ),
      'count_badge' => WooCommerce::getCartItemsCount(),
      'attributes'  => array(
        'class'               => 'jetexir-fly-cart',
        'href'                => '#',
        'data-jetexir-toggle' => 'modal',
        'data-jetexir-target' => '#jetexir-fly-cart-modal'
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

    /**
     * Filters whether the fly cart should be hidden.
     *
     * @param bool $hide Whether the fly cart should be hidden.
     *
     * @return bool Whether the fly cart should be hidden.
     *
     * @since 1.0
     *
     */
    $hide = (bool) apply_filters( 'jetexir_fly_cart_hide', $hide );

    Cache::set( 'fly_cart_hide', $hide );

    return $hide;
  }

  public function wpEnqueueScriptsAction(): void {
    $pluginVersion = Assets::getVersion();
    $debugName     = JETEXIR_DEBUG_MODE ? '' : '.min';

    wp_enqueue_style( JETEXIR_PLUGIN_KEY . '-fly-cart-style',
      Assets::url( 'css/fly-cart' . $debugName . '.css' ),
      false, $pluginVersion );

    wp_enqueue_script( JETEXIR_PLUGIN_SLUG . '-fly-cart-script',
      Assets::url( 'js/fly-cart.min.js' ),
      [ JETEXIR_PLUGIN_SLUG . '-global' ], $pluginVersion, [ 'in_footer' => true ] );

    wp_localize_script( JETEXIR_PLUGIN_SLUG . '-fly-cart-script', JETEXIR_PLUGIN_KEYCAP . 'FlyCart', array(
      'reloadOnLoad' => Sanitizing::int( $this->getSetting( 'fly_cart_reload_page_load', true ) )
    ) );
  }

  public static function getBasketIcons( $icon = null, $tag = false ) {
    $icons = array(
      'jetexir-icon-shopping-cart',
      'jetexir-icon-shopping-cart1',
      'jetexir-icon-shopping-cart2',
      'jetexir-icon-shopping-cart3',
      'jetexir-icon-shopping-cart4',
      'jetexir-icon-shopping-cart5',
      'jetexir-icon-shopping-cart6',
      'jetexir-icon-shopping-cart7',
      'jetexir-icon-shopping-bag',
      'jetexir-icon-shopping-bag1',
      'jetexir-icon-shopping-basket',
      'jetexir-icon-shopping-basket1',
      'jetexir-icon-shopping-basket2',
      'jetexir-icon-shopping-basket3',
    );

    if ( is_null( $icon ) ) {
      return $icons;
    }

    $icon = in_array( $icon, $icons, true ) ? $icon : 'jetexir-icon-shopping-cart';

    return $tag ? '<i class="' . $icon . '"></i>' : $icon;
  }

  public function addSectionSettings( $sections ) {
    $icons       = self::getBasketIcons();
    $basketIcons = [];
    foreach ( $icons as $icon ) {
      $basketIcons[ $icon ] = '<i class="' . $icon . '"></i>';
    }
    $sections[ $this->addonID ] = array(
      'title'        => esc_html__( 'Fly Cart', 'jetexir' ),
      'desc'         => esc_html__( 'Fly Cart', 'jetexir' ),
      'settings_key' => $this->addonID,
      'settings'     => [
        'fly_cart_start_grid_icon' => array(
          'id'    => 'fly_cart_start_grid_icon',
          'title' => esc_html__( 'Fly Cart Icon', 'jetexir' ),
          'type'  => 'startGrid',
        ),
        'fly_cart_position'        => array(
          'id'       => 'fly_cart_position',
          'title'    => esc_html__( 'Position', 'jetexir' ),
          'type'     => 'select',
          'options'  => array(
            'top-left'     => esc_html__( 'Top Left', 'jetexir' ),
            'top-right'    => esc_html__( 'Top Right', 'jetexir' ),
            'bottom-left'  => esc_html__( 'Bottom Left', 'jetexir' ),
            'bottom-right' => esc_html__( 'Bottom Right', 'jetexir' ),
          ),
          'default'  => 'bottom-left',
          'sanitize' => 'text'
        ),
        'fly_cart_icon'            => array(
          'id'       => 'fly_cart_icon',
          'title'    => esc_html__( 'Icon', 'jetexir' ),
          'type'     => 'radioInline',
          'default'  => 'jetexir-icon-shopping-cart',
          'options'  => $basketIcons,
          'sanitize' => 'text'
        ),
        'fly_cart_title'           => array(
          'id'      => 'fly_cart_title',
          'title'   => esc_html__( 'Title', 'jetexir' ),
          'type'    => 'text',
          'default' => esc_html__( 'Cart', 'jetexir' )
        ),
        'fly_cart_empty_message'   => array(
          'id'      => 'fly_cart_empty_message',
          'title'   => esc_html__( 'Empty shopping cart message', 'jetexir' ),
          'type'    => 'text',
          'default' => esc_html__( 'Your cart is currently empty!', 'jetexir' )
        ),
        'fly_cart_end_grid_icon'   => array(
          'type' => 'endGrid',
        ),

        'fly_cart_start_grid_modal'         => array(
          'id'    => 'fly_cart_start_grid_modal',
          'title' => esc_html__( 'Cart Modal', 'jetexir' ),
          'type'  => 'startGrid',
        ),
        'fly_cart_item_price'               => array(
          'id'       => 'fly_cart_item_price',
          'title'    => esc_html__( 'Product price', 'jetexir' ),
          'type'     => 'select',
          'options'  => array(
            'price'    => esc_html__( 'Price', 'jetexir' ),
            'subtotal' => esc_html__( 'Subtotal', 'jetexir' ),
          ),
          'default'  => 'price',
          'sanitize' => 'text'
        ),
        'fly_cart_quantity_buttons'         => array(
          'id'       => 'fly_cart_quantity_buttons',
          'title'    => esc_html__( 'Quantity plus/minus buttons', 'jetexir' ),
          'type'     => 'toggle',
          'value'    => 1,
          'default'  => true,
          'sanitize' => 'bool'
        ),
        'fly_cart_subtotal'                 => array(
          'id'       => 'fly_cart_subtotal',
          'title'    => esc_html__( 'Subtotal', 'jetexir' ),
          'type'     => 'toggle',
          'value'    => 1,
          'default'  => true,
          'sanitize' => 'bool'
        ),
        'fly_cart_total'                    => array(
          'id'       => 'fly_cart_total',
          'title'    => esc_html__( 'Total', 'jetexir' ),
          'type'     => 'toggle',
          'value'    => 1,
          'default'  => true,
          'sanitize' => 'bool'
        ),
        'start_inline_elements_cart_button' => array(
          'title' => esc_html__( 'Cart button', 'jetexir' ),
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
          'default' => esc_html__( 'Cart', 'jetexir' )
        ),
        'end_inline_elements_cart_button'   => array(
          'type' => 'endInlineElements',
        ),

        'start_inline_elements_checkout_button' => array(
          'title' => esc_html__( 'Checkout button', 'jetexir' ),
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
          'default' => esc_html__( 'Checkout', 'jetexir' )
        ),
        'end_inline_elements_checkout_button'   => array(
          'type' => 'endInlineElements',
        ),
        'fly_cart_reload_page_load'             => array(
          'id'       => 'fly_cart_reload_page_load',
          'title'    => esc_html__( 'Reload cart', 'jetexir' ),
          'desc'     => esc_html__( 'Reload the shopping cart after the page opens.', 'jetexir' ),
          'type'     => 'toggle',
          'value'    => 1,
          'default'  => true,
          'sanitize' => 'bool'
        ),
        'fly_cart_overlay_layer'                => array(
          'id'       => 'fly_cart_overlay_layer',
          'title'    => esc_html__( 'Overlay layer', 'jetexir' ),
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
          'title' => esc_html__( 'Hide on', 'jetexir' ),
          'type'  => 'startGrid',
        ),
        'fly_cart_hide_on_home'     => array(
          'id'       => 'fly_cart_hide_on_home',
          'title'    => esc_html__( 'Home', 'jetexir' ),
          'desc'     => esc_html__( 'Hide on Home page', 'jetexir' ),
          'type'     => 'toggle',
          'value'    => 1,
          'default'  => false,
          'sanitize' => 'bool'
        ),
        'fly_cart_hide_on_blog'     => array(
          'id'       => 'fly_cart_hide_on_blog',
          'title'    => esc_html__( 'Blog', 'jetexir' ),
          'desc'     => esc_html__( 'Hide on Blog page', 'jetexir' ),
          'type'     => 'toggle',
          'value'    => 1,
          'default'  => false,
          'sanitize' => 'bool'
        ),
        'fly_cart_hide_on_posts'    => array(
          'id'       => 'fly_cart_hide_on_posts',
          'title'    => esc_html__( 'Posts', 'jetexir' ),
          'desc'     => esc_html__( 'Hide on Posts', 'jetexir' ),
          'type'     => 'toggle',
          'value'    => 1,
          'default'  => false,
          'sanitize' => 'bool'
        ),
        'fly_cart_hide_on_cart'     => array(
          'id'       => 'fly_cart_hide_on_cart',
          'title'    => esc_html__( 'Cart', 'jetexir' ),
          'desc'     => esc_html__( 'Hide on Cart page', 'jetexir' ),
          'type'     => 'toggle',
          'value'    => 1,
          'default'  => false,
          'sanitize' => 'bool'
        ),
        'fly_cart_hide_on_checkout' => array(
          'id'       => 'fly_cart_hide_on_checkout',
          'title'    => esc_html__( 'Checkout', 'jetexir' ),
          'desc'     => esc_html__( 'Hide on Checkout page', 'jetexir' ),
          'type'     => 'toggle',
          'value'    => 1,
          'default'  => false,
          'sanitize' => 'bool'
        ),
        'fly_cart_hide_on_pages'    => array(
          'id'                => 'fly_cart_hide_on_pages',
          'title'             => esc_html__( 'Hide on Pages', 'jetexir' ),
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
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><g stroke="#873eff" stroke-width="1.5"><path stroke-linecap="round" d="m2 3 .265.088c1.32.44 1.98.66 2.357 1.184S5 5.492 5 6.883V9.5c0 2.828 0 4.243.879 5.121.878.879 2.293.879 5.121.879h8"/><path d="M7.5 18a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM16.5 18a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Z"/><path stroke-linecap="round" d="M11 9H8"/><path d="M5 6h11.45c2.055 0 3.083 0 3.528.674.444.675.04 1.619-.77 3.508l-.429 1c-.378.882-.567 1.322-.942 1.57-.376.248-.856.248-1.815.248H5"/></g></svg>';

    return array(
      'id'             => $this->addonID,
      'title'          => esc_html__( 'Fly Cart', 'jetexir' ),
      'desc'           => esc_html__( 'Floating Cart for WooCommerce', 'jetexir' ),
      'tags'           => [ esc_html__( 'Cart', 'jetexir' ) ],
      'cat'            => 'cart',
      'icon'           => $icon,
      'more_info_link' => '{jetexir_website}/addons/fly-cart',
      'settings_key'   => $this->addonID,
    );
  }
}
