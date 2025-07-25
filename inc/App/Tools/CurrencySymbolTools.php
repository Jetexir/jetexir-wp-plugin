<?php

namespace WooAssistant\App\Tools;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Addons\Addon;
use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Cache;
use WooAssistant\Interfaces\AddonInterface;

class CurrencySymbolTools extends Addon implements AddonInterface {
	public string $addonID = 'currency-symbol-tools';
	public string $currentTab = 'tools';
	public string $currentSection = 'currency-symbol';

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

		$sections[ $this->currentSection ] = array(
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
			)
		);

		return $sections;
	}

	public function info(): array {
		$icon = '<svg viewBox="-2.4 -2.4 28.80 28.80" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"><path transform="translate(-2.4, -2.4), scale(0.8999999999999999)" d="M16,29.127165039380394C19.34606314574884,29.421679379724043,23.098493060021354,29.68452966540872,25.633453143931263,27.48070239336807C28.153680194246228,25.28968363119016,28.193309649411354,21.5335139307896,28.553464908117615,18.213514564678047C28.88574599515392,15.15046613857932,28.83380546102176,12.125110002410977,27.605769662742027,9.299405761063102C26.228709773812238,6.130797831074589,24.590111864673688,2.4848633415625416,21.28317147502537,1.484605669261029C18.01435660490479,0.49587990831101136,15.035656684150137,3.3655097735509383,11.823723607670061,4.525774914672073C8.599307032727639,5.6905495420621355,3.8396385615924844,5.061275612497645,2.568174728559704,8.245132063825919C1.263626322367811,11.511835175008317,5.597795375470705,14.283137630690856,6.578073902813863,17.66133978117512C7.374508173588277,20.405984176826202,5.877247700213757,23.69348985343226,7.6916563834909155,25.901498353481866C9.636440497580036,28.268164636688958,12.948580796149416,28.85858471833183,16,29.127165039380394" fill="#fff" strokewidth="0"></path></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M6 16C6 18.2091 7.79086 20 10 20H14C16.2091 20 18 18.2091 18 16C18 13.7909 16.2091 12 14 12H10C7.79086 12 6 10.2091 6 8C6 5.79086 7.79086 4 10 4H14C16.2091 4 18 5.79086 18 8M12 2V22" stroke="#873eff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>';

		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Currency Symbol', 'woo-assistant' ),
			'desc'           => __( 'Modify the currency symbol.', 'woo-assistant' ),
			'tags'           => [ __( 'Currency', 'woo-assistant' ) ],
			'cat'            => 'customizations',
			'icon'           => $icon,
			'more_info_link' => 'https://parsa.ws',
			'settings_key'   => $this->addonID,
		);
	}
}