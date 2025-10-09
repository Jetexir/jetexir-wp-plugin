<?php

namespace AssistantForWooCommerce\App\Product;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Enums\ProductStatus;
use AssistantForWooCommerce\Addons\Addon;
use AssistantForWooCommerce\App\App;
use AssistantForWooCommerce\Helper\Assets;
use AssistantForWooCommerce\Helper\Helper;
use AssistantForWooCommerce\Helper\HTML;
use AssistantForWooCommerce\Helper\Nonce;
use AssistantForWooCommerce\Helper\Notice;
use AssistantForWooCommerce\Helper\Param;
use AssistantForWooCommerce\Helper\Sanitizing;
use AssistantForWooCommerce\Helper\UserMeta;
use AssistantForWooCommerce\Helper\WooCommerce;
use AssistantForWooCommerce\Helper\WordPress;
use AssistantForWooCommerce\Interfaces\AddonInterface;

class ProductWishList extends Addon implements AddonInterface {
	public string $addonID = 'product-wishlist';
	public string $currentTab = 'product';
	public string $currentSection = 'wishlist';
	private const buttonShortCode = 'asfowoo_product_wishlist_button';
	private const wishlistShortcode = 'asfowoo_products_wishlist';
	private const userMeta = ASSISTANTFORWOOCOMMERCE_PLUGIN_KEY . '_wishlist_items';
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
			add_action( 'wp_ajax_asfowoo_product_wishlist_add_remove', [ $this, 'addRemoveItem' ] );
			add_action( 'wp_ajax_asfowoo_product_wishlist_remove', [ $this, 'addRemoveItem' ] );

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
		$menuItems = [ 'wishlist' => __( 'Wishlist', 'assistant-for-woocommerce' ) ];

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
			'message' => __( 'Security code is not valid, page will be refreshed.', 'assistant-for-woocommerce' ),
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
		$listKeys = apply_filters( 'assistant_for_woocommerce_wishlist_list_keys', $listKeys, $userID );
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
			return apply_filters( 'assistant_for_woocommerce_wishlist_items', $wishlist, $userId );;
		}

		$wishlist = $wishlist[ $list ] ?? [];

		$wishlist = apply_filters( 'assistant_for_woocommerce_wishlist_list_items', $wishlist, $list, $userId );

		return $wishlist;
	}

	public function addButton(): void {
		if ( WooCommerce::isProduct() && WordPress::isAction( 'woocommerce_single_product_summary' ) ) {
			$buttonAppearance = $this->getSetting( 'wishlist_product_button', 'icon_text' );
		} else {
			$buttonAppearance = $this->getSetting( 'wishlist_archive_button', 'icon' );
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->buttonShortcode( array(
			'type'         => esc_html( $this->getSetting( 'wishlist_button_type', 'button' ) ),
			'icon'         => wp_kses_post( $this->getButtonIcons( $this->getSetting( 'wishlist_button_icon', 'asfowoo-icon-heart' ), true ) ),
			'text'         => esc_html( $this->getSetting( 'wishlist_button_text', __( 'Add to wishlist', 'assistant-for-woocommerce' ) ) ),
			'appearance'   => esc_html( $buttonAppearance ),
			'remove_text'  => esc_html( $this->getSetting( 'wishlist_button_remove_text', __( 'Remove from wishlist', 'assistant-for-woocommerce' ) ) ),
			'browse_text'  => esc_html( $this->getSetting( 'wishlist_button_browse_text', __( 'Browse wishlist', 'assistant-for-woocommerce' ) ) ),
			'added_action' => esc_html( $this->getSetting( 'wishlist_added_action', 'remove' ) )
		) );
	}

	public function buttonShortcode( $atts ): string {
		$atts = shortcode_atts( array(
			'product_id'    => WooCommerce::getCurrentProductId(),
			'icon'          => $this->getButtonIcons( $this->getSetting( 'wishlist_button_icon', 'asfowoo-icon-heart' ), true ),
			'text'          => $this->getSetting( 'wishlist_button_text', __( 'Add to wishlist', 'assistant-for-woocommerce' ) ),
			'remove_text'   => $this->getSetting( 'wishlist_button_remove_text', __( 'Remove from wishlist', 'assistant-for-woocommerce' ) ),
			'browse_text'   => $this->getSetting( 'wishlist_button_browse_text', __( 'Browse wishlist', 'assistant-for-woocommerce' ) ),
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

		$defaultClass = 'asfowoo-product-wishlist-button ' . ( $exists ? 'asfowoo-product-wishlist-added ' : '' );
		$defaultClass .= $atts['default_class'] === 'on' ? ( $type === 'button' ? 'button asfowoo-button asfowoo-button-secondary asfowoo-inline-flex ' : 'asfowoo-inline-flex ' ) : '';
		$defaultClass .= $exists && $atts['added_action'] === 'remove' ? 'asfowoo-remove-action ' : '';
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
				'message' => __( 'Your wishlist is empty.', 'assistant-for-woocommerce' ),
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
		echo '<div class="asfowoo-product-list-wrap asfowoo-product-wishlist-wrap">';
		echo '<div class="asfowoo-loader-wrap" style="display: none"><div class="asfowoo-loader"></div></div>';
		echo '<div class="asfowoo-product-list-notice" style="display: none">' . esc_html( $emptyNotice ) . '</div>';

		foreach ( $listItems as $productID => $data ) {
			if ( isset( $products[ $productID ] ) ) {
				$product     = $products[ $productID ];
				$productLink = $product->get_permalink();
				$name        = wp_strip_all_tags( $product->get_name() );

				echo '<div class="asfowoo-product-item-wrap asfowoo-product-wishlist-item" data-product-id="' . esc_html( $productID ) . '">';

				// Image
				echo '<a href="' . esc_url_raw( $productLink ) . '" target="_blank" class="asfowoo-product-item-image asfowoo-wishlist-item-image">' . wp_kses_post( $product->get_image() ) . '</a>';

				// Info (Name, Date, Price)
				echo '<div class="asfowoo-product-item-info">';
				echo '<a href="' . esc_url_raw( $productLink ) . '" target="_blank" class="asfowoo-product-item-title">' . esc_html( $name ) . '</a>';
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<div class="asfowoo-product-item-price asfowoo-product-item-meta">' . $product->get_price_html() . '</div>';
				echo '<div class="asfowoo-product-item-date asfowoo-product-item-meta">' . esc_html( wp_date( $dateFormat, $data['timestamp'] ) ) . '</div>';
				echo '</div>';

				echo '<div class="asfowoo-product-item-actions">';
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo WooCommerce::getAddToCartButton( $product );
				echo '<a href="#" class="asfowoo-product-item-remove asfowoo-flex asfowoo-product-wishlist-remove" data-asfowoo-product-remove-action="asfowoo_product_wishlist_remove" data-product-id="' . esc_html( $productID ) . '"><i class="asfowoo-icon-cross"></i> ' . esc_html__( 'Remove', 'assistant-for-woocommerce' ) . '</a>';
				echo '</div>';

				echo '</div>';
			}
		}

		echo '</div>';

		//self::dd( $products );

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
			'asfowoo-icon-heart',
			'asfowoo-icon-heart1',
			'asfowoo-icon-heart2',
			'asfowoo-icon-heart3',
			'asfowoo-icon-heart',
			'asfowoo-icon-bookmark',
			'asfowoo-icon-bookmark_outline',
			'asfowoo-icon-bookmarks',
			'asfowoo-icon-star_rate',
			'asfowoo-icon-star_outline',
			'asfowoo-icon-star_half',
			'asfowoo-icon-check',
			'asfowoo-icon-check1',
			'asfowoo-icon-tick-outline',
			'asfowoo-icon-checkmark',
			'asfowoo-icon-checkmark2',
			'asfowoo-icon-check_circle',
			'asfowoo-icon-check_circle_outline',
			'asfowoo-icon-check_box',
			'asfowoo-icon-library_add_check',
			'asfowoo-icon-library_add',
			'asfowoo-icon-plus',
			'asfowoo-icon-magic-wand',
			'asfowoo-icon-magic-wand1',
			'asfowoo-icon-magic-wand2',
			'asfowoo-icon-magic-wand3',
			'asfowoo-icon-magic-lamp',
			'asfowoo-icon-magic-lamp1',
		);

		if ( is_null( $icon ) ) {
			return $icons;
		}

		$icon = in_array( $icon, $icons, true ) ? $icon : 'asfowoo-icon-heart';

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
		$debugName     = ASSISTANTFORWOOCOMMERCE_DEBUG_MODE ? '' : '.min';

		/*wp_enqueue_style( ASSISTANTFORWOOCOMMERCE_PLUGIN_KEY . '-product-wishlist-style',
			Assets::url( 'css/product-wishlist' . $debugName . '.css' ),
			false, $pluginVersion );*/

		wp_enqueue_script( ASSISTANTFORWOOCOMMERCE_PLUGIN_KEY . '-product-wishlist-script',
			Assets::url( 'js/product-wishlist.min.js' ),
			[ ASSISTANTFORWOOCOMMERCE_PLUGIN_SLUG . '-global' ], $pluginVersion, [ 'in_footer' => true ] );

		wp_localize_script( ASSISTANTFORWOOCOMMERCE_PLUGIN_KEY . '-product-wishlist-script', ASSISTANTFORWOOCOMMERCE_PLUGIN_KEYCAP . 'ProductWishlist', array(
			'maxItems'           => $this->getSetting( 'wishlist_max_items', 10 ),
			'maxExceededMessage' => __( 'It is not possible to add more than %number% product to the wishlist.', 'assistant-for-woocommerce' ),
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
				'title' => __( 'Products Wishlist', 'assistant-for-woocommerce' ),
				'type'  => 'startGrid',
			),
			'wishlist_page'               => array(
				'id'                => 'wishlist_page',
				'title'             => __( 'Wishlist page', 'assistant-for-woocommerce' ),
				'type'              => 'postSelect',
				'args'              => array(
					'post_type' => 'page'
				),
				'default'           => 0,
				'option_none'       => __( 'Add tab to "My account" page', 'assistant-for-woocommerce' ),
				'option_none_value' => 0,
				/* translators: %s: Shortcode */
				'desc'              => wp_sprintf( __( 'Insert shortcode in the custom wishlist page %s', 'assistant-for-woocommerce' ), '<code  class="asfowoo-copy-text">[' . self::wishlistShortcode . ']</code>' )
			),
			'wishlist_max_items'          => array(
				'id'         => 'wishlist_max_items',
				'title'      => __( 'Max items', 'assistant-for-woocommerce' ),
				'desc'       => __( 'Max wishlist items per user', 'assistant-for-woocommerce' ),
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
				'title'    => __( 'Auto remove', 'assistant-for-woocommerce' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => false,
				'desc'     => __( 'Auto remove product from the wishlist after create order.', 'assistant-for-woocommerce' ),
				'sanitize' => 'bool'
			),
			'wishlist_product_position'   => array(
				'id'          => 'wishlist_product_position',
				'title'       => __( 'Position on single page', 'assistant-for-woocommerce' ),
				'type'        => 'select',
				'options'     => array(
					'before_title'       => __( 'Before title', 'assistant-for-woocommerce' ),
					'after_title'        => __( 'After title', 'assistant-for-woocommerce' ),
					'after_rating'       => __( 'After rating', 'assistant-for-woocommerce' ),
					'after_price'        => __( 'After price', 'assistant-for-woocommerce' ),
					'after_excerpt'      => __( 'After excerpt', 'assistant-for-woocommerce' ),
					'before_add_to_cart' => __( 'Before add to cart button', 'assistant-for-woocommerce' ),
					'after_add_to_cart'  => __( 'After add to cart button', 'assistant-for-woocommerce' ),
					'after_meta'         => __( 'After meta', 'assistant-for-woocommerce' ),
					'after_sharing'      => __( 'After sharing', 'assistant-for-woocommerce' ),
				),
				'option_none' => __( 'Hide', 'assistant-for-woocommerce' ),
				'default'     => 'after_add_to_cart',
				'sanitize'    => 'text',
			),
			'wishlist_archive_position'   => array(
				'id'          => 'wishlist_archive_position',
				'title'       => __( 'Position on archive page', 'assistant-for-woocommerce' ),
				'type'        => 'select',
				'options'     => array(
					'before_title'       => __( 'Before title', 'assistant-for-woocommerce' ),
					'after_title'        => __( 'After title', 'assistant-for-woocommerce' ),
					'after_rating'       => __( 'After rating', 'assistant-for-woocommerce' ),
					'after_price'        => __( 'After price', 'assistant-for-woocommerce' ),
					'before_add_to_cart' => __( 'Before add to cart button', 'assistant-for-woocommerce' ),
					'after_add_to_cart'  => __( 'After add to cart button', 'assistant-for-woocommerce' ),
				),
				'option_none' => __( 'Hide', 'assistant-for-woocommerce' ),
				'default'     => 'after_add_to_cart',
				'sanitize'    => 'text',
			),
			'end_grid_wishlist_general'   => array(
				'type' => 'endgrid',
			),

			'start_grid_wishlist_button'  => array(
				'title' => __( 'Button', 'assistant-for-woocommerce' ),
				'type'  => 'startGrid',
			),
			'wishlist_button_type'        => array(
				'id'       => 'wishlist_button_type',
				'title'    => __( 'Type', 'assistant-for-woocommerce' ),
				'type'     => 'select',
				'options'  => array(
					'button' => __( 'Button', 'assistant-for-woocommerce' ),
					'a'      => __( 'Link', 'assistant-for-woocommerce' ),
				),
				'default'  => 'button',
				'sanitize' => 'text',
			),
			'wishlist_button_icon'        => array(
				'id'       => 'wishlist_button_icon',
				'title'    => __( 'Button icon', 'assistant-for-woocommerce' ),
				'type'     => 'radioInline',
				'default'  => 'asfowoo-icon-heart',
				'options'  => $buttonIcons,
				'sanitize' => 'text'
			),
			'wishlist_button_text'        => array(
				'id'      => 'wishlist_button_text',
				'title'   => __( 'Button text', 'assistant-for-woocommerce' ),
				'type'    => 'text',
				'default' => __( 'Add to wishlist', 'assistant-for-woocommerce' ),
			),
			'wishlist_button_remove_text' => array(
				'id'      => 'wishlist_button_remove_text',
				'title'   => __( 'Remove button text', 'assistant-for-woocommerce' ),
				'type'    => 'text',
				'default' => __( 'Remove from wishlist', 'assistant-for-woocommerce' ),
			),
			'wishlist_button_browse_text' => array(
				'id'      => 'wishlist_button_browse_text',
				'title'   => __( 'Browse button text', 'assistant-for-woocommerce' ),
				'type'    => 'text',
				'default' => __( 'Browse wishlist', 'assistant-for-woocommerce' ),
			),
			'wishlist_added_action'       => array(
				'id'       => 'wishlist_added_action',
				'title'    => __( 'Action after adding the product', 'assistant-for-woocommerce' ),
				'desc'     => __( 'Specify the action to perform following product addition', 'assistant-for-woocommerce' ),
				'type'     => 'select',
				'options'  => array(
					'open_page' => __( 'Open wishlist page', 'assistant-for-woocommerce' ),
					'remove'    => __( 'Remove from wishlist', 'assistant-for-woocommerce' ),
				),
				'default'  => 'remove',
				'sanitize' => 'text',
			),
			'wishlist_product_button'     => array(
				'id'       => 'wishlist_product_button',
				'title'    => __( 'Product appearance', 'assistant-for-woocommerce' ),
				'type'     => 'select',
				'options'  => array(
					'icon'      => __( 'Icon', 'assistant-for-woocommerce' ),
					'text'      => __( 'Text', 'assistant-for-woocommerce' ),
					'icon_text' => __( 'Icon with text', 'assistant-for-woocommerce' ),
				),
				'default'  => 'icon_text',
				'sanitize' => 'text',
				'desc'     => __( 'Select single product button appearance', 'assistant-for-woocommerce' )
			),
			'wishlist_archive_button'     => array(
				'id'       => 'wishlist_archive_button',
				'title'    => __( 'Archive appearance', 'assistant-for-woocommerce' ),
				'type'     => 'select',
				'options'  => array(
					'icon'      => __( 'Icon', 'assistant-for-woocommerce' ),
					'text'      => __( 'Text', 'assistant-for-woocommerce' ),
					'icon_text' => __( 'Icon with text', 'assistant-for-woocommerce' ),
				),
				'default'  => 'icon',
				'sanitize' => 'text',
				'desc'     => __( 'Select archive button appearance', 'assistant-for-woocommerce' )
			),
			'end_grid_wishlist_button'    => array(
				'type' => 'endgrid',
			),


		);

		$sections[ $this->currentSection ] = array(
			'title'        => __( 'WishList', 'assistant-for-woocommerce' ),
			'desc'         => __( 'Products WishList', 'assistant-for-woocommerce' ),
			'settings_key' => $this->addonID,
			'settings'     => $settings
		);

		return $sections;
	}

	public function info(): array {
		$icon = '<svg fill="#873eff" viewBox="-6.4 -6.4 76.80 76.80" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"><path transform="translate(-6.4, -6.4), scale(2.4)" d="M16,29.448634807020426C17.908411268544064,30.062035941845213,20.24816837042389,30.42145879134935,21.947305690156085,29.35786728574036C23.699563215455186,28.26102467160645,23.3755833530513,25.421098709229852,24.810897638483052,23.93336786758443C26.314409877076624,22.374948536814543,29.564008330192664,22.681024425683788,30.33950779532143,20.659188516985232C31.097491189996294,18.68301946147673,28.737577694983486,16.78286790019963,28.351786056647754,14.701774972350794C27.999636605427813,12.802159585036712,29.20473886520523,10.605332250403379,28.172935637598357,8.971952332804605C27.13250750923678,7.3249189013312055,24.71300625216217,7.310081211385153,23.045824094314497,6.3022551017653505C21.516721891403872,5.377899525431689,20.37669925515836,3.894852270020933,18.709797884033932,3.251403284172982C16.936809669404468,2.567003182051679,15.02498865179598,2.4481238532298586,13.124647905150628,2.4725319592196726C10.986438740582319,2.499995263371469,8.495598325261021,2.040275575067282,6.84829106877217,3.403753285643445C5.177242392724141,4.786881790726136,5.765442378971156,7.586295885138002,4.678419800072371,9.463482623919838C3.5896928415611074,11.343612667531307,0.8967561208412813,12.23500040160054,0.49769448534420846,14.370642034027211C0.10503287727184463,16.47203290466402,1.3707513534531974,18.623346539808992,2.646262973004834,20.33889217838538C3.824381937871543,21.923446031851384,5.775103819303469,22.645759648633828,7.439782158288802,23.707654764747282C8.77677678807254,24.56052107194189,10.224700626740333,25.13001535857779,11.541780227607713,26.01332555493994C13.11710884472485,27.069832564238588,14.194183316780032,28.86820960149451,16,29.448634807020426" fill="#ffffff" strokewidth="0"></path></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#873eff" stroke-width="1.28"> <g data-name="24 wishlist" id="_24_wishlist"> <path d="M27.11,17.74a1,1,0,0,1-1,1H16.79a1,1,0,0,1,0-2h9.32A1,1,0,0,1,27.11,17.74Z"></path> <path d="M39.79,16.65,35.71,20a1.025,1.025,0,0,1-.64.23.948.948,0,0,1-.65-.25l-2.78-2.42a1,1,0,0,1-.1-1.41,1.011,1.011,0,0,1,1.42-.1l2.13,1.87,3.44-2.82a.989.989,0,0,1,1.4.14A1,1,0,0,1,39.79,16.65Z"></path> <path d="M27.11,27.06a1,1,0,0,1-1,1H16.79a1,1,0,0,1,0-2h9.32A1,1,0,0,1,27.11,27.06Z"></path> <path d="M39.79,25.97l-4.08,3.35a.97.97,0,0,1-.64.23.948.948,0,0,1-.65-.25l-2.78-2.42a1,1,0,0,1-.1-1.41,1.011,1.011,0,0,1,1.42-.1l2.13,1.87,3.44-2.82a.989.989,0,0,1,1.4.14A1,1,0,0,1,39.79,25.97Z"></path> <path d="M27.11,36.38a1,1,0,0,1-1,1H16.79a1,1,0,0,1,0-2h9.32A1,1,0,0,1,27.11,36.38Z"></path> <path d="M39.79,35.29l-4.08,3.36a1.015,1.015,0,0,1-.64.22.987.987,0,0,1-.65-.24L31.64,36.2a1,1,0,0,1-.1-1.41,1.01,1.01,0,0,1,1.42-.09l2.13,1.86,3.44-2.82a1,1,0,0,1,1.26,1.55Z"></path> <path d="M27.11,45.7a1,1,0,0,1-1,1H16.79a1,1,0,0,1,0-2h9.32A1,1,0,0,1,27.11,45.7Z"></path> <path d="M45.75,38.46V9.93A3.718,3.718,0,0,0,41.96,6.3H35.5V5.5a2.006,2.006,0,0,0-2-2H22.45a2.006,2.006,0,0,0-2,2v.8H13.99a3.727,3.727,0,0,0-3.8,3.63V52.2a3.728,3.728,0,0,0,3.8,3.64H33.45a11.248,11.248,0,1,0,12.3-17.38ZM22.45,5.5H33.5V9.09H22.45ZM13.99,53.84a1.752,1.752,0,0,1-1.8-1.64V9.93a1.751,1.751,0,0,1,1.8-1.63h6.46v.79a2,2,0,0,0,2,2H33.5a2,2,0,0,0,2-2V8.3h6.46a1.741,1.741,0,0,1,1.79,1.63V38.06a11.726,11.726,0,0,0-1.2-.07A11.238,11.238,0,0,0,32.29,53.84ZM42.55,58.5a9.255,9.255,0,1,1,9.26-9.25A9.261,9.261,0,0,1,42.55,58.5Z"></path> <path d="M49.52,46.61c-.01-.11-.03-.21-.05-.32a3.519,3.519,0,0,0-3.48-2.94h-.02a5,5,0,0,0-3.42,1.46,4.963,4.963,0,0,0-3.42-1.46h-.01a3.326,3.326,0,0,0-.96.15.749.749,0,0,0-.16.04,3.5,3.5,0,0,0-2.01,1.73c-.01.03-.02.05-.03.08a3.682,3.682,0,0,0-.33.95c-.02.1-.03.2-.05.31-.65,4.9,4.37,8.58,5.89,9.57l.51.35a.931.931,0,0,0,.57.19.959.959,0,0,0,.58-.19l.47-.33C45.15,55.19,50.17,51.51,49.52,46.61ZM42.55,54.5c-2.67-1.76-5.38-4.67-4.98-7.63l.03-.21a1.526,1.526,0,0,1,1.52-1.31,3.026,3.026,0,0,1,2.54,1.58,1.039,1.039,0,0,0,1.78,0,3.039,3.039,0,0,1,2.54-1.58,1.518,1.518,0,0,1,1.52,1.3l.04.22C47.93,49.82,45.25,52.72,42.55,54.5Z"></path> </g> </g><g id="SVGRepo_iconCarrier"> <g data-name="24 wishlist" id="_24_wishlist"> <path d="M27.11,17.74a1,1,0,0,1-1,1H16.79a1,1,0,0,1,0-2h9.32A1,1,0,0,1,27.11,17.74Z"></path> <path d="M39.79,16.65,35.71,20a1.025,1.025,0,0,1-.64.23.948.948,0,0,1-.65-.25l-2.78-2.42a1,1,0,0,1-.1-1.41,1.011,1.011,0,0,1,1.42-.1l2.13,1.87,3.44-2.82a.989.989,0,0,1,1.4.14A1,1,0,0,1,39.79,16.65Z"></path> <path d="M27.11,27.06a1,1,0,0,1-1,1H16.79a1,1,0,0,1,0-2h9.32A1,1,0,0,1,27.11,27.06Z"></path> <path d="M39.79,25.97l-4.08,3.35a.97.97,0,0,1-.64.23.948.948,0,0,1-.65-.25l-2.78-2.42a1,1,0,0,1-.1-1.41,1.011,1.011,0,0,1,1.42-.1l2.13,1.87,3.44-2.82a.989.989,0,0,1,1.4.14A1,1,0,0,1,39.79,25.97Z"></path> <path d="M27.11,36.38a1,1,0,0,1-1,1H16.79a1,1,0,0,1,0-2h9.32A1,1,0,0,1,27.11,36.38Z"></path> <path d="M39.79,35.29l-4.08,3.36a1.015,1.015,0,0,1-.64.22.987.987,0,0,1-.65-.24L31.64,36.2a1,1,0,0,1-.1-1.41,1.01,1.01,0,0,1,1.42-.09l2.13,1.86,3.44-2.82a1,1,0,0,1,1.26,1.55Z"></path> <path d="M27.11,45.7a1,1,0,0,1-1,1H16.79a1,1,0,0,1,0-2h9.32A1,1,0,0,1,27.11,45.7Z"></path> <path d="M45.75,38.46V9.93A3.718,3.718,0,0,0,41.96,6.3H35.5V5.5a2.006,2.006,0,0,0-2-2H22.45a2.006,2.006,0,0,0-2,2v.8H13.99a3.727,3.727,0,0,0-3.8,3.63V52.2a3.728,3.728,0,0,0,3.8,3.64H33.45a11.248,11.248,0,1,0,12.3-17.38ZM22.45,5.5H33.5V9.09H22.45ZM13.99,53.84a1.752,1.752,0,0,1-1.8-1.64V9.93a1.751,1.751,0,0,1,1.8-1.63h6.46v.79a2,2,0,0,0,2,2H33.5a2,2,0,0,0,2-2V8.3h6.46a1.741,1.741,0,0,1,1.79,1.63V38.06a11.726,11.726,0,0,0-1.2-.07A11.238,11.238,0,0,0,32.29,53.84ZM42.55,58.5a9.255,9.255,0,1,1,9.26-9.25A9.261,9.261,0,0,1,42.55,58.5Z"></path> <path d="M49.52,46.61c-.01-.11-.03-.21-.05-.32a3.519,3.519,0,0,0-3.48-2.94h-.02a5,5,0,0,0-3.42,1.46,4.963,4.963,0,0,0-3.42-1.46h-.01a3.326,3.326,0,0,0-.96.15.749.749,0,0,0-.16.04,3.5,3.5,0,0,0-2.01,1.73c-.01.03-.02.05-.03.08a3.682,3.682,0,0,0-.33.95c-.02.1-.03.2-.05.31-.65,4.9,4.37,8.58,5.89,9.57l.51.35a.931.931,0,0,0,.57.19.959.959,0,0,0,.58-.19l.47-.33C45.15,55.19,50.17,51.51,49.52,46.61ZM42.55,54.5c-2.67-1.76-5.38-4.67-4.98-7.63l.03-.21a1.526,1.526,0,0,1,1.52-1.31,3.026,3.026,0,0,1,2.54,1.58,1.039,1.039,0,0,0,1.78,0,3.039,3.039,0,0,1,2.54-1.58,1.518,1.518,0,0,1,1.52,1.3l.04.22C47.93,49.82,45.25,52.72,42.55,54.5Z"></path> </g> </g></svg>';

		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Products WishList', 'assistant-for-woocommerce' ),
			'desc'           => __( 'Add wishlist functionality to your store.', 'assistant-for-woocommerce' ),
			'tags'           => [ __( 'Product', 'assistant-for-woocommerce' ) ],
			'cat'            => 'product',
			'icon'           => $icon,
			'more_info_link' => 'https://parsa.ws',
			'settings_key'   => $this->addonID,
		);
	}
}