<?php

namespace WooAssistant\Admin;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Interfaces\AdminTabInterface;

class AdminTools implements AdminTabInterface {
	public const tab = 'tools';
	public const icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
  <g stroke="#873eff" stroke-width="1.5">
    <circle cx="12" cy="12" r="10"/>
    <circle cx="12" cy="12" r="3"/>
    <path d="M2 13s2.2 2 4 2c1.212 0 2.606-.908 3.387-1.5M14 14.224c.471.415 1.088.776 1.805.776 1.69 0 1.69-2 3.38-2 1.077 0 1.925.814 2.399 1.403"/>
    <path stroke-linecap="round" d="M14.5 7 16 5M19 7l1-1M12 5l-1-1M10.5 7l-1.366.366M16.65 8.977l.066 1.412M20.678 10.085 19 11.563M7 5 6 4M6.792 9.144l-.585-1.288M5.665 12.642 6.5 11.5M3.683 10.35l-.079-1.412"/>
  </g>
</svg>';

	private static ?array $settings = null;

	public function __construct() {
		add_filter( 'woo_assistant_menus', [ $this, 'addMenu' ] );
		add_filter( 'woo_assistant_' . self::tab . '_settings', [ $this, 'settings' ] );
		add_filter( 'woo_assistant_settings', [ $this, 'allSettings' ] );
		add_filter( 'woo_assistant_' . self::tab . '_tab_display_notice', '__return_false' );
		add_filter( 'woo_assistant_' . self::tab . '_tab_content_display_notice', '__return_true' );
	}

	public function addMenu( $menus ) {
		$settings = $this->settings();
		if ( ! empty( $settings['sections'] ) ) {
			$menus[ self::tab ] = array(
				'title' => __( 'Tools', 'woo-assistant' ),
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
				'title'    => __( 'Tools', 'woo-assistant' ),
				'desc'     => __( 'Tools for WordPress and WooCommerce', 'woo-assistant' ),
				'sections' => apply_filters( 'woo_assistant_' . self::tab . '_settings_sections', [] )
			);
		}

		return self::$settings;
	}
}