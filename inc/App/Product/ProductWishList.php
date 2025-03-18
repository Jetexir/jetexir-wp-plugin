<?php

namespace WooAssistant\App\Product;

use WooAssistant\Addons\Addon;
use WooAssistant\App\App;
use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Nonce;
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
	private const shortCode = 'wa_product_wishlist';
	private const userMeta = WOOASSISTANT_PLUGIN_KEY . '_wishlist_items';

	public function initAction(): void {
		add_filter( 'woo_assistant_product_settings_sections', [ $this, 'addSectionSettings' ] );
		App::addShortcode( self::shortCode, [ $this, 'wishlistShortcode' ] );

		if ( WordPress::isUserLoggedIn() ) {
			add_action( 'wp_ajax_wa_product_wishlist_add_remove', [ $this, 'addRemoveItem' ] );
			if ( $position = Settings::get( 'wishlist_product_position', 'after_add_to_cart' ) ) {
				add_action( 'woocommerce_single_product_summary', [
					$this,
					'addButton'
				], $this->getProductPostion( $position ) );
			}
		}
	}

	public function addRemoveItem(): void {
		if ( Nonce::verify() ) {
			$productID = Sanitizing::int( Param::post( 'product_id', 0 ) );
			$list      = 'default'; //Sanitizing::text( Param::post( 'list', 'default' ) );
			$max       = Settings::get( 'wishlist_max_items', 10 );
			$update    = $this->updateStorage( $productID, $list, $max );

			$data = array(
				'status'   => $update['status'],
				'list'     => $list,
				'count'    => $update['count'],
				'max'      => (int) $max,
				'redirect' => $update['status'] === 'max_exceeded' ? get_permalink( Settings::get( 'product_compare_page', 0 ) ) : ''
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
	private function updateStorage( int $productID, $list = 'default', int $max = 10 ): array {
		$productIDs = self::getListItems( $list );
		$count      = count( $productIDs );
		$status     = 'added';

		if ( ( $key = array_search( $productID, $productIDs, true ) ) !== false ) {
			unset( $productIDs[ $key ] );
			$productIDs = array_values( $productIDs );
			$status     = 'removed';
			$count --;
		} else {
			if ( $count >= $max ) {
				return [ 'status' => 'max_exceeded', 'count' => $count ];
			}

			$productIDs[] = $productID;
			$count ++;
		}

		if ( ! self::saveListItems( $list, $productIDs ) ) {
			$status = 'error';
		}

		return [ 'status' => $status, 'count' => $count ];
	}

	public static function checkExistsItem( $productID, $list = 'default' ): bool {
		$productID  = (int) $productID;
		$productIDs = self::getListItems( $list );

		return in_array( $productID, $productIDs, true );
	}

	public static function saveListItems( $list, $productIDs, $userId = 0 ) {
		if ( $userId === 0 ) {
			$userId = WordPress::getCurrentUserID();
		}

		$wishlist          = self::getListItems();
		$wishlist[ $list ] = $productIDs;

		return UserMeta::update( $userId, self::userMeta, $wishlist );
	}

	public static function getListItems( $list = null, $userId = 0 ): array {
		if ( $userId === 0 ) {
			$userId = WordPress::getCurrentUserID();
		}

		$wishlist = UserMeta::get( $userId, self::userMeta );
		$wishlist = is_array( $wishlist ) ? $wishlist : [];

		if ( is_null( $list ) ) {
			return $wishlist;
		}

		$wishlist = $wishlist[ $list ] ?? [];
		$wishlist = array_filter( $wishlist );

		return array_map( 'intval', $wishlist );
	}

	public function addButton() {
		$type = Settings::get( 'wishlist_button_type', 'button' );
		$icon = $this->getButtonIcons( Settings::get( 'wishlist_button_icon', 'wa-icon-heart' ), true );
		$text = Settings::get( 'wishlist_button_text', __( 'Add to wishlist', 'woo-assistant' ) );

		if ( WooCommerce::isProduct() ) {
			$buttonAppearance = Settings::get( 'wishlist_product_button', 'icon_text' );
		}

		echo $this->wishlistShortcode( array(
			'type'              => $type,
			'icon'              => $icon,
			'text'              => $text,
			'button_appearance' => $buttonAppearance,
		) );
	}

	public function wishlistShortcode( $atts ): string {
		$atts = shortcode_atts( array(
			'product_id'        => WooCommerce::getCurrentProductId(),
			'icon'              => '<i class="wa-icon-heart"></i>',
			'text'              => __( 'Add to wishlist', 'woo-assistant' ),
			'type'              => 'button',
			'button_appearance' => 'icon',
			'class'             => '',
			'default_class'     => 'on'
		), $atts, self::shortCode );

		if ( empty( $atts['text'] ) ) {
			return '';
		}

		$type       = in_array( $atts['type'], [ 'button', 'a' ] ) ? $atts['type'] : 'button';
		$appearance = in_array( $atts['button_appearance'], [
			'icon',
			'text',
			'icon_text'
		] ) ? $atts['button_appearance'] : 'icon';

		if ( $appearance === 'icon' ) {
			$title = $atts['icon'];
		} else if ( $appearance === 'text' ) {
			$title = $atts['text'];
		} else {
			$title = $atts['icon'] . ' ' . $atts['text'];
		}

		$exists       = self::checkExistsItem( $atts['product_id'] );
		$defaultClass = 'wa-product-wishlist-button ' . ( $exists ? 'wa-button-remove ' : '' );
		$defaultClass .= $atts['default_class'] === 'on' ? ( $type === 'button' ? 'button wa-button wa-button-secondary wa-inline-flex' : 'wa-inline-flex' ) : '';
		$class        = trim( $defaultClass . ' ' . $atts['class'] );

		return '<' . $type . ' ' . ( $type === 'a' ? 'href="#"' : 'type="button"' ) . ' class="' . $class . '" data-id="' . $atts['product_id'] . '" data-in-wishlist="">' . $title . '</' . $type . '>';
	}

	private function getProductPostion( $position ) {
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
		if ( ! WooCommerce::isWoocommerce() ) {
			return;
		}

		$pluginVersion = Assets::getVersion();
		$debugName     = WOOASSISTANT_DEBUG_MODE ? '' : '.min';

		wp_enqueue_style( WOOASSISTANT_PLUGIN_KEY . '-product-wishlist-style',
			Assets::url( 'css/product-wishlist' . $debugName . '.css' ),
			false, $pluginVersion );

		wp_enqueue_script( WOOASSISTANT_PLUGIN_KEY . '-product-wishlist-script',
			Assets::url( 'js/product-wishlist.min.js' ),
			[ WOOASSISTANT_PLUGIN_SLUG . '-global' ], $pluginVersion, [ 'in_footer' => true ] );

		wp_localize_script( WOOASSISTANT_PLUGIN_KEY . '-product-wishlist-script', WOOASSISTANT_PLUGIN_KEYCAP . 'ProductWishlist', array(
			'maxItems'           => Settings::get( 'wishlist_max_items', 10 ),
			'maxExceededMessage' => __( 'It is not possible to add more than %number% product to the wishlist.', 'woo-assistant' ),
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
			'wishlist_product_position'  => array(
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
			'wishlist_archive_position'  => array(
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

			'start_grid_wishlist_button' => array(
				'title' => __( 'Button', 'woo-assistant' ),
				'type'  => 'startGrid',
			),
			'wishlist_button_type'       => array(
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
			'wishlist_button_icon'       => array(
				'id'       => 'wishlist_button_icon',
				'title'    => __( 'Button icon', 'woo-assistant' ),
				'type'     => 'radioInline',
				'default'  => 'wa-icon-heart',
				'options'  => $buttonIcons,
				'sanitize' => 'text'
			),
			'wishlist_button_text'       => array(
				'id'      => 'wishlist_button_text',
				'title'   => __( 'Button text', 'woo-assistant' ),
				'type'    => 'text',
				'default' => __( 'Add to wishlist', 'woo-assistant' ),
			),
			'wishlist_product_button'    => array(
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
			'wishlist_archive_button'    => array(
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
			'end_grid_wishlist_button'   => array(
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