<?php

namespace Jetexir\App\Product;

defined( 'ABSPATH' ) || exit;

use Jetexir\Addons\Addon;
use Jetexir\App\App;
use Jetexir\Helper\Assets;
use Jetexir\Helper\WooCommerce;
use Jetexir\Interfaces\AddonInterface;

class ProductSocialShare extends Addon implements AddonInterface {
  public string $addonID = 'product-social-share';
  public string $currentTab = 'product';
  public string $currentSection = 'social-share';
  private const shortCode = 'jetexir_product_share';

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
    $title            = $this->getSetting( 'product_social_share_title', esc_html__( 'Share On:', 'jetexir' ) );
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

    echo wp_kses_post( $this->shareShortcode( $args ) );
  }

  public function shareShortcode( $atts ): string {
    $atts = shortcode_atts( array(
      'product_id'        => get_the_ID(),
      'socials'           => 'x,facebook,linkedin,telegram,whatsapp',
      'copy_clipboard'    => 'on',
      'link_type'         => 'long',
      'encode_url'        => 'on',
      'title'             => esc_html__( 'Share On:', 'jetexir' ),
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
      'jetexir-product-share-link',
      'jetexir-product-share-link-appearance-' . $buttonAppearance,
      'jetexir-product-share-link-shape-' . $buttonShape,
      'jetexir-product-share-link-size-' . $buttonSize,
    ];

    $productLink = $linkType === 'long' ? get_permalink( $productId ) : wp_get_shortlink( $productId );
    $productLink = $encodeUrl ? urlencode( $productLink ) : $productLink;

    $wrap = '<div class="jetexir-product-share-wrapper">';
    if ( ! empty( $title ) ) {
      $wrap .= '<span class="jetexir-product-share-title">' . $title . '</span>';
    }

    foreach ( $socials as $social ) {
      if ( $social === 'twitter' ) {
        $social = 'x';
      }
      if ( array_key_exists( $social, $socialNetworks ) ) {
        $linkClass   = $linkClassDefault;
        $linkClass[] = 'jetexir-product-share-social-' . $social;
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
      $linkClass[]       = 'jetexir-copy-text';
      $linkClass[]       = 'jetexir-product-share-copy';
      $copyClipboardIcon = '<i class="jetexir-icon-file_copy"></i>';
      /**
       * Filters the copy to clipboard button text.
       *
       * @param string $text Button text.
       *
       * @return string Button text.
       *
       * @since 1.0
       *
       */
      $copyClipboardText = (string) apply_filters( 'jetexir_copy_clipboard_text', esc_html__( 'Copy to Clipboard', 'jetexir' ) );
      if ( $buttonAppearance === 'icon' ) {
        $copyText = $copyClipboardIcon;
      } else if ( $buttonAppearance === 'text' ) {
        $copyText = $copyClipboardText;
      } else {
        $copyText = $copyClipboardIcon . ' ' . $copyClipboardText;
      }
      $links[] = '<a href="#" data-copy="' . $productLink . '" class="' . implode( ' ', $linkClass ) . '" title="' . $copyClipboardText . '">' . $copyText . '</a>';
    }

    $wrap .= '<div class="jetexir-product-share-links">' . implode( '', $links ) . '</div>';
    $wrap .= '</div>';

    return $wrap;
  }

  public function socialNetworks(): array {
    return array(
      'x'         => [
        'icon'       => '<i class="jetexir-icon-x-twitter"></i>',
        'title'      => esc_html__( 'X', 'jetexir' ),
        'share_link' => 'https://twitter.com/intent/tweet?url=%1$s',
      ],
      'facebook'  => [
        'icon'       => '<i class="jetexir-icon-facebook"></i>',
        'title'      => esc_html__( 'Facebook', 'jetexir' ),
        'share_link' => 'https://www.facebook.com/sharer/sharer.php?u=%1$s'
      ],
      'linkedin'  => [
        'icon'       => '<i class="jetexir-icon-linkedin"></i>',
        'title'      => esc_html__( 'Linkedin', 'jetexir' ),
        'share_link' => 'https://www.linkedin.com/shareArticle?mini=true&url=%1$s',
      ],
      'telegram'  => [
        'icon'       => '<i class="jetexir-icon-telegram"></i>',
        'title'      => esc_html__( 'Telegram', 'jetexir' ),
        'share_link' => 'https://t.me/share/url?url=%1$s',
      ],
      'whatsapp'  => [
        'icon'       => '<i class="jetexir-icon-whatsapp"></i>',
        'title'      => esc_html__( 'WhatsApp', 'jetexir' ),
        'share_link' => 'https://api.whatsapp.com/send?text=%1$s',
      ],
      'pinterest' => [
        'icon'       => '<i class="jetexir-icon-pinterest"></i>',
        'title'      => esc_html__( 'Pinterest', 'jetexir' ),
        'share_link' => 'https://pinterest.com/pin/create/button/?url=%1$s',
      ],
      'tumblr'    => [
        'icon'       => '<i class="jetexir-icon-tumblr"></i>',
        'title'      => esc_html__( 'Tumblr', 'jetexir' ),
        'share_link' => 'https://www.tumblr.com/widgets/share/tool?posttype=link&canonicalUrl=%1$s',
      ],
      'vk'        => [
        'icon'       => '<i class="jetexir-icon-vk"></i>',
        'title'      => esc_html__( 'VK', 'jetexir' ),
        'share_link' => 'https://vk.com/share.php?url=%1$s'
      ],
      'viber'     => [
        'icon'       => '<i class="jetexir-icon-viber"></i>',
        'title'      => esc_html__( 'Viber', 'jetexir' ),
        'share_link' => 'viber://forward?text=%1$s',
      ],
      'reddit'    => [
        'icon'       => '<i class="jetexir-icon-reddit"></i>',
        'title'      => esc_html__( 'Reddit', 'jetexir' ),
        'share_link' => 'https://reddit.com/submit?url=%1$s'
      ],
      'xing'      => [
        'icon'       => '<i class="jetexir-icon-xing"></i>',
        'title'      => esc_html__( 'Xing', 'jetexir' ),
        'share_link' => 'https://www.xing.com/app/user?op=share&url=%1$s'
      ],
      'weibo'     => [
        'icon'       => '<i class="jetexir-icon-weibo"></i>',
        'title'      => esc_html__( 'Weibo', 'jetexir' ),
        'share_link' => 'https://service.weibo.com/share/share.php?url=%1$s'
      ],
      'mastodon'  => [
        'icon'       => '<i class="jetexir-icon-mastodon"></i>',
        'title'      => esc_html__( 'Mastodon', 'jetexir' ),
        'share_link' => 'https://mastodonshare.com/?url=%1$s'
      ],
      'bluesky'   => [
        'icon'       => '<i class="jetexir-icon-bluesky"></i>',
        'title'      => esc_html__( 'Bluesky', 'jetexir' ),
        'share_link' => 'https://bsky.app/intent/compose?text=%1$s'
      ],
      /*'pocket'    => [
        'icon'       => '<i class="jetexir-icon-pocket"></i>',
        'title'      => esc_html__( 'Pocket', 'jetexir' ),
        'share_link' => 'https://getpocket.com/save?url=%1$s'
      ],*/
      'evernote'  => [
        'icon'       => '<i class="jetexir-icon-evernote"></i>',
        'title'      => esc_html__( 'Evernote', 'jetexir' ),
        'share_link' => 'https://www.evernote.com/clip.action?url=%1$s'
      ],
      'yahoo'     => [
        'icon'       => '<i class="jetexir-icon-yahoo"></i>',
        'title'      => esc_html__( 'Yahoo', 'jetexir' ),
        'share_link' => 'https://compose.mail.yahoo.com/?body=%1$s'
      ],
      'email'     => [
        'icon'       => '<i class="jetexir-icon-email"></i>',
        'title'      => esc_html__( 'Email', 'jetexir' ),
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
    $debugName     = JETEXIR_DEBUG_MODE ? '' : '.min';

    wp_enqueue_style( JETEXIR_PLUGIN_KEY . '-product-share-style',
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
      'title'        => esc_html__( 'Share', 'jetexir' ),
      'desc'         => esc_html__( 'Product Social Share', 'jetexir' ),
      'settings_key' => $this->addonID,
      'settings'     => array(
        'product_social_share_start_grid_1'        => array(
          'id'    => 'product_social_share_start_grid_1',
          'title' => esc_html__( 'Product Social Share', 'jetexir' ),
          'type'  => 'startgrid',
        ),
        'product_social_share_position'            => array(
          'id'                => 'product_social_share_position',
          'title'             => esc_html__( 'Position', 'jetexir' ),
          'type'              => 'select',
          'options'           => array(
            'after_title'      => esc_html__( 'After product title', 'jetexir' ),
            'after_price'      => esc_html__( 'After product price', 'jetexir' ),
            'after_categories' => esc_html__( 'After product categories', 'jetexir' ),
          ),
          'option_none'       => '---',
          'option_none_value' => '',
          'default'           => 'after_categories',
          'sanitize'          => 'text',
          /* translators: %s: Shortcode */
          'desc'              => sprintf( esc_html__( 'You can display social share with %s shortcode.', 'jetexir' ), '<code class="jetexir-copy-text">[jetexir_product_share]</code>' )
        ),
        'product_social_share_link_type_start'     => array(
          'id'    => 'product_social_share_link_type_start',
          'title' => esc_html__( 'Link type', 'jetexir' ),
          'type'  => 'startInlineElements',
        ),
        'product_social_share_link_type_long'      => array(
          'id'       => 'product_social_share_link_type',
          'title'    => esc_html__( 'Long link', 'jetexir' ),
          'type'     => 'radio',
          'default'  => 'long',
          'value'    => 'long',
          'sanitize' => 'text'
        ),
        'product_social_share_link_type_short'     => array(
          'id'       => 'product_social_share_link_type',
          'title'    => esc_html__( 'Short link', 'jetexir' ),
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
          'title'    => esc_html__( 'Encode URL', 'jetexir' ),
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
          'title' => esc_html__( 'Social networks', 'jetexir' ),
          'type'  => 'startgrid',
        ),
        'product_social_share_networks'            => array(
          'id'               => 'product_social_share_networks',
          'title'            => esc_html__( 'Select Social Networks', 'jetexir' ),
          'type'             => 'checkboxInline',
          'default'          => [ 'x', 'facebook', 'linkedin', 'telegram', 'whatsapp' ],
          'options'          => $socials,
          'not_equal'        => true,
          'sanitize'         => 'array',
          'sanitize_options' => 'text'
        ),
        'product_social_share_copy_clipboard'      => array(
          'id'       => 'product_social_share_copy_clipboard',
          'title'    => esc_html__( 'Enable "Copy to Clipboard"', 'jetexir' ),
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
          'title' => esc_html__( 'Appearance', 'jetexir' ),
          'type'  => 'startgrid',
        ),
        'product_social_share_title'               => array(
          'id'          => 'product_social_share_title',
          'title'       => esc_html__( 'Title', 'jetexir' ),
          'type'        => 'text',
          'default'     => esc_html__( 'Share On:', 'jetexir' ),
          'placeholder' => esc_html__( 'Share On:', 'jetexir' ),
          'desc'        => esc_html__( 'Display title before social icons', 'jetexir' )
        ),
        'product_social_share_appearance'          => array(
          'id'       => 'product_social_share_appearance',
          'title'    => esc_html__( 'Button Appearance', 'jetexir' ),
          'type'     => 'select',
          'options'  => array(
            'icon'      => esc_html__( 'Icon', 'jetexir' ),
            'text'      => esc_html__( 'Text', 'jetexir' ),
            'icon_text' => esc_html__( 'Icon with text', 'jetexir' ),
          ),
          'default'  => 'icon',
          'sanitize' => 'text',
          'desc'     => esc_html__( 'Select social share icon appearance', 'jetexir' )
        ),
        'product_social_share_shape'               => array(
          'id'       => 'product_social_share_shape',
          'title'    => esc_html__( 'Button Shape', 'jetexir' ),
          'type'     => 'select',
          'options'  => array(
            'round'          => esc_html__( 'Round', 'jetexir' ),
            'square'         => esc_html__( 'Square', 'jetexir' ),
            'rounded_corner' => esc_html__( 'Rounded Corner', 'jetexir' ),
          ),
          'default'  => 'round',
          'sanitize' => 'text',
        ),
        'product_social_share_button_size_start'   => array(
          'id'    => 'product_social_share_button_size_start',
          'title' => esc_html__( 'Button Size', 'jetexir' ),
          'type'  => 'startInlineElements',
        ),
        'product_social_share_button_size_default' => array(
          'id'       => 'product_social_share_button_size',
          'title'    => esc_html__( 'Default', 'jetexir' ),
          'type'     => 'radio',
          'default'  => 'default',
          'value'    => 'default',
          'sanitize' => 'text'
        ),
        'product_social_share_button_size_large'   => array(
          'id'       => 'product_social_share_button_size',
          'title'    => esc_html__( 'Large', 'jetexir' ),
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
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><g stroke="#873eff" stroke-width="1.5"><path d="M12 9a3 3 0 1 1 0-6 3 3 0 0 1 0 6ZM5.5 21a3 3 0 1 1 0-6 3 3 0 0 1 0 6ZM18.5 21a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"/><path stroke-linecap="round" d="M20 13a7.98 7.98 0 0 0-2.708-6M4 13a7.98 7.98 0 0 1 2.708-6M10 20.748c.64.165 1.31.252 2 .252s1.36-.087 2-.252"/></g></svg>';

    return array(
      'id'             => $this->addonID,
      'title'          => esc_html__( 'Product Social Share', 'jetexir' ),
      'desc'           => esc_html__( 'Enable social sharing on WooCommerce product pages.', 'jetexir' ),
      'tags'           => [ esc_html__( 'Product', 'jetexir' ) ],
      'cat'            => 'product',
      'icon'           => $icon,
      'more_info_link' => '{jetexir_website}/addons/social-sharing',
      'settings_key'   => $this->addonID,
    );
  }
}
