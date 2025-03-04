<?php

namespace WooAssistant\App\GlobalSettings;

use WooAssistant\Enums\Colors;

class Styles {
	private const sectionID = 'compare';

	public function __construct() {
		add_filter( 'woo_assistant_global_settings_sections', [ $this, 'addSectionSettings' ] );
	}

	public function addSectionSettings( $sections ) {
		$settings = [
			'start_grid_enable_styles' => array(
				'title' => __( 'Styles', 'woo-assistant' ),
				'type'  => 'startgrid',
			),
			'disable_styles'           => array(
				'id'       => 'enable_styles',
				'title'    => __( 'Enable Styles', 'woo-assistant' ),
				'type'     => 'toggle',
				'value'    => 1,
				'default'  => true,
				'desc'     => __( 'If you want to change elements based on the theme style, disable this option.', 'woo-assistant' ),
				'sanitize' => 'bool'
			),
			'end_grid_enable_styles'   => array(
				'type' => 'endgrid',
			)
		];

		// Input styles
		$settings = array_merge( $settings, [
			'start_grid_input_styles' => array(
				'title' => __( 'Input box', 'woo-assistant' ),
				'type'  => 'startgrid',
			),

			'start_inline_elements_input_border_radius' => array(
				'title' => __( 'Border radius', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'input_border_radius_enable'                => array(
				'id'       => 'input_border_radius_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => false,
				'sanitize' => 'bool'
			),
			'input_border_radius'                       => array(
				'id'         => 'input_border_radius',
				'type'       => 'text',
				'default'    => '5px',
				'attributes' => array(
					'placeholder' => 'eg: 4px'
				)
			),
			'end_inline_elements_input_border_radius'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_input_border_width' => array(
				'title' => __( 'Border width', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'input_border_width_enable'                => array(
				'id'       => 'input_border_width_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'input_border_width'                       => array(
				'id'         => 'input_border_width',
				'type'       => 'text',
				'default'    => '1px',
				'attributes' => array(
					'placeholder' => 'eg: 1px'
				)
			),
			'end_inline_elements_input_border_width'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_input_color' => array(
				'title' => __( 'Text color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'input_color_enable'                => array(
				'id'       => 'input_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'input_color'                       => array(
				'id'       => 'input_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::inputText,
				'sanitize' => 'color'
			),
			'end_inline_elements_input_color'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_input_bg_color' => array(
				'title' => __( 'Background color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'input_bg_color_enable'                => array(
				'id'       => 'input_bg_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'input_bg_color'                       => array(
				'id'       => 'input_bg_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::inputBg,
				'sanitize' => 'color'
			),
			'end_inline_elements_input_bg_color'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_input_border_color' => array(
				'title' => __( 'Border color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'input_border_color_enable'                => array(
				'id'       => 'input_border_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'input_border_color'                       => array(
				'id'       => 'input_border_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::inputBorder,
				'sanitize' => 'color'
			),
			'end_inline_elements_input_border_color'   => array(
				'type' => 'endInlineElements',
			),

			'end_grid_input_styles' => array(
				'type' => 'endgrid',
			)
		] );

		$sections[ self::sectionID ] = array(
			'title'    => __( 'Styles', 'woo-assistant' ),
			'desc'     => __( 'Global Styles', 'woo-assistant' ),
			'settings' => $settings
		);

		return $sections;
	}
}