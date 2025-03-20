<?php

namespace WooAssistant\App\Product;

use Automattic\WooCommerce\Enums\ProductStatus;
use WooAssistant\Addons\Addon;
use WooAssistant\App\App;
use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Helper;
use WooAssistant\Helper\HTML;
use WooAssistant\Helper\Nonce;
use WooAssistant\Helper\Notice;
use WooAssistant\Helper\Param;
use WooAssistant\Helper\Sanitizing;
use WooAssistant\Helper\UserMeta;
use WooAssistant\Helper\WooCommerce;
use WooAssistant\Helper\WordPress;
use WooAssistant\Interfaces\AddonInterface;
use WooAssistant\Settings\Settings;

class ProductWishList extends Addon implements AddonInterface {
	public string $addonID = 'product-wishlist';
	private const sectionID = 'wishlist';
	private const buttonShortCode = 'wa_product_wishlist_button';
	private const wishlistShortcode = 'wa_products_wishlist';
	private const userMeta = WOOASSISTANT_PLUGIN_KEY . '_wishlist_items';
	private const defaultList = 'default';

	public function initAction(): void {
		add_filter( 'woo_assistant_product_settings_sections', [ $this, 'addSectionSettings' ] );
		App::addShortcode( self::buttonShortCode, [ $this, 'buttonShortcode' ] );
		App::addShortcode( self::wishlistShortcode, [ $this, 'wishlistShortcode' ] );

		if ( WordPress::isUserLoggedIn() ) {
			if ( Settings::get( 'wishlist_page', 0 ) === 0 ) {
				add_rewrite_endpoint( 'wishlist', EP_PAGES );
				add_action( 'woocommerce_account_wishlist_endpoint', [ $this, 'wishlistEndPointContent' ] );
			}

			add_action( 'woocommerce_thankyou', [ $this, 'removeWishlistItem' ], 99999 );
			add_action( 'wp_ajax_wa_product_wishlist_add_remove', [ $this, 'addRemoveItem' ] );
			add_action( 'wp_ajax_wa_product_wishlist_remove', [ $this, 'addRemoveItem' ] );

			if ( $position = Settings::get( 'wishlist_product_position', 'after_add_to_cart' ) ) {
				add_action( 'woocommerce_single_product_summary', [
					$this,
					'addButton'
				], $this->getProductPosition( $position ) );
			}

			if ( $position = Settings::get( 'wishlist_archive_position', 'after_add_to_cart' ) ) {
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
		if ( ! Settings::get( 'wishlist_auto_remove', false ) || ! WordPress::isUserLoggedIn() ) {
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
		$menuItems = [ 'wishlist' => __( 'Wishlist', 'woo-assistant' ) ];

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
			$max       = Settings::get( 'wishlist_max_items', 10 );
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
			'message' => __( 'Security code is not valid, page will be refreshed.', 'woo-assistant' ),
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
		$listKeys = apply_filters( 'woo_assistant_wishlist_list_keys', $listKeys, $userID );
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
			return apply_filters( 'woo_assistant_wishlist_items', $wishlist, $userId );;
		}

		$wishlist = $wishlist[ $list ] ?? [];

		$wishlist = apply_filters( 'woo_assistant_wishlist_list_items', $wishlist, $list, $userId );

		return $wishlist;
	}

	public function addButton(): void {
		if ( WooCommerce::isProduct() ) {
			$buttonAppearance = Settings::get( 'wishlist_product_button', 'icon_text' );
		} else {
			$buttonAppearance = Settings::get( 'wishlist_archive_button', 'icon' );
		}

		echo $this->buttonShortcode( array(
			'type'              => Settings::get( 'wishlist_button_type', 'button' ),
			'icon'              => $this->getButtonIcons( Settings::get( 'wishlist_button_icon', 'wa-icon-heart' ), true ),
			'text'              => Settings::get( 'wishlist_button_text', __( 'Add to wishlist', 'woo-assistant' ) ),
			'button_appearance' => $buttonAppearance,
			'remove_text'       => Settings::get( 'wishlist_button_remove_text', __( 'Remove from wishlist', 'woo-assistant' ) ),
			'browse_text'       => Settings::get( 'wishlist_button_browse_text', __( 'Browse wishlist', 'woo-assistant' ) ),
			'added_action'      => Settings::get( 'wishlist_added_action', 'remove' )
		) );
	}

	public function buttonShortcode( $atts ): string {
		$atts = shortcode_atts( array(
			'product_id'        => WooCommerce::getCurrentProductId(),
			'icon'              => $this->getButtonIcons( Settings::get( 'wishlist_button_icon', 'wa-icon-heart' ), true ),
			'text'              => Settings::get( 'wishlist_button_text', __( 'Add to wishlist', 'woo-assistant' ) ),
			'remove_text'       => Settings::get( 'wishlist_button_remove_text', __( 'Remove from wishlist', 'woo-assistant' ) ),
			'browse_text'       => Settings::get( 'wishlist_button_browse_text', __( 'Browse wishlist', 'woo-assistant' ) ),
			'added_action'      => Settings::get( 'wishlist_added_action', 'remove' ),
			'type'              => Settings::get( 'wishlist_button_type', 'button' ),
			'button_appearance' => 'icon_text',
			'class'             => '',
			'default_class'     => 'on'
		), $atts, self::buttonShortCode );

		if ( empty( $atts['text'] ) ) {
			return '';
		}

		$exists     = self::checkExistsItem( $atts['product_id'] );
		$type       = in_array( $atts['type'], [ 'button', 'a' ] ) ? $atts['type'] : 'button';
		$appearance = in_array( $atts['button_appearance'], [
			'icon',
			'text',
			'icon_text'
		] ) ? $atts['button_appearance'] : 'icon';

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

		$defaultClass = 'wa-product-wishlist-button ' . ( $exists ? 'wa-product-wishlist-added ' : '' );
		$defaultClass .= $atts['default_class'] === 'on' ? ( $type === 'button' ? 'button wa-button wa-button-secondary wa-inline-flex ' : 'wa-inline-flex ' ) : '';
		$defaultClass .= $exists && $atts['added_action'] === 'remove' ? 'wa-remove-action ' : '';
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

		$emptyNotice = Notice::addAndDisplay( 'product-compare', array(
			array(
				'type'    => 'info',
				'message' => __( 'Your wishlist is empty.', 'woo-assistant' ),
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
			'limit'   => Settings::get( 'wishlist_max_items', 10 ),
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
		echo '<div class="wa-product-list-wrap wa-product-wishlist-wrap">';
		echo '<div class="wa-loader-wrap" style="display: none"><div class="wa-loader"></div></div>';
		echo '<div class="wa-product-list-notice" style="display: none">' . $emptyNotice . '</div>';

		foreach ( $listItems as $productID => $data ) {
			if ( isset( $products[ $productID ] ) ) {
				$product     = $products[ $productID ];
				$productLink = $product->get_permalink();
				$name        = wp_strip_all_tags( $product->get_name() );

				echo '<div class="wa-product-item-wrap wa-product-wishlist-item" data-product-id="' . $productID . '">';

				// Image
				echo '<a href="' . $productLink . '" target="_blank" class="wa-product-item-image wa-wishlist-item-image">' . $product->get_image() . '</a>';

				// Info (Name, Date, Price)
				echo '<div class="wa-product-item-info">';
				echo '<a href="' . $productLink . '" target="_blank" class="wa-product-item-title">' . $name . '</a>';
				echo '<div class="wa-product-item-price wa-product-item-meta">' . $product->get_price_html() . '</div>';
				echo '<div class="wa-product-item-date wa-product-item-meta">' . wp_date( $dateFormat, $data['timestamp'] ) . '</div>';
				echo '</div>';

				echo '<div class="wa-product-item-actions">';
				echo WooCommerce::getAddToCartButton( $product );
				echo '<a href="#" class="wa-product-item-remove wa-flex wa-product-wishlist-remove" data-wa-product-remove-action="wa_product_wishlist_remove" data-product-id="' . $productID . '"><i class="wa-icon-cross"></i> ' . __( 'Remove', 'woo-assistant' ) . '</a>';
				echo '</div>';

				echo '</div>';
			}
		}

		echo '</div>';

		//self::dd( $products );

		return '';
	}

	private function getWishlistPage() {
		$page = (int) Settings::get( 'wishlist_page', 0 );

		if ( $page === 0 ) {
			return WooCommerce::url( 'myaccount', 'wishlist' );
		}

		return get_permalink( $page );
	}

	private function getProductPosition( $position ): int {
		switch ( $position ) {
			case 'before_title':
				return 1;
			case 'after_title':
				return 6;
			case 'after_rating':
				return 11;
			case 'after_price':
				return 13;
			case 'after_excerpt':
				return 21;
			case 'before_add_to_cart':
				return 29;
			case 'after_meta':
				return 41;
			case 'after_sharing':
				return 51;
			default:
				return 31; // after_add_to_cart
		}
	}

	private function getButtonIcons( $icon = null, $tag = false ) {
		$icons = array(
			'wa-icon-heart',
			'wa-icon-heart1',
			'wa-icon-heart2',
			'wa-icon-heart3',
			'wa-icon-heart',
			'wa-icon-bookmark',
			'wa-icon-bookmark_outline',
			'wa-icon-bookmarks',
			'wa-icon-star_rate',
			'wa-icon-star_outline',
			'wa-icon-star_half',
			'wa-icon-check',
			'wa-icon-check1',
			'wa-icon-tick-outline',
			'wa-icon-checkmark',
			'wa-icon-checkmark2',
			'wa-icon-check_circle',
			'wa-icon-check_circle_outline',
			'wa-icon-check_box',
			'wa-icon-library_add_check',
			'wa-icon-library_add',
			'wa-icon-plus',
			'wa-icon-magic-wand',
			'wa-icon-magic-wand1',
			'wa-icon-magic-wand2',
			'wa-icon-magic-wand3',
			'wa-icon-magic-lamp',
			'wa-icon-magic-lamp1',
		);

		if ( is_null( $icon ) ) {
			return $icons;
		}

		$icon = in_array( $icon, $icons, true ) ? $icon : 'wa-icon-heart';

		return $tag ? '<i class="' . $icon . '"></i>' : $icon;
	}

	/**
	 * Enqueue style and script
	 *
	 * @return void
	 */
	public function wpEnqueueScriptsAction(): void {
		$wishlistPage = Settings::get( 'wishlist_page', 0 );
		if ( ! WooCommerce::isWoocommerce() && ! WooCommerce::isDashboard() && ! WordPress::isPage( $wishlistPage ) ) {
			return;
		}

		$pluginVersion = Assets::getVersion();
		$debugName     = WOOASSISTANT_DEBUG_MODE ? '' : '.min';

		/*wp_enqueue_style( WOOASSISTANT_PLUGIN_KEY . '-product-wishlist-style',
			Assets::url( 'css/product-wishlist' . $debugName . '.css' ),
			false, $pluginVersion );*/

		wp_enqueue_script( WOOASSISTANT_PLUGIN_KEY . '-product-wishlist-script',
			Assets::url( 'js/product-wishlist.min.js' ),
			[ WOOASSISTANT_PLUGIN_SLUG . '-global' ], $pluginVersion, [ 'in_footer' => true ] );

		wp_localize_script( WOOASSISTANT_PLUGIN_KEY . '-product-wishlist-script', WOOASSISTANT_PLUGIN_KEYCAP . 'ProductWishlist', array(
			'maxItems'           => Settings::get( 'wishlist_max_items', 10 ),
			'maxExceededMessage' => __( 'It is not possible to add more than %number% product to the wishlist.', 'woo-assistant' ),
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
				'title' => __( 'Product Wishlist', 'woo-assistant' ),
				'type'  => 'startGrid',
			),
			'wishlist_page'               => array(
				'id'                => 'wishlist_page',
				'title'             => __( 'Wishlist page', 'woo-assistant' ),
				'type'              => 'postSelect',
				'args'              => array(
					'post_type' => 'page'
				),
				'default'           => 0,
				'option_none'       => __( 'Add tab to "My account" page', 'woo-assistant' ),
				'option_none_value' => 0,
				'desc'              => wp_sprintf( __( 'Insert shortcode in the custom wishlist page %s', 'woo-assistant' ), '<code>[' . self::wishlistShortcode . ']</code>' )
			),
			'wishlist_max_items'          => array(
				'id'         => 'wishlist_max_items',
				'title'      => __( 'Max items', 'woo-assistant' ),
				'desc'       => __( 'Max wishlist items per user', 'woo-assistant' ),
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
				'title'    => __( 'Auto remove', 'woo-assistant' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => false,
				'desc'     => __( 'Auto remove product from the wishlist after create order.', 'woo-assistant' ),
				'sanitize' => 'bool'
			),
			'wishlist_product_position'   => array(
				'id'          => 'wishlist_product_position',
				'title'       => __( 'Position on single page', 'woo-assistant' ),
				'type'        => 'select',
				'options'     => array(
					'before_title'       => __( 'Before title', 'woo-assistant' ),
					'after_title'        => __( 'After title', 'woo-assistant' ),
					'after_rating'       => __( 'After rating', 'woo-assistant' ),
					'after_price'        => __( 'After price', 'woo-assistant' ),
					'after_excerpt'      => __( 'After excerpt', 'woo-assistant' ),
					'before_add_to_cart' => __( 'Before add to cart button', 'woo-assistant' ),
					'after_add_to_cart'  => __( 'After add to cart button', 'woo-assistant' ),
					'after_meta'         => __( 'After meta', 'woo-assistant' ),
					'after_sharing'      => __( 'After sharing', 'woo-assistant' ),
				),
				'option_none' => __( 'Hide', 'woo-assistant' ),
				'default'     => 'after_add_to_cart',
				'sanitize'    => 'text',
			),
			'wishlist_archive_position'   => array(
				'id'          => 'wishlist_archive_position',
				'title'       => __( 'Position on archive page', 'woo-assistant' ),
				'type'        => 'select',
				'options'     => array(
					'before_title'       => __( 'Before title', 'woo-assistant' ),
					'after_title'        => __( 'After title', 'woo-assistant' ),
					'after_rating'       => __( 'After rating', 'woo-assistant' ),
					'after_price'        => __( 'After price', 'woo-assistant' ),
					'before_add_to_cart' => __( 'Before add to cart button', 'woo-assistant' ),
					'after_add_to_cart'  => __( 'After add to cart button', 'woo-assistant' ),
				),
				'option_none' => __( 'Hide', 'woo-assistant' ),
				'default'     => 'after_add_to_cart',
				'sanitize'    => 'text',
			),
			'end_grid_wishlist_general'   => array(
				'type' => 'endgrid',
			),

			'start_grid_wishlist_button'  => array(
				'title' => __( 'Button', 'woo-assistant' ),
				'type'  => 'startGrid',
			),
			'wishlist_button_type'        => array(
				'id'       => 'wishlist_button_type',
				'title'    => __( 'Type', 'woo-assistant' ),
				'type'     => 'select',
				'options'  => array(
					'button' => __( 'Button', 'woo-assistant' ),
					'a'      => __( 'Link', 'woo-assistant' ),
				),
				'default'  => 'button',
				'sanitize' => 'text',
			),
			'wishlist_button_icon'        => array(
				'id'       => 'wishlist_button_icon',
				'title'    => __( 'Button icon', 'woo-assistant' ),
				'type'     => 'radioInline',
				'default'  => 'wa-icon-heart',
				'options'  => $buttonIcons,
				'sanitize' => 'text'
			),
			'wishlist_button_text'        => array(
				'id'      => 'wishlist_button_text',
				'title'   => __( 'Button text', 'woo-assistant' ),
				'type'    => 'text',
				'default' => __( 'Add to wishlist', 'woo-assistant' ),
			),
			'wishlist_button_remove_text' => array(
				'id'      => 'wishlist_button_remove_text',
				'title'   => __( 'Remove button text', 'woo-assistant' ),
				'type'    => 'text',
				'default' => __( 'Remove from wishlist', 'woo-assistant' ),
			),
			'wishlist_button_browse_text' => array(
				'id'      => 'wishlist_button_browse_text',
				'title'   => __( 'Browse button text', 'woo-assistant' ),
				'type'    => 'text',
				'default' => __( 'Browse wishlist', 'woo-assistant' ),
			),
			'wishlist_added_action'       => array(
				'id'       => 'wishlist_added_action',
				'title'    => __( 'Action product added', 'woo-assistant' ),
				'type'     => 'select',
				'options'  => array(
					'open_page' => __( 'Open wishlist page', 'woo-assistant' ),
					'remove'    => __( 'Remove from wishlist', 'woo-assistant' ),
				),
				'default'  => 'remove',
				'sanitize' => 'text',
				'desc'     => __( 'Select archive button appearance', 'woo-assistant' )
			),
			'wishlist_product_button'     => array(
				'id'       => 'wishlist_product_button',
				'title'    => __( 'Product appearance', 'woo-assistant' ),
				'type'     => 'select',
				'options'  => array(
					'icon'      => __( 'Icon', 'woo-assistant' ),
					'text'      => __( 'Text', 'woo-assistant' ),
					'icon_text' => __( 'Icon with text', 'woo-assistant' ),
				),
				'default'  => 'icon_text',
				'sanitize' => 'text',
				'desc'     => __( 'Select single product button appearance', 'woo-assistant' )
			),
			'wishlist_archive_button'     => array(
				'id'       => 'wishlist_archive_button',
				'title'    => __( 'Archive appearance', 'woo-assistant' ),
				'type'     => 'select',
				'options'  => array(
					'icon'      => __( 'Icon', 'woo-assistant' ),
					'text'      => __( 'Text', 'woo-assistant' ),
					'icon_text' => __( 'Icon with text', 'woo-assistant' ),
				),
				'default'  => 'icon',
				'sanitize' => 'text',
				'desc'     => __( 'Select archive button appearance', 'woo-assistant' )
			),
			'end_grid_wishlist_button'    => array(
				'type' => 'endgrid',
			),


		);

		$sections[ self::sectionID ] = array(
			'title'    => __( 'WishList', 'woo-assistant' ),
			'desc'     => __( 'Product WishList', 'woo-assistant' ),
			'settings' => $settings
		);

		return $sections;
	}

	public function info(): array {
		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Product WishList', 'woo-assistant' ),
			'desc'           => __( 'Add Wishlist features to your store.', 'woo-assistant' ),
			'tags'           => [ __( 'Product', 'woo-assistant' ) ],
			'cat'            => 'product',
			'more_info_link' => 'https://parsa.ws'
		);
	}
}