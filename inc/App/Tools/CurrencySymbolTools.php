<?php

namespace WooAssistant\App\Tools;

use WooAssistant\Addons\Addon;
use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Cache;
use WooAssistant\Interfaces\AddonInterface;

class CurrencySymbolTools extends Addon implements AddonInterface {
	public string $addonID = 'currency-symbol-tools';
	public string $currentTab = 'tools';
	private const sectionID = 'currency-symbol';

	public function initAction(): void {
		add_filter( 'woocommerce_currency_symbol', [ $this, 'changeCurrencySymbol' ], 10, 2 );
	}

	public function changeCurrencySymbol( $symbol, $code ) {
		for ( $i = 1; $i <= 3; $i ++ ) {
			$currency = $this->getSetting( 'currency_' . $i, '' );

			if ( ! empty( $currency ) && $currency === $code ) {
				$mediaID = $this->getSetting( 'currency_media_' . $i, '' );

				if ( ! empty( $mediaID ) ) {
					$mediaID  = (int) $mediaID;
					$cacheKey = $this->addonID . '_' . $i . '_' . $mediaID;

					if ( $file = Cache::get( $cacheKey, false ) ) {
						return $file;
					}

					$file = get_attached_file( $mediaID );

					if ( $file ) {
						$file = file_get_contents( Assets::pathCorrection( $file ) );
						$file = Assets::setSvgDimensions( $file, 14 );
						Cache::set( $cacheKey, $file );

						return $file;
					}
				}

				$symbol = $this->getSetting( 'currency_symbol_' . $i, '' );
				if ( ! empty( $symbol ) ) {
					return $symbol;
				}
			}
		}

		return $symbol;
	}

	public function addSectionSettings( $sections ) {
		$currency = get_option( 'woocommerce_currency', 'USD' );
		$symbol   = get_woocommerce_currency_symbol( $currency );
		$symbol   = Assets::isSvgImageString( $symbol ) || Assets::isImageString( $symbol ) ? '' : $symbol;

		$sections[ self::sectionID ] = array(
			'title'        => __( 'Currency Symbol', 'woo-assistant' ),
			'settings_key' => $this->info()['settings_key'],
			'settings'     => array(
				'start_grid_currency_symbol_1' => array(
					'title' => __( 'Currency Symbol', 'woo-assistant' ),
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
					'default' => $symbol
				),
				'currency_media_1'             => array(
					'id'                       => 'currency_media_1',
					'title'                    => __( 'SVG Icon', 'woo-assistant' ),
					'select_button'            => __( 'Select SVG', 'woo-assistant' ),
					'remove_all_button'        => false,
					'type'                     => 'media',
					'media_type'               => 'image/svg+xml',
					'upload_accept_extensions' => 'svg'
				),

				'end_grid_currency_symbol_1' => array(
					'type' => 'endgrid',
				),
				'product_compare_sep_1'      => array(
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