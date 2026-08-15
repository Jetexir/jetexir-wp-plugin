<?php

namespace Jetexir\App\Cart;

defined( 'ABSPATH' ) || exit;

use Jetexir\Addons\Addon;
use Jetexir\Helper\Assets;
use Jetexir\Helper\WooCommerce;
use Jetexir\Interfaces\AddonInterface;

class MenuCart extends Addon implements AddonInterface {
  public string $addonID = 'menu-cart';
  public string $currentTab = 'cart';
  private static $cart = false;

  public function initAction(): void {
    add_filter( 'wp_nav_menu_items', [ $this, 'addCartToMenu' ], 10, 2 );
  }

  public function wpEnqueueScriptsAction(): void {
    if ( ! $this->getSetting( 'menu_cart_load_styles', true ) ) {
      return;
    }

    wp_register_style( JETEXIR_PLUGIN_SLUG . '-menu-cart', false, [], Assets::getVersion() );
    wp_enqueue_style( JETEXIR_PLUGIN_SLUG . '-menu-cart' );

    $styles = '.jetexir-menu-cart a{display: inline-flex !important;column-gap: 5px;align-items: center;}';
    wp_add_inline_style( JETEXIR_PLUGIN_SLUG . '-menu-cart', esc_html( $styles ) );
  }

  public function addCartToMenu( $items, $args ) {
    $menus              = $this->getSetting( 'menu_cart_menus', [] );
    $hideOnCartCheckout = $this->getSetting( 'menu_cart_cart_checkout_hide', true );
    $HideEmpty          = $this->getSetting( 'menu_cart_display_empty', true );

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
    $icon       = $this->getSetting( 'menu_cart_icon', 'jetexir-icon-shopping-cart' );
    $icon       = $icon === 'none' ? '' : FlyCart::getBasketIcons( $icon, true );
    $content    = $this->getSetting( 'menu_cart_content', 'items-count-price' );
    $priceType  = $this->getSetting( 'menu_cart_price_type', 'total' );
    $link       = $this->getSetting( 'menu_cart_link', 'cart' );
    $itemsCount = WooCommerce::getCartItemsCount();
    $count      = '<span class="jetexir-menu-cart-count">' . $itemsCount . ' ' . esc_html__( 'items', 'jetexir' ) . '</span>';

    if ( $priceType === 'subtotal' ) {
      $price = WooCommerce::getCartSubTotal();
    } else {
      $price = WooCommerce::getCartTotal();
    }
    $price = '<span class="jetexir-menu-cart-amount">' . $price . '</span>';

    $attr = '';
    if ( $link === 'fly-cart-modal' ) {
      $url  = '#';
      $attr = 'data-jetexir-toggle="modal" data-jetexir-target="#jetexir-fly-cart-modal"';
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

    $output = '<li id="jetexir-menu-cart-' . $menuSlug . '" class="menu-item jetexir-menu-cart" >';
    $output .= '<a href="' . $url . '" aria-label="' . esc_html__( 'Menu Cart', 'jetexir' ) . '" ' . $attr . '>';
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
      'title'        => esc_html__( 'Menu Cart', 'jetexir' ),
      'desc'         => esc_html__( 'Menu Cart', 'jetexir' ),
      'settings_key' => $this->addonID,
      'settings'     => [
        'menu_cart_display_start_grid' => array(
          'id'    => 'fly_cart_start_grid_icon',
          'title' => esc_html__( 'Menu Cart', 'jetexir' ),
          'type'  => 'startGrid',
        ),
        'menu_cart_menus'              => array(
          'id'                => 'menu_cart_menus',
          'title'             => esc_html__( 'Select the menu(s) to display the Menu Cart', 'jetexir' ),
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
          'title'    => esc_html__( 'Hide empty cart', 'jetexir' ),
          'type'     => 'toggle',
          'value'    => 1,
          'default'  => true,
          'sanitize' => 'bool'
        ),
        'menu_cart_cart_checkout_hide' => array(
          'id'       => 'menu_cart_cart_checkout_hide',
          'title'    => esc_html__( 'Hide on cart & checkout page', 'jetexir' ),
          'type'     => 'toggle',
          'value'    => 1,
          'default'  => true,
          'sanitize' => 'bool'
        ),
        'menu_cart_load_styles'        => array(
          'id'       => 'menu_cart_load_styles',
          'title'    => esc_html__( 'Add menu styles', 'jetexir' ),
          'desc'     => esc_html__( 'Styles to better display the menu', 'jetexir' ),
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
          'title' => esc_html__( 'Menu Cart content', 'jetexir' ),
          'type'  => 'startGrid',
        ),
        'menu_cart_icon'               => array(
          'id'       => 'menu_cart_icon',
          'title'    => esc_html__( 'Icon', 'jetexir' ),
          'type'     => 'radioInline',
          'default'  => 'jetexir-icon-shopping-cart',
          'options'  => $basketIcons,
          'sanitize' => 'text'
        ),
        'menu_cart_content'            => array(
          'id'       => 'menu_cart_content',
          'title'    => esc_html__( 'Menu content', 'jetexir' ),
          'type'     => 'select',
          'options'  => array(
            'count'       => esc_html__( 'Products count', 'jetexir' ),
            'price'       => esc_html__( 'Price', 'jetexir' ),
            'count-price' => esc_html__( 'Products count and price', 'jetexir' ),
          ),
          'default'  => 'count-price',
          'sanitize' => 'text'
        ),
        'menu_cart_price_type'         => array(
          'id'       => 'menu_cart_price_type',
          'title'    => esc_html__( 'Price type', 'jetexir' ),
          'type'     => 'select',
          'options'  => array(
            'total'    => esc_html__( 'Total', 'jetexir' ),
            'subtotal' => esc_html__( 'Subtotal', 'jetexir' ),
          ),
          'default'  => 'total',
          'sanitize' => 'text'
        ),
        'menu_cart_link'               => array(
          'id'       => 'menu_cart_link',
          'title'    => esc_html__( 'Link to', 'jetexir' ),
          'type'     => 'select',
          'options'  => array(
            'fly-cart-modal' => esc_html__( 'Display Fly Cart modal', 'jetexir' ),
            'cart'           => esc_html__( 'Cart page', 'jetexir' ),
            'checkout'       => esc_html__( 'Checkout page', 'jetexir' ),
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
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><g stroke="#873eff" stroke-width="1.5"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></g></svg>';

    return array(
      'id'             => $this->addonID,
      'title'          => esc_html__( 'Menu Cart', 'jetexir' ),
      'desc'           => esc_html__( 'Add a shopping cart icon to the menu bar.', 'jetexir' ),
      'tags'           => [ esc_html__( 'Cart', 'jetexir' ) ],
      'cat'            => 'cart',
      'icon'           => $icon,
      'more_info_link' => '{jetexir_website}/addons/menu-cart',
      'settings_key'   => $this->addonID,
    );
  }
}
