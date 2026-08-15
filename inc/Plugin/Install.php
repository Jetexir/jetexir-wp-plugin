<?php

namespace Jetexir\Plugin;

use Jetexir\Helper\Cache;

class Install {
  public function __construct() {
    add_action( 'plugins_loaded', [ $this, 'init' ] );
  }

  public function init() {
    if ( self::checkVersion() ) {
      self::update();

      self::updateVersion();
    }

    $settings = get_option( JETEXIR_PLUGIN_KEY, [] );
    if ( empty( $settings ) ) {
      self::install();
    }
  }

  private static function install() {
    $settings = get_option( JETEXIR_PLUGIN_KEY, [] );

    if ( empty( $settings ) ) {
      $pluginSettings = [
        'save_options_time_123456'                   => 1,
        'enable_styles'                              => true,
        'primary_color_enable'                       => true,
        'primary_color'                              => '#873eff',
        'text_color_enable'                          => true,
        'text_color'                                 => '#333333',
        'bg_color_enable'                            => true,
        'bg_color'                                   => '#f6f5f9',
        'element_color_enable'                       => true,
        'element_color'                              => '#333333',
        'element_hover_color_enable'                 => true,
        'element_hover_color'                        => '#873eff',
        'element_bg_color'                           => '#f6f5f9',
        'element_hover_bg_color'                     => '#f2edff',
        'element_border_color_enable'                => true,
        'element_border_color'                       => '#e3e0e5',
        'element_hover_border_color_enable'          => true,
        'element_hover_border_color'                 => '#873eff',
        'element_border_radius_enable'               => true,
        'element_border_radius'                      => '5px',
        'element_border_width_enable'                => true,
        'element_border_width'                       => '1px',
        'input_color_enable'                         => true,
        'input_color'                                => '#333333',
        'input_bg_color_enable'                      => true,
        'input_bg_color'                             => '#ffffff',
        'input_border_color_enable'                  => true,
        'input_border_color'                         => '#000000',
        'input_border_radius_enable'                 => true,
        'input_border_radius'                        => '5px',
        'input_border_width_enable'                  => true,
        'input_border_width'                         => '1px',
        'button_color_enable'                        => true,
        'button_color'                               => '#ffffff',
        'button_hover_color_enable'                  => true,
        'button_hover_color'                         => '#f7f7f7',
        'button_bg_color_enable'                     => true,
        'button_bg_color'                            => '#333333',
        'button_hover_bg_color_enable'               => true,
        'button_hover_bg_color'                      => '#555555',
        'button_border_color_enable'                 => true,
        'button_border_color'                        => '#000000',
        'button_hover_border_color_enable'           => true,
        'button_hover_border_color'                  => '#333333',
        'button_border_radius_enable'                => true,
        'button_border_radius'                       => '5px',
        'button_border_width_enable'                 => true,
        'button_border_width'                        => '1px',
        'secondary_button_color_enable'              => true,
        'secondary_button_color'                     => '#333333',
        'secondary_button_hover_color_enable'        => true,
        'secondary_button_hover_color'               => '#555555',
        'secondary_button_bg_color_enable'           => true,
        'secondary_button_bg_color'                  => '#eaeaea',
        'secondary_button_hover_bg_color_enable'     => true,
        'secondary_button_hover_bg_color'            => '#fcfcfc',
        'secondary_button_border_color_enable'       => true,
        'secondary_button_border_color'              => '#c1c1c1',
        'secondary_button_hover_border_color_enable' => true,
        'secondary_button_hover_border_color'        => '#adadad',
        'secondary_button_border_radius_enable'      => true,
        'secondary_button_border_radius'             => '5px',
        'secondary_button_border_width_enable'       => true,
        'secondary_button_border_width'              => '1px',
        'svg_enable'                                 => true,
        'internal_addon_product-price-variation'     => true,
        'internal_addon_product-quantity'            => true,
        'internal_addon_product-faq'                 => true,
        'internal_addon_product-call'                => true,
        'internal_addon_product-sale-progress-bar'   => true,
        'internal_addon_menu-cart'                   => true,
        'internal_addon_checkout-fields'             => true,
        'internal_addon_order-status'                => true,
        'internal_addon_order-number'                => true,
        'internal_addon_announcement-bar-tools'      => true,
        'internal_addon_currency-symbol-tools'       => true
      ];

      update_option( JETEXIR_PLUGIN_KEY, $pluginSettings, false );
    }
  }

  public static function update(): void {
    $oldVersion = get_option( JETEXIR_PLUGIN_KEY . '_plugin_version', '1.0' );
    $settings   = get_option( JETEXIR_PLUGIN_KEY, [] );

    if ( empty( $settings ) && version_compare( $oldVersion, '1.0', '<' ) ) {
      $pluginSettings = array();
      // update_option( JETEXIR_PLUGIN_KEY, $pluginSettings, false );
    }
  }

  private static function checkVersion( $oldVersion = '1.0' ) {
    $currentVersion = self::getCurrentPluginVersion();
    $oldVersion     = get_option( JETEXIR_PLUGIN_KEY . '_plugin_version', $oldVersion );

    return version_compare( $oldVersion, $currentVersion, '<' );
  }

  private static function updateVersion() {
    update_option( JETEXIR_PLUGIN_KEY . '_plugin_version', self::getCurrentPluginVersion(), true );
  }

  private static function getCurrentPluginVersion() {
    $version = Cache::get( 'plugin_version', false );

    if ( $version ) {
      return $version;
    }

    $pluginData     = get_plugin_data( JETEXIR_PLUGIN_FILE_PATH );
    $currentVersion = $pluginData['Version'];
    Cache::set( 'plugin_version', $currentVersion );

    return $currentVersion;
  }
}
