<?php

namespace WooAssistant\App\Tools;

use WooAssistant\Addons\Addon;
use WooAssistant\Helper\Assets;
use WooAssistant\Interfaces\AddonInterface;

class CurrencySymbolTools extends Addon implements AddonInterface {
	public string $addonID = 'currency-symbol-tools';
	public string $currentTab = 'tools';
	private const sectionID = 'currency-symbol';

	public function initAction(): void {

	}

	public function addSectionSettings( $sections ) {
		$currency = get_option( 'woocommerce_currency', 'USD' );
		$symbol   = get_woocommerce_currency_symbol( $currency );
		$symbol   = Assets::isSvgImageString( $symbol ) || Assets::isImageString( $symbol ) ? '' : $symbol;
		$symbol1  = $this->getSetting( '', $symbol );

		$sections[ self::sectionID ] = array(
			'title'        => __( 'Currency Symbol', 'woo-assistant' ),
			'settings_key' => $this->info()['settings_key'],
			'settings'     => array(
				'start_grid_currency_symbol_1' => array(
					'title' => __( 'Currency Symbol (1)', 'woo-assistant' ),
					'type'  => 'startgrid',
				),
				'currency_1'                   => array(
					'id'                => 'currency_1',
					'title'             => __( 'Currency', 'woo-assistant' ),
					'type'              => 'currencySelect',
					'default'           => $currency,
					'option_none'       => __( 'No changes', 'woo-assistant' ),
					'option_none_value' => '',
					'sanitize'          => 'text'
				),
				'currency_symbol_1'            => array(
					'id'      => 'currency_symbol_1',
					'title'   => __( 'Symbol', 'woo-assistant' ),
					'type'    => 'text',
					'default' => $symbol1
				),
				'currency_media_1'             => array(
					'id'                       => 'currency_media_1',
					'title'                    => __( 'SVG Icon', 'woo-assistant' ),
					'type'                     => 'media',
					'media_type'               => 'image/svg+xml',
					'upload_accept_extensions' => 'svg'
				),

				'end_grid_currency_symbol_1' => array(
					'type' => 'endgrid',
				),

				'product_compare_sep_1' => array(
					'type' => 'hr',
				),
			)
		);

		return $sections;
	}

	public function info(): array {
		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Currency Symbol', 'woo-assistant' ),
			'desc'           => __( 'Change currency symbol', 'woo-assistant' ),
			'tags'           => [ __( 'Currency', 'woo-assistant' ) ],
			'cat'            => 'customizations',
			'more_info_link' => 'https://parsa.ws',
			'settings_key'   => $this->addonID,
		);
	}
}