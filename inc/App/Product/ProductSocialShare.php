<?php

namespace WooAssistant\App\Product;

use WooAssistant\App\App;
use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Sanitizing;
use WooAssistant\Helper\WooCommerce;
use WooAssistant\Settings\Settings;

class ProductSocialShare {
	private const sectionID = 'social-share';
	private const shortCode = 'wa_product_share';

	public function __construct() {
		add_filter( 'woo_assistant_product_settings_sections', [ $this, 'addSectionSettings' ] );
		add_action( 'woo_assistant_init', [ $this, 'init' ] );
	}

	public function init(): void {
		if ( Settings::get( 'product_social_share_enable', false ) ) {
			App::addShortcode( self::shortCode, [ $this, 'shareShortcode' ] );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueueScripts' ) );

			$position = Settings::get( 'product_social_share_position', false );

			if ( $position === 'after_categories' ) {
				add_action( 'woocommerce_share', [ $this, 'displayLinks' ] );

			} elseif ( $position === 'after_title' ) {
				add_action( 'woocommerce_single_product_summary', [ $this, 'displayLinks' ], 6 );

			} elseif ( $position === 'after_price' ) {
				add_action( 'woocommerce_single_product_summary', [ $this, 'displayLinks' ], 11 );
			}
		}
	}

	public function displayLinks(): void {
		$socials          = implode( ',', Settings::get( 'product_social_share_networks', [] ) );
		$linkType         = Settings::get( 'product_social_share_link_type', 'long' );
		$encodeUrl        = Settings::get( 'product_social_share_encode_url', true ) ? 'on' : 'off';
		$copyClipboard    = Settings::get( 'product_social_share_copy_clipboard', true ) ? 'on' : 'off';
		$title            = Settings::get( 'product_social_share_title', __( 'Share On:', 'woo-assistant' ) );
		$buttonAppearance = Settings::get( 'product_social_share_appearance', 'icon' );
		$buttonShape      = Settings::get( 'product_social_share_shape', 'round' );
		$buttonSize       = Settings::get( 'product_social_share_button_size', 'default' );
		$primaryColor     = Settings::get( 'product_social_share_primary_color', '#720eec' );
		$bgColor          = Settings::get( 'product_social_share_bg_color', '#ffffff' );

		$args = array(
			'socials'           => $socials,
			'copy_clipboard'    => $copyClipboard,
			'link_type'         => $linkType,
			'encode_url'        => $encodeUrl,
			'title'             => $title,
			'button_appearance' => $buttonAppearance,
			'button_shape'      => $buttonShape,
			'button_size'       => $buttonSize,
			'primary_color'     => $primaryColor,
			'bg_color'          => $bgColor,
		);

		echo $this->shareShortcode( $args );
	}

