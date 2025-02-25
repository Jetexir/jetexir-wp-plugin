<?php

namespace WooAssistant\App\Product;

class ProductSocialShare {
	private const sectionID = 'social-share';

	public function __construct() {
		add_filter( 'woo_assistant_product_settings_sections', [ $this, 'addSectionSettings' ] );
	}

	public function addSectionSettings( $sections ) {
		$sections[ self::sectionID ] = array(
			'title'    => __( 'Social Share', 'woo-assistant' ),
			'desc'     => __( 'Product Social Share', 'woo-assistant' ),
			'settings' => array(
				'product_social_share_start_grid_1'    => array(
					'id'    => 'product_social_share_start_grid_1',
					'title' => __( 'Product Social Share', 'woo-assistant' ),
					'type'  => 'startgrid',
				),
				'product_social_share_enable'          => array(
					'id'       => 'product_social_share_enable',
					'title'    => __( 'Enable social share feature', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => false,
					'sanitize' => 'bool'
				),
				'product_social_share_position'        => array(
					'id'                => 'product_social_share_position',
					'title'             => __( 'Position', 'woo-assistant' ),
					'type'              => 'select',
					'options'           => array(
						'after_categories'    => __( 'After product categories', 'woo-assistant' ),
						'after_product_title' => __( 'After product title', 'woo-assistant' ),
						'after_product_price' => __( 'After product price', 'woo-assistant' ),
					),
					'option_none'       => '---',
					'option_none_value' => '',
					'default'           => 'after_categories',
					'sanitize'          => 'text',
					'desc'              => sprintf( __( 'You can display social share with %s shortcode.', 'woo-assistant' ), '<code>[wa_product_share]</code>' )
				),
				'product_social_share_link_type_start' => array(
					'id'    => 'radios',
					'title' => 'Link type',
					'type'  => 'startradiogroup',
				),
				'radio_4'                              => array(
					'id'       => 'product_social_share_link_type',
					'title'    => __( 'Long link', 'woo-assistant' ),
					'type'     => 'radio',
					'default'  => 'long',
					'value'    => 'long',
					'sanitize' => 'text'
				),
				'radio_5'                              => array(
					'id'       => 'product_social_share_link_type',
					'title'    => __( 'Short link', 'woo-assistant' ),
					'type'     => 'radio',
					'default'  => 'long',
					'value'    => 'short',
					'sanitize' => 'text'
				),
				'product_social_share_link_type_end'   => array(
					'type' => 'endradiogroup',
				),
				'product_social_share_end_grid_1'      => array(
					'type' => 'endgrid',
				),
				'product_social_share_start_grid_2'    => array(
					'id'    => 'product_social_share_start_grid_2',
					'title' => __( 'Social networks', 'woo-assistant' ),
					'type'  => 'startgrid',
				),
				'product_social_share_networks'        => array(
					'id'               => 'product_social_share_networks',
					'title'            => __( 'Select Social Networks', 'woo-assistant' ),
					'type'             => 'checkboxInline',
					'default'          => [ 'x' ],
					'options'          => array(
						'x'         => '<i class="wa-icon-x-twitter"></i> ' . __( 'Twitter', 'woo-assistant' ),
						'facebook'  => '<i class="wa-icon-facebook"></i> ' . __( 'Facebook', 'woo-assistant' ),
						'linkedin'  => '<i class="wa-icon-linkedin"></i> ' . __( 'Linkedin', 'woo-assistant' ),
						'telegram'  => '<i class="wa-icon-telegram"></i> ' . __( 'Telegram', 'woo-assistant' ),
						'whatsapp'  => '<i class="wa-icon-whatsapp"></i> ' . __( 'WhatsApp', 'woo-assistant' ),
						'pinterest' => '<i class="wa-icon-pinterest"></i> ' . __( 'Pinterest', 'woo-assistant' ),
						'tumblr'    => '<i class="wa-icon-tumblr"></i> ' . __( 'Tumblr', 'woo-assistant' ),
						'vk'        => '<i class="wa-icon-vk"></i> ' . __( 'Vk', 'woo-assistant' ),
						'viber'     => '<i class="wa-icon-viber"></i> ' . __( 'Viber', 'woo-assistant' ),
						'reddit'    => '<i class="wa-icon-reddit"></i> ' . __( 'Reddit', 'woo-assistant' ),
						'xing'      => '<i class="wa-icon-xing"></i> ' . __( 'Xing', 'woo-assistant' ),
						'weibo'     => '<i class="wa-icon-weibo"></i> ' . __( 'Weibo', 'woo-assistant' ),
						'mastodon'  => '<i class="wa-icon-mastodon"></i> ' . __( 'Mastodon', 'woo-assistant' ),
						'bluesky'   => '<i class="wa-icon-bluesky"></i> ' . __( 'Bluesky', 'woo-assistant' ),
						'pocket'    => '<i class="wa-icon-pocket"></i> ' . __( 'Pocket', 'woo-assistant' ),
						'evernote'  => '<i class="wa-icon-evernote"></i> ' . __( 'Evernote', 'woo-assistant' ),
						'email'     => '<i class="wa-icon-email"></i> ' . __( 'Email', 'woo-assistant' ),
					),
					'not_equal'        => true,
					'sanitize'         => 'array',
					'sanitize_options' => 'text'
				),
				'product_social_share_copy_clipboard'  => array(
					'id'       => 'product_social_share_copy_clipboard',
					'title'    => __( 'Enable "Copy to Clipboard"', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => true,
					'sanitize' => 'bool'
				),
				'product_social_share_end_grid_2'      => array(
					'type' => 'endgrid',
				),

				'product_social_share_start_grid_3'  => array(
					'id'    => 'product_social_share_start_grid_2',
					'title' => __( 'Appearance', 'woo-assistant' ),
					'type'  => 'startgrid',
				),
				'product_social_share_title'         => array(
					'id'      => 'product_social_share_title',
					'title'   => __( 'Title', 'woo-assistant' ),
					'type'    => 'text',
					'default' => __( 'Share On:', 'woo-assistant' ),
					'desc'    => __( 'Display title before social icons.', 'woo-assistant' )
				),
				'product_social_share_appearance'    => array(
					'id'       => 'product_social_share_appearance',
					'title'    => __( 'Button Appearance', 'woo-assistant' ),
					'type'     => 'select',
					'options'  => array(
						'icon'      => __( 'Icon', 'woo-assistant' ),
						'text'      => __( 'Text', 'woo-assistant' ),
						'icon_text' => __( 'Icon with text', 'woo-assistant' ),
					),
					'default'  => 'icon',
					'sanitize' => 'text',
					'desc'     => __( 'Select social share icon appearance', 'woo-assistant' )
				),
				'product_social_share_button_shape'  => array(
					'id'       => 'product_social_share_button_shape',
					'title'    => __( 'Button Shape', 'woo-assistant' ),
					'type'     => 'select',
					'options'  => array(
						'round'          => __( 'Round', 'woo-assistant' ),
						'square'         => __( 'Square', 'woo-assistant' ),
						'rounded_corner' => __( 'Rounded Corner', 'woo-assistant' ),
					),
					'default'  => 'round',
					'sanitize' => 'text',
				),
				'product_social_share_primary_color' => array(
					'id'       => 'product_social_share_primary_color',
					'title'    => __( 'Primary color', 'woo-assistant' ),
					'type'     => 'wpColorPicker',
					'default'  => '#720eec',
					'sanitize' => 'color'
				),
				'product_social_share_bg_color'      => array(
					'id'       => 'product_social_share_bg_color',
					'title'    => __( 'Background color', 'woo-assistant' ),
					'type'     => 'wpColorPicker',
					'default'  => '#ffffff',
					'sanitize' => 'color'
				),
				'product_social_share_end_grid_3'    => array(
					'type' => 'endgrid',
				),
			)
		);

		return $sections;
	}
}