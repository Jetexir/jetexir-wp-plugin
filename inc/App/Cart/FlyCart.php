<?php

namespace WooAssistant\App\Cart;

use WooAssistant\Addons\Addon;
use WooAssistant\Enums\Colors;
use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Sanitizing;
use WooAssistant\Interfaces\AddonInterface;
use WooAssistant\Settings\Settings;

class FlyCart extends Addon implements AddonInterface {
	public string $addonID = 'fly-cart';

	public function initAction(): void {
		add_filter( 'woo_assistant_cart_settings_sections', [ $this, 'addSectionSettings' ] );
		add_filter( 'woo_assistant_site_fly_icons', [ $this, 'addFlyIcon' ] );
		add_action( 'woo_assistant_site_modals', [ $this, 'printCart' ] );

		if ( Settings::get( 'fly_cart_overlay_layer', true ) ) {
			add_filter( 'woo_assistant_modal_overlay', '__return_true' );
		}
	}

	public function printCart(): void {
		?>
        <div id="wa-fly-cart-modal" class="wa-modal wa-fade" tabindex="-1"
             aria-labelledby="staticBackdropLabel" aria-hidden="true">
            test
        </div>
		<?php
	}

	public function addFlyIcon( $icons ) {
		$icons[] = array(
			'id'          => $this->addonID,
			'tag'         => 'a',
			'title'       => Settings::get( 'fly_cart_title', __( 'Cart', 'woo-assistant' ) ),
			'icon'        => $this->getBasketIcons( Settings::get( 'fly_cart_icon', 'wa-icon-shopping-cart' ), true ),
			'count_badge' => 3,
			'attributes'  => array(
				'class'          => 'wa-fly-cart',
				'href'           => '#',
				'data-wa-toggle' => 'modal',
				'data-wa-target' => '#wa-fly-cart-modal'
			),
			'position'    => Settings::get( 'fly_cart_position', 'bottom-left' ),
		);

		return $icons;
	}

	private function getBasketIcons( $icon = null, $tag = false ) {
		$icons = array(
			'wa-icon-shopping-cart',
			'wa-icon-shopping-cart1',
			'wa-icon-shopping-cart2',
			'wa-icon-shopping-cart3',
			'wa-icon-shopping-cart4',
			'wa-icon-shopping-cart5',
			'wa-icon-shopping-cart6',
			'wa-icon-shopping-cart7',
			'wa-icon-shopping-bag',
			'wa-icon-shopping-bag1',
			'wa-icon-shopping-basket',
			'wa-icon-shopping-basket1',
			'wa-icon-shopping-basket2',
		);

		if ( is_null( $icon ) ) {
			return $icons;
		}

		$icon = in_array( $icon, $icons, true ) ? $icon : 'wa-icon-shopping-cart';

		return $tag ? '<i class="' . $icon . '"></i>' : $icon;
	}

	public function addSectionSettings( $sections ) {
		$icons       = $this->getBasketIcons();
		$basketIcons = [];
		foreach ( $icons as $icon ) {
			$basketIcons[ $icon ] = '<i class="' . $icon . '"></i>';
		}
		$sections[ $this->addonID ] = array(
			'title'    => __( 'Fly Cart', 'woo-assistant' ),
			'desc'     => __( 'Fly Cart', 'woo-assistant' ),
			'settings' => [
				'fly_cart_start_grid_1'  => array(
					'id'    => 'fly_cart_start_grid_1',
					'title' => __( 'Appearance', 'woo-assistant' ),
					'type'  => 'startGrid',
				),
				'fly_cart_overlay_layer' => array(
					'id'       => 'fly_cart_overlay_layer',
					'title'    => __( 'Overlay layer', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => true,
					'sanitize' => 'bool'
				),
				'fly_cart_end_grid_1'    => array(
					'type' => 'endGrid',
				),

				'fly_cart_start_grid_icon' => array(
					'id'    => 'fly_cart_start_grid_1',
					'title' => __( 'Fly Cart Icon', 'woo-assistant' ),
					'type'  => 'startGrid',
				),
				'fly_cart_position'        => array(
					'id'       => 'fly_cart_position',
					'title'    => __( 'Position', 'woo-assistant' ),
					'type'     => 'select',
					'options'  => array(
						'top-left'     => __( 'Top Left', 'woo-assistant' ),
						'top-right'    => __( 'Top Right', 'woo-assistant' ),
						'bottom-left'  => __( 'Bottom Left', 'woo-assistant' ),
						'bottom-right' => __( 'Bottom Right', 'woo-assistant' ),
					),
					'default'  => 'bottom-left',
					'sanitize' => 'text'
				),
				'fly_cart_icon'            => array(
					'id'       => 'fly_cart_icon',
					'title'    => __( 'Icon', 'woo-assistant' ),
					'type'     => 'radioInline',
					'default'  => 'wa-icon-shopping-cart',
					'options'  => $basketIcons,
					'sanitize' => 'text'
				),
				'fly_cart_title'           => array(
					'id'      => 'fly_cart_title',
					'title'   => __( 'Title', 'woo-assistant' ),
					'type'    => 'text',
					'default' => __( 'Cart', 'woo-assistant' )
				),
				/*'fly_cart_primary_color' => array(
					'id'       => 'fly_cart_primary_color',
					'title'    => __( 'Primary color', 'woo-assistant' ),
					'type'     => 'wpColorPicker',
					'default'  => Colors::primary,
					'sanitize' => 'color',
				),*/
				'fly_cart_end_grid_icon'   => array(
					'type' => 'endGrid',
				),
			]
		);

		return $sections;
	}

	public function info(): array {
		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Fly Cart', 'woo-assistant' ),
			'desc'           => __( 'Float Cart for WooCommerce', 'woo-assistant' ),
			'tags'           => [ __( 'Cart', 'woo-assistant' ) ],
			'cat'            => 'cart',
			'more_info_link' => 'https://parsa.ws'
		);
	}

	public function wpEnqueueScriptsAction(): void {
		$pluginVersion = Assets::getVersion();
		$debugName     = WOOASSISTANT_DEBUG_MODE ? '' : '.min';

		wp_enqueue_style( WOOASSISTANT_PLUGIN_KEY . '-fly-cart-style',
			Assets::url( 'css/fly-cart' . $debugName . '.css' ),
			false, $pluginVersion );

		wp_enqueue_script( WOOASSISTANT_PLUGIN_SLUG . '-fly-cart-script',
			Assets::url( 'js/fly-cart.min.js' ),
			[ 'jquery' ], $pluginVersion, [ 'in_footer' => true ] );

		/*wp_localize_script( WOOASSISTANT_PLUGIN_SLUG . '-fly-cart-script', WOOASSISTANT_PLUGIN_KEYCAP . 'FlyCart', array(
			'plusMinusButtons' => Sanitizing::int( Settings::get( 'quantity_input_plus_minus_button', false ) )
		) );*/
	}
}