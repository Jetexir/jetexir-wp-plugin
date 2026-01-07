<?php

namespace Jetexir\App;

defined( 'ABSPATH' ) || exit;

use Jetexir\Helper\Assets;
use Jetexir\Helper\Nonce;
use Jetexir\Helper\WordPress;

class AppAssets {
  public function __construct() {
    add_action( 'wp_enqueue_scripts', array( $this, 'enqueueScripts' ) );
  }

  public function enqueueScripts(): void {
    $pluginVersion = Assets::getVersion();
    $debugName     = JETEXIR_DEBUG_MODE ? '' : '.min';

    wp_enqueue_style( JETEXIR_PLUGIN_SLUG . '-global-style',
      Assets::url( 'css/style' . $debugName . '.css' ), false, $pluginVersion );

    wp_enqueue_script( JETEXIR_PLUGIN_SLUG . '-global',
      Assets::url( 'js/global.min.js' ),
      [ 'jquery' ], $pluginVersion, [ 'in_footer' => true ] );

    wp_add_inline_script( JETEXIR_PLUGIN_SLUG . '-global', 'var jetexirAjax, jetexirModalCloseEvent;', 'before' );

    wp_localize_script( JETEXIR_PLUGIN_SLUG . '-global', JETEXIR_PLUGIN_KEYCAP, array(
      'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
      'ajaxNonce' => Nonce::create(),
      'sslError'  => esc_html__( 'Your site does not have SSL support, For example: https://example.com', 'jetexir' ),
      'pageName'  => WordPress::getPageName(),
      'direction' => WordPress::isRTL() ? 'rtl' : 'ltr',
    ) );

    // Owl Carousel
    wp_register_style( JETEXIR_PLUGIN_SLUG . '-owl-carousel',
      Assets::url( 'css/owl-carousel' . $debugName . '.css' ), false, '2.3.4' );
    /*wp_register_style( JETEXIR_PLUGIN_SLUG . '-owl-carousel-theme',
      Assets::url( 'css/owl.theme.default' . $debugName . '.css' ), false, '2.3.4' );*/
    wp_register_script( JETEXIR_PLUGIN_SLUG . '-owl-carousel',
      Assets::url( 'js/owl.carousel.min.js' ),
      [ 'jquery' ], '2.3.4', [ 'in_footer' => true ] );

  }
}
