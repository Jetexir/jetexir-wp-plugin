<?php

namespace WooAssistant\App\GlobalSettings;

use WooAssistant\Enums\Colors;
use WooAssistant\Helper\Assets;
use WooAssistant\Helper\DebugTrait;
use WooAssistant\Settings\Settings;

class Styles {
	private const sectionID = 'styles';

	public function __construct() {
		add_filter( 'woo_assistant_global_settings_sections', [ $this, 'addSectionSettings' ] );
		add_action( 'wp_head', [ $this, 'printStyles' ], 0 );
	}

	public function printStyles(): void {
		if ( ! Settings::get( 'enable_styles', false ) ) {
			return;
		}
		$settings   = $this->addSectionSettings( [] );
		$settings   = $settings[ self::sectionID ]['settings'] ?? [];
		$variables  = [];
		$properties = [];

		if ( ! empty( $settings ) && is_array( $settings ) ) {
			foreach ( $settings as $setting ) {
				if ( isset( $setting['meta']['css_variable'] ) ) {
					// DebugTrait::dd($setting['id'] . '_enable');
					$add = ! isset( $settings[ $setting['id'] . '_enable' ] ) || Settings::get( $setting['id'] . '_enable', true );

					if ( $add && $value = Settings::get( $setting['id'], $setting['default'] ?? false ) ) {
						$name         = WOOASSISTANT_INPUT_CLASS_PREFIX . str_replace( '_', '-', $setting['meta']['css_variable'] );
						$syntax       = $setting['meta']['css_syntax'] ?? '*';
						$inherits     = $setting['meta']['css_inherits'] ?? true;
						$initialValue = $setting['meta']['css_initial_value'] ?? '';

						$properties[] = Assets::generateCssProperty( $name, $syntax, $inherits, $initialValue );
						$variables[]  = '--' . $name . ': ' . $value . ';';
					}
				}
			}
		}

		if ( empty( $variables ) ) {
			return;
		}

		$sep = "\n\t\t\t";
		?>
        <style>
            <?php
			echo implode($sep,$properties) .$sep;
			echo ":root{".$sep."\t".implode($sep."\t",$variables) ."$sep}\n";
			?>
        </style>
		<?php
	}

