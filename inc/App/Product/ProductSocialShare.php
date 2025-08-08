<?php

namespace WooAssistant\App\Product;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Addons\Addon;
use WooAssistant\App\App;
use WooAssistant\Helper\Assets;
use WooAssistant\Helper\WooCommerce;
use WooAssistant\Interfaces\AddonInterface;

class ProductSocialShare extends Addon implements AddonInterface {
	public string $addonID = 'product-social-share';
	public string $currentTab = 'product';
	public string $currentSection = 'social-share';
	private const shortCode = 'wa_product_share';

	public function initAction(): void {
		App::addShortcode( self::shortCode, [ $this, 'shareShortcode' ] );

		$position = $this->getSetting( 'product_social_share_position', false );

		if ( $position === 'after_categories' ) {
			add_action( 'woocommerce_share', [ $this, 'displayLinks' ] );

		} elseif ( $position === 'after_title' ) {
			add_action( 'woocommerce_single_product_summary', [ $this, 'displayLinks' ], 6 );

		} elseif ( $position === 'after_price' ) {
			add_action( 'woocommerce_single_product_summary', [ $this, 'displayLinks' ], 11 );
		}
	}

	public function displayLinks(): void {
		$socials          = implode( ',', $this->getSetting( 'product_social_share_networks', [] ) );
		$linkType         = $this->getSetting( 'product_social_share_link_type', 'long' );
		$encodeUrl        = $this->getSetting( 'product_social_share_encode_url', true ) ? 'on' : 'off';
		$copyClipboard    = $this->getSetting( 'product_social_share_copy_clipboard', true ) ? 'on' : 'off';
		$title            = $this->getSetting( 'product_social_share_title', __( 'Share On:', 'woo-assistant' ) );
		$buttonAppearance = $this->getSetting( 'product_social_share_appearance', 'icon' );
		$buttonShape      = $this->getSetting( 'product_social_share_shape', 'round' );
		$buttonSize       = $this->getSetting( 'product_social_share_button_size', 'default' );

		$args = array(
			'socials'           => $socials,
			'copy_clipboard'    => $copyClipboard,
			'link_type'         => $linkType,
			'encode_url'        => $encodeUrl,
			'title'             => $title,
			'button_appearance' => $buttonAppearance,
			'button_shape'      => $buttonShape,
			'button_size'       => $buttonSize,
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

		$wrap = '<div class="wa-product-share-wrapper">';
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
			/*'pocket'    => [
				'icon'       => '<i class="wa-icon-pocket"></i>',
				'title'      => __( 'Pocket', 'woo-assistant' ),
				'share_link' => 'https://getpocket.com/save?url=%1$s'
			],*/
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
	public function wpEnqueueScriptsAction(): void {
		if ( ! WooCommerce::isWoocommerce() ) {
			return;
		}

		$pluginVersion = Assets::getVersion();
		$debugName     = WOOASSISTANT_DEBUG_MODE ? '' : '.min';

		wp_enqueue_style( WOOASSISTANT_PLUGIN_KEY . '-product-share-style',
			Assets::url( 'css/product-share' . $debugName . '.css' ),
			false, $pluginVersion );
	}

	public function addSectionSettings( $sections ) {
		$socials        = [];
		$socialNetworks = $this->socialNetworks();
		foreach ( $socialNetworks as $key => $socialNetwork ) {
			$socials[ $key ] = $socialNetwork['icon'] . ' ' . $socialNetwork['title'];
		}

		$sections[ $this->currentSection ] = array(
			'title'        => __( 'Share', 'woo-assistant' ),
			'desc'         => __( 'Product Social Share', 'woo-assistant' ),
			'settings_key' => $this->addonID,
			'settings'     => array(
				'product_social_share_start_grid_1'        => array(
					'id'    => 'product_social_share_start_grid_1',
					'title' => __( 'Product Social Share', 'woo-assistant' ),
					'type'  => 'startgrid',
				),
				'product_social_share_position'            => array(
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
				'product_social_share_link_type_start'     => array(
					'id'    => 'product_social_share_link_type_start',
					'title' => __( 'Link type', 'woo-assistant' ),
					'type'  => 'startInlineElements',
				),
				'product_social_share_link_type_long'      => array(
					'id'       => 'product_social_share_link_type',
					'title'    => __( 'Long link', 'woo-assistant' ),
					'type'     => 'radio',
					'default'  => 'long',
					'value'    => 'long',
					'sanitize' => 'text'
				),
				'product_social_share_link_type_short'     => array(
					'id'       => 'product_social_share_link_type',
					'title'    => __( 'Short link', 'woo-assistant' ),
					'type'     => 'radio',
					'default'  => 'long',
					'value'    => 'short',
					'sanitize' => 'text'
				),
				'product_social_share_link_type_end'       => array(
					'type' => 'endInlineElements',
				),
				'product_social_share_encode_url'          => array(
					'id'       => 'product_social_share_encode_url',
					'title'    => __( 'Encode URL', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => false,
					'sanitize' => 'bool'
				),
				'product_social_share_end_grid_1'          => array(
					'type' => 'endgrid',
				),
				'product_social_share_sep_1'               => array(
					'type' => 'hr',
				),
				'product_social_share_start_grid_2'        => array(
					'id'    => 'product_social_share_start_grid_2',
					'title' => __( 'Social networks', 'woo-assistant' ),
					'type'  => 'startgrid',
				),
				'product_social_share_networks'            => array(
					'id'               => 'product_social_share_networks',
					'title'            => __( 'Select Social Networks', 'woo-assistant' ),
					'type'             => 'checkboxInline',
					'default'          => [ 'x', 'facebook', 'linkedin', 'telegram', 'whatsapp' ],
					'options'          => $socials,
					'not_equal'        => true,
					'sanitize'         => 'array',
					'sanitize_options' => 'text'
				),
				'product_social_share_copy_clipboard'      => array(
					'id'       => 'product_social_share_copy_clipboard',
					'title'    => __( 'Enable "Copy to Clipboard"', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => true,
					'sanitize' => 'bool'
				),
				'product_social_share_end_grid_2'          => array(
					'type' => 'endgrid',
				),
				'product_social_share_sep_2'               => array(
					'type' => 'hr',
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
					'type'  => 'startInlineElements',
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
					'type' => 'endInlineElements',
				),
				'product_social_share_end_grid_3'          => array(
					'type' => 'endgrid',
				),
			)
		);

		return $sections;
	}

	public function info(): array {
		$icon = '<svg viewBox="-2.6 -2.6 31.20 31.20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"><path transform="translate(-2.6, -2.6), scale(0.975)" d="M16,28.909013129305094C18.435408659729845,28.864217314702323,20.56564509509127,27.587831450916212,22.734471981579365,26.4790429492896C25.13602026867621,25.25127823306282,28.031574085073064,24.451055810569695,29.336811090604733,22.09071754390961C30.662586466053423,19.693238512588,29.848092275938757,16.77236122358177,29.611463634967638,14.042966979789737C29.360600924825018,11.149389793886401,29.756835842510316,7.921393157155611,27.91377133609411,5.676658372084297C26.061931017158795,3.4212352081958874,22.924800237896665,2.4609007432641867,20.008744773187587,2.347469081991047C17.38558091732938,2.24543061339445,15.362367938579851,4.535616579007,12.796122964634678,5.088596865685988C9.921958078133422,5.707928433418891,6.607352798856866,4.098685189656093,4.180343076678868,5.758208974975473C1.6754444929005525,7.470991062993854,0.21943662595032987,10.689595041451053,0.16759481233944662,13.723643792280999C0.11732141956535119,16.665900513681756,2.2445135083504573,19.095534199904876,3.91101180611801,21.52085592128264C5.328241994997167,23.58340777980267,6.969080018564395,25.424746504357874,9.104409533901274,26.729748204849503C11.203806238600441,28.012789651127978,13.539997057359825,28.954261320117148,16,28.909013129305094" fill="#fff" strokewidth="0"></path></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>share</title> <desc>Created with Sketch Beta.</desc> <defs> </defs> <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" sketch:type="MSPage"> <g id="Icon-Set" sketch:type="MSLayerGroup" transform="translate(-312.000000, -726.000000)" fill="#873eff"> <path d="M331,750 C329.343,750 328,748.657 328,747 C328,745.343 329.343,744 331,744 C332.657,744 334,745.343 334,747 C334,748.657 332.657,750 331,750 L331,750 Z M317,742 C315.343,742 314,740.657 314,739 C314,737.344 315.343,736 317,736 C318.657,736 320,737.344 320,739 C320,740.657 318.657,742 317,742 L317,742 Z M331,728 C332.657,728 334,729.343 334,731 C334,732.657 332.657,734 331,734 C329.343,734 328,732.657 328,731 C328,729.343 329.343,728 331,728 L331,728 Z M331,742 C329.23,742 327.685,742.925 326.796,744.312 L321.441,741.252 C321.787,740.572 322,739.814 322,739 C322,738.497 321.903,738.021 321.765,737.563 L327.336,734.38 C328.249,735.37 329.547,736 331,736 C333.762,736 336,733.762 336,731 C336,728.238 333.762,726 331,726 C328.238,726 326,728.238 326,731 C326,731.503 326.097,731.979 326.235,732.438 L320.664,735.62 C319.751,734.631 318.453,734 317,734 C314.238,734 312,736.238 312,739 C312,741.762 314.238,744 317,744 C318.14,744 319.179,743.604 320.02,742.962 L320,743 L326.055,746.46 C326.035,746.64 326,746.814 326,747 C326,749.762 328.238,752 331,752 C333.762,752 336,749.762 336,747 C336,744.238 333.762,742 331,742 L331,742 Z" id="share" sketch:type="MSShapeGroup"> </path> </g> </g> </g></svg>';

		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Product Social Share', 'woo-assistant' ),
			'desc'           => __( 'Enable social sharing on WooCommerce product pages.', 'woo-assistant' ),
			'tags'           => [ __( 'Product', 'woo-assistant' ) ],
			'cat'            => 'product',
			'icon'           => $icon,
			'more_info_link' => 'https://parsa.ws',
			'settings_key'   => $this->addonID,
		);
	}
}