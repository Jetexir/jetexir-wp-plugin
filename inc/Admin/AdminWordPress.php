<?php

namespace WooAssistant\Admin;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Interfaces\AdminTabInterface;

class AdminWordPress implements AdminTabInterface {
	public const tab = 'wordpress';
	public const icon = '<svg fill="#873eff" viewBox="-3.2 -3.2 38.40 38.40" version="1.1" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"><path transform="translate(-3.2, -3.2), scale(1.2)" d="M16,27.57523903204128C18.899520762300256,27.112021365184575,21.110220974967355,25.250758765143026,23.33823121207117,23.33823121207117C25.855116561673825,21.177732800136067,29.295143364411857,19.3115789561524,29.48464288469404,16C29.677941203438806,12.622035540450998,26.926384907257365,9.813591006663012,24.229042175066184,7.7709578249338165C21.89072701406501,6.000207641726134,18.928155325620914,5.9592249339929095,16,5.7884022407233715C12.78328188222202,5.600745374449983,9.412014925383708,4.933839974184967,6.751344064243154,6.751344064243153C3.642718741505921,8.874845817523301,1.2218911218221784,12.23919703933139,1.0510924556292593,15.999999999999996C0.8747444644992546,19.882993128298672,2.7979614573542197,23.748929682564658,5.871312750444858,26.128687249555135C8.674594380320707,28.299324457901662,12.498968333478679,28.13455205412528,16,27.57523903204128" fill="#fff" strokewidth="0"></path></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>wordpress</title> <path d="M1.563 16.219c0-1.375 0.281-2.656 0.813-3.813l4.469 12.25c-3.125-1.5-5.281-4.719-5.281-8.438zM17.281 15.75c0 0.813-0.313 1.719-0.719 3.031l-0.938 3.125-3.406-10.094c0.563-0.031 1.094-0.094 1.094-0.094 0.5-0.063 0.438-0.781-0.063-0.75 0 0-1.531 0.094-2.5 0.094-0.938 0-2.469-0.094-2.469-0.094-0.5-0.031-0.563 0.719-0.063 0.75 0 0 0.469 0.063 0.969 0.094l1.469 4-2.063 6.156-3.406-10.156c0.563-0.031 1.063-0.094 1.063-0.094 0.531-0.063 0.469-0.781-0.063-0.75 0 0-1.5 0.094-2.5 0.094h-0.594c1.688-2.531 4.563-4.219 7.844-4.219 2.438 0 4.656 0.938 6.344 2.469-0.063-0.031-0.094-0.031-0.125-0.031-0.938 0-1.594 0.813-1.594 1.688 0 0.75 0.469 1.406 0.938 2.188 0.344 0.625 0.781 1.438 0.781 2.594zM8.281 25.219l2.813-8.188 2.906 7.906c0 0.063 0.031 0.094 0.063 0.125-0.969 0.344-2.031 0.531-3.125 0.531-0.906 0-1.813-0.125-2.656-0.375zM19.156 11.719c0.75 1.344 1.156 2.875 1.156 4.5 0 3.469-1.875 6.469-4.656 8.125l2.875-8.313c0.531-1.313 0.688-2.406 0.688-3.344 0-0.344 0-0.656-0.063-0.969zM10.938 5.281c6.031 0 10.938 4.906 10.938 10.938s-4.906 10.938-10.938 10.938-10.938-4.906-10.938-10.938 4.906-10.938 10.938-10.938zM10.938 26.656c5.75 0 10.438-4.688 10.438-10.438s-4.688-10.438-10.438-10.438-10.438 4.688-10.438 10.438 4.688 10.438 10.438 10.438z"></path> </g></svg>';

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