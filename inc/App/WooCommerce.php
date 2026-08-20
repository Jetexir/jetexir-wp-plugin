<?php

namespace Jetexir\App;

defined( 'ABSPATH' ) || exit;

use Jetexir\Helper\Param;
use Jetexir\Helper\Templates;

class WooCommerce {
  public function __construct() {
    add_filter( 'woocommerce_locate_template', [ $this, 'locateTemplate' ], 10, 4 );
    add_filter( 'admin_body_class', [ $this, 'addBodyClass' ] );
    add_action( 'before_woocommerce_init', [ $this, 'declareCompatibility' ] );
  }

  /**
   * Declare WooCommerce feature compatibility
   *
   * @return void
   */
  public function declareCompatibility() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
      \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', JETEXIR_PLUGIN_FILE_PATH, true );
      \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'product_instance_caching', JETEXIR_PLUGIN_FILE_PATH, true );
    }
  }

  public function addBodyClass( $classes ) {
    if ( Param::get( 'page' ) === 'wc-orders' && Param::get( 'action' ) === 'edit' ) {
      $orderId = Param::get( 'id' );
      $order   = wc_get_order( $orderId );

      $classes .= 'wc-order-status-' . $order->get_status();
    }

    return $classes;
  }

  /**
   * Filter to customize the path of a given WooCommerce template.
   *
   * @param string $template Full file path of the template.
   * @param string $templateName Template name.
   * @param string $templatePath Template path.
   * @param string $defaultPath Default WooCommerce templates path.
   */
  public function locateTemplate( $template, $templateName, $templatePath, $defaultPath ): string {
    /**
     * Filters the WooCommerce template path located by Jetexir.
     *
     * @param string|bool $override Template path to use instead of the default one, or false.
     * @param string $templateName Template name.
     * @param string $template Full file path of the template.
     * @param string $templatePath Template path.
     * @param string $defaultPath Default WooCommerce templates path.
     *
     * @return string|bool Template path to use instead of the default one, or false.
     *
     * @since 1.0
     *
     */
    $jetexirTemplate = (string) apply_filters( 'jetexir_wc_locate_template', false, $templateName, $template, $templatePath, $defaultPath );

    if ( ! $jetexirTemplate ) {
      return $template;
    }

    $path = Templates::getPath( $jetexirTemplate, 'woocommerce' );
    if ( file_exists( $path ) ) {
      $template = $path;
    }

    return $template;
  }
}
