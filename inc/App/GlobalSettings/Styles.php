<?php

namespace WooAssistant\App\GlobalSettings;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Admin\AdminPages;
use WooAssistant\Enums\Colors;
use WooAssistant\Helper\Assets;
use WooAssistant\Settings\Settings;

class Styles {
	private const sectionID = 'styles';

	public function __construct() {
		add_filter( 'woo_assistant_global_settings_sections', [ $this, 'addSectionSettings' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'addInlineStyles' ], 0 );
		add_filter( 'woo_assistant_dashboard_custom_links', [ $this, 'addDashboardLink' ] );
	}

	public function addDashboardLink( $links ) {
		$links[] = [
			'title' => __( 'Plugin Styles', 'woo-assistant' ),
			'desc'  => __( 'Global plugin styles', 'woo-assistant' ),
			'link'  => AdminPages::link( [
				'tab'     => 'global',
				'section' => self::sectionID,
			] ),
			'icon'  => '<svg viewBox="-2.4 -2.4 28.80 28.80" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"><path transform="translate(-2.4, -2.4), scale(0.8999999999999999)" d="M16,30.74576831702143C18.93549041833936,30.349797180913146,20.478469839391327,27.201734053701948,22.91971549604768,25.524171303564245C25.461527300644363,23.777502020354156,29.509072968614763,23.629973818345828,30.569555366992,20.73393550409155C31.627528903318076,17.844748562982602,28.777597481269947,15.083410617916059,27.81280448608413,12.161787154722832C26.85463279381834,9.260214577123296,27.209663389895645,5.740540714765194,24.942008668532715,3.692380935935061C22.606918203328586,1.583312775908333,19.128963884560267,1.1601225840786962,16.000000000000004,1.492389033548534C13.069216887820268,1.8036105544025451,10.689956734760795,3.6780791125865058,8.338949908316351,5.455469161982597C6.03548576855882,7.1969163560503295,3.231574789681348,8.826258647430745,2.5786088474490594,11.63912566369013C1.9376754143331079,14.400158662541992,4.2192094823637065,16.847662074894327,5.0665304468427586,19.552499605983577C5.926999053491774,22.29930734373296,5.565276455332466,25.534705967670547,7.576597185339855,27.593819342944215C9.723972419013958,29.79222012478945,12.95444341584934,31.156586384480974,16,30.74576831702143" fill="#ffffff" strokewidth="0"></path></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M10.97 2H8.97C3.97 2 1.97 4 1.97 9V15C1.97 20 3.97 22 8.97 22H14.97C19.97 22 21.97 20 21.97 15V13" stroke="#873eff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M21.88 3.55998C20.65 6.62998 17.56 10.81 14.98 12.88L13.4 14.14C13.2 14.29 13 14.41 12.77 14.5C12.77 14.35 12.76 14.2 12.74 14.04C12.65 13.37 12.35 12.74 11.81 12.21C11.26 11.66 10.6 11.35 9.92 11.26C9.76 11.25 9.6 11.24 9.44 11.25C9.53 11 9.66 10.77 9.83 10.58L11.09 8.99998C13.16 6.41998 17.35 3.30998 20.41 2.07998C20.88 1.89998 21.34 2.03998 21.63 2.32998C21.93 2.62998 22.07 3.08998 21.88 3.55998Z" stroke="#873eff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M12.78 14.49C12.78 15.37 12.44 16.21 11.81 16.85C11.32 17.34 10.66 17.68 9.87 17.78L7.9 17.99C6.83 18.11 5.91 17.2 6.03 16.11L6.24 14.14C6.43 12.39 7.89 11.27 9.45 11.24C9.61 11.23 9.77 11.24 9.93 11.25C10.61 11.34 11.27 11.65 11.82 12.2C12.36 12.74 12.66 13.36 12.75 14.03C12.77 14.19 12.78 14.35 12.78 14.49Z" stroke="#873eff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M15.82 11.9799C15.82 9.88994 14.13 8.18994 12.03 8.18994" stroke="#873eff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>',
			'type'  => 'style'
		];

		return $links;
	}

