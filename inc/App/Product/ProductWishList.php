<?php

namespace Jetexir\App\Product;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Enums\ProductStatus;
use Jetexir\Addons\Addon;
use Jetexir\App\App;
use Jetexir\Helper\{Assets,
  Helper,
  HTML,
  Nonce,
  Notice,
  Param,
  Sanitizing,
  UserMeta,
  WooCommerce,
  WordPress
};
use Jetexir\Interfaces\AddonInterface;

class ProductWishList extends Addon implements AddonInterface {
  public string $addonID = 'product-wishlist';
  public string $currentTab = 'product';
  public string $currentSection = 'wishlist';
  private const buttonShortCode = 'jetexir_product_wishlist_button';
  private const wishlistShortcode = 'jetexir_products_wishlist';
  private const userMeta = JETEXIR_PLUGIN_KEY . '_wishlist_items';
  private const defaultList = 'default';

  public function initAction(): void {
    App::addShortcode( self::buttonShortCode, [ $this, 'buttonShortcode' ] );
    App::addShortcode( self::wishlistShortcode, [ $this, 'wishlistShortcode' ] );
    add_rewrite_endpoint( 'wishlist', EP_PAGES );

    if ( WordPress::isUserLoggedIn() ) {
      if ( $this->getSetting( 'wishlist_page', 0 ) === 0 ) {
        add_action( 'woocommerce_account_wishlist_endpoint', [ $this, 'wishlistEndPointContent' ] );
      }

      add_action( 'woocommerce_thankyou', [ $this, 'removeWishlistItem' ], 99999 );
      add_action( 'wp_ajax_jetexir_product_wishlist_add_remove', [ $this, 'addRemoveItem' ] );
      add_action( 'wp_ajax_jetexir_product_wishlist_remove', [ $this, 'addRemoveItem' ] );

      if ( $position = $this->getSetting( 'wishlist_product_position', 'after_add_to_cart' ) ) {
        add_action( 'woocommerce_single_product_summary', [
          $this,
          'addButton'
        ], WooCommerce::getProductPositionPriority( $position ) );
      }

      if ( $position = $this->getSetting( 'wishlist_archive_position', 'after_add_to_cart' ) ) {
        if ( $position === 'before_title' ) {
          add_action( 'woocommerce_shop_loop_item_title', [ $this, 'addButton' ], 9 );

        } elseif ( $position === 'after_title' ) {
          add_action( 'woocommerce_shop_loop_item_title', [ $this, 'addButton' ], 11 );

        } elseif ( $position === 'after_rating' ) {
          add_action( 'woocommerce_after_shop_loop_item_title', [ $this, 'addButton' ], 6 );

        } elseif ( $position === 'after_price' ) {
          add_action( 'woocommerce_after_shop_loop_item_title', [ $this, 'addButton' ], 11 );

        } elseif ( $position === 'before_add_to_cart' ) {
          add_action( 'woocommerce_after_shop_loop_item', [ $this, 'addButton' ], 9 );

        } elseif ( $position === 'after_add_to_cart' ) {
          add_action( 'woocommerce_after_shop_loop_item', [ $this, 'addButton' ], 11 );
        }
      }
    }
  }

  public function removeWishlistItem( $orderID ): void {
    if ( ! $this->getSetting( 'wishlist_auto_remove', false ) || ! WordPress::isUserLoggedIn() ) {
      return;
    }

    $order = wc_get_order( $orderID );
    if ( ! $order ) {
      return;
    }

    $productIDs = [];
    foreach ( $order->get_items() as $item ) {
      $productIDs[] = $item->get_product_id();
    }

    if ( empty( $productIDs ) ) {
      return;
    }

    $userID   = WordPress::getCurrentUserID();
    $listKeys = self::getListKeys( $userID );

    foreach ( $listKeys as $listKey ) {
      $items = self::getListItems( $listKey, $userID );
      if ( empty( $items ) ) {
        continue;
      }

      $initItemsCount = count( $items );
      $itemKeys       = array_keys( $items );
      foreach ( $itemKeys as $productID ) {
        if ( in_array( $productID, $productIDs, true ) ) {
          unset( $items[ $productID ] );
        }
      }

      if ( $initItemsCount !== count( $items ) ) {
        self::saveListItems( $listKey, $items, $userID );
      }
    }
  }

