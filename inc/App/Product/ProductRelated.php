<?php

namespace WooAssistant\App\Product;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Addons\Addon;
use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Param;
use WooAssistant\Helper\Sanitizing;
use WooAssistant\Helper\Transient;
use WooAssistant\Helper\WooCommerce;
use WooAssistant\Interfaces\AddonInterface;

class ProductRelated extends Addon implements AddonInterface {
	public string $addonID = 'product-related';
	public string $currentTab = 'product';
	public string $currentSection = 'related';

	public function initAction(): void {
		add_action( 'woo_assistant_submit_settings_form', [ $this, 'clearRelatedCache' ] );
		add_filter( 'woocommerce_product_related_products_heading', [ $this, 'setTitle' ] );
		add_filter( 'woocommerce_product_related_posts_shuffle', [ $this, 'setShuffle' ] );
		add_filter( 'woocommerce_output_related_products_args', [ $this, 'setOrderByArgs' ] );
		add_filter( 'shortcode_atts_related_products', [ $this, 'setOrderByArgs' ] );
		//add_filter( 'woo_assistant_wc_locate_template', [ $this, 'changeTemplate' ], 10, 2 );

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
		if ( $customTitle = $this->getSetting( 'product_related_title', __( 'Related products', 'woocommerce' ) ) ) {
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
		$debugName     = WOOASSISTANT_DEBUG_MODE ? '' : '.min';

		wp_enqueue_style( WOOASSISTANT_PLUGIN_KEY . '-product-related-style',
			Assets::url( 'css/product-related' . $debugName . '.css' ),
			false, $pluginVersion );

		wp_enqueue_style( WOOASSISTANT_PLUGIN_SLUG . '-owl-carousel' );
		wp_enqueue_style( WOOASSISTANT_PLUGIN_SLUG . '-owl-carousel-theme' );
		wp_enqueue_script( WOOASSISTANT_PLUGIN_SLUG . '-owl-carousel' );

		wp_enqueue_script( WOOASSISTANT_PLUGIN_KEY . '-product-related-script',
			Assets::url( 'js/product-related.min.js' ),
			[
				WOOASSISTANT_PLUGIN_SLUG . '-global',
				WOOASSISTANT_PLUGIN_SLUG . '-owl-carousel'
			], $pluginVersion, [ 'in_footer' => true ] );

		wp_localize_script( WOOASSISTANT_PLUGIN_KEY . '-product-related-script', WOOASSISTANT_PLUGIN_KEYCAP . 'ProductRelated', array(
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
		if ( Param::post( WOOASSISTANT_INPUT_PREFIX . 'product_related_delete_wc_cache' ) === '1' ) {
			Transient::deleteLike( 'wc_related' );
		}
	}

	public function addSectionSettings( $sections ): array {
		$settings = array(
			'start_grid_product_related' => array(
				'title' => __( 'Related Products', 'woo-assistant' ),
				'type'  => 'startgrid',
			),
			'product_related_mode'       => array(
				'id'       => 'product_related_mode',
				'title'    => __( 'Mode', 'woo-assistant' ),
				'type'     => 'select',
				'options'  => array(
					'custom'  => __( 'Custom related products', 'woo-assistant' ),
					'default' => __( 'Use WooCommerce built-in related products', 'woo-assistant' ),
					'disable' => __( 'Disable related products', 'woo-assistant' ),
				),
				'default'  => 'custom',
				'sanitize' => 'text'
			),
			'product_related_title'      => array(
				'id'          => 'product_related_title',
				'title'       => __( 'Title', 'woo-assistant' ),
				'type'        => 'text',
				'default'     => __( 'Related products', 'woocommerce' ),
				'placeholder' => __( 'Related products', 'woocommerce' )
			),
			'product_related_orderby'    => array(
				'id'                => 'product_related_orderby',
				'title'             => __( 'Sort by', 'woo-assistant' ),
				'type'              => 'select',
				'options'           => array(
					'title'      => __( 'Product title', 'woo-assistant' ),
					'id'         => __( 'ID', 'woo-assistant' ),
					'date'       => __( 'Date', 'woo-assistant' ),
					'modified'   => __( 'Last modified', 'woo-assistant' ),
					'menu_order' => __( 'Menu order', 'woo-assistant' ),
					'price'      => __( 'Price', 'woo-assistant' ),
					'none'       => __( 'None', 'woo-assistant' ),
				),
				'option_none'       => __( 'Random', 'woo-assistant' ),
				'option_none_value' => 'rand',
				'default'           => 'rand',
				'sanitize'          => 'text'
			),
			'product_related_order'      => array(
				'id'       => 'product_related_order',
				'title'    => __( 'Sort order', 'woo-assistant' ),
				'type'     => 'select',
				'options'  => array(
					'asc'  => __( 'Ascending', 'woo-assistant' ),
					'desc' => __( 'Descending', 'woo-assistant' ),
				),
				'default'  => 'asc',
				'sanitize' => 'text'
			),
			'end_grid_product_related'   => array(
				'type' => 'endgrid',
			),

			'start_grid_product_related_custom' => array(
				'title' => __( 'Display related products by', 'woo-assistant' ),
				'type'  => 'startgrid',
			),
			'product_related_by_cat'            => [
				'id'       => 'product_related_by_cat',
				'title'    => __( 'Category', 'woo-assistant' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			],
			'product_related_exclude_cats'      => array(
				'id'                => 'product_related_exclude_cats',
				'title'             => __( 'Exclude categories', 'woo-assistant' ),
				'type'              => 'termSelect',
				'args'              => array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => true,
				),
				'multiple'          => true,
				'default'           => 0,
				'option_none'       => '---',
				'option_none_value' => '',
				'desc'              => __( 'Select categories excluded from related products', 'woo-assistant' ),
				'sanitize'          => 'array',
				'sanitize_options'  => 'int',
				'attributes'        => array(
					'size' => 5,
				)
			),
			'product_related_by_tag'            => [
				'id'       => 'product_related_by_tag',
				'title'    => __( 'Tag', 'woo-assistant' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => false,
				'sanitize' => 'bool'
			],
			'product_related_by_brand'          => [
				'id'       => 'product_related_by_brand',
				'title'    => __( 'Brand', 'woo-assistant' ),
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
				'title' => __( 'Slider', 'woo-assistant' ),
				'type'  => 'startgrid',
			),
			'product_related_slider'                  => [
				'id'       => 'product_related_slider',
				'title'    => __( 'Enable slider', 'woo-assistant' ),
				'desc'     => __( 'Enabling sliders on related products', 'woo-assistant' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			],
			'product_related_slider_loop'             => [
				'id'       => 'product_related_slider_loop',
				'title'    => __( 'Slider loop', 'woo-assistant' ),
				'desc'     => __( 'Enabling slider loop', 'woo-assistant' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			],
			'product_related_slider_center'           => [
				'id'       => 'product_related_slider_center',
				'title'    => __( 'Slider center', 'woo-assistant' ),
				'desc'     => __( 'Enabling slider center', 'woo-assistant' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => false,
				'sanitize' => 'bool'
			],
			'product_related_slider_dots'             => [
				'id'       => 'product_related_slider_dots',
				'title'    => __( 'Slider dots navigation', 'woo-assistant' ),
				'desc'     => __( 'Enabling slider dots navigation', 'woo-assistant' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => false,
				'sanitize' => 'bool'
			],
			'product_related_slider_arrow'            => [
				'id'       => 'product_related_slider_arrow',
				'title'    => __( 'Slider arrow navigation', 'woo-assistant' ),
				'desc'     => __( 'Enabling slider arrow navigation', 'woo-assistant' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			],
			'product_related_slider_autoplay'         => [
				'id'       => 'product_related_slider_autoplay',
				'title'    => __( 'Slider autoplay', 'woo-assistant' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => false,
				'sanitize' => 'bool'
			],
			'product_related_slider_autoplay_timeout' => array(
				'id'         => 'product_related_slider_autoplay_timeout',
				'title'      => __( 'Slider autoplay timeout', 'woo-assistant' ),
				'desc'       => __( 'Milliseconds', 'woo-assistant' ),
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
				'title'      => __( 'Slider item margin', 'woo-assistant' ),
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
				'title'      => __( 'Number of products', 'woo-assistant' ),
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
				'title'      => __( 'Number of products in mobile view', 'woo-assistant' ),
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
				'title'      => __( 'Number of products in tablet view', 'woo-assistant' ),
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
				'title'      => __( 'Number of products in desktop view', 'woo-assistant' ),
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
				'title' => __( 'Cache', 'woo-assistant' ),
				'type'  => 'startgrid',
			),
			'product_related_disable_cache'    => [
				'id'       => 'product_related_disable_cache',
				'title'    => __( 'Disable cache', 'woo-assistant' ),
				'desc'     => __( 'Disable WooCommerce related products cache', 'woo-assistant' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => false,
				'sanitize' => 'bool'
			],
			'product_related_delete_wc_cache'  => [
				'id'       => 'product_related_delete_wc_cache',
				'title'    => __( 'Delete related products cache', 'woo-assistant' ),
				'desc'     => __( 'Delete all related products cache (Not saved)', 'woo-assistant' ),
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
			'title'        => __( 'Related', 'woo-assistant' ),
			'desc'         => __( 'Related Products', 'woo-assistant' ),
			'settings_key' => $this->addonID,
			'settings'     => $settings
		);

		return $sections;
	}

	public function info(): array {
		$icon = '<svg viewBox="-19.2 -19.2 230.40 230.40" xmlns="http://www.w3.org/2000/svg" fill="none"><g id="SVGRepo_bgCarrier" stroke-width="0"><path transform="translate(-19.2, -19.2), scale(7.199999999999999)" d="M16,31.95005203586291C20.729034042849662,32.057941980240564,24.892865642906695,29.14154989047278,27.7939028469599,25.405323671514942C30.643255322670957,21.7356618650651,32.67733498527118,16.916921800594032,31.05219222082645,12.564435350330186C29.56499785151951,8.581404761919824,24.584712867260606,7.959911811022065,20.665096590517507,6.3128271127137445C17.153362406260584,4.837140915129632,13.570083245004778,2.176567771938685,10.10692464411791,3.7629029318977025C6.604148593971601,5.3673852690296195,5.927230011508407,9.749823841674491,4.998634398855501,13.489010092158498C4.035554422244052,17.36705491338472,2.816591144768958,21.449585290023457,4.8621364256757555,24.882149809526236C7.230335328969726,28.856149117411846,11.375078033710494,31.84453734318515,16,31.95005203586291" fill="#fff" strokewidth="0"></path></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path stroke="#873eff" stroke-linecap="round" stroke-linejoin="round" stroke-width="12" d="M60 32h72v128H60zm92 108h18V52h-18M40 140H22V52h18"></path></g></svg>';

		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Related Products', 'woo-assistant' ),
			'desc'           => __( 'Displays custom related products based on category, tags, attributes, or specific products for your WooCommerce store.', 'woo-assistant' ),
			'tags'           => [ __( 'Product', 'woo-assistant' ) ],
			'cat'            => 'product',
			'icon'           => $icon,
			'more_info_link' => 'https://parsa.ws',
			'settings_key'   => $this->addonID,
		);
	}
}