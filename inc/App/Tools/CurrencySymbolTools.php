<?php

namespace Jetexir\App\Tools;

defined( 'ABSPATH' ) || exit;

use Jetexir\Addons\Addon;
use Jetexir\Helper\Assets;
use Jetexir\Helper\Cache;
use Jetexir\Helper\Param;
use Jetexir\Helper\Sanitizing;
use Jetexir\Interfaces\AddonInterface;

class CurrencySymbolTools extends Addon implements AddonInterface {
  public string $addonID = 'currency-symbol-tools';
  public string $currentTab = 'tools';
  public string $currentSection = 'currency-symbol';

  public function initAction(): void {
    add_filter( 'woocommerce_currency_symbol', [ $this, 'changeCurrencySymbol' ], 10, 2 );
    add_action( 'wp_enqueue_scripts', [ $this, 'addInlineStyles' ], 0 );
  }

  public function changeCurrencySymbol( $symbol, $code ) {
    if ( 'wc-settings' === Param::get( 'page' ) && 'general' === Param::get( 'tab', 'general' ) && is_admin() ) {
      return $symbol;
    }

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
            $file = Assets::setSvgDimensions( $file, $this->getSetting( 'currency_media_size', 14 ) );
            $file = wp_kses( $file, Sanitizing::svgAllowedTags() );
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

  public function addInlineStyles(): void {
    if ( $this->getSetting( 'price_currency_style', true ) ) {
      $styles = '.woocommerce-Price-amount bdi{display: inline-flex;align-items: center;gap: 3px;line-height: 1.2;}';
      wp_register_style( JETEXIR_PLUGIN_SLUG . '-' . $this->addonID . '-style', false, [], Assets::getVersion() );
      wp_enqueue_style( JETEXIR_PLUGIN_SLUG . '-' . $this->addonID . '-style' );
      wp_add_inline_style( JETEXIR_PLUGIN_SLUG . '-' . $this->addonID . '-style', esc_html( $styles ) );
    }
  }

  public function addSectionSettings( $sections ) {
    $currency = get_option( 'woocommerce_currency', 'USD' );
    $symbol   = get_woocommerce_currency_symbol( $currency );
    $symbol   = Assets::isSvgImageString( $symbol ) || Assets::isImageString( $symbol ) ? '' : $symbol;

    $sections[ $this->currentSection ] = array(
      'title'        => esc_html__( 'Currency Symbol', 'jetexir' ),
      'settings_key' => $this->info()['settings_key'],
      'settings'     => array(
        'start_grid_currency_symbol_1' => array(
          'title' => esc_html__( 'Currency Symbol', 'jetexir' ),
          'type'  => 'startgrid',
        ),
        'currency_1'                   => array(
          'id'                => 'currency_1',
          'title'             => esc_html__( 'Currency', 'jetexir' ),
          'type'              => 'currencySelect',
          'default'           => $currency,
          'option_none'       => esc_html__( 'No changes', 'jetexir' ),
          'option_none_value' => '',
          'sanitize'          => 'text'
        ),
        'currency_symbol_1'            => array(
          'id'      => 'currency_symbol_1',
          'title'   => esc_html__( 'Symbol', 'jetexir' ),
          'type'    => 'text',
          'default' => $symbol
        ),
        'currency_media_1'             => array(
          'id'                       => 'currency_media_1',
          'title'                    => esc_html__( 'SVG Icon', 'jetexir' ),
          'select_button'            => esc_html__( 'Select SVG', 'jetexir' ),
          'remove_all_button'        => false,
          'type'                     => 'media',
          'media_type'               => 'image/svg+xml',
          'upload_accept_extensions' => 'svg'
        ),

        'currency_media_size' => array(
          'id'         => 'currency_media_size',
          'title'      => esc_html__( 'SVG media size', 'jetexir' ),
          'desc'       => esc_html__( 'Pixel', 'jetexir' ),
          'type'       => 'number',
          'default'    => 14,
          'attributes' => array(
            'placeholder' => 'eg: 14',
            'step'        => 1,
            'min'         => 10,
            'max'         => 50,
          ),
          'sanitize'   => 'int'
        ),

        'price_currency_style' => [
          'id'       => 'price_currency_style',
          'title'    => esc_html__( 'Price style', 'jetexir' ),
          'type'     => 'toggle',
          'value'    => 1,
          'default'  => true,
          'desc'     => esc_html__( 'Styling the price along with the currency symbol', 'jetexir' ),
          'sanitize' => 'bool'
        ],

        'end_grid_currency_symbol_1' => array(
          'type' => 'endgrid',
        ),
      )
    );

    return $sections;
  }

  public function info(): array {
    $icon = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M6 16C6 18.2091 7.79086 20 10 20H14C16.2091 20 18 18.2091 18 16C18 13.7909 16.2091 12 14 12H10C7.79086 12 6 10.2091 6 8C6 5.79086 7.79086 4 10 4H14C16.2091 4 18 5.79086 18 8M12 2V22" stroke="#873eff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>';

    return array(
      'id'             => $this->addonID,
      'title'          => esc_html__( 'Currency Symbol', 'jetexir' ),
      'desc'           => esc_html__( 'Change the currency symbol', 'jetexir' ),
      'tags'           => [ esc_html__( 'Currency', 'jetexir' ) ],
      'cat'            => 'customizations',
      'icon'           => $icon,
      'more_info_link' => '{jetexir_website}/addons/currency-symbols',
      'settings_key'   => $this->addonID,
    );
  }
}