  public function wishlistEndPointContent(): void {
    echo do_shortcode( '[' . self::wishlistShortcode . ']' );
  }

  public function wooAccountMenuItemsFilter( $items ): array {
    $menuItems = [ 'wishlist' => esc_html__( 'Wishlist', 'jetexir' ) ];

    if ( isset( $items['customer-logout'] ) ) {
      $index = array_search( 'customer-logout', array_keys( $items ), true );
      $items = Helper::arrayInsertAfter( $items, $index, $menuItems );
    } else {
      $items = array_merge( $items, $menuItems );
    }

    return $items;
  }

  public function queryVarsFilter( $vars ) {
    $vars[] = 'wishlist';

    return $vars;
  }

  public function addRemoveItem(): void {
    if ( Nonce::verify() ) {
      $productID = Sanitizing::int( Param::post( 'product_id', 0 ) );
      $list      = self::defaultList; //Sanitizing::text( Param::post( 'list', self::defaultList ) );
      $max       = $this->getSetting( 'wishlist_max_items', 10 );
      $update    = $this->updateStorage( $productID, $list, $max );

      $data = array(
        'status'   => $update['status'],
        'list'     => $list,
        'count'    => $update['count'],
        'max'      => (int) $max,
        'redirect' => $update['status'] === 'max_exceeded' ? $this->getWishlistPage() : ''
      );

      wp_send_json_success( $data );
    }

    wp_send_json_error( [
      'error'   => 'nonce-invalid',
      'message' => esc_html__( 'Security code is not valid, page will be refreshed.', 'jetexir' ),
      'refresh' => true
    ], 403 );
  }

  /**
   * Update (add/remove) item in storage
   *
   * @param int $productID Product id
   * @param string $list List name
   * @param int $max Max items
   *
   * @return array Return status and count of items
   */
  private function updateStorage( int $productID, $list = self::defaultList, int $max = 10 ): array {
    $listItems = self::getListItems( $list );
    $count     = count( $listItems );
    $status    = 'added';

    if ( isset( $listItems[ $productID ] ) ) {
      unset( $listItems[ $productID ] );
      $status = 'removed';
      $count --;
    } else {
      if ( $count >= $max ) {
        return [ 'status' => 'max_exceeded', 'count' => $count ];
      }

      $listItems[ $productID ] = array(
        'datetime'  => current_time( 'mysql' ),
        'timestamp' => current_time( 'U' ),
      );
      $count ++;
    }

    if ( ! self::saveListItems( $list, $listItems ) ) {
      $status = 'error';
    }

    return [ 'status' => $status, 'count' => $count ];
  }

  public static function getListKeys( $userID = 0 ): array {
    if ( $userID === 0 ) {
      $userID = WordPress::getCurrentUserID();
    }

    $listKeys = array( self::defaultList );
    /**
     * Filters the wishlist list keys.
     *
     * @param array $listKeys List keys.
     * @param int $userID Current user ID.
     *
     * @return array List keys.
     *
     * @since 1.0
     *
     */
    $listKeys = (array) apply_filters( 'jetexir_wishlist_list_keys', $listKeys, $userID );
    $listKeys = array_values( $listKeys );

    if ( ! in_array( self::defaultList, $listKeys, true ) ) {
      $listKeys[] = self::defaultList;
    }

    return $listKeys;
  }

  public static function checkExistsItem( $productID, $list = self::defaultList ): bool {
    $productID = (int) $productID;
    $listItems = self::getListItems( $list );

    return array_key_exists( $productID, $listItems );
  }

