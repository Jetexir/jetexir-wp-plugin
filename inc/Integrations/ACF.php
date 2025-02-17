<?php

namespace WooAssistant\Integrations;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Admin\AdminAssets;
use WooAssistant\Interfaces\PluginInterface;
use WooAssistant\Plugins\Plugin;

class ACF extends Plugin implements PluginInterface {
	public function __construct() {
		$this->pluginID = 'acf';
		parent::__construct();
	}

	public function initAction(): void {
		if ( $this->getSettings( 'add_extra_html', 0 ) ) {
			add_action( 'acf/render_field', [ $this, 'my_acf_render_field' ] );
		}
	}

	public function my_acf_render_field( $field ): void {
		echo '<p>Some extra HTML.</p>';
	}

	public function settings(): array {
		return array(
			'options_id' => $this->pluginID,
			'title'      => __( 'ACF', 'woo-assistant' ),
			'desc'       => __( 'ACF Integration', 'woo-assistant' ),
			'sections'   => array(
				'post_edit' => array(
					'title'    => __( 'Post edit', 'woo-assistant' ),
					'settings' => array(
						'start_grid_product' => array(
							'id'    => 'product',
							'title' => __( 'Product', 'woo-assistant' ),
							'cols'  => 2,
							'type'  => 'startgrid',
						),
						'extra_html'         => array(
							'id'       => 'add_extra_html',
							'title'    => __( 'Add extra html', 'woo-assistant' ),
							'type'     => 'toggle',
							'value'    => 1,
							'default'  => 1,
							'sanitize' => 'bool'
						),

						'extra_html2' => array(
							'id'       => 'extra_html2',
							'title'    => __( 'Add extra html', 'woo-assistant' ),
							'type'     => 'checkbox',
							'value'    => 1,
							'default'  => 1,
							'sanitize' => 'bool'
						),

						'end_grid_1' => array(
							'type' => 'endgrid',
						),
					)
				),
				'shipping'  => array(
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
		);
	}

	public function content(): void {
		//	echo $this->pluginID;
	}

	public function info(): array {
		$svg = '<svg width="128" height="128" viewBox="0 0 128 128" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M28.48 3.52055L3.52 28.4845C1.26 30.7448 0 33.7853 0 36.9658V121.999C0 125.32 2.68 128 6 128H122C125.32 128 128 125.32 128 121.999V6.00094C128 2.68042 125.32 0 122 0H36.98C33.8 0 30.74 1.2602 28.5 3.52055H28.48Z" fill="url(#paint0_radial_145_5142)"/>
<path d="M90.5646 84.3314H81.2979V47.0397H106V55.7236H90.5646V62.3781H105.112V70.846H90.5646V84.3329V84.3314Z" fill="white"/>
<path opacity="0.05" d="M78.8536 68.6808H88.0385C86.6796 78.1726 78.7004 84.4498 69.0333 84.4498C58.447 84.4498 49.8279 76.5143 49.8279 65.8283C49.8058 63.3432 50.2875 60.8795 51.2438 58.5869C52.2001 56.2944 53.6111 54.2207 55.391 52.492C59.0442 48.9431 63.9481 46.987 69.0333 47.0501C78.611 47.0501 86.813 53.3759 87.9793 62.7156H78.8036C76.0448 52.136 58.8535 53.1705 58.8535 65.8283C58.8535 78.4876 76.2602 79.4201 78.8536 68.6839V68.6808Z" fill="#002447"/>
<path d="M76.9739 68.6809C75.3981 74.0209 69.7895 77.196 64.2689 75.8663C58.7452 74.5321 55.2842 69.1769 56.4308 63.7334C57.5759 58.2885 62.9176 54.7011 68.5292 55.6094C70.8281 55.9217 72.9479 57.0239 74.5275 58.7281C75.6193 59.8555 76.4386 61.2192 76.9223 62.7141H85.8297C84.6634 53.3532 76.4385 47.0501 66.8836 47.0501C61.7978 46.9853 56.8927 48.9404 53.2382 52.489C51.4571 54.2182 50.0453 56.2927 49.0887 58.5864C48.1321 60.8802 47.6506 63.3451 47.6736 65.8314C47.6736 76.5175 56.2427 84.4499 66.8866 84.4499C76.5462 84.4499 84.5026 78.1727 85.8858 68.6809H76.9724H76.9739Z" fill="white"/>
<path opacity="0.05" d="M49.2941 78.9061H36.3979L34.2898 84.3252H24.4209L39.5238 47H46.106L61.8095 84.3373H51.3901L49.291 78.9061H49.2941ZM40.0637 69.6668L39.7406 70.5097H46.0014L45.7845 69.8768L42.8695 61.8729L40.0637 69.6668Z" fill="#002447"/>
<path d="M46.8519 78.9061H33.977L31.8704 84.3252H22L37.1044 47H43.6867L59.3901 84.3373H48.9752L46.8519 78.9061ZM37.6428 69.6668L37.3213 70.5097H43.582L43.3636 69.8768L40.4501 61.8729L37.6443 69.6668H37.6428Z" fill="white"/>
<defs>
<radialGradient id="paint0_radial_145_5142" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="rotate(45) scale(181.019)">
<stop stop-color="#0ECAD4"/>
<stop offset="1" stop-color="#006BD6"/>
</radialGradient>
</defs>
</svg>';

		return array(
			'id'               => $this->pluginID,
			'name'             => __( 'ACF', 'woo-assistant' ),
			'title'            => __( 'ACF', 'woo-assistant' ),
			'menu_title'       => __( 'ACF', 'woo-assistant' ),
			'has_page'         => true,
			'force_enable'     => false,
			'desc'             => __( 'Use ACF fields to power your meta tags and templates, and analyze all of your content.', 'woo-assistant' ),
			'image'            => AdminAssets::imageUrl( 'acf.jpg' ),
			'icon'             => $svg,
			'image_link'       => 'https://www.advancedcustomfields.com/',
			'tags'             => [ __( 'New', 'woo-assistant' ) ],
			'cat'              => 'customizations',
			'more_info_link'   => 'https://parsa.ws',
			'requires_plugins' => [
				'advanced-custom-fields/acf.php' => array(
					'is_wp_plugin'   => true,
					'is_free'        => true,
					'plugin_link'    => 'https://wordpress.org/plugins/advanced-custom-fields/',
					'function_check' => '',
					'class_check'    => 'ACF',
				)
			]
		);
	}
}