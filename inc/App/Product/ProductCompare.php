<?php

namespace Jetexir\App\Product;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Utilities\I18nUtil;
use Jetexir\Addons\Addon;
use Jetexir\App\App;
use Jetexir\Helper\{Assets, Cookie, JSON, Nonce, Notice, Param, Sanitizing, WooCommerce, WordPress};
use Jetexir\Interfaces\AddonInterface;

class ProductCompare extends Addon implements AddonInterface {
  public string $addonID = 'product-compare';
  public string $currentTab = 'product';
  public string $currentSection = 'compare';
  private const shortCode = 'jetexir_products_compare';
  private const cookieName = 'wc_products_compare';
  private const maxItems = 4;

  public function initAction(): void {
    App::addShortcode( self::shortCode, [ $this, 'compareShortcode' ] );
    if ( $this->getSetting( 'product_compare_archive_button', false ) ) {
      add_action( 'woocommerce_after_shop_loop_item', [ $this, 'addButton' ], 9999 );
    }
    add_action( 'woocommerce_after_add_to_cart_button', [ $this, 'addButton' ], 9999 );
    add_action( 'wp_ajax_jetexir_product_compare_add_remove', [ $this, 'addRemoveItem' ] );
    add_action( 'wp_ajax_nopriv_jetexir_product_compare_add_remove', [ $this, 'addRemoveItem' ] );
  }

