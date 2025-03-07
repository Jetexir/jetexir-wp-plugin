<?php

namespace WooAssistant\Admin;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Interfaces\AdminTabInterface;

class AdminTools implements AdminTabInterface {
	public const tab = 'tools';

	public function __construct() {
		add_filter( 'woo_assistant_menus', [ $this, 'addMenu' ] );
		add_filter( 'woo_assistant_tools_settings', [ $this, 'settings' ] );
		add_filter( 'woo_assistant_settings', [ $this, 'allSettings' ] );
		add_action( 'woo_assistant_admin_init', [ $this, 'notice' ] );
		add_filter( 'woo_assistant_tools_tab_display_notice', '__return_false' );
		add_filter( 'woo_assistant_tools_tab_content_display_notice', '__return_true' );
	}

	public function addMenu( $menus ) {
		$menus[ self::tab ] = __( 'Tools', 'woo-assistant' );

		return $menus;
	}

	public function allSettings( $settings ): array {
		$settings[ self::tab ] = $this->settings();

		return $settings;
	}

	public function settings(): array {
		$settings = array(
			'title'    => __( 'Tools', 'woo-assistant' ),
			'desc'     => __( 'WooCommerce Tools', 'woo-assistant' ),
			'sections' => array(
				'product'  => array(
					'title'    => __( 'Product', 'woo-assistant' ),
					'desc'     => __( 'Product Tools', 'woo-assistant' ),
					'settings' => array(
						'start_grid_quantity_input_style' => array(
							'id'    => 'grid_quantity_input_style',
							'title' => __( 'Quantity Plus/Minus button', 'woo-assistant' ),
							'cols'  => 2,
							'type'  => 'startgrid',
						),

						'post_type_page' => array(
							'id'                => 'post_type_page',
							'title'             => __( 'Select post type', 'woo-assistant' ),
							'type'              => 'postTypeSelect',
							'default'           => 'post',
							'option_none'       => '---',
							'option_none_value' => ''
						),

						'taxonomy_select_test' => array(
							'id'                => 'taxonomy_select_test',
							'title'             => __( 'Select taxonomy', 'woo-assistant' ),
							'type'              => 'taxonomySelect',
							'default'           => 'category',
							'option_none'       => '---',
							'option_none_value' => ''
						),

						'term_tax' => array(
							'id'                => 'term_tax',
							'title'             => __( 'Select term', 'woo-assistant' ),
							'type'              => 'termselect',
							'args'              => array(
								'taxonomy'   => 'category',
								'hide_empty' => false,
							),
							'multiple'          => true,
							'default'           => 0,
							'option_none'       => '---',
							'option_none_value' => '',
							'desc'              => __( 'Select category', 'woo-assistant' ),
							'sanitize'          => 'array',
							'sanitize_options'  => 'int',
							'attributes'        => array(
								'size' => 5,
							)
						),

						'end_grid_quantity_input_style' => array(
							'type' => 'endgrid',
						),
					)
				),
				'shipping' => array(
					'title'    => __( 'Shipping', 'woo-assistant' ),
					'desc'     => __( 'Shipping Tools', 'woo-assistant' ),
					'settings' => array(
						'start_grid_1' => array(
							'id'    => 'site_info',
							'title' => __( 'Site info', 'woo-assistant' ),
							'cols'  => 2,
							'type'  => 'startgrid',
						),
						'search'       => array(
							'id'          => 'search',
							'title'       => __( 'search', 'woo-assistant' ),
							'type'        => 'search',
							'placeholder' => __( 'search', 'woo-assistant' ),
						),
						'year_range'   => array(
							'id'            => 'year_range',
							'title'         => __( 'Year range', 'woo-assistant' ),
							'type'          => 'range',
							'default'       => 1,
							'display_value' => true,
							'attributes'    => array(
								'min' => 1,
								'max' => 10,
							),
							'desc'          => __( '1-10', 'woo-assistant' ),
						),
						'password'     => array(
							'id'          => 'password',
							'title'       => __( 'Password', 'woo-assistant' ),
							'type'        => 'password',
							'placeholder' => __( 'Password', 'woo-assistant' ),
							'save'        => false,
							'force_value' => ''
						),
						'year'         => array(
							'id'         => 'year',
							'title'      => __( 'Year', 'woo-assistant' ),
							'type'       => 'number',
							'default'    => date( 'Y' ),
							'sanitize'   => 'int',
							'attributes' => array(
								'min' => date( 'Y' ),
							)
						),
						'month'        => array(
							'id'         => 'month',
							'title'      => __( 'Month', 'woo-assistant' ),
							'type'       => 'number',
							'default'    => date( 'm' ),
							'class'      => 'wa-appearance-text-field',
							'attributes' => array(
								'min'  => 0,
								'step' => 0.1,
							)
						),
						'color1'       => array(
							'id'       => 'color1',
							'title'    => __( 'Color 1', 'woo-assistant' ),
							'type'     => 'color',
							'default'  => '#ff0000',
							'sanitize' => 'color'
						),
						'website_name' => array(
							'id'    => 'website_name',
							'title' => __( 'Website name', 'woo-assistant' ),
							'type'  => 'text',
							'desc'  => __( 'Styling quantity field', 'woo-assistant' )
						),
						'user_email'   => array(
							'id'    => 'user_email',
							'title' => __( 'Your email', 'woo-assistant' ),
							'type'  => 'email',
							'desc'  => __( 'Styling quantity field', 'woo-assistant' )
						),
						'user_tel'     => array(
							'id'          => 'user_tel',
							'title'       => __( 'Your phone', 'woo-assistant' ),
							'placeholder' => '09031859658',
							'type'        => 'tel',
							'attributes'  => array(
								'pattern' => '[0-9]{3}-[0-9]{3}-[0-9]{4}'
							)
						),
						'user_url'     => array(
							'id'    => 'user_url',
							'title' => __( 'Your website url', 'woo-assistant' ),
							'type'  => 'url',
							'desc'  => __( 'Styling quantity field', 'woo-assistant' )
						),
						/*'custom_btn'     => [
							'id'          => 'settings-reset',
							'title'       => __( 'Discard changes', 'woo-assistant' ),
							'type'        => 'button',
							'button_type' => 'reset',
							'class'       => 'wa-button-primary'
						],*/
						'end_grid_1'   => array(
							'type' => 'endgrid',
						),
					)
				),
			),
			'settings' => array(
				'start_grid_product'   => array(
					'id'    => 'product',
					'title' => __( 'Product', 'woo-assistant' ),
					'cols'  => 2,
					'type'  => 'startgrid',
				),
				'quantity_input_style' => array(
					'id'       => 'quantity_input_style',
					'title'    => __( 'Quantity Up/Down style', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => 0,
					'desc'     => __( 'Styling quantity field', 'woo-assistant' ),
					'sanitize' => 'int'
				),

				'post_type_page' => array(
					'id'                => 'post_type_page',
					'title'             => __( 'Select page', 'woo-assistant' ),
					'type'              => 'postType',
					'args'              => array(
						'post_type' => 'page'
					),
					'default'           => 0,
					'option_none'       => '---',
					'option_none_value' => '',
					'desc'              => __( 'Select page', 'woo-assistant' )
				),

				'select_product' => array(
					'id'                => 'select_product',
					'title'             => __( 'Select product', 'woo-assistant' ),
					'type'              => 'select',
					'options'           => array(
						'Test',
						'Goal'
					),
					'default'           => 0,
					'option_none'       => '---',
					'option_none_value' => '',
					'desc'              => __( 'Select page', 'woo-assistant' )
				),

				'end_grid_product' => array(
					'type' => 'endgrid',
				),
				'sep_product'      => array(
					'type' => 'hr',
				),
				'hidden_test'      => array(
					'id'       => 'hidden_test',
					'type'     => 'hidden',
					'default'  => 'test',
					'sanitize' => 'text'
				),
				'start_grid_1'     => array(
					'id'    => 'site_info',
					'title' => __( 'Site info', 'woo-assistant' ),
					'cols'  => 2,
					'type'  => 'startgrid',
				),
				'search'           => array(
					'id'          => 'search',
					'title'       => __( 'search', 'woo-assistant' ),
					'type'        => 'search',
					'placeholder' => __( 'search', 'woo-assistant' ),
				),
				'year_range'       => array(
					'id'            => 'year_range',
					'title'         => __( 'Year range', 'woo-assistant' ),
					'type'          => 'range',
					'default'       => 1,
					'display_value' => true,
					'attributes'    => array(
						'min' => 1,
						'max' => 10,
					),
					'desc'          => __( '1-10', 'woo-assistant' ),
				),
				'password'         => array(
					'id'          => 'password',
					'title'       => __( 'Password', 'woo-assistant' ),
					'type'        => 'password',
					'placeholder' => __( 'Password', 'woo-assistant' ),
					'save'        => false,
					'force_value' => ''
				),
				'year'             => array(
					'id'         => 'year',
					'title'      => __( 'Year', 'woo-assistant' ),
					'type'       => 'number',
					'default'    => date( 'Y' ),
					'sanitize'   => 'int',
					'attributes' => array(
						'min' => date( 'Y' ),
					)
				),
				'month'            => array(
					'id'         => 'month',
					'title'      => __( 'Month', 'woo-assistant' ),
					'type'       => 'number',
					'default'    => date( 'm' ),
					'class'      => 'wa-appearance-text-field',
					'attributes' => array(
						'min'  => 0,
						'step' => 0.1,
					)
				),
				'color1'           => array(
					'id'       => 'color1',
					'title'    => __( 'Color 1', 'woo-assistant' ),
					'type'     => 'color',
					'default'  => '#ff0000',
					'sanitize' => 'color'
				),
				'website_name'     => array(
					'id'    => 'website_name',
					'title' => __( 'Website name', 'woo-assistant' ),
					'type'  => 'text',
					'desc'  => __( 'Styling quantity field', 'woo-assistant' )
				),
				'user_email'       => array(
					'id'    => 'user_email',
					'title' => __( 'Your email', 'woo-assistant' ),
					'type'  => 'email',
					'desc'  => __( 'Styling quantity field', 'woo-assistant' )
				),
				'user_tel'         => array(
					'id'          => 'user_tel',
					'title'       => __( 'Your phone', 'woo-assistant' ),
					'placeholder' => '09031859658',
					'type'        => 'tel',
					'attributes'  => array(
						'pattern' => '[0-9]{3}-[0-9]{3}-[0-9]{4}'
					)
				),
				'user_url'         => array(
					'id'    => 'user_url',
					'title' => __( 'Your website url', 'woo-assistant' ),
					'type'  => 'url',
					'desc'  => __( 'Styling quantity field', 'woo-assistant' )
				),
				/*'custom_btn'     => [
					'id'          => 'settings-reset',
					'title'       => __( 'Discard changes', 'woo-assistant' ),
					'type'        => 'button',
					'button_type' => 'reset',
					'class'       => 'wa-button-primary'
				],*/
				'end_grid_1'       => array(
					'type' => 'endgrid',
				),
				'sep_0'            => array(
					'type' => 'hr',
				),
				'start_grid_2'     => array(
					'id'    => 'style',
					'title' => __( 'Style', 'woo-assistant' ),
					'cols'  => 2,
					'type'  => 'startgrid',
				),
				'wc_style'         => array(
					'id'       => 'wc_style',
					'title'    => __( 'WC style', 'woo-assistant' ),
					'type'     => 'checkbox',
					'disabled' => false,
					'value'    => 1,
					'default'  => 0,
					'desc'     => __( 'Styling quantity field', 'woo-assistant' ),
					'sanitize' => 'int'
				),
				'end_grid_2'       => array(
					'type' => 'endgrid',
				),
				'sep_1'            => array(
					'type' => 'hr',
				),
				'basket'           => array(
					'id'       => 'basket_style',
					'title'    => __( 'Basket style', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => 0,
					'desc'     => __( 'Basket style', 'woo-assistant' ),
					'sanitize' => 'int'
				),
				'sep_2'            => array(
					'type' => 'hr',
				),
				'start_grid_3'     => array(
					'id'    => 'style',
					'title' => __( 'Style', 'woo-assistant' ),
					'cols'  => 2,
					'type'  => 'startgrid',
				),

				'title_separator_radioinline' => array(
					'id'       => 'title_separator',
					'title'    => __( 'Title separator', 'woo-assistant' ),
					'type'     => 'radioInline',
					'default'  => '|',
					'options'  => array(
						'|',
						'-',
						':',
						'>',
						'<',
						'~',
						'*',
						'+',
						'_',
						'.',
						'=',
						'&',
						'^',
						'%',
						'$',
						'#'
					),
					'sanitize' => 'text'
				),
				/*'title_separator_dash' => array(
					'id'       => 'title_separator',
					'title'    => __( '-', 'woo-assistant' ),
					'type'     => 'radioinline',
					'default'  => '|',
					'value'    => '-',
					'sanitize' => 'text'
				),
				*/
				'rg_1'                        => array(
					'id'    => 'radios',
					'title' => 'Title separator',
					'type'  => 'startradiogroup',
				),
				'radio_4'                     => array(
					'id'       => 'radio_5',
					'title'    => __( 'Radio 4', 'woo-assistant' ),
					'type'     => 'radio',
					'default'  => 1,
					'value'    => 1,
					'sanitize' => 'int'
				),
				'radio_5'                     => array(
					'id'       => 'radio_5',
					'title'    => __( 'Radio 5', 'woo-assistant' ),
					'type'     => 'radio',
					'default'  => 1,
					'value'    => 2,
					'sanitize' => 'int'
				),
				'end_rg_1'                    => array(
					'type' => 'endradiogroup',
				),
				'end_grid_3'                  => array(
					'type' => 'endgrid',
				),
			)
		);

		return $settings;
	}

	public function notice(): void {

	}

	public function content(): void {
		// TODO: Implement content() method.
	}
}