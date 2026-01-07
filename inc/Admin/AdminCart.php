<?php

namespace Jetexir\Admin;

defined( 'ABSPATH' ) || exit;

use Jetexir\Interfaces\AdminTabInterface;

class AdminCart implements AdminTabInterface {
  public const tab = 'cart';
  public const icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><g stroke="#873eff" stroke-width="1.5"><path d="M3.555 14.257c-.718-3.353-1.078-5.03-.177-6.143C4.278 7 5.993 7 9.422 7h5.156c3.43 0 5.143 0 6.044 1.114.9 1.114.541 2.79-.177 6.143l-.429 2c-.487 2.273-.73 3.409-1.555 4.076-.825.667-1.987.667-4.311.667h-4.3c-2.324 0-3.486 0-4.31-.667-.826-.667-1.07-1.803-1.556-4.076l-.429-2Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h8M10 15h4M18 9l-3-6M6 9l3-6"/></g></svg>';

  private static ?array $settings = null;

  public function __construct() {
    add_filter( 'jetexir_menus', [ $this, 'addMenu' ] );
    add_filter( 'jetexir_' . self::tab . '_settings', [ $this, 'settings' ] );
    add_filter( 'jetexir_settings', [ $this, 'allSettings' ] );
    add_filter( 'jetexir_' . self::tab . '_tab_display_notice', '__return_false' );
    add_filter( 'jetexir_' . self::tab . '_tab_content_display_notice', '__return_true' );
  }

  public function addMenu( $menus ) {
    $settings = $this->settings();
    if ( ! empty( $settings['sections'] ) ) {
      $menus[ self::tab ] = array(
        'title' => esc_html__( 'Cart', 'jetexir' ),
        'icon'  => self::icon
      );
    }

    return $menus;
  }

  public function allSettings( $settings ): array {
    $settings[ self::tab ] = $this->settings();

    return $settings;
  }

  public function settings(): array {
    if ( self::$settings === null ) {
      self::$settings = array(
        'title'    => esc_html__( 'Cart', 'jetexir' ),
        'desc'     => esc_html__( 'Tools to enhance your WooCommerce cart', 'jetexir' ),
        'sections' => apply_filters( 'jetexir_' . self::tab . '_settings_sections', [] )
      );
    }

    return self::$settings;
  }
}