  public static function saveListItems( $list, $listItems, $userId = 0 ) {
    if ( $userId === 0 ) {
      $userId = WordPress::getCurrentUserID();
    }

    $wishlist          = self::getListItems();
    $wishlist[ $list ] = $listItems;

    return UserMeta::update( $userId, self::userMeta, $wishlist );
  }

  public static function getListItems( $list = null, $userId = 0 ): array {
    $userId = (int) $userId;
    if ( $userId === 0 ) {
      $userId = WordPress::getCurrentUserID();
    }

    $wishlist = UserMeta::get( $userId, self::userMeta );
    $wishlist = is_array( $wishlist ) ? $wishlist : [];

    if ( is_null( $list ) ) {
      /**
       * Filters all the wishlist items.
       *
       * @param array $wishlist Wishlist items.
       * @param int $userId Current user ID.
       *
       * @return array Wishlist items.
       *
       * @since 1.0
       *
       */
      return (array) apply_filters( 'jetexir_wishlist_items', $wishlist, $userId );
    }

    $wishlist = $wishlist[ $list ] ?? [];

    /**
     * Filters the wishlist items of a specific list.
     *
     * @param array $wishlist Wishlist items.
     * @param string $list List key.
     * @param int $userId Current user ID.
     *
     * @return array Wishlist items.
     *
     * @since 1.0
     *
     */
    return (array) apply_filters( 'jetexir_wishlist_list_items', $wishlist, $list, $userId );
  }

  public function addButton(): void {
    if ( WooCommerce::isProduct() && WordPress::isAction( 'woocommerce_single_product_summary' ) ) {
      $buttonAppearance = $this->getSetting( 'wishlist_product_button', 'icon_text' );
    } else {
      $buttonAppearance = $this->getSetting( 'wishlist_archive_button', 'icon' );
    }

    echo wp_kses_post( $this->buttonShortcode( array(
      'type'         => esc_html( $this->getSetting( 'wishlist_button_type', 'button' ) ),
      'icon'         => wp_kses_post( $this->getButtonIcons( $this->getSetting( 'wishlist_button_icon', 'jetexir-icon-heart' ), true ) ),
      'text'         => esc_html( $this->getSetting( 'wishlist_button_text', esc_html__( 'Add to wishlist', 'jetexir' ) ) ),
      'appearance'   => esc_html( $buttonAppearance ),
      'remove_text'  => esc_html( $this->getSetting( 'wishlist_button_remove_text', esc_html__( 'Remove from wishlist', 'jetexir' ) ) ),
      'browse_text'  => esc_html( $this->getSetting( 'wishlist_button_browse_text', esc_html__( 'Browse wishlist', 'jetexir' ) ) ),
      'added_action' => esc_html( $this->getSetting( 'wishlist_added_action', 'remove' ) )
    ) ) );
  }

