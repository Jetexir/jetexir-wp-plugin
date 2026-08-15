<?php

namespace Jetexir\App\Product;

defined( 'ABSPATH' ) || exit;

use Jetexir\Addons\Addon;
use Jetexir\Helper\Assets;
use Jetexir\Helper\Param;
use Jetexir\Helper\Sanitizing;
use Jetexir\Helper\Transient;
use Jetexir\Helper\WooCommerce;
use Jetexir\Interfaces\AddonInterface;

class ProductRelated extends Addon implements AddonInterface {
  public string $addonID = 'product-related';
  public string $currentTab = 'product';
  public string $currentSection = 'related';

  public function initAction(): void {
    add_action( 'jetexir_submit_settings_form', [ $this, 'clearRelatedCache' ] );
    add_filter( 'woocommerce_product_related_products_heading', [ $this, 'setTitle' ] );
    add_filter( 'woocommerce_product_related_posts_shuffle', [ $this, 'setShuffle' ] );
    add_filter( 'woocommerce_output_related_products_args', [ $this, 'setOrderByArgs' ] );
    add_filter( 'shortcode_atts_related_products', [ $this, 'setOrderByArgs' ] );
    //add_filter( 'jetexir_wc_locate_template', [ $this, 'changeTemplate' ], 10, 2 );

    $mode = $this->getSetting( 'product_related_mode', 'custom' );
    if ( $mode === 'disable' ) {
      add_filter( 'woocommerce_product_related_posts_relate_by_category', '__return_false' );
      add_filter( 'woocommerce_product_related_posts_relate_by_tag', '__return_false' );
      add_filter( 'woocommerce_product_related_posts_force_display', '__return_false' );

    } elseif ( $mode === 'custom' ) {
      add_filter( 'woocommerce_product_related_posts_relate_by_category', [ $this, 'relatedByCategory' ], 10, 2 );
      add_filter( 'woocommerce_product_related_posts_relate_by_tag', [ $this, 'relatedByTag' ], 10, 2 );
      add_filter( 'woocommerce_product_related_posts_relate_by_brand', [ $this, 'relatedByBrand' ], 10, 2 );
      add_filter( 'woocommerce_product_related_posts_force_display', '__return_true' );
      add_filter( 'woocommerce_product_related_posts_query', [ $this, 'changeQuery' ], 9999, 10 );
      if ( $this->getSetting( 'product_related_slider', true ) ) {
        add_filter( 'woocommerce_related_products_columns', '__return_false' );
      }
    }
  }

  public function templateRedirectAction(): void {
    if ( $this->getSetting( 'product_related_disable_cache', false ) && WooCommerce::isProduct() ) {
      add_filter( 'pre_transient_wc_related_' . get_the_ID(), static function () {
        return 0;
      } );
    }
  }

  /**
   * @copyright Based on get_related_products_query() WC method
   */
  public function changeQuery( $query, $productID, $args ): array {
    global $wpdb;

    $brandsArray    = apply_filters( 'woocommerce_product_related_posts_relate_by_brand', true, $productID ) ? apply_filters( 'woocommerce_get_related_product_brand_terms', wc_get_product_term_ids( $productID, 'product_brand' ), $productID ) : array();
    $includeTermIDs = array_merge( $args['categories'], $args['tags'], $brandsArray );
    $excludeIDs     = $args['exclude_ids'];

    $attributes = WooCommerce::getAttributeTaxonomies();
    if ( ! empty( $attributes ) ) {
      $attributesIDs = [];
      foreach ( $attributes as $key => $attribute ) {
        if ( $this->getSetting( 'product_related_by_attribute_' . $key, false ) ) {
          $taxName      = 'pa_' . $attribute['name'];
          $attributeIDs = apply_filters( 'woocommerce_product_related_posts_relate_by_' . $taxName, true, $productID ) ? apply_filters( 'woocommerce_get_related_product_' . $taxName . '_terms', wc_get_product_term_ids( $productID, $taxName ), $productID ) : array();
          if ( ! empty( $attributeIDs ) && is_array( $attributeIDs ) ) {
            $attributesIDs[] = $attributeIDs;
          }
        }
      }
      $includeTermIDs = array_merge( $includeTermIDs, ...$attributesIDs );
    }

    $changeQuery              = array(
      'fields' => "
				SELECT DISTINCT ID FROM {$wpdb->posts} p
			",
      'join'   => '',
      'where'  => "
				WHERE 1=1
				AND p.post_status = 'publish'
				AND p.post_type = 'product'
			",
      'limits' => '
				LIMIT ' . absint( $args['limit'] ) . '
			',
    );
    $excludeTermIDs           = array();
    $excludeCats              = $this->getSetting( 'product_related_exclude_cats', [] );
    $productVisibilityTermIDs = wc_get_product_visibility_term_ids();

    if ( ! empty( $excludeCats ) ) {
      $excludeTermIDs = array_merge( $excludeTermIDs, $excludeCats );
    }

    if ( $productVisibilityTermIDs['exclude-from-catalog'] ) {
      $excludeTermIDs[] = $productVisibilityTermIDs['exclude-from-catalog'];
    }
    if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && $productVisibilityTermIDs['outofstock'] ) {
      $excludeTermIDs[] = $productVisibilityTermIDs['outofstock'];
    }

