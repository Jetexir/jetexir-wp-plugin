<?php

namespace WooAssistant\App;

use WooAssistant\Helper\Templates;

class WooCommerce {
	public function __construct() {
		add_filter( 'woocommerce_locate_template', [ $this, 'locateTemplate' ], 10, 4 );
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
		$waTemplate = apply_filters( 'woo_assistant_wc_locate_template', false, $templateName, $template, $templatePath, $defaultPath );

		if ( ! $waTemplate ) {
			return $template;
		}

		$path = Templates::getPath( $waTemplate );
		if ( file_exists( $path ) ) {
			$template = $path;
		}

		return $template;
	}
}