  public function buttonShortcode( $atts ): string {
    $atts = shortcode_atts( array(
      'product_id'    => WooCommerce::getCurrentProductId(),
      'icon'          => $this->getButtonIcons( $this->getSetting( 'wishlist_button_icon', 'jetexir-icon-heart' ), true ),
      'text'          => $this->getSetting( 'wishlist_button_text', esc_html__( 'Add to wishlist', 'jetexir' ) ),
      'remove_text'   => $this->getSetting( 'wishlist_button_remove_text', esc_html__( 'Remove from wishlist', 'jetexir' ) ),
      'browse_text'   => $this->getSetting( 'wishlist_button_browse_text', esc_html__( 'Browse wishlist', 'jetexir' ) ),
      'added_action'  => $this->getSetting( 'wishlist_added_action', 'remove' ),
      'type'          => $this->getSetting( 'wishlist_button_type', 'button' ),
      'appearance'    => 'icon_text',
      'class'         => '',
      'default_class' => 'on'
    ), $atts, self::buttonShortCode );

    if ( empty( $atts['text'] ) ) {
      return '';
    }

    $exists     = self::checkExistsItem( $atts['product_id'] );
    $type       = in_array( $atts['type'], [ 'button', 'a' ] ) ? $atts['type'] : 'button';
    $appearance = in_array( $atts['appearance'], [
      'icon',
      'text',
      'icon_text'
    ] ) ? $atts['appearance'] : 'icon';

    $addedText = $atts['added_action'] === 'remove' ? $atts['remove_text'] : $atts['browse_text'];
    $text      = $exists ? $addedText : $atts['text'];
    if ( $appearance === 'icon' ) {
      $buttonAddText = $buttonAddedText = $buttonText = $atts['icon'];
    } else if ( $appearance === 'text' ) {
      $buttonText      = $text;
      $buttonAddText   = $atts['text'];
      $buttonAddedText = $addedText;
    } else {
      $buttonText      = $atts['icon'] . ' ' . $text;
      $buttonAddText   = $atts['icon'] . ' ' . $atts['text'];
      $buttonAddedText = $atts['icon'] . ' ' . $addedText;
    }

    $defaultClass = 'jetexir-product-wishlist-button ' . ( $exists ? 'jetexir-product-wishlist-added ' : '' );
    $defaultClass .= $atts['default_class'] === 'on' ? ( $type === 'button' ? 'button jetexir-button jetexir-button-secondary jetexir-inline-flex ' : 'jetexir-inline-flex ' ) : '';
    $defaultClass .= $exists && $atts['added_action'] === 'remove' ? 'jetexir-remove-action ' : '';
    $class        = trim( $defaultClass . ' ' . $atts['class'] );

    $attributes = array(
      'class'             => $class,
      'data-product-id'   => $atts['product_id'],
      'data-icon'         => str_replace( '"', "'", $atts['icon'] ),
      'data-added-action' => $atts['added_action'],
      'data-in-wishlist'  => $exists ? self::defaultList : '',
      'data-add-text'     => str_replace( '"', "'", $buttonAddText ),
      'data-added-text'   => str_replace( '"', "'", $buttonAddedText ),
    );
    $attributes = HTML::getAttributes( [ 'attributes' => $attributes ] );

    return '<' . $type . ' ' . ( $type === 'a' ? 'href="#"' : 'type="button"' ) . ' ' . $attributes . '>' . $buttonText . '</' . $type . '>';
  }

