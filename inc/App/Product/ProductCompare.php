<?php

namespace WooAssistant\App\Product;

use Automattic\WooCommerce\Utilities\I18nUtil;
use WooAssistant\App\App;
use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Cookie;
use WooAssistant\Helper\DebugTrait;
use WooAssistant\Helper\Nonce;
use WooAssistant\Helper\Notice;
use WooAssistant\Helper\WooCommerce;
use WooAssistant\Helper\WordPress;
use WooAssistant\Settings\Settings;

class ProductCompare {
	private const sectionID = 'compare';
	private const shortCode = 'wa_products_compare';
	private const cookieName = 'wc_products_compare';
	private const maxItems = 4;

	public function __construct() {
		add_filter( 'woo_assistant_product_settings_sections', [ $this, 'addSectionSettings' ] );
		add_action( 'woo_assistant_init', [ $this, 'init' ] );
	}

	public function init() {
		if ( Settings::get( 'product_compare_enable', false ) ) {
			App::addShortcode( self::shortCode, [ $this, 'compareShortcode' ] );
			if ( Settings::get( 'product_compare_archive_button', false ) ) {
				add_action( 'woocommerce_after_shop_loop_item', [ $this, 'addButton' ], 9999 );
			}
			add_action( 'woocommerce_after_add_to_cart_button', [ $this, 'addButton' ], 9999 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueueScripts' ) );
			add_action( 'wp_ajax_wa_product_compare_add_remove', [ $this, 'addRemoveItem' ] );
			add_action( 'wp_ajax_nopriv_wa_product_compare_add_remove', [ $this, 'addRemoveItem' ] );
		}
	}

	public function compareShortcode( $atts ) {
		$atts = shortcode_atts( array(
			'max_items'  => null,
			'image_size' => null,
		), $atts, self::shortCode );

		$maxItems = (int) ( is_null( $atts['max_items'] ) ? Settings::get( 'product_compare_max_items', 2 ) : $atts['max_items'] );
		$maxItems = min( $maxItems, self::maxItems );

		$imageSizes = array_keys( Assets::getImageSizes() );
		$imageSize  = is_null( $atts['image_size'] ) ? Settings::get( 'product_compare_image_size', 'thumbnail' ) : $atts['image_size'];
		$imageSize  = in_array( $imageSize, $imageSizes, true ) ? $imageSize : '';

		ob_start();

		$productIDs = $this->getStorageItems();

		if ( empty( $productIDs ) ) {
			Notice::addAndDisplay( 'product-compare', array(
				array(
					'type'    => 'warning',
					'message' => __( 'Your product compare list is empty', 'woo-assistant' ),
				)
			) );

		} else {
			$productIDs = array_slice( $productIDs, 0, $maxItems );
			$products   = WooCommerce::getProducts( array(
				'limit'   => $maxItems,
				'orderby' => 'date',
				'order'   => 'DESC',
				'include' => $productIDs
			) );

			if ( count( $products ) ) {
				$addToCardButton = Settings::get( 'product_compare_add_to_cart_button', false );
				$fields          = Product::getFields();
				$attributes      = WooCommerce::getAttributeTaxonomies();
				$data            = [ 'count' => count( $products ) ];
				?>
                <div class="wa-product-compare-wrapper wa-product-compare-cols-<?php echo $data['count'] ?>">
					<?php
					/**
					 * \WC_Product $product
					 */
					foreach ( $products as $product ) {
						$productID = $product->get_id();
						$imageID   = (int) $product->get_image_id();

						$data['removeButton'][] = '<button type="button" class="button wa-button wa-button-product-compare wa-button-remove" data-id="' . $productID . '" data-action="refresh"><svg width="28px" height="28px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 8L8 16M8.00001 8L16 16" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>';

						$data['images'][] = ! empty( $imageSize ) && $imageID ? wp_get_attachment_image( $imageID, $imageSize, false,
							[ 'class' => 'wa-product-compare-image' ] ) : '';

						if ( ! $product->is_visible() ) {
							$data['title'][] = esc_html( $product->get_name() );
						} else {
							$data['title'][] = sprintf( '<a href="%s">%s</a>', esc_url( $product->get_permalink() ),
								esc_html( $product->get_name() ) );
						}

						if ( $addToCardButton ) {
							$button = '';
							if ( $product->is_purchasable() && $product->is_in_stock() ) {
								$button = WooCommerce::getAddToCardButton( $product );
							}

							$data['addToCard'][] = $button;
						}

						foreach ( $fields as $key => $field ) {
							if ( Settings::get( 'product_compare_display_field_' . $key, false ) ) {
								$value = false;

								if ( $key === 'brand' ) {
									$value = do_shortcode( '[product_brand post_id="' . $productID . '" class="test"]' );

								} elseif ( $key === 'dimensions' && $product->has_dimensions() ) {
									$value = preg_replace( '/ /', '', $product->get_dimensions(), 4 );

								} elseif ( $key === 'weight' && $product->has_weight() ) {
									$weight_unit_label = I18nUtil::get_weight_unit_label( get_option( 'woocommerce_weight_unit',
										'g' ) );
									$value             = $product->get_weight() . ' ' . $weight_unit_label;

								} elseif ( $key === 'stock' ) {
									$availability = $product->get_availability();
									$value        = sprintf( '<span class="%s">%s</span>',
										esc_attr( $availability['class'] ),
										$availability['availability'] ? esc_html( $availability['availability'] ) : esc_html__( 'In stock',
											'woo-assistant' ) );

								} elseif ( $key === 'rating' && wc_review_ratings_enabled() ) {
									$value = wc_get_rating_html( $product->get_average_rating() );

								} elseif ( $key === 'price' && $price_html = $product->get_price_html() ) {
									$value = sprintf( '<span class="price">%s</span>', $price_html );
								}

								if ( $value === false ) {
									continue;
								}
								$data['fields'][ $key ]['label']   = $field;
								$data['fields'][ $key ]['value'][] = $value;
							}
						}

						foreach ( $attributes as $key => $attribute ) {
							if ( Settings::get( 'product_compare_display_attribute_' . $key, false ) ) {
								$data['fields'][ $key ]['label']   = $attribute['label'];
								$data['fields'][ $key ]['value'][] = $product->get_attribute( $attribute['name'] );
							}
						}
					}

					// Head
					echo '<div class="wa-product-compare-row wa-product-compare-head">';
					foreach ( $data['title'] as $i => $title ) {
						echo '<div class="wa-product-compare-col">';
						echo $data['removeButton'][ $i ];
						echo $data['images'][ $i ];
						echo $title;
						if ( ! empty( $data['addToCard'][ $i ] ) ) {
							echo '<div class="wa-product-compare-add-to-card">' . $data['addToCard'][ $i ] . '</div>';
						}
						echo '</div>';
					}
					echo '</div>';

					// Fields
					foreach ( $data['fields'] as $key => $field ) {
						$value = array_filter( $field['value'] );
						if ( empty( $value ) ) {
							continue;
						}

						echo '<div class="wa-product-compare-field-title">';
						echo $field['label'];
						echo '</div>';
						echo '<div class="wa-product-compare-row wa-product-compare-row-' . $key . '">';
						foreach ( $field['value'] as $i => $value ) {
							echo '<div class="wa-product-compare-col">';
							echo empty( $value ) ? '---' : $value;
							echo '</div>';
						}
						echo '</div>';
					}
					?>
                </div>
				<?php

			} else {
				Notice::addAndDisplay( 'product-compare', array(
					array(
						'type'    => 'warning',
						'message' => __( 'Your product compare list is empty', 'woo-assistant' ),
					)
				) );
			}
		}

		return ob_get_clean();
	}

	/**
	 * Add or remove product id from storage
	 *
	 * @return void
	 */
	public function addRemoveItem(): void {
		$productID = (int) $_POST['product_id'];

		if ( Nonce::verify() ) {
			$max    = Settings::get( 'product_compare_max_items', 2 );
			$update = $this->updateStorage( $productID, $max );

			$data = array(
				'status'   => $update['status'],
				'count'    => $update['count'],
				'max'      => (int) $max,
				'redirect' => $update['count'] >= $max ? get_permalink( Settings::get( 'product_compare_page', 0 ) ) : ''
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
	 * @param int $max Max items
	 *
	 * @return array Return status and count of items
	 */
	private function updateStorage( $productID, $max = 2 ): array {
		$productIDs = $this->getStorageItems();
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

		$productIDs = json_encode( $productIDs );
		$expire     = current_time( 'timestamp' ) + HOUR_IN_SECONDS;
		Cookie::set( self::cookieName, $productIDs, $expire );

		return [ 'status' => $status, 'count' => $count ];
	}

	/**
	 * Print add to compare button
	 *
	 * @return void
	 */
	public function addButton(): void {
		$productID = get_the_ID();
		$exists    = $this->checkExistsItem( $productID );
		echo '<button type="button" class="button wa-button wa-button-product-compare' . ( $exists ? ' wa-button-remove' : '' ) . '" data-id="' . $productID . '" data-action="non">' .
		     __( 'Compare', 'woo-assistant' )
		     . '</button>';
	}

	/**
	 * Get all product ids
	 *
	 * @return array
	 */
	private function getStorageItems(): array {
		$value      = Cookie::get( self::cookieName, '' );
		$productIDs = json_decode( $value, true );
		$productIDs = is_array( $productIDs ) ? $productIDs : [];
		$productIDs = array_filter( $productIDs );
		$productIDs = array_values( $productIDs );

		return array_map( 'intval', $productIDs );
	}

	/**
	 * Check exists product id
	 *
	 * @param int $productID Product id
	 *
	 * @return bool Product id exists status
	 */
	private function checkExistsItem( $productID ): bool {
		return in_array( $productID, $this->getStorageItems(), true );
	}

	/**
	 * Enqueue style and script
	 *
	 * @return void
	 */
	public function enqueueScripts(): void {
		$pluginVersion = Assets::getVersion();

		if ( ! WooCommerce::isWoocommerce() && ! WordPress::isPage( Settings::get( 'product_compare_page', 0 ) ) ) {
			return;
		}

		wp_enqueue_style( WOOASSISTANT_PLUGIN_KEY . '-product-compare-style',
			Assets::url( 'css/product-compare.min.css' ),
			false, $pluginVersion );

		wp_enqueue_script( WOOASSISTANT_PLUGIN_KEY . '-product-compare-script',
			Assets::url( 'js/product-compare.min.js' ),
			[ 'jquery' ], $pluginVersion, [ 'in_footer' => true ] );

		wp_localize_script( WOOASSISTANT_PLUGIN_KEY . '-product-compare-script', 'WooAssistantProductCompare', array(
			'ajaxurl'            => admin_url( 'admin-ajax.php' ),
			'ajaxnonce'          => Nonce::create(),
			'maxExceededMessage' => __( 'It is not possible to add more than %number% product to the comparison.',
				'woo-assistant' ),
		) );
	}

	public function addSectionSettings( $sections ) {
		$settings = array(
			'start_grid_product_compare'         => array(
				'title' => __( 'Product Compare', 'woo-assistant' ),
				'type'  => 'startgrid',
			),
			'product_compare_enable'             => array(
				'id'       => 'product_compare_enable',
				'title'    => __( 'Enable product compare feature', 'woo-assistant' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => false,
				'sanitize' => 'bool'
			),
			'product_compare_page'               => array(
				'id'                => 'product_compare_page',
				'title'             => __( 'Compare page', 'woo-assistant' ),
				'type'              => 'postType',
				'args'              => array(
					'post_type' => 'page'
				),
				'default'           => 0,
				'option_none'       => '---',
				'option_none_value' => '',
				'desc'              => wp_sprintf( __( 'Insert shortcode in the compare page %s', 'woo-assistant' ), '<code>[wa_products_compare]</code>' )
			),
			'product_compare_max_items'          => array(
				'id'      => 'product_compare_max_items',
				'title'   => __( 'Max product items', 'woo-assistant' ),
				'type'    => 'select',
				'options' => array( 2, 3, 4 ),
				'default' => 0,
				'desc'    => __( 'Select max product items for comparing.', 'woo-assistant' )
			),
			'product_compare_image_size'         => array(
				'id'                => 'product_compare_image_size',
				'title'             => __( 'Image size', 'woo-assistant' ),
				'type'              => 'imageSize',
				'args'              => array(
					'post_type' => 'page'
				),
				'default'           => 'thumbnail',
				'option_none'       => '---',
				'option_none_value' => '',
				'desc'              => __( 'Select product image size', 'woo-assistant' )
			),
			'product_compare_archive_button'     => array(
				'id'       => 'product_compare_archive_button',
				'title'    => __( 'Archive compare button', 'woo-assistant' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => false,
				'desc'     => __( 'Display compare button in WooCommerce archive pages', 'woo-assistant' ),
				'sanitize' => 'bool'
			),
			'product_compare_add_to_cart_button' => array(
				'id'       => 'product_compare_add_to_cart_button',
				'title'    => __( 'Add to cart button', 'woo-assistant' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => false,
				'desc'     => __( 'Display add to cart button in product compare page', 'woo-assistant' ),
				'sanitize' => 'bool'
			),
			'end_grid_product_compare'           => array(
				'type' => 'endgrid',
			),

			'product_compare_sep_1' => array(
				'type' => 'hr',
			),

			'start_grid_product_compare_fields' => array(
				'title' => __( 'Product fields', 'woo-assistant' ),
				'type'  => 'startgrid',
			),
		);

		$fields = Product::getFields();
		foreach ( $fields as $key => $field ) {
			$settings[ 'product_compare_display_field_' . $key ] = array(
				'title'    => $field,
				'id'       => 'product_compare_display_field_' . $key,
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => false,
				'sanitize' => 'bool'
			);
		}

		$settings['end_grid_product_compare_fields'] = array(
			'type' => 'endgrid',
		);

		$settings['product_compare_sep_2'] = array(
			'type' => 'hr',
		);

		$settings['start_grid_product_compare_attributes'] = array(
			'title' => __( 'Product attributes', 'woo-assistant' ),
			'type'  => 'startgrid',
		);

		$attributes = WooCommerce::getAttributeTaxonomies();
		if ( empty( $attributes ) ) {
			$settings['product_compare_no_attributes_notice'] = array(
				'id'      => 'product_compare_no_attributes_notice',
				'notices' => array(
					array(
						'message' => __( 'Your product attributes is empty, Add attribute in "Products > Attributes" menu', 'woo-assistant' ),
						'type'    => 'warning',
					)
				),
				'type'    => 'notice',
			);

		} else {
			foreach ( $attributes as $key => $attribute ) {
				$settings[ 'product_compare_display_attribute_' . $key ] = array(
					'title'    => $attribute['label'],
					'id'       => 'product_compare_display_attribute_' . $key,
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => false,
					'sanitize' => 'bool'
				);
			}
		}

		$settings['end_grid_product_compare_attributes'] = array(
			'type' => 'endgrid',
		);

		$sections[ self::sectionID ] = array(
			'title'    => __( 'Compare', 'woo-assistant' ),
			'desc'     => __( 'Product compare', 'woo-assistant' ),
			'settings' => $settings
		);

		return $sections;
	}
}