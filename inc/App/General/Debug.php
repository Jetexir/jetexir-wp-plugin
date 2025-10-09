<?php

namespace AssistantForWooCommerce\App\General;

use AssistantForWooCommerce\Helper\Notice;
use AssistantForWooCommerce\Settings\Settings;

defined( 'ABSPATH' ) || exit;

class Debug {
	private const sectionID = 'debug';

	public function __construct() {
		add_filter( 'assistant_for_woocommerce_general_settings_sections', [ $this, 'addSectionSettings' ] );
		add_action( 'assistant_for_woocommerce_admin_init', [ $this, 'addNotice' ] );
	}

	public function addNotice( $tab ): void {
		if ( Settings::get( 'debug_mode', false ) ) {
			Notice::add( 'dashboard', esc_html__( 'Debug mode is enabled!', 'assistant-for-woocommerce' ), 'warning' );
		}
	}

	public function addSectionSettings( array $sections ): array {
		$settings = [
			'start_grid_debug_mode' => array(
				'title' => __( 'Debugging', 'assistant-for-woocommerce' ),
				'type'  => 'startGrid',
			),
			'debug_mode'            => array(
				'id'       => 'debug_mode',
				'title'    => __( 'Enable debug mode', 'assistant-for-woocommerce' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => false,
				'desc'     => __( 'By enabling this option, the uncompressed version of the JS and CSS files will be loaded.', 'assistant-for-woocommerce' ),
				'sanitize' => 'bool'
			),
			'end_grid_debug_mode'   => array(
				'type' => 'endGrid',
			)
		];

		$sections[ self::sectionID ] = array(
			'title'    => __( 'Debug', 'assistant-for-woocommerce' ),
			'desc'     => __( 'Debug Settings', 'assistant-for-woocommerce' ),
			'settings' => $settings
		);

		return $sections;
	}
}