  public function wishlistShortcode( $atts ): string {
    $atts = shortcode_atts( array(
      'user_id' => 0,
      'list'    => self::defaultList
    ), $atts, self::wishlistShortcode );

    $emptyNotice = Notice::addAndDisplay( 'product-wishlist', array(
      array(
        'type'    => 'info',
        'message' => esc_html__( 'Your wishlist is empty.', 'jetexir' ),
      )
    ), false );
    $listItems   = self::getListItems( $atts['list'], $atts['user_id'] );
    if ( empty( $listItems ) ) {
      return $emptyNotice;
    }
    //self::dd( $listItems );

    $listItems = array_reverse( $listItems, true );
    $products  = WooCommerce::getProducts( array(
      'include' => array_keys( $listItems ),
      'limit'   => $this->getSetting( 'wishlist_max_items', 10 ),
      'status'  => ProductStatus::PUBLISH,
      'orderby' => 'date',
      'order'   => 'DESC',
    ) );

    if ( empty( $products ) ) {
      return $emptyNotice;
    }

    $productIDs = wp_list_pluck( $products, 'id' );
    $products   = array_combine( $productIDs, $products );
    $dateFormat = get_option( 'date_format' );

    ob_start();
    echo '<div class="jetexir-product-list-wrap jetexir-product-wishlist-wrap">';
    echo '<div class="jetexir-loader-wrap" style="display: none"><div class="jetexir-loader"></div></div>';
    echo '<div class="jetexir-product-list-notice" style="display: none">' . $emptyNotice . '</div>';

    foreach ( $listItems as $productID => $data ) {
      if ( isset( $products[ $productID ] ) ) {
        $product     = $products[ $productID ];
        $productLink = $product->get_permalink();
        $name        = wp_strip_all_tags( $product->get_name() );

        echo '<div class="jetexir-product-item-wrap jetexir-product-wishlist-item" data-product-id="' . esc_html( $productID ) . '">';

        // Image
        echo '<a href="' . esc_url( $productLink ) . '" target="_blank" class="jetexir-product-item-image jetexir-wishlist-item-image">' . wp_kses_post( $product->get_image() ) . '</a>';

        // Info (Name, Date, Price)
        echo '<div class="jetexir-product-item-info">';
        echo '<a href="' . esc_url( $productLink ) . '" target="_blank" class="jetexir-product-item-title">' . esc_html( $name ) . '</a>';
        echo '<div class="jetexir-product-item-price jetexir-product-item-meta">' . wp_kses_post( $product->get_price_html() ) . '</div>';
        echo '<div class="jetexir-product-item-date jetexir-product-item-meta">' . esc_html( wp_date( $dateFormat, $data['timestamp'] ) ) . '</div>';
        echo '</div>';

        echo '<div class="jetexir-product-item-actions">';
        echo wp_kses_post( WooCommerce::getAddToCartButton( $product ) );
        echo '<a href="#" class="jetexir-product-item-remove jetexir-flex jetexir-product-wishlist-remove" data-jetexir-product-remove-action="jetexir_product_wishlist_remove" data-product-id="' . esc_attr( $productID ) . '"><i class="jetexir-icon-cross"></i> ' . esc_html__( 'Remove', 'jetexir' ) . '</a>';
        echo '</div>';

        echo '</div>';
      }
    }

    echo '</div>';

    return '';
  }

  private function getWishlistPage() {
    $page = (int) $this->getSetting( 'wishlist_page', 0 );

    if ( $page === 0 ) {
      return WooCommerce::url( 'myaccount', 'wishlist' );
    }

    return get_permalink( $page );
  }

  private function getButtonIcons( $icon = null, $tag = false ) {
    $icons = array(
      'jetexir-icon-heart',
      'jetexir-icon-heart1',
      'jetexir-icon-heart2',
      'jetexir-icon-heart3',
      'jetexir-icon-heart',
      'jetexir-icon-bookmark',
      'jetexir-icon-bookmark_outline',
      'jetexir-icon-bookmarks',
      'jetexir-icon-star_rate',
      'jetexir-icon-star_outline',
      'jetexir-icon-star_half',
      'jetexir-icon-check',
      'jetexir-icon-check1',
      'jetexir-icon-tick-outline',
      'jetexir-icon-checkmark',
      'jetexir-icon-checkmark2',
      'jetexir-icon-check_circle',
      'jetexir-icon-check_circle_outline',
      'jetexir-icon-check_box',
      'jetexir-icon-library_add_check',
      'jetexir-icon-library_add',
      'jetexir-icon-plus',
      'jetexir-icon-magic-wand',
      'jetexir-icon-magic-wand1',
      'jetexir-icon-magic-wand2',
      'jetexir-icon-magic-wand3',
      'jetexir-icon-magic-lamp',
      'jetexir-icon-magic-lamp1',
    );

    if ( is_null( $icon ) ) {
      return $icons;
    }

    $icon = in_array( $icon, $icons, true ) ? $icon : 'jetexir-icon-heart';

    return $tag ? '<i class="' . $icon . '"></i>' : $icon;
  }

