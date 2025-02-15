<?php

namespace WooAssistant\Admin;

defined( 'ABSPATH' ) || exit;

use WooAssistant\App\ProductQuantity;
use WooAssistant\Interfaces\AdminTabInterface;

class AdminProduct implements AdminTabInterface {
	public const tab = 'product';

	public function __construct() {
		add_filter( 'woo_assistant_menus', [ $this, 'addMenu' ] );
		add_filter( 'woo_assistant_' . self::tab . '_settings', [ $this, 'settings' ] );
		add_filter( 'woo_assistant_settings', [ $this, 'allSettings' ] );
		add_filter( 'woo_assistant_' . self::tab . '_tab_display_notice', '__return_false' );
		add_filter( 'woo_assistant_' . self::tab . '_tab_content_display_notice', '__return_true' );
	}

	public function addMenu( $menus ) {
		$menus[ self::tab ] = __( 'Product', 'woo-assistant' );

		return $menus;
	}

	public function allSettings( $settings ): array {
		$settings[ self::tab ] = $this->settings();

		return $settings;
	}

	public function settings(): array {
		return array(
			'title'    => __( 'Product', 'woo-assistant' ),
			'desc'     => __( 'Product enhance tools', 'woo-assistant' ),
			'sections' => apply_filters( 'woo_assistant_product_settings_sections', [] )
		);
	}
}