	public function shareShortcode( $atts ) {
		$atts = shortcode_atts( array(
			'product_id'        => get_the_ID(),
			'socials'           => 'x,facebook,linkedin,telegram,whatsapp',
			'copy_clipboard'    => 'on',
			'link_type'         => 'long',
			'encode_url'        => 'on',
			'title'             => __( 'Share On:', 'woo-assistant' ),
			'button_appearance' => 'icon',
			'button_shape'      => 'round',
			'button_size'       => 'default',
			'primary_color'     => '#720eec',
			'bg_color'          => '#ffffff',
		), $atts, self::shortCode );

		$productId = (int) $atts['product_id'];

		if ( ! $productId || empty( $atts['socials'] ) ) {
			return '';
		}

		$socials          = explode( ',', strtolower( $atts['socials'] ) );
		$socials          = array_map( 'trim', $socials );
		$copyClipboard    = $atts['copy_clipboard'] === 'on';
		$encodeUrl        = $atts['encode_url'] === 'on';
		$linkType         = in_array( $atts['link_type'], [ 'long', 'short' ] ) ? $atts['link_type'] : 'long';
		$title            = is_string( $atts['title'] ) ? $atts['title'] : '';
		$buttonAppearance = in_array( $atts['button_appearance'], [
			'icon',
			'text',
			'icon_text'
		] ) ? $atts['button_appearance'] : 'icon';
		$buttonShape      = in_array( $atts['button_shape'], [
			'round',
			'square',
			'rounded_corner'
		] ) ? $atts['button_shape'] : 'round';
		$buttonSize       = in_array( $atts['button_size'], [
			'default',
			'large',
		] ) ? $atts['button_size'] : 'default';
		$primaryColor     = ! empty( $atts['primary_color'] ) ? Sanitizing::color( $atts['primary_color'] ) : '';
		$primaryColor     = empty( $primaryColor ) ? '#720eec' : $primaryColor;
		$bgColor          = ! empty( $atts['bg_color'] ) ? Sanitizing::color( $atts['bg_color'] ) : '';
		$bgColor          = empty( $bgColor ) ? '#ffffff' : $bgColor;

		$socialNetworks   = $this->socialNetworks();
		$links            = [];
		$linkClassDefault = [
			'wa-product-share-link',
			'wa-product-share-link-appearance-' . $buttonAppearance,
			'wa-product-share-link-shape-' . $buttonShape,
			'wa-product-share-link-size-' . $buttonSize,
		];

		$productLink = $linkType === 'long' ? get_permalink( $productId ) : wp_get_shortlink( $productId );
		$productLink = $encodeUrl ? urlencode( esc_url( $productLink ) ) : esc_url( $productLink );

		$wrap = '<div class="wa-product-share-wrapper" style="--wa-product-share-primary-color: ' . $primaryColor . '; --wa-product-share-bg-color: ' . $bgColor . ';">';
		if ( ! empty( $title ) ) {
			$wrap .= '<span class="wa-product-share-title">' . $title . '</span>';
		}

		foreach ( $socials as $social ) {
			if ( $social === 'twitter' ) {
				$social = 'x';
			}
			if ( array_key_exists( $social, $socialNetworks ) ) {
				$linkClass   = $linkClassDefault;
				$linkClass[] = 'wa-product-share-social-' . $social;
				$socialInfo  = $socialNetworks[ $social ];
				$link        = wp_sprintf( $socialInfo['share_link'], $productLink );

				if ( $buttonAppearance === 'icon' ) {
					$title = $socialInfo['icon'];
				} else if ( $buttonAppearance === 'text' ) {
					$title = $socialInfo['title'];
				} else {
					$title = $socialInfo['icon'] . ' ' . $socialInfo['title'];
				}

				$links[] = '<a href="' . $link . '" target="_blank" class="' . implode( ' ', $linkClass ) . '" title="' . $socialInfo['title'] . '">' . $title . '</a>';
			}
		}

		var_dump( $copyClipboard );
		if ( $copyClipboard ) {
			$linkClass         = $linkClassDefault;
			$linkClass[]       = 'wa-copy-text';
			$linkClass[]       = 'wa-product-share-copy';
			$copyClipboardIcon = '<i class="wa-icon-file_copy"></i>';
			$copyClipboardText = apply_filters( 'woo_assistant_copy_clipboard_text', __( 'Copy to Clipboard', 'woo-assistant' ) );
			if ( $buttonAppearance === 'icon' ) {
				$copyText = $copyClipboardIcon;
			} else if ( $buttonAppearance === 'text' ) {
				$copyText = $copyClipboardText;
			} else {
				$copyText = $copyClipboardIcon . ' ' . $copyClipboardText;
			}
			$links[] = '<a href="#" data-copy="' . $productLink . '" class="' . implode( ' ', $linkClass ) . '" title="' . $copyClipboardText . '">' . $copyText . '</a>';
		}

		$wrap .= '<div class="wa-product-share-links">' . implode( '', $links ) . '</div>';
		$wrap .= '</div>';

		return $wrap;
	}