  /**
   * Enqueue style and script
   *
   * @return void
   */
  public function wpEnqueueScriptsAction(): void {
    $wishlistPage = $this->getSetting( 'wishlist_page', 0 );
    if ( ! WooCommerce::isWoo() && ! WordPress::isPage( $wishlistPage ) ) {
      return;
    }

    $pluginVersion = Assets::getVersion();
    $debugName     = JETEXIR_DEBUG_MODE ? '' : '.min';

    /*wp_enqueue_style( JETEXIR_PLUGIN_KEY . '-product-wishlist-style',
      Assets::url( 'css/product-wishlist' . $debugName . '.css' ),
      false, $pluginVersion );*/

    wp_enqueue_script( JETEXIR_PLUGIN_KEY . '-product-wishlist-script',
      Assets::url( 'js/product-wishlist.min.js' ),
      [ JETEXIR_PLUGIN_SLUG . '-global' ], $pluginVersion, [ 'in_footer' => true ] );

    wp_localize_script( JETEXIR_PLUGIN_KEY . '-product-wishlist-script', JETEXIR_PLUGIN_KEYCAP . 'ProductWishlist', array(
      'maxItems'           => $this->getSetting( 'wishlist_max_items', 10 ),
      'maxExceededMessage' => esc_html__( 'It is not possible to add more than %number% product to the wishlist.', 'jetexir' ),
      'wishlistPage'       => $this->getWishlistPage()
    ) );
  }

