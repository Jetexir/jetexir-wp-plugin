<?php

namespace AssistantForWooCommerce\App;

defined( 'ABSPATH' ) || exit;

use AssistantForWooCommerce\Helper\Param;
use AssistantForWooCommerce\Helper\Templates;

class WooCommerce {
	public function __construct() {
		add_filter( 'woocommerce_locate_template', [ $this, 'locateTemplate' ], 10, 4 );
		add_filter( 'admin_body_class', [ $this, 'addBodyClass' ] );
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
	 * Note: the $defaultPath argument was added in WooCommerce 9.5.0.
	 *
	 * @param string $template Full file path of the template.
	 * @param string $templateName Template name.
	 * @param string $templatePath Template path.
	 * @param string $templatePath Default WooCommerce templates path.
	 *
	 * @since 9.5.0 $defaultPath argument added.
	 */
	public function locateTemplate( $template, $templateName, $templatePath, $defaultPath ): string {
		$waTemplate = apply_filters( 'assistant_for_woocommerce_wc_locate_template', false, $templateName, $template, $templatePath, $defaultPath );

		if ( ! $waTemplate ) {
			return $template;
		}

		$path = Templates::getPath( $waTemplate, 'woocommerce' );
		if ( file_exists( $path ) ) {
			$template = $path;
		}

		return $template;
	}
}