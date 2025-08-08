<?php

namespace WooAssistant\Admin;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Interfaces\AdminTabInterface;

class AdminWordPress implements AdminTabInterface {
	public const tab = 'wordpress';
	public const icon = '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="800" height="800" fill="#873eff" viewBox="-4 -4 48 48"><path fill="#fff" stroke-width="0" d="M20 35.976c3.907.219 7.422 4.604 11.083 3.22 3.515-1.33 4.712-5.9 5.636-9.543.808-3.183-.2-6.418-.765-9.653-.479-2.744-1.282-5.292-2.294-7.887-1.177-3.016-1.838-6.326-4.139-8.604C26.94.95 23.623-1.118 20-1.414c-3.708-.304-7.567.954-10.467 3.285C6.766 4.095 6.545 8.15 4.496 11.05c-2.32 3.283-6.8 5.05-7.77 8.951-1.002 4.024.157 8.582 2.571 11.953 2.36 3.296 6.333 5.27 10.312 6.044 3.524.685 6.807-2.22 10.391-2.02"/><path d="M34.213 7.072c-1.016-1.017-2.354-1.324-3.77-.864-.668.216-1.482.967-1.797 2.156-.289 1.086-.23 2.823 1.684 4.915 2.792 3.049 1.906 7.486 1.896 7.528-.001.008-.002.016-.004.021l-2.132 5.818-5.675-16.24h2.01a.783.783 0 0 0 .783-.78.78.78 0 0 0-.783-.779h-9.804a.78.78 0 1 0 0 1.559h1.333l3.098 8.642a.795.795 0 0 0-.094.178l-2.708 7.413-5.672-16.232h2.007a.779.779 0 1 0 0-1.559H4.784a.78.78 0 1 0 0 1.559h1.334l8.271 23.076a.78.78 0 0 0 .736.517h1.554a.782.782 0 0 0 .736-.513l1.555-4.25 2.894-7.924 4.362 12.17c.11.309.406.517.737.517h1.555a.78.78 0 0 0 .734-.513l5.916-16.146a.32.32 0 0 0 .008-.023c1.385-4.163 1-8.281-.963-10.246zM16.131 32.438h-.454L7.782 10.407h3.139l6.481 18.554-1.271 3.477zm11.839 0h-.454l-7.897-22.031h3.139l6.484 18.559-1.272 3.472zm5.747-15.697c-.293-1.472-.934-3.098-2.229-4.513-1.44-1.573-1.517-2.753-1.328-3.467.182-.685.629-1.023.77-1.07.267-.087.521-.13.767-.13.528 0 .999.204 1.406.612 1.282 1.283 1.899 4.634.614 8.568z"/></svg>';

	private static ?array $settings = null;

	public function __construct() {
		add_filter( 'woo_assistant_menus', [ $this, 'addMenu' ] );
		add_filter( 'woo_assistant_' . self::tab . '_settings', [ $this, 'settings' ] );
		add_filter( 'woo_assistant_settings', [ $this, 'allSettings' ] );
		add_filter( 'woo_assistant_' . self::tab . '_tab_display_notice', '__return_false' );
		add_filter( 'woo_assistant_' . self::tab . '_tab_content_display_notice', '__return_true' );
		add_filter( 'woo_assistant_dashboard_custom_links', [ $this, 'addDashboardLink' ] );
	}

	public function addDashboardLink( $links ) {
		$settings = $this->settings();
		if ( ! empty( $settings['sections'] ) ) {
			$links[] = [
				'title' => __( 'WordPress' ),
				'desc'  => __( 'WordPress customize options', 'woo-assistant' ),
				'link'  => AdminPages::link( [
					'tab' => self::tab
				] ),
				'icon'  => self::icon,
				'type'  => 'wordpress'
			];
		}

		return $links;
	}

	public function addMenu( $menus ) {
		$settings = $this->settings();
		if ( ! empty( $settings['sections'] ) ) {
			$menus[ self::tab ] = __( 'WordPress', 'woo-assistant' );
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
				'title'    => __( 'WordPress', 'woo-assistant' ),
				'desc'     => __( 'Tools to enhance your WordPress site', 'woo-assistant' ),
				'sections' => apply_filters( 'woo_assistant_' . self::tab . '_settings_sections', [] )
			);
		}

		return self::$settings;
	}
}