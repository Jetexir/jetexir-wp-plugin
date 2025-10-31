<?php

namespace AssistantForWooCommerce\AppHelper;

defined( 'ABSPATH' ) || exit;

class FlyIcons {
  public function __construct() {
    add_action( 'admin_footer', [ $this, 'adminIcons' ] );
    add_action( 'wp_footer', [ $this, 'siteIcons' ] );
  }

  private function printIcons( $flyIcons ): void {
    if ( empty( $flyIcons ) || ! is_array( $flyIcons ) ) {
      return;
    }
    $positions     = $this->getAllowedPositions();
    $positionIcons = [];
    foreach ( $flyIcons as $flyIcon ) {
      if ( empty( $flyIcon['id'] ) || empty( $flyIcon['position'] ) || ( empty( $flyIcon['title'] ) && empty( $flyIcon['icon'] ) ) || ! in_array( $flyIcon['position'], $positions, true ) ) {
        continue;
      }

      $iconHTML = $this->getIconHTML( $flyIcon );
      if ( ! empty( $iconHTML ) ) {
        $positionIcons[ $flyIcon['position'] ][] = $iconHTML;
      }
    }

    if ( ! empty( $positionIcons ) ) {
      foreach ( $positionIcons as $position => $icons ) {
        $icons     = implode( '', $icons );
        $positions = explode( '-', $position );
        echo '<div id="asfowoo-fly-icons-' . esc_html( $position ) . '" class="asfowoo-fly-icons asfowoo-fly-icons-' . esc_html( $position ) . ' asfowoo-fly-icons-' . esc_html( $positions[0] ) . ' asfowoo-fly-icons-' . esc_html( $positions[1] ) . '">' . wp_kses_post( $icons ) . '</div>';
      }
    }
  }

  private function getIconHTML( $flyIcon ): string {
    $defaultArgs = array(
      'id'          => '',
      'tag'         => 'a',
      'title'       => '',
      'icon'        => '',
      'count_badge' => '',
      'attributes'  => '',
      'position'    => ''
    );
    $flyIcon     = wp_parse_args( $flyIcon, $defaultArgs );

    $id                             = 'asfowoo-fly-icon-' . $flyIcon['id'];
    $tag                            = in_array( $flyIcon['tag'], [ 'div', 'a' ] ) ? $flyIcon['tag'] : 'div';
    $title                          = is_string( $flyIcon['title'] ) ? $flyIcon['title'] : '';
    $icon                           = is_string( $flyIcon['icon'] ) ? $flyIcon['icon'] : '';
    $countBadge                     = (string) ( $flyIcon['count_badge'] ?? '' );
    $flyIcon['attributes']['class'] = 'asfowoo-fly-icon ' . $flyIcon['attributes']['class'] ?? '';
    $attributes                     = $this->getAttributes( $flyIcon['attributes'] ?? '' );

    $output = '<' . $tag . ' id="' . $id . '" ' . $attributes . '>';
    $output .= $icon;
    if ( ! empty( $title ) ) {
      $output .= '<span class="asfowoo-fly-icon-title">' . $title . '</span>';
    }
    if ( ! empty( $countBadge ) ) {
      $output .= '<span class="asfowoo-fly-icon-count">' . $countBadge . '</span>';
    }
    $output .= '</' . $tag . '>';

    return $output;
  }

  private function getAttributes( $iconAttributes ): string {
    if ( empty( $iconAttributes ) ) {
      return '';
    }

    if ( is_string( $iconAttributes ) ) {
      return $iconAttributes;
    }

    if ( is_array( $iconAttributes ) ) {
      $attributes = [];
      foreach ( $iconAttributes as $attribute => $value ) {
        $attributes[] = $attribute . '="' . $value . '"';
      }

      return implode( ' ', $attributes );
    }

    return '';
  }

  private function getAllowedPositions(): array {
    return array(
      'top-left',
      'top-right',
      'bottom-left',
      'bottom-right',
    );
  }

  public function siteIcons(): void {
    $this->printIcons( apply_filters( 'assistant_for_woocommerce_site_fly_icons', [] ) );
  }

  public function adminIcons(): void {
    $this->printIcons( apply_filters( 'assistant_for_woocommerce_admin_fly_icons', [] ) );
  }
}
