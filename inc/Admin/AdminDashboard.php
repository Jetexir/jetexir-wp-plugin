<?php

namespace Jetexir\Admin;

defined( 'ABSPATH' ) || exit;

use Jetexir\Addons\Addons;
use Jetexir\Helper\{Assets, Notice, User};
use Jetexir\Interfaces\AdminTabInterface;

class AdminDashboard implements AdminTabInterface {
  public const tab = 'dashboard';
  public const icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
  <g stroke="#873eff" stroke-linecap="round" stroke-width="1.5">
    <path d="m15.578 3.382 2 1.05c2.151 1.129 3.227 1.693 3.825 2.708C22 8.154 22 9.417 22 11.94v.117c0 2.525 0 3.788-.597 4.802-.598 1.015-1.674 1.58-3.825 2.709l-2 1.049C13.822 21.539 12.944 22 12 22s-1.822-.46-3.578-1.382l-2-1.05c-2.151-1.129-3.227-1.693-3.825-2.708C2 15.846 2 14.583 2 12.06v-.117c0-2.525 0-3.788.597-4.802.598-1.015 1.674-1.58 3.825-2.708l2-1.05C10.178 2.461 11.056 2 12 2s1.822.46 3.578 1.382ZM21 7.5 12 12m0 0L3 7.5m9 4.5v9.5"/>
  </g>
</svg>';

  public function __construct() {
    add_action( 'jetexir_dashboard_tab_content', [ $this, 'content' ] );
    add_action( 'jetexir_admin_init', [ $this, 'notice' ] );
    add_filter( 'jetexir_menus', [ $this, 'addMenu' ] );
  }

  public function addMenu( $menus ) {
    $menus[ self::tab ] = array(
      'title' => esc_html__( 'Dashboard', 'jetexir' ),
      'icon'  => self::icon
    );

    return $menus;
  }

  public function notice(): void {
    Notice::add( self::tab, esc_html__( 'Welcome to Jetexir!', 'jetexir' ), 'default' );
  }

  public function content(): void {
    $dashboardTypeLinks = array(
      'addons' => $this->getAddons(),
      'custom' => apply_filters( 'jetexir_dashboard_custom_links', [] )
    );

    if ( empty( $dashboardTypeLinks['addons'] ) ) {
      $message = '<strong>' . esc_html__( 'Hello', 'jetexir' ) . ', ' . User::getData( 'display_name' ) . '!</strong>';
      $message .= '<p>' . esc_html__( 'Jetexir is here to help you sell more in your store. To get started, go to the Addons tab and activate the required addons.', 'jetexir' ) . '</p>';

      echo '<div class="jetexir-dashboard-welcome">' . wp_kses( $message, [
          'strong' => [],
          'p'      => []
        ] ) . '</div>';
    }

    echo '<div class="jetexir-dashboard-links-wrap">';
    foreach ( $dashboardTypeLinks as $dashboardLinks ) {
      foreach ( $dashboardLinks as $link ) {
        $icon = ! empty( $link['icon'] ) && Assets::isSvgImageString( $link['icon'] ) ? Assets::setSvgDimensions( $link['icon'], 50 ) : '';
        echo '<a href="' . esc_url( $link['link'] ) . '" title="' . esc_html( $link['desc'] ) . '" class="jetexir-link-type-' . esc_html( $link['type'] ) . '">' . wp_kses_post( $icon ) . '<span>' . esc_html( $link['title'] ) . '</span></a>';
      }
    }
    echo '</div>';
  }

  private function getAddons(): array {
    $addons    = apply_filters( 'jetexir_dashboard_addon_links', [] );
    $addonCats = Addons::getAddonCats();
    $addonList = array();
    foreach ( array_keys( $addonCats ) as $addonCat ) {
      if ( ! empty( $addons[ $addonCat ] ) ) {
        $addonList[] = $addons[ $addonCat ];
      }
    }

    return array_merge( [], ...$addonList );
  }
}