  public function addSectionSettings( $sections ) {
    $icons       = $this->getButtonIcons();
    $buttonIcons = [];
    foreach ( $icons as $icon ) {
      $buttonIcons[ $icon ] = '<i class="' . $icon . '"></i>';
    }

    $settings = array(
      'start_grid_wishlist_general' => array(
        'title' => esc_html__( 'Products Wishlist', 'jetexir' ),
        'type'  => 'startGrid',
      ),
      'wishlist_page'               => array(
        'id'                => 'wishlist_page',
        'title'             => esc_html__( 'Wishlist page', 'jetexir' ),
        'type'              => 'postSelect',
        'args'              => array(
          'post_type' => 'page'
        ),
        'default'           => 0,
        'option_none'       => esc_html__( 'Add tab to "My account" page', 'jetexir' ),
        'option_none_value' => 0,
        /* translators: %s: Shortcode */
        'desc'              => wp_sprintf( esc_html__( 'Insert shortcode in the custom wishlist page %s', 'jetexir' ), '<code  class="jetexir-copy-text">[' . self::wishlistShortcode . ']</code>' )
      ),
      'wishlist_max_items'          => array(
        'id'         => 'wishlist_max_items',
        'title'      => esc_html__( 'Max items', 'jetexir' ),
        'desc'       => esc_html__( 'Max wishlist items per user', 'jetexir' ),
        'type'       => 'number',
        'default'    => 10,
        'attributes' => array(
          'placeholder' => 10,
          'step'        => 1,
          'min'         => 1,
          'max'         => 100,
        ),
        'sanitize'   => 'int'
      ),
      'wishlist_auto_remove'        => array(
        'id'       => 'wishlist_auto_remove',
        'title'    => esc_html__( 'Auto remove', 'jetexir' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => false,
        'desc'     => esc_html__( 'Auto remove product from the wishlist after create order.', 'jetexir' ),
        'sanitize' => 'bool'
      ),
      'wishlist_product_position'   => array(
        'id'          => 'wishlist_product_position',
        'title'       => esc_html__( 'Position on single page', 'jetexir' ),
        'type'        => 'select',
        'options'     => array(
          'before_title'       => esc_html__( 'Before title', 'jetexir' ),
          'after_title'        => esc_html__( 'After title', 'jetexir' ),
          'after_rating'       => esc_html__( 'After rating', 'jetexir' ),
          'after_price'        => esc_html__( 'After price', 'jetexir' ),
          'after_excerpt'      => esc_html__( 'After excerpt', 'jetexir' ),
          'before_add_to_cart' => esc_html__( 'Before add to cart button', 'jetexir' ),
          'after_add_to_cart'  => esc_html__( 'After add to cart button', 'jetexir' ),
          'after_meta'         => esc_html__( 'After meta', 'jetexir' ),
          'after_sharing'      => esc_html__( 'After sharing', 'jetexir' ),
        ),
        'option_none' => esc_html__( 'Hide', 'jetexir' ),
        'default'     => 'after_add_to_cart',
        'sanitize'    => 'text',
      ),
      'wishlist_archive_position'   => array(
        'id'          => 'wishlist_archive_position',
        'title'       => esc_html__( 'Position on archive page', 'jetexir' ),
        'type'        => 'select',
        'options'     => array(
          'before_title'       => esc_html__( 'Before title', 'jetexir' ),
          'after_title'        => esc_html__( 'After title', 'jetexir' ),
          'after_rating'       => esc_html__( 'After rating', 'jetexir' ),
          'after_price'        => esc_html__( 'After price', 'jetexir' ),
          'before_add_to_cart' => esc_html__( 'Before add to cart button', 'jetexir' ),
          'after_add_to_cart'  => esc_html__( 'After add to cart button', 'jetexir' ),
        ),
        'option_none' => esc_html__( 'Hide', 'jetexir' ),
        'default'     => 'after_add_to_cart',
        'sanitize'    => 'text',
      ),
      'end_grid_wishlist_general'   => array(
        'type' => 'endgrid',
      ),

      'start_grid_wishlist_button'  => array(
        'title' => esc_html__( 'Button', 'jetexir' ),
        'type'  => 'startGrid',
      ),
      'wishlist_button_type'        => array(
        'id'       => 'wishlist_button_type',
        'title'    => esc_html__( 'Type', 'jetexir' ),
        'type'     => 'select',
        'options'  => array(
          'button' => esc_html__( 'Button', 'jetexir' ),
          'a'      => esc_html__( 'Link', 'jetexir' ),
        ),
        'default'  => 'button',
        'sanitize' => 'text',
      ),
      'wishlist_button_icon'        => array(
        'id'       => 'wishlist_button_icon',
        'title'    => esc_html__( 'Button icon', 'jetexir' ),
        'type'     => 'radioInline',
        'default'  => 'jetexir-icon-heart',
        'options'  => $buttonIcons,
        'sanitize' => 'text'
      ),
      'wishlist_button_text'        => array(
        'id'      => 'wishlist_button_text',
        'title'   => esc_html__( 'Button text', 'jetexir' ),
        'type'    => 'text',
        'default' => esc_html__( 'Add to wishlist', 'jetexir' ),
      ),
      'wishlist_button_remove_text' => array(
        'id'      => 'wishlist_button_remove_text',
        'title'   => esc_html__( 'Remove button text', 'jetexir' ),
        'type'    => 'text',
        'default' => esc_html__( 'Remove from wishlist', 'jetexir' ),
      ),
      'wishlist_button_browse_text' => array(
        'id'      => 'wishlist_button_browse_text',
        'title'   => esc_html__( 'Browse button text', 'jetexir' ),
        'type'    => 'text',
        'default' => esc_html__( 'Browse wishlist', 'jetexir' ),
      ),
      'wishlist_added_action'       => array(
        'id'       => 'wishlist_added_action',
        'title'    => esc_html__( 'Action after adding the product', 'jetexir' ),
        'desc'     => esc_html__( 'Specify the action to perform following product addition', 'jetexir' ),
        'type'     => 'select',
        'options'  => array(
          'open_page' => esc_html__( 'Open wishlist page', 'jetexir' ),
          'remove'    => esc_html__( 'Remove from wishlist', 'jetexir' ),
        ),
        'default'  => 'remove',
        'sanitize' => 'text',
      ),
      'wishlist_product_button'     => array(
        'id'       => 'wishlist_product_button',
        'title'    => esc_html__( 'Product appearance', 'jetexir' ),
        'type'     => 'select',
        'options'  => array(
          'icon'      => esc_html__( 'Icon', 'jetexir' ),
          'text'      => esc_html__( 'Text', 'jetexir' ),
          'icon_text' => esc_html__( 'Icon with text', 'jetexir' ),
        ),
        'default'  => 'icon_text',
        'sanitize' => 'text',
        'desc'     => esc_html__( 'Select single product button appearance', 'jetexir' )
      ),
      'wishlist_archive_button'     => array(
        'id'       => 'wishlist_archive_button',
        'title'    => esc_html__( 'Archive appearance', 'jetexir' ),
        'type'     => 'select',
        'options'  => array(
          'icon'      => esc_html__( 'Icon', 'jetexir' ),
          'text'      => esc_html__( 'Text', 'jetexir' ),
          'icon_text' => esc_html__( 'Icon with text', 'jetexir' ),
        ),
        'default'  => 'icon',
        'sanitize' => 'text',
        'desc'     => esc_html__( 'Select archive button appearance', 'jetexir' )
      ),
      'end_grid_wishlist_button'    => array(
        'type' => 'endgrid',
      ),


    );

    $sections[ $this->currentSection ] = array(
      'title'        => esc_html__( 'WishList', 'jetexir' ),
      'desc'         => esc_html__( 'Products WishList', 'jetexir' ),
      'settings_key' => $this->addonID,
      'settings'     => $settings
    );

    return $sections;
  }

  public function info(): array {
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="#873eff" stroke-width="1.5" d="M16 4c2.175.012 3.353.109 4.121.877C21 5.756 21 7.17 21 9.998v6c0 2.829 0 4.243-.879 5.122-.878.878-2.293.878-5.121.878H9c-2.828 0-4.243 0-5.121-.878C3 20.24 3 18.827 3 15.998v-6c0-2.828 0-4.242.879-5.121C4.647 4.109 5.825 4.012 8 4"/><path fill="#873eff" d="m12 11.691-.519.542a.75.75 0 0 0 1.038 0zm0 4.137v-.75zm-.514-1.067c-.417-.307-.878-.69-1.227-1.093-.368-.426-.509-.757-.509-.971h-1.5c0 .77.441 1.45.875 1.952.453.525 1.014.984 1.474 1.321zM9.75 12.697c0-.576.263-.827.492-.907.25-.088.714-.06 1.24.443l1.037-1.083c-.825-.79-1.861-1.096-2.773-.776-.934.327-1.496 1.226-1.496 2.323zm3.65 3.273c.46-.337 1.022-.796 1.475-1.32.434-.502.875-1.183.875-1.953h-1.5c0 .214-.141.545-.51.971-.348.403-.809.786-1.226 1.093zm2.35-3.273c0-1.097-.562-1.996-1.496-2.323-.912-.32-1.948-.014-2.773.776l1.038 1.083c.525-.503.989-.531 1.24-.443.228.08.491.33.491.907zM10.6 15.97c.368.27.782.607 1.4.608v-1.5c-.024 0-.04 0-.094-.03a4 4 0 0 1-.42-.287zm1.914-1.21a4 4 0 0 1-.42.289c-.054.029-.07.029-.094.029v1.5c.618 0 1.032-.337 1.4-.608z"/><path stroke="#873eff" stroke-width="1.5" d="M8 3.5A1.5 1.5 0 0 1 9.5 2h5A1.5 1.5 0 0 1 16 3.5v1A1.5 1.5 0 0 1 14.5 6h-5A1.5 1.5 0 0 1 8 4.5z"/></svg>';

    return array(
      'id'             => $this->addonID,
      'title'          => esc_html__( 'Products WishList', 'jetexir' ),
      'desc'           => esc_html__( 'Add wishlist functionality to your store.', 'jetexir' ),
      'tags'           => [ esc_html__( 'Product', 'jetexir' ) ],
      'cat'            => 'product',
      'icon'           => $icon,
      'more_info_link' => '{jetexir_website}/addons/wishlist',
      'settings_key'   => $this->addonID,
    );
  }
}