    $includeTermIDs = array_values( array_diff( $includeTermIDs, $excludeTermIDs ) );

    if ( count( $excludeTermIDs ) ) {
      $changeQuery['join']  .= " LEFT JOIN ( SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ( " . implode( ',', array_map( 'absint', $excludeTermIDs ) ) . ' ) ) AS exclude_join ON exclude_join.object_id = p.ID';
      $changeQuery['where'] .= ' AND exclude_join.object_id IS NULL';
    }

    if ( count( $includeTermIDs ) ) {
      $changeQuery['join'] .= " INNER JOIN ( SELECT object_id FROM {$wpdb->term_relationships} INNER JOIN {$wpdb->term_taxonomy} using( term_taxonomy_id ) WHERE term_id IN ( " . implode( ',', array_map( 'absint', $includeTermIDs ) ) . ' ) ) AS include_join ON include_join.object_id = p.ID';
    }

    if ( count( $excludeIDs ) ) {
      $changeQuery['where'] .= ' AND p.ID NOT IN ( ' . implode( ',', array_map( 'absint', $excludeIDs ) ) . ' )';
    }

    return $changeQuery;
  }

  public function relatedByBrand( $use, $productID ): bool {
    if ( $this->getSetting( 'product_related_by_brand', true ) === false ) {
      return false;
    }

    return $use;
  }

  public function relatedByTag( $use, $productID ): bool {
    if ( $this->getSetting( 'product_related_by_tag', true ) === false ) {
      return false;
    }

    return $use;
  }

  public function relatedByCategory( $use, $productID ): bool {
    if ( $this->getSetting( 'product_related_by_cat', true ) === false ) {
      return false;
    }

    return $use;
  }

  public function changeTemplate( $waTemplate, $templateName ) {
    if ( $templateName === 'single-product/related.php' && $this->getSetting( 'product_related_mode', 'custom' ) === 'custom' ) {
      $waTemplate = $templateName;
    }

    return $waTemplate;
  }

  public function setOrderByArgs( $args ) {
    if ( $this->getSetting( 'product_related_slider', true ) ) {
      $args['posts_per_page'] = $this->getSetting( 'product_related_slider_limit', 9 );
      $args['limit']          = $args['posts_per_page'];
    }

    $args['orderby'] = $this->getSetting( 'product_related_orderby', 'rand' );
    $args['order']   = $this->getSetting( 'product_related_order', 'desc' );

    return $args;
  }

  public function setShuffle( $shuffle ): bool {
    return $this->getSetting( 'product_related_orderby', 'rand' ) === 'rand';
  }

  public function setTitle( $title ) {
    if ( $customTitle = $this->getSetting( 'product_related_title', esc_html__( 'Related Products', 'jetexir' ) ) ) {
      return $customTitle;
    }

    return $title;
  }

  /**
   * Enqueue style and script
   *
   * @return void
   */
  public function wpEnqueueScriptsAction(): void {
    if ( ! WooCommerce::isProduct() ) {
      return;
    }

    $pluginVersion = Assets::getVersion();
    $debugName     = JETEXIR_DEBUG_MODE ? '' : '.min';

    wp_enqueue_style( JETEXIR_PLUGIN_KEY . '-product-related-style',
      Assets::url( 'css/product-related' . $debugName . '.css' ),
      false, $pluginVersion );

    wp_enqueue_style( JETEXIR_PLUGIN_SLUG . '-owl-carousel' );
    wp_enqueue_style( JETEXIR_PLUGIN_SLUG . '-owl-carousel-theme' );
    wp_enqueue_script( JETEXIR_PLUGIN_SLUG . '-owl-carousel' );

    wp_enqueue_script( JETEXIR_PLUGIN_KEY . '-product-related-script',
      Assets::url( 'js/product-related.min.js' ),
      [
        JETEXIR_PLUGIN_SLUG . '-global',
        JETEXIR_PLUGIN_SLUG . '-owl-carousel'
      ], $pluginVersion, [ 'in_footer' => true ] );

    wp_localize_script( JETEXIR_PLUGIN_KEY . '-product-related-script', JETEXIR_PLUGIN_KEYCAP . 'ProductRelated', array(
      'loop'            => Sanitizing::int( $this->getSetting( 'product_related_slider_loop', true ) ),
      'center'          => Sanitizing::int( $this->getSetting( 'product_related_slider_center', false ) ),
      'dots'            => Sanitizing::int( $this->getSetting( 'product_related_slider_dots', false ) ),
      'arrow'           => Sanitizing::int( $this->getSetting( 'product_related_slider_arrow', true ) ),
      'autoplay'        => Sanitizing::int( $this->getSetting( 'product_related_slider_autoplay', false ) ),
      'autoplayTimeout' => Sanitizing::int( $this->getSetting( 'product_related_slider_autoplay_timeout', 4000 ) ),
      'margin'          => Sanitizing::int( $this->getSetting( 'product_related_slider_margin', 10 ) ),
      'mobileLimit'     => Sanitizing::int( $this->getSetting( 'product_related_slider_mobile_limit', 1 ) ),
      'tabletLimit'     => Sanitizing::int( $this->getSetting( 'product_related_slider_tablet_limit', 2 ) ),
      'desktopLimit'    => Sanitizing::int( $this->getSetting( 'product_related_slider_desktop_limit', 3 ) ),
    ) );
  }

  public function clearRelatedCache(): void {
    if ( Param::post( JETEXIR_INPUT_PREFIX . 'product_related_delete_wc_cache' ) === '1' ) {
      Transient::deleteLike( 'wc_related' );
    }
  }

  public function addSectionSettings( $sections ): array {
    $settings = array(
      'start_grid_product_related' => array(
        'title' => esc_html__( 'Related Products', 'jetexir' ),
        'type'  => 'startgrid',
      ),
      'product_related_mode'       => array(
        'id'       => 'product_related_mode',
        'title'    => esc_html__( 'Mode', 'jetexir' ),
        'type'     => 'select',
        'options'  => array(
          'custom'  => esc_html__( 'Custom related products', 'jetexir' ),
          'default' => esc_html__( 'Use WooCommerce built-in related products', 'jetexir' ),
          'disable' => esc_html__( 'Disable related products', 'jetexir' ),
        ),
        'default'  => 'custom',
        'sanitize' => 'text'
      ),
      'product_related_title'      => array(
        'id'          => 'product_related_title',
        'title'       => esc_html__( 'Title', 'jetexir' ),
        'type'        => 'text',
        'default'     => esc_html__( 'Related Products', 'jetexir' ),
        'placeholder' => esc_html__( 'Related Products', 'jetexir' )
      ),
      'product_related_orderby'    => array(
        'id'                => 'product_related_orderby',
        'title'             => esc_html__( 'Sort by', 'jetexir' ),
        'type'              => 'select',
        'options'           => array(
          'title'      => esc_html__( 'Product title', 'jetexir' ),
          'id'         => esc_html__( 'ID', 'jetexir' ),
          'date'       => esc_html__( 'Date', 'jetexir' ),
          'modified'   => esc_html__( 'Last modified', 'jetexir' ),
          'menu_order' => esc_html__( 'Menu order', 'jetexir' ),
          'price'      => esc_html__( 'Price', 'jetexir' ),
          'none'       => esc_html__( 'None', 'jetexir' ),
        ),
        'option_none'       => esc_html__( 'Random', 'jetexir' ),
        'option_none_value' => 'rand',
        'default'           => 'rand',
        'sanitize'          => 'text'
      ),
      'product_related_order'      => array(
        'id'       => 'product_related_order',
        'title'    => esc_html__( 'Sort order', 'jetexir' ),
        'type'     => 'select',
        'options'  => array(
          'asc'  => esc_html__( 'Ascending', 'jetexir' ),
          'desc' => esc_html__( 'Descending', 'jetexir' ),
        ),
        'default'  => 'asc',
        'sanitize' => 'text'
      ),
      'end_grid_product_related'   => array(
        'type' => 'endgrid',
      ),

      'start_grid_product_related_custom' => array(
        'title' => esc_html__( 'Display related products by', 'jetexir' ),
        'type'  => 'startgrid',
      ),
      'product_related_by_cat'            => [
        'id'       => 'product_related_by_cat',
        'title'    => esc_html__( 'Category', 'jetexir' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ],
      'product_related_exclude_cats'      => array(
        'id'                => 'product_related_exclude_cats',
        'title'             => esc_html__( 'Exclude categories', 'jetexir' ),
        'type'              => 'termSelect',
        'args'              => array(
          'taxonomy'   => 'product_cat',
          'hide_empty' => true,
        ),
        'multiple'          => true,
        'default'           => 0,
        'option_none'       => '---',
        'option_none_value' => '',
        'desc'              => esc_html__( 'Choose the categories to exclude from the related products section.', 'jetexir' ),
        'sanitize'          => 'array',
        'sanitize_options'  => 'int',
        'attributes'        => array(
          'size' => 5,
        )
      ),
      'product_related_by_tag'            => [
        'id'       => 'product_related_by_tag',
        'title'    => esc_html__( 'Tag', 'jetexir' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => false,
        'sanitize' => 'bool'
      ],
      'product_related_by_brand'          => [
        'id'       => 'product_related_by_brand',
        'title'    => esc_html__( 'Brand', 'jetexir' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => false,
        'sanitize' => 'bool'
      ],
    );

    $attributes = WooCommerce::getAttributeTaxonomies();
    if ( ! empty( $attributes ) ) {
      foreach ( $attributes as $key => $attribute ) {
        $settings[ 'product_related_by_attribute_' . $key ] = array(
          'title'    => $attribute['label'],
          'id'       => 'product_related_by_attribute_' . $key,
          'type'     => 'toggle',
          'value'    => 1,
          'default'  => false,
          'sanitize' => 'bool'
        );
      }
    }

    $settings = array_merge( $settings, [
      'end_grid_product_related_custom' => array(
        'type' => 'endgrid',
      ),

      'start_grid_product_related_slider'       => array(
        'title' => esc_html__( 'Slider', 'jetexir' ),
        'type'  => 'startgrid',
      ),
      'product_related_slider'                  => [
        'id'       => 'product_related_slider',
        'title'    => esc_html__( 'Activate the slider', 'jetexir' ),
        'desc'     => esc_html__( 'Activate slider for the related products section', 'jetexir' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ],
      'product_related_slider_loop'             => [
        'id'       => 'product_related_slider_loop',
        'title'    => esc_html__( 'Slider loop', 'jetexir' ),
        'desc'     => esc_html__( 'Enabling slider loop', 'jetexir' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ],
      'product_related_slider_center'           => [
        'id'       => 'product_related_slider_center',
        'title'    => esc_html__( 'Slider center', 'jetexir' ),
        'desc'     => esc_html__( 'Enabling slider center', 'jetexir' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => false,
        'sanitize' => 'bool'
      ],
      'product_related_slider_dots'             => [
        'id'       => 'product_related_slider_dots',
        'title'    => esc_html__( 'Slider dots navigation', 'jetexir' ),
        'desc'     => esc_html__( 'Enabling slider dots navigation', 'jetexir' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => false,
        'sanitize' => 'bool'
      ],
      'product_related_slider_arrow'            => [
        'id'       => 'product_related_slider_arrow',
        'title'    => esc_html__( 'Slider arrow navigation', 'jetexir' ),
        'desc'     => esc_html__( 'Enabling slider arrow navigation', 'jetexir' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => true,
        'sanitize' => 'bool'
      ],
      'product_related_slider_autoplay'         => [
        'id'       => 'product_related_slider_autoplay',
        'title'    => esc_html__( 'Slider autoplay', 'jetexir' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => false,
        'sanitize' => 'bool'
      ],
      'product_related_slider_autoplay_timeout' => array(
        'id'         => 'product_related_slider_autoplay_timeout',
        'title'      => esc_html__( 'Slider autoplay timeout', 'jetexir' ),
        'desc'       => esc_html__( 'Milliseconds', 'jetexir' ),
        'type'       => 'number',
        'default'    => 4000,
        'attributes' => array(
          'placeholder' => 'eg: 10',
          'step'        => 1,
          'min'         => 1000,
        ),
        'sanitize'   => 'int'
      ),
      'product_related_slider_margin'           => array(
        'id'         => 'product_related_slider_margin',
        'title'      => esc_html__( 'Slide margin', 'jetexir' ),
        'type'       => 'number',
        'default'    => 10,
        'attributes' => array(
          'placeholder' => 'eg: 10',
          'step'        => 1,
          'min'         => 0,
          'max'         => 100
        ),
        'sanitize'   => 'int'
      ),
      'product_related_slider_limit'            => array(
        'id'         => 'product_related_slider_limit',
        'title'      => esc_html__( 'Number of products', 'jetexir' ),
        'type'       => 'number',
        'default'    => 9,
        'attributes' => array(
          'placeholder' => 'eg: 10',
          'step'        => 1,
          'min'         => 1,
          'max'         => 20
        ),
        'sanitize'   => 'int'
      ),
      'product_related_slider_mobile_limit'     => array(
        'id'         => 'product_related_slider_mobile_limit',
        'title'      => esc_html__( 'Number of products in mobile view', 'jetexir' ),
        'type'       => 'number',
        'default'    => 1,
        'attributes' => array(
          'placeholder' => 'eg: 1',
          'step'        => 1,
          'min'         => 1,
          'max'         => 3
        ),
        'sanitize'   => 'int'
      ),
      'product_related_slider_tablet_limit'     => array(
        'id'         => 'product_related_slider_tablet_limit',
        'title'      => esc_html__( 'Number of products in tablet view', 'jetexir' ),
        'type'       => 'number',
        'default'    => 2,
        'attributes' => array(
          'placeholder' => 'eg: 2',
          'step'        => 1,
          'min'         => 1,
          'max'         => 6
        ),
        'sanitize'   => 'int'
      ),
      'product_related_slider_desktop_limit'    => array(
        'id'         => 'product_related_slider_desktop_limit',
        'title'      => esc_html__( 'Number of products in desktop view', 'jetexir' ),
        'type'       => 'number',
        'default'    => 3,
        'attributes' => array(
          'placeholder' => 'eg: 3',
          'step'        => 1,
          'min'         => 1,
          'max'         => 9
        ),
        'sanitize'   => 'int'
      ),
      'end_grid_product_related_slider'         => array(
        'type' => 'endgrid',
      ),

      'start_grid_product_related_cache' => array(
        'title' => esc_html__( 'Cache', 'jetexir' ),
        'type'  => 'startgrid',
      ),
      'product_related_disable_cache'    => [
        'id'       => 'product_related_disable_cache',
        'title'    => esc_html__( 'Disable cache', 'jetexir' ),
        'desc'     => esc_html__( 'Disable WooCommerce related products cache', 'jetexir' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => false,
        'sanitize' => 'bool'
      ],
      'product_related_delete_wc_cache'  => [
        'id'       => 'product_related_delete_wc_cache',
        'title'    => esc_html__( 'Delete related products cache', 'jetexir' ),
        'desc'     => esc_html__( 'Delete all related products cache (Not saved)', 'jetexir' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => false,
        'save'     => false,
        'sanitize' => 'bool'
      ],
      'end_grid_product_related_cache'   => array(
        'type' => 'endgrid',
      ),
    ] );

    $sections[ $this->currentSection ] = array(
      'title'        => esc_html__( 'Related', 'jetexir' ),
      'desc'         => esc_html__( 'Related Products', 'jetexir' ),
      'settings_key' => $this->addonID,
      'settings'     => $settings
    );

    return $sections;
  }

  public function info(): array {
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><g stroke="#873eff" stroke-width="1.5"><path d="M12.5 5c1.886 0 2.828 0 3.414.586S16.5 7.114 16.5 9v6c0 1.886 0 2.828-.586 3.414S14.386 19 12.5 19h-1c-1.886 0-2.828 0-3.414-.586S7.5 16.886 7.5 15V9c0-1.886 0-2.828.586-3.414S9.614 5 11.5 5z"/><path stroke-linecap="round" d="M22 19h-.5a2.5 2.5 0 0 1-2.5-2.5v-9A2.5 2.5 0 0 1 21.5 5h.5M2 19h.5A2.5 2.5 0 0 0 5 16.5v-9A2.5 2.5 0 0 0 2.5 5H2"/></g></svg>';

    return array(
      'id'             => $this->addonID,
      'title'          => esc_html__( 'Related Products', 'jetexir' ),
      'desc'           => esc_html__( 'Displays custom related products based on category, tags, attributes, or specific products for your WooCommerce store.', 'jetexir' ),
      'tags'           => [ esc_html__( 'Product', 'jetexir' ) ],
      'cat'            => 'product',
      'icon'           => $icon,
      'more_info_link' => '{jetexir_website}/addons/related-products',
      'settings_key'   => $this->addonID,
    );
  }
}