	public function addInlineStyles(): void {
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

		$sep    = WOOASSISTANT_DEBUG_MODE ? "\n\t\t\t" : '';
		$styles = implode( $sep, $properties ) . $sep . ":root{" . $sep . "\t" . implode( $sep . "\t", $variables ) . "$sep}\n";

		wp_register_style( WOOASSISTANT_PLUGIN_SLUG . '-global-inline-style', false );
		wp_enqueue_style( WOOASSISTANT_PLUGIN_SLUG . '-global-inline-style' );
		wp_add_inline_style( WOOASSISTANT_PLUGIN_SLUG . '-global-inline-style', $styles );
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

			'start_inline_elements_primary_color' => array(
				'title' => __( 'Primary color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'primary_color_enable'                => array(
				'id'       => 'primary_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'primary_color'                       => array(
				'id'       => 'primary_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::primary,
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'primary-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_primary_color'   => array(
				'type' => 'endInlineElements',
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

			'start_inline_elements_element_color' => array(
				'title' => __( 'Text color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'element_color_enable'                => array(
				'id'       => 'element_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'element_color'                       => array(
				'id'       => 'element_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::text,
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'element-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_element_color'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_element_hover_color' => array(
				'title' => __( 'Hover text color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'element_hover_color_enable'                => array(
				'id'       => 'element_hover_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'element_hover_color'                       => array(
				'id'       => 'element_hover_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::primary,
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'element-hover-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_element_hover_color'   => array(
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

			'start_inline_elements_element_hover_bg_color' => array(
				'title' => __( 'Hover background color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'element_hover_bg_color_enable'                => array(
				'id'       => 'bg_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'element_hover_bg_color'                       => array(
				'id'       => 'element_hover_bg_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::primaryLight2,
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'element-hover-bg-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_element_hover_bg_color'   => array(
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

			'start_inline_elements_element_hover_border_color' => array(
				'title' => __( 'Hover border color', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'element_hover_border_color_enable'                => array(
				'id'       => 'element_hover_border_color_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'element_hover_border_color'                       => array(
				'id'       => 'element_hover_border_color',
				'type'     => 'wpColorPicker',
				'default'  => Colors::primary,
				'sanitize' => 'color',
				'meta'     => [
					'css_variable' => 'element-hover-border-color',
					'css_syntax'   => 'color',
				]
			),
			'end_inline_elements_element_hover_border_color'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_element_border_radius' => array(
				'title' => __( 'Border radius', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'element_border_radius_enable'                => array(
				'id'       => 'element_border_radius_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'element_border_radius'                       => array(
				'id'         => 'element_border_radius',
				'type'       => 'text',
				'default'    => '5px',
				'attributes' => array(
					'placeholder' => 'eg: 4px'
				),
				'meta'       => [
					'css_variable' => 'element-border-radius',
					'css_syntax'   => [ 'length', 'percentage' ],
				]
			),
			'end_inline_elements_element_border_radius'   => array(
				'type' => 'endInlineElements',
			),

			'start_inline_elements_element_border_width' => array(
				'title' => __( 'Border width', 'woo-assistant' ),
				'type'  => 'startInlineElements',
			),
			'element_border_width_enable'                => array(
				'id'       => 'element_border_width_enable',
				'type'     => 'checkbox',
				'value'    => 1,
				'default'  => true,
				'sanitize' => 'bool'
			),
			'element_border_width'                       => array(
				'id'         => 'element_border_width',
				'type'       => 'text',
				'default'    => '1px',
				'attributes' => array(
					'placeholder' => 'eg: 1px'
				),
				'meta'       => [
					'css_variable' => 'element-border-width',
					'css_syntax'   => [ 'length', 'percentage' ],
				]
			),
			'end_inline_elements_element_border_width'   => array(
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