	public function addSectionSettings( $sections ) {
		$settings = [
			'start_grid_enable_styles' => array(
				'title' => __( 'Styles', 'woo-assistant' ),
				'type'  => 'startgrid',
			),
			'enable_styles'            => array(
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

		$settings = array_merge( $settings, [
			'start_grid_global_styles' => array(
				'title' => __( 'Global', 'woo-assistant' ),
				'type'  => 'startgrid',
			),

			'start_inline_elements_text_color' => array(
				'title' => __( 'Text color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'text_color_enable'                => array(
				'id'       => 'text_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'text_color'                       => array(
				'id'       => 'text_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::text,
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'text-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_text_color'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_bg_color' => array(
				'title' => __( 'Background color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'bg_color_enable'                => array(
				'id'       => 'bg_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'bg_color'                       => array(
				'id'       => 'bg_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::bg,
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'bg-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_bg_color'   => array(
				'type' => 'endInlineElements',
			),

			'end_grid_global_styles' => array(
				'type' => 'endgrid',
			),
		] );

		$settings = array_merge( $settings, [
			'start_grid_elements_styles' => array(
				'title' => __( 'Elements', 'woo-assistant' ),
				'type'  => 'startgrid',
			),

			'start_inline_elements_element_text_color' => array(
				'title' => __( 'Text color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'element_text_color_enable'                => array(
				'id'       => 'element_text_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'element_text_color'                       => array(
				'id'       => 'element_text_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::text,
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'element-text-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_element_text_color'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_element_bg_color' => array(
				'title' => __( 'Background color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'element_bg_color_enable'                => array(
				'id'       => 'bg_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'element_bg_color'                       => array(
				'id'       => 'element_bg_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::primaryLight,
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'element-bg-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_element_bg_color'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_element_border_color' => array(
				'title' => __( 'Border color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'element_border_color_enable'                => array(
				'id'       => 'element_border_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'element_border_color'                       => array(
				'id'       => 'element_border_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::border,
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'element-border-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_element_border_color'   => array(
				'type' => 'endInlineElements',
			),

			'end_grid_elements_styles' => array(
				'type' => 'endgrid',
			),
		] );

		// Input styles
		$settings = array_merge( $settings, [
			'start_grid_input_styles' => array(
				'title' => __( 'Input box', 'woo-assistant' ),
				'type'  => 'startgrid',
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
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'input-color',
					'css_syntax'   => 'color',
				]
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
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'input-bg-color',
					'css_syntax'   => 'color',
				]
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
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'input-border-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_input_border_color'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_input_border_radius' => array(
				'title' => __( 'Border radius', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'input_border_radius_enable'                => array(
				'id'       => 'input_border_radius_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'input_border_radius'                       => array(
				'id'         => 'input_border_radius',
				'type'       => 'text',
				'default'    => '5px',
				'attributes' => array(
					'placeholder' => 'eg: 4px'
				),
				'meta'       => [
					'css_variable' => 'input-border-radius',
					'css_syntax'   => [ 'length', 'percentage' ],
				]
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
				),
				'meta'       => [
					'css_variable' => 'input-border-width',
					'css_syntax'   => [ 'length', 'percentage' ],
				]
			),
			'end_inline_elements_input_border_width'   => array(
				'type' => 'endInlineElements',
			),

			'end_grid_input_styles' => array(
				'type' => 'endgrid',
			)
		] );


		// Primary Button styles
		$settings = array_merge( $settings, [
			'start_grid_button_styles' => array(
				'title' => __( 'Primary Button', 'woo-assistant' ),
				'type'  => 'startgrid',
			),

			'start_inline_elements_button_color' => array(
				'title' => __( 'Text color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'button_color_enable'                => array(
				'id'       => 'button_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'button_color'                       => array(
				'id'       => 'button_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::buttonText,
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'button-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_button_color'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_button_hover_color' => array(
				'title' => __( 'Hover text color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'button_hover_color_enable'                => array(
				'id'       => 'button_hover_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'button_hover_color'                       => array(
				'id'       => 'button_hover_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::buttonHoverText,
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'button-hover-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_button_hover_color'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_button_bg_color' => array(
				'title' => __( 'Background color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'button_bg_color_enable'                => array(
				'id'       => 'button_bg_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'button_bg_color'                       => array(
				'id'       => 'button_bg_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::buttonBg,
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'button-bg-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_button_bg_color'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_button_hover_bg_color' => array(
				'title' => __( 'Hover background color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'button_hover_bg_color_enable'                => array(
				'id'       => 'button_hover_bg_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'button_hover_bg_color'                       => array(
				'id'       => 'button_hover_bg_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::buttonHoverBg,
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'button-hover-bg-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_button_hover_bg_color'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_button_border_color' => array(
				'title' => __( 'Border color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'button_border_color_enable'                => array(
				'id'       => 'button_border_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'button_border_color'                       => array(
				'id'       => 'button_border_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::buttonBorder,
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'button-border-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_button_border_color'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_button_hover_border_color' => array(
				'title' => __( 'Hover border color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'button_hover_border_color_enable'                => array(
				'id'       => 'button_hover_border_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'button_hover_border_color'                       => array(
				'id'       => 'button_hover_border_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::buttonHoverBorder,
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'button-hover-border-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_button_hover_border_color'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_button_border_radius' => array(
				'title' => __( 'Border radius', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'button_border_radius_enable'                => array(
				'id'       => 'button_border_radius_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'button_border_radius'                       => array(
				'id'         => 'button_border_radius',
				'type'       => 'text',
				'default'    => '5px',
				'attributes' => array(
					'placeholder' => 'eg: 4px'
				),
				'meta'       => [
					'css_variable' => 'button-border-radius',
					'css_syntax'   => [ 'length', 'percentage' ],
				]
			),
			'end_inline_elements_button_border_radius'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_button_border_width' => array(
				'title' => __( 'Border width', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'button_border_width_enable'                => array(
				'id'       => 'button_border_width_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'button_border_width'                       => array(
				'id'         => 'button_border_width',
				'type'       => 'text',
				'default'    => '1px',
				'attributes' => array(
					'placeholder' => 'eg: 1px'
				),
				'meta'       => [
					'css_variable' => 'button-border-width',
					'css_syntax'   => [ 'length', 'percentage' ],
				]
			),
			'end_inline_elements_button_border_width'   => array(
				'type' => 'endInlineElements',
			),

			'end_grid_button_styles' => array(
				'type' => 'endgrid',
			)
		] );

		// Secondary button styles
		$settings = array_merge( $settings, [
			'start_grid_secondary_button_styles' => array(
				'title' => __( 'Secondary Button', 'woo-assistant' ),
				'type'  => 'startGrid',
			),

			'start_inline_elements_secondary_button_color' => array(
				'title' => __( 'Text color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'secondary_button_color_enable'                => array(
				'id'       => 'secondary_button_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'secondary_button_color'                       => array(
				'id'       => 'secondary_button_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::secondaryButtonText,
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'secondary-button-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_secondary_button_color'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_secondary_button_hover_color' => array(
				'title' => __( 'Hover text color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'secondary_button_hover_color_enable'                => array(
				'id'       => 'secondary_button_hover_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'secondary_button_hover_color'                       => array(
				'id'       => 'secondary_button_hover_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::secondaryButtonHoverText,
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'secondary-button-hover-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_secondary_button_hover_color'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_secondary_button_bg_color' => array(
				'title' => __( 'Background color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'secondary_button_bg_color_enable'                => array(
				'id'       => 'secondary_button_bg_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'secondary_button_bg_color'                       => array(
				'id'       => 'secondary_button_bg_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::secondaryButtonBg,
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'secondary-button-bg-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_secondary_button_bg_color'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_secondary_button_hover_bg_color' => array(
				'title' => __( 'Hover background color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'secondary_button_hover_bg_color_enable'                => array(
				'id'       => 'secondary_button_hover_bg_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'secondary_button_hover_bg_color'                       => array(
				'id'       => 'secondary_button_hover_bg_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::secondaryButtonHoverBg,
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'secondary-button-hover-bg-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_secondary_button_hover_bg_color'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_secondary_button_border_color' => array(
				'title' => __( 'Border color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'secondary_button_border_color_enable'                => array(
				'id'       => 'secondary_button_border_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'secondary_button_border_color'                       => array(
				'id'       => 'secondary_button_border_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::secondaryButtonBorder,
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'secondary-button-border-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_secondary_button_border_color'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_secondary_button_hover_border_color' => array(
				'title' => __( 'Hover border color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'secondary_button_hover_border_color_enable'                => array(
				'id'       => 'secondary_button_hover_border_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'secondary_button_hover_border_color'                       => array(
				'id'       => 'secondary_button_hover_border_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::secondaryButtonHoverBorder,
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'secondary-button-hover-border-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_secondary_button_hover_border_color'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_secondary_button_border_radius' => array(
				'title' => __( 'Border radius', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'secondary_button_border_radius_enable'                => array(
				'id'       => 'secondary_button_border_radius_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'secondary_button_border_radius'                       => array(
				'id'         => 'secondary_button_border_radius',
				'type'       => 'text',
				'default'    => '5px',
				'attributes' => array(
					'placeholder' => 'eg: 4px'
				),
				'meta'       => [
					'css_variable' => 'secondary-button-border-radius',
					'css_syntax'   => [ 'length', 'percentage' ],
				]
			),
			'end_inline_elements_secondary_button_border_radius'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_secondary_button_border_width' => array(
				'title' => __( 'Border width', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'secondary_button_border_width_enable'                => array(
				'id'       => 'secondary_button_border_width_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'secondary_button_border_width'                       => array(
				'id'         => 'secondary_button_border_width',
				'type'       => 'text',
				'default'    => '1px',
				'attributes' => array(
					'placeholder' => 'eg: 1px'
				),
				'meta'       => [
					'css_variable' => 'secondary-button-border-width',
					'css_syntax'   => [ 'length', 'percentage' ],
				]
			),
			'end_inline_elements_secondary_button_border_width'   => array(
				'type' => 'endInlineElements',
			),

			'end_grid_secondary_button_styles' => array(
				'type' => 'endGrid',
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