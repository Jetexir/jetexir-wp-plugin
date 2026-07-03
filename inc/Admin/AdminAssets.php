<?php

namespace Jetexir\Admin;

defined( 'ABSPATH' ) || exit;

use Jetexir\Helper\Assets;
use Jetexir\Helper\Nonce;

class AdminAssets {
  public function __construct() {
    add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );

    add_filter( 'safe_style_css', [ $this, 'addSafeStyle' ], PHP_INT_MAX );
  }

  public function addSafeStyle( $styles ): array {
    return array_merge( $styles, [
      'clip-rule',
      'fill-rule',
      'fill'
    ] );
  }

  public function enqueueScripts(): void {
    if ( ! AdminPages::isSettingPage() ) {
      return;
    }

    $pluginVersion = Assets::getVersion();
    $debugName     = JETEXIR_DEBUG_MODE ? '' : '.min';

    wp_enqueue_media();
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );

    wp_enqueue_style( JETEXIR_PLUGIN_SLUG . '-admin-style',
      Assets::url( 'css-admin/admin-style' . $debugName . '.css' ), false, $pluginVersion );

    wp_enqueue_script( JETEXIR_PLUGIN_SLUG . '-dom-drag',
      Assets::url( 'js-admin/dom-drag.js' ),
      [], $pluginVersion, [ 'in_footer' => true ] );

    /*wp_enqueue_script( JETEXIR_PLUGIN_SLUG . '-modal',
      Assets::url( 'js-admin/modal.min.js' ),
      [], $pluginVersion, [ 'in_footer' => true ] );*/

    wp_enqueue_script( JETEXIR_PLUGIN_SLUG . '-admin',
      Assets::url( 'js-admin/script.min.js' ),
      [
        'jquery',
        'jquery-ui-sortable',
        JETEXIR_PLUGIN_SLUG . '-dom-drag',
        //JETEXIR_PLUGIN_SLUG . '-modal'
      ], $pluginVersion, [ 'in_footer' => true ] );

    wp_add_inline_script( JETEXIR_PLUGIN_SLUG . '-admin', 'var jetexirAjax = false, jetexirModalCloseEvent;', 'before' );

    wp_localize_script( JETEXIR_PLUGIN_SLUG . '-admin', JETEXIR_PLUGIN_KEYCAP, array(
      'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
      'ajaxNonce'          => Nonce::create(),
      'pageRefreshedAfter' => apply_filters( 'jetexir_settings_page_refreshed_after', 0 ),
      'pageRefreshUrl'     => apply_filters( 'jetexir_settings_page_refresh_url', null ),
      'removeText'         => esc_html__( 'Remove', 'jetexir' ),
      'dtuConfirmDelete'   => esc_html__( 'Are you sure you want to delete this item(s)?', 'jetexir' ),
      'copyText'           => esc_html__( 'Click to copy this text.', 'jetexir' ),
    ) );
  }

  public static function imageUrl( $path ): string {
    return JETEXIR_PLUGIN_URL . 'assets/images/' . $path;
  }
}
