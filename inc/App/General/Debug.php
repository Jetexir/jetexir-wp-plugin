<?php

namespace WooAssistant\App\General;

defined( 'ABSPATH' ) || exit;

class Debug {
	private const sectionID = 'debug';

	public function __construct() {
		add_filter( 'woo_assistant_general_settings_sections', [ $this, 'addSectionSettings' ] );
	}

	public function addSectionSettings( array $sections ): array {
		$settings = [
			'start_grid_debug_enable' => array(
				'title' => __( 'Debugging', 'wc-assistant' ),
				'type'  => 'startGrid',
			),
			'debug_enable'            => array(
				'id'       => 'debug_enable',
				'title'    => __( 'Enable debug mode', 'wc-assistant' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => false,
				'desc'     => __( 'By enabling this option, the uncompressed version of the JS and CSS files will be loaded.', 'wc-assistant' ),
				'sanitize' => 'bool'
			),
			'end_grid_debug_enable'   => array(
				'type' => 'endGrid',
			)
		];

		$sections[ self::sectionID ] = array(
			'title'    => __( 'Debug', 'wc-assistant' ),
			'desc'     => __( 'Debug Settings', 'wc-assistant' ),
			'settings' => $settings
		);

		return $sections;
	}
}