<?php

namespace AssistantForWooCommerce\Admin;

defined( 'ABSPATH' ) || exit;

use AssistantForWooCommerce\Interfaces\AdminTabInterface;

class AdminOrder implements AdminTabInterface {
	public const tab = 'order';
	public const icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
  <g stroke="#873eff" stroke-width="1.5">
    <path d="M16 4.002c2.175.012 3.353.109 4.121.877C21 5.758 21 7.172 21 10v6c0 2.829 0 4.243-.879 5.122C19.243 22 17.828 22 15 22H9c-2.828 0-4.243 0-5.121-.878C3 20.242 3 18.829 3 16v-6c0-2.828 0-4.242.879-5.121.768-.768 1.946-.865 4.121-.877"/>
    <path stroke-linecap="round" d="M10.5 14H17M7 14h.5M7 10.5h.5M7 17.5h.5M10.5 10.5H17M10.5 17.5H17"/>
    <path d="M8 3.5A1.5 1.5 0 0 1 9.5 2h5A1.5 1.5 0 0 1 16 3.5v1A1.5 1.5 0 0 1 14.5 6h-5A1.5 1.5 0 0 1 8 4.5v-1Z"/>
  </g>
</svg>';

	private static ?array $settings = null;

	public function __construct() {
		add_filter( 'assistant_for_woocommerce_menus', [ $this, 'addMenu' ] );
		add_filter( 'assistant_for_woocommerce_' . self::tab . '_settings', [ $this, 'settings' ] );
		add_filter( 'assistant_for_woocommerce_settings', [ $this, 'allSettings' ] );
		add_filter( 'assistant_for_woocommerce_' . self::tab . '_tab_display_notice', '__return_false' );
		add_filter( 'assistant_for_woocommerce_' . self::tab . '_tab_content_display_notice', '__return_true' );
	}

	public function addMenu( $menus ) {
		$settings = $this->settings();
		if ( ! empty( $settings['sections'] ) ) {
			$menus[ self::tab ] = array(
				'title' => __( 'Order', 'assistant-for-woocommerce' ),
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
				'title'    => __( 'Order', 'assistant-for-woocommerce' ),
				'desc'     => __( 'Tools to enhance your WooCommerce orders', 'assistant-for-woocommerce' ),
				'sections' => apply_filters( 'assistant_for_woocommerce_' . self::tab . '_settings_sections', [] )
			);
		}

		return self::$settings;
	}
}