	public function socialNetworks(): array {
		return array(
			'x'         => [
				'icon'       => '<i class="wa-icon-x-twitter"></i>',
				'title'      => __( 'Twitter', 'woo-assistant' ),
				'share_link' => 'https://twitter.com/intent/tweet?url=%1$s',
			],
			'facebook'  => [
				'icon'       => '<i class="wa-icon-facebook"></i>',
				'title'      => __( 'Facebook', 'woo-assistant' ),
				'share_link' => 'https://www.facebook.com/sharer/sharer.php?u=%1$s'
			],
			'linkedin'  => [
				'icon'       => '<i class="wa-icon-linkedin"></i>',
				'title'      => __( 'Linkedin', 'woo-assistant' ),
				'share_link' => 'https://www.linkedin.com/shareArticle?mini=true&url=%1$s',
			],
			'telegram'  => [
				'icon'       => '<i class="wa-icon-telegram"></i>',
				'title'      => __( 'Telegram', 'woo-assistant' ),
				'share_link' => 'https://t.me/share/url?url=%1$s',
			],
			'whatsapp'  => [
				'icon'       => '<i class="wa-icon-whatsapp"></i>',
				'title'      => __( 'WhatsApp', 'woo-assistant' ),
				'share_link' => 'https://api.whatsapp.com/send?text=%1$s',
			],
			'pinterest' => [
				'icon'       => '<i class="wa-icon-pinterest"></i>',
				'title'      => __( 'Pinterest', 'woo-assistant' ),
				'share_link' => 'https://pinterest.com/pin/create/button/?url=%1$s',
			],
			'tumblr'    => [
				'icon'       => '<i class="wa-icon-tumblr"></i>',
				'title'      => __( 'Tumblr', 'woo-assistant' ),
				'share_link' => 'https://www.tumblr.com/widgets/share/tool?posttype=link&canonicalUrl=%1$s',
			],
			'vk'        => [
				'icon'       => '<i class="wa-icon-vk"></i>',
				'title'      => __( 'VK', 'woo-assistant' ),
				'share_link' => 'https://vk.com/share.php?url=%1$s'
			],
			'viber'     => [
				'icon'       => '<i class="wa-icon-viber"></i>',
				'title'      => __( 'Viber', 'woo-assistant' ),
				'share_link' => 'viber://forward?text=%1$s',
			],
			'reddit'    => [
				'icon'       => '<i class="wa-icon-reddit"></i>',
				'title'      => __( 'Reddit', 'woo-assistant' ),
				'share_link' => 'https://reddit.com/submit?url=%1$s'
			],
			'xing'      => [
				'icon'       => '<i class="wa-icon-xing"></i>',
				'title'      => __( 'Xing', 'woo-assistant' ),
				'share_link' => 'https://www.xing.com/app/user?op=share&url=%1$s'
			],
			'weibo'     => [
				'icon'       => '<i class="wa-icon-weibo"></i>',
				'title'      => __( 'Weibo', 'woo-assistant' ),
				'share_link' => 'https://service.weibo.com/share/share.php?url=%1$s'
			],
			'mastodon'  => [
				'icon'       => '<i class="wa-icon-mastodon"></i>',
				'title'      => __( 'Mastodon', 'woo-assistant' ),
				'share_link' => 'https://mastodonshare.com/?url=%1$s'
			],
			'bluesky'   => [
				'icon'       => '<i class="wa-icon-bluesky"></i>',
				'title'      => __( 'Bluesky', 'woo-assistant' ),
				'share_link' => 'https://bsky.app/intent/compose?text=%1$s'
			],
			'pocket'    => [
				'icon'       => '<i class="wa-icon-pocket"></i>',
				'title'      => __( 'Pocket', 'woo-assistant' ),
				'share_link' => 'https://getpocket.com/save?url=%1$s'
			],
			'evernote'  => [
				'icon'       => '<i class="wa-icon-evernote"></i>',
				'title'      => __( 'Evernote', 'woo-assistant' ),
				'share_link' => 'https://www.evernote.com/clip.action?url=%1$s'
			],
			'yahoo'     => [
				'icon'       => '<i class="wa-icon-yahoo"></i>',
				'title'      => __( 'Yahoo', 'woo-assistant' ),
				'share_link' => 'https://compose.mail.yahoo.com/?body=%1$s'
			],
			'email'     => [
				'icon'       => '<i class="wa-icon-email"></i>',
				'title'      => __( 'Email', 'woo-assistant' ),
				'share_link' => 'mailto:%2$s?subject=%3$s&body=%1$s'
			],
		);
	}

	/**
	 * Enqueue style and script
	 *
	 * @return void
	 */
	public function enqueueScripts(): void {
		$pluginVersion = Assets::getVersion();

		if ( ! WooCommerce::isWoocommerce() ) {
			return;
		}

		wp_enqueue_style( WOOASSISTANT_PLUGIN_KEY . '-product-share-style',
			Assets::url( 'css/product-share.min.css' ),
			false, $pluginVersion );
	}

	public function addSectionSettings( $sections ) {
		$socials        = [];
		$socialNetworks = $this->socialNetworks();
		foreach ( $socialNetworks as $key => $socialNetwork ) {
			$socials[ $key ] = $socialNetwork['icon'] . ' ' . $socialNetwork['title'];
		}

		$sections[ self::sectionID ] = array(
			'title'    => __( 'Share', 'woo-assistant' ),
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
						'after_title'      => __( 'After product title', 'woo-assistant' ),
						'after_price'      => __( 'After product price', 'woo-assistant' ),
						'after_categories' => __( 'After product categories', 'woo-assistant' ),
					),
					'option_none'       => '---',
					'option_none_value' => '',
					'default'           => 'after_categories',
					'sanitize'          => 'text',
					'desc'              => sprintf( __( 'You can display social share with %s shortcode.', 'woo-assistant' ), '<code>[wa_product_share]</code>' )
				),
				'product_social_share_link_type_start' => array(
					'id'    => 'product_social_share_link_type_start',
					'title' => __( 'Link type', 'woo-assistant' ),
					'type'  => 'startradiogroup',
				),
				'product_social_share_link_type_long'  => array(
					'id'       => 'product_social_share_link_type',
					'title'    => __( 'Long link', 'woo-assistant' ),
					'type'     => 'radio',
					'default'  => 'long',
					'value'    => 'long',
					'sanitize' => 'text'
				),
				'product_social_share_link_type_short' => array(
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
				'product_social_share_encode_url'      => array(
					'id'       => 'product_social_share_encode_url',
					'title'    => __( 'Encode URL', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => false,
					'sanitize' => 'bool'
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
					'default'          => [ 'x', 'facebook', 'linkedin', 'telegram', 'whatsapp' ],
					'options'          => $socials,
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

				'product_social_share_start_grid_3'        => array(
					'id'    => 'product_social_share_start_grid_2',
					'title' => __( 'Appearance', 'woo-assistant' ),
					'type'  => 'startgrid',
				),
				'product_social_share_title'               => array(
					'id'      => 'product_social_share_title',
					'title'   => __( 'Title', 'woo-assistant' ),
					'type'    => 'text',
					'default' => __( 'Share On:', 'woo-assistant' ),
					'desc'    => __( 'Display title before social icons.', 'woo-assistant' )
				),
				'product_social_share_appearance'          => array(
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
				'product_social_share_shape'               => array(
					'id'       => 'product_social_share_shape',
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
				'product_social_share_button_size_start'   => array(
					'id'    => 'product_social_share_button_size_start',
					'title' => __( 'Button Size', 'woo-assistant' ),
					'type'  => 'startradiogroup',
				),
				'product_social_share_button_size_default' => array(
					'id'       => 'product_social_share_button_size',
					'title'    => __( 'Default', 'woo-assistant' ),
					'type'     => 'radio',
					'default'  => 'default',
					'value'    => 'default',
					'sanitize' => 'text'
				),
				'product_social_share_button_size_large'   => array(
					'id'       => 'product_social_share_button_size',
					'title'    => __( 'Large', 'woo-assistant' ),
					'type'     => 'radio',
					'default'  => 'default',
					'value'    => 'large',
					'sanitize' => 'text'
				),
				'product_social_share_button_size_end'     => array(
					'type' => 'endradiogroup',
				),
				'product_social_share_primary_color'       => array(
					'id'       => 'product_social_share_primary_color',
					'title'    => __( 'Primary color', 'woo-assistant' ),
					'type'     => 'wpColorPicker',
					'default'  => '#424242',
					'sanitize' => 'color'
				),
				'product_social_share_bg_color'            => array(
					'id'       => 'product_social_share_bg_color',
					'title'    => __( 'Background color', 'woo-assistant' ),
					'type'     => 'wpColorPicker',
					'default'  => '#f6f5f9',
					'sanitize' => 'color'
				),
				'product_social_share_end_grid_3'          => array(
					'type' => 'endgrid',
				),
			)
		);

		return $sections;
	}
}