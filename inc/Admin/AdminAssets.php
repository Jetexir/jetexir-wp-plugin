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
    $pluginVersion = Assets::getVersion();
    $debugName     = JETEXIR_DEBUG_MODE ? '' : '.min';

    wp_enqueue_style( JETEXIR_PLUGIN_SLUG . '-admin',
      Assets::url( 'css-admin/admin' . $debugName . '.css' ), false, $pluginVersion );

    if ( ! AdminPages::isSettingPage() ) {
      return;
    }

    wp_enqueue_media();
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );

    wp_enqueue_style( JETEXIR_PLUGIN_SLUG . '-plugin',
      Assets::url( 'css-admin/plugin' . $debugName . '.css' ), false, $pluginVersion );

    wp_enqueue_script( JETEXIR_PLUGIN_SLUG . '-dom-drag',
      Assets::url( 'js-admin/dom-drag' . $debugName . '.js' ),
      [], $pluginVersion, [ 'in_footer' => true ] );

    /*wp_enqueue_script( JETEXIR_PLUGIN_SLUG . '-modal',
      Assets::url( 'js-admin/modal.min.js' ),
      [], $pluginVersion, [ 'in_footer' => true ] );*/

    wp_enqueue_script( JETEXIR_PLUGIN_SLUG . '-plugin',
      Assets::url( 'js-admin/plugin.min.js' ),
      [
        'jquery',
        'jquery-ui-sortable',
        JETEXIR_PLUGIN_SLUG . '-dom-drag',
        //JETEXIR_PLUGIN_SLUG . '-modal'
      ], $pluginVersion, [ 'in_footer' => true ] );

    wp_add_inline_script( JETEXIR_PLUGIN_SLUG . '-plugin', 'var jetexirAjax = false, jetexirModalCloseEvent;', 'before' );

    /**
     * Filters the delay (in milliseconds) before the settings page is refreshed after saving.
     *
     * @param int $delay Delay in milliseconds.
     *
     * @return int Delay in milliseconds.
     *
     * @since 1.0
     *
     */
    $pageRefreshedAfter = (int) apply_filters( 'jetexir_settings_page_refreshed_after', 0 );

    /**
     * Filters the URL to redirect the settings page to after saving.
     *
     * @param string|null $url Redirect URL.
     *
     * @return string|null Redirect URL.
     *
     * @since 1.0
     *
     */
    $pageRefreshUrl = (string) apply_filters( 'jetexir_settings_page_refresh_url', null );

    wp_localize_script( JETEXIR_PLUGIN_SLUG . '-plugin', JETEXIR_PLUGIN_KEYCAP, array(
      'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
      'ajaxNonce'          => Nonce::create(),
      'pageRefreshedAfter' => $pageRefreshedAfter,
      'pageRefreshUrl'     => $pageRefreshUrl,
      'removeText'         => esc_html__( 'Remove', 'jetexir' ),
      'dtuConfirmDelete'   => esc_html__( 'Are you sure you want to delete this item(s)?', 'jetexir' ),
      'copyText'           => esc_html__( 'Click to copy this text.', 'jetexir' ),
    ) );
  }

  public static function imageUrl( $path ): string {
    return JETEXIR_PLUGIN_URL . 'assets/images/' . $path;
  }
}