  public function compareShortcode( $atts ) {
    $atts = shortcode_atts( array(
      'max_items'  => null,
      'image_size' => null,
    ), $atts, self::shortCode );

    $maxItems = (int) ( is_null( $atts['max_items'] ) ? $this->getSetting( 'product_compare_max_items', 2 ) : $atts['max_items'] );
    $maxItems = min( $maxItems, self::maxItems );

    $imageSizes = array_keys( Assets::getImageSizes() );
    $imageSize  = is_null( $atts['image_size'] ) ? $this->getSetting( 'product_compare_image_size', 'thumbnail' ) : $atts['image_size'];
    $imageSize  = in_array( $imageSize, $imageSizes, true ) ? $imageSize : '';

    ob_start();

    $productIDs = $this->getStorageItems();

    if ( empty( $productIDs ) ) {
      Notice::addAndDisplay( 'product-compare', array(
        array(
          'type'    => 'warning',
          'message' => esc_html__( 'Your product compare list is empty', 'jetexir' ),
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
        $addToCartButton = $this->getSetting( 'product_compare_add_to_cart_button', false );
        $fields          = Product::getFields();
        $attributes      = WooCommerce::getAttributeTaxonomies();
        $data            = [ 'count' => count( $products ) ];
        ?>
        <div
          class="jetexir-product-compare-wrapper jetexir-product-compare-cols-<?php echo esc_html( $data['count'] ) ?>">
          <?php
          /**
           * \WC_Product $product
           */
          foreach ( $products as $product ) {
            $productID = $product->get_id();
            $imageID   = (int) $product->get_image_id();

            $data['removeButton'][] = '<button type="button" class="button jetexir-button jetexir-product-compare-button jetexir-button-remove" data-id="' . $productID . '" data-action="refresh"><svg width="28px" height="28px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 8L8 16M8.00001 8L16 16" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>';

            $data['images'][] = ! empty( $imageSize ) && $imageID ? wp_get_attachment_image( $imageID, $imageSize, false,
              [ 'class' => 'jetexir-product-compare-image' ] ) : '';

            if ( ! $product->is_visible() ) {
              $data['title'][] = esc_html( $product->get_name() );
            } else {
              $data['title'][] = wp_sprintf( '<a href="%s">%s</a>', esc_url( $product->get_permalink() ),
                esc_html( $product->get_name() ) );
            }

            if ( $addToCartButton ) {
              $button = '';
              if ( $product->is_purchasable() && $product->is_in_stock() ) {
                $button = WooCommerce::getAddToCartButton( $product );
              }

              $data['addToCart'][] = $button;
            }

            foreach ( $fields as $key => $field ) {
              if ( $this->getSetting( 'product_compare_display_field_' . $key, false ) ) {
                $value = false;

                if ( $key === 'brand' ) {
                  $value = do_shortcode( '[product_brand post_id="' . $productID . '" class="jetexir-product-compare-brand"]' );

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
                      'jetexir' ) );

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
              if ( $this->getSetting( 'product_compare_display_attribute_' . $key, false ) ) {
                $data['fields'][ $key ]['label']   = $attribute['label'];
                $data['fields'][ $key ]['value'][] = $product->get_attribute( $attribute['name'] );
              }
            }
          }

          // Head
          echo '<div class="jetexir-product-compare-row jetexir-product-compare-head">';
          foreach ( $data['title'] as $i => $title ) {
            $i = (int) $i;
            echo '<div class="jetexir-product-compare-col">';
            echo wp_kses_post( $data['removeButton'][ $i ] );
            echo wp_kses_post( $data['images'][ $i ] );
            echo wp_kses_post( $title );

            if ( ! empty( $data['addToCart'][ $i ] ) ) {
              echo '<div class="jetexir-product-compare-add-to-cart">' . wp_kses_post( $data['addToCart'][ $i ] ) . '</div>';
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

            echo '<div class="jetexir-product-compare-field-title">';
            echo esc_html( $field['label'] );
            echo '</div>';
            echo '<div class="jetexir-product-compare-row jetexir-product-compare-row-field jetexir-product-compare-row-' . esc_html( $key ) . '">';
            foreach ( $field['value'] as $value ) {
              echo '<div class="jetexir-product-compare-col">';
              echo empty( $value ) ? '---' : wp_kses_post( $value );
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
            'message' => esc_html__( 'Your product compare list is empty', 'jetexir' ),
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
    if ( Nonce::verify() ) {
      $productID = Sanitizing::int( Param::post( 'product_id', 0 ) );
      $max       = $this->getSetting( 'product_compare_max_items', 2 );
      $update    = $this->updateStorage( $productID, $max );

      $data = array(
        'status'   => $update['status'],
        'count'    => $update['count'],
        'max'      => (int) $max,
        'redirect' => $update['status'] === 'max_exceeded' ? get_permalink( $this->getSetting( 'product_compare_page', 0 ) ) : ''
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
   * @param int $max Max items
   *
   * @return array Return status and count of items
   */
  private function updateStorage( int $productID, int $max = 2 ): array {
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

    $productIDs = JSON::encode( $productIDs );
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
    echo '<button type="button" class="button jetexir-button jetexir-button-secondary jetexir-product-compare-button' . ( $exists ? ' jetexir-button-remove' : '' ) . '" data-id="' . esc_html( $productID ) . '" data-action="non">' .
         esc_html( $this->getSetting( 'product_compare_button_text', esc_html__( 'Compare', 'jetexir' ) ) )
         . '</button>';
  }

  /**
   * Get all product ids
   *
   * @return array
   */
  private function getStorageItems(): array {
    $value      = Cookie::get( self::cookieName, '' );
    $productIDs = JSON::decode( $value, true );
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
  public function wpEnqueueScriptsAction(): void {
    if ( ! WooCommerce::isWoocommerce() && ! WordPress::isPage( $this->getSetting( 'product_compare_page', 0 ) ) ) {
      return;
    }

    $pluginVersion = Assets::getVersion();
    $debugName     = JETEXIR_DEBUG_MODE ? '' : '.min';

    wp_enqueue_style( JETEXIR_PLUGIN_KEY . '-product-compare-style',
      Assets::url( 'css/product-compare' . $debugName . '.css' ),
      false, $pluginVersion );

    wp_enqueue_script( JETEXIR_PLUGIN_KEY . '-product-compare-script',
      Assets::url( 'js/product-compare.min.js' ),
      [ JETEXIR_PLUGIN_SLUG . '-global' ], $pluginVersion, [ 'in_footer' => true ] );

    wp_localize_script( JETEXIR_PLUGIN_KEY . '-product-compare-script', JETEXIR_PLUGIN_KEYCAP . 'ProductCompare', array(
      'maxExceededMessage' => esc_html__( 'It is not possible to add more than %number% product to the comparison.', 'jetexir' ),
    ) );
  }

  public function addSectionSettings( $sections ) {
    $settings = array(
      'start_grid_product_compare'         => array(
        'title' => esc_html__( 'Product Compare', 'jetexir' ),
        'type'  => 'startgrid',
      ),
      'product_compare_button_text'        => array(
        'id'      => 'product_compare_button_text',
        'title'   => esc_html__( 'Button Text', 'jetexir' ),
        'type'    => 'text',
        'default' => esc_html__( 'Compare', 'jetexir' ),
        'desc'    => esc_html__( 'Compare button text', 'jetexir' )
      ),
      'product_compare_archive_button'     => array(
        'id'       => 'product_compare_archive_button',
        'title'    => esc_html__( 'Archive compare button', 'jetexir' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => false,
        'desc'     => esc_html__( 'Display compare button in WooCommerce archive pages', 'jetexir' ),
        'sanitize' => 'bool'
      ),
      'product_compare_page'               => array(
        'id'                => 'product_compare_page',
        'title'             => esc_html__( 'Compare page', 'jetexir' ),
        'type'              => 'postSelect',
        'args'              => array(
          'post_type' => 'page'
        ),
        'default'           => 0,
        'option_none'       => '---',
        'option_none_value' => '',
        /* translators: %s: Shortcode */
        'desc'              => wp_sprintf( esc_html__( 'Insert shortcode in the compare page %s', 'jetexir' ), '<code class="jetexir-copy-text">[jetexir_products_compare]</code>' )
      ),
      'product_compare_max_items'          => array(
        'id'      => 'product_compare_max_items',
        'title'   => esc_html__( 'Max product items', 'jetexir' ),
        'type'    => 'select',
        'options' => array( 2, 3, 4 ),
        'default' => 0,
        'desc'    => esc_html__( 'Select max product items for comparing.', 'jetexir' )
      ),
      'product_compare_image_size'         => array(
        'id'                => 'product_compare_image_size',
        'title'             => esc_html__( 'Image size', 'jetexir' ),
        'type'              => 'imageSizeSelect',
        'default'           => 'thumbnail',
        'option_none'       => '---',
        'option_none_value' => '',
        'desc'              => esc_html__( 'Select product image size', 'jetexir' )
      ),
      'product_compare_add_to_cart_button' => array(
        'id'       => 'product_compare_add_to_cart_button',
        'title'    => esc_html__( 'Add to cart button', 'jetexir' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => false,
        'desc'     => esc_html__( 'Display add to cart button in product compare page', 'jetexir' ),
        'sanitize' => 'bool'
      ),
      'end_grid_product_compare'           => array(
        'type' => 'endgrid',
      ),

      'product_compare_sep_1' => array(
        'type' => 'hr',
      ),

      'start_grid_product_compare_fields' => array(
        'title' => esc_html__( 'Product fields', 'jetexir' ),
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
      'title' => esc_html__( 'Product attributes', 'jetexir' ),
      'type'  => 'startgrid',
    );

    $attributes = WooCommerce::getAttributeTaxonomies();
    if ( empty( $attributes ) ) {
      $settings['product_compare_no_attributes_notice'] = array(
        'id'      => 'product_compare_no_attributes_notice',
        'notices' => array(
          array(
            'message' => esc_html__( 'Your product attributes is empty, Add attribute in "Products > Attributes" menu.', 'jetexir' ),
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

    $sections[ $this->currentSection ] = array(
      'title'        => esc_html__( 'Compare', 'jetexir' ),
      'desc'         => esc_html__( 'Product compare', 'jetexir' ),
      'settings_key' => $this->addonID,
      'settings'     => $settings
    );

    return $sections;
  }

  public function info(): array {
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><g stroke="#873eff" stroke-width="1.5"><path d="M12 3h-1C7.229 3 5.343 3 4.172 4.172S3 7.229 3 11v2c0 3.771 0 5.657 1.172 6.828S7.229 21 11 21h1"/><path stroke-dasharray="2.5 3" stroke-linecap="round" d="M11 3h4c2.828 0 4.243 0 5.121.879C21 4.757 21 6.172 21 9v6c0 2.828 0 4.243-.879 5.121C19.243 21 17.828 21 15 21h-4"/><path stroke-linecap="round" d="M12 2v20"/></g></svg>';

    return array(
      'id'             => $this->addonID,
      'title'          => esc_html__( 'Products Compare', 'jetexir' ),
      'desc'           => esc_html__( 'Enables customers to compare products.', 'jetexir' ),
      'tags'           => [ esc_html__( 'Product', 'jetexir' ) ],
      'cat'            => 'product',
      'icon'           => $icon,
      'more_info_link' => '{jetexir_website}/addons/product-comparison',
      'settings_key'   => $this->addonID,
    );
  }
}
