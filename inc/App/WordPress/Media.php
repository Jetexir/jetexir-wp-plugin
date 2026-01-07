<?php

namespace Jetexir\App\WordPress;

defined( 'ABSPATH' ) || exit;

use Jetexir\Settings\Settings;

class Media {
  private const sectionID = 'media';

  public function __construct() {
    add_filter( 'jetexir_wordpress_settings_sections', [ $this, 'addSectionSettings' ] );
    add_filter( 'wp_kses_allowed_html', [ $this, 'addSvgToKses' ], 10, 2 );

    if ( Settings::get( 'svg_enable', true ) ) {
      add_filter( 'upload_mimes', [ $this, 'addSvgToMedia' ] );
      add_filter( 'image_downsize', [ $this, 'fixSvgSize' ], 10, 2 );
    }
  }

  public function addSvgToKses( $tags, $context ) {
    if ( $context === 'post' && is_array( $tags ) && ! isset( $tags['svg'] ) ) {
      $svg = array(
        'svg'      => array(
          'class'           => true,
          'version'         => true,
          'aria-hidden'     => true,
          'aria-labelledby' => true,
          'role'            => true,
          'xmlns'           => true,
          'xmlns:sketch'    => true,
          'width'           => true,
          'height'          => true,
          'viewbox'         => true,
          'fill'            => true,
          'focusable'       => true,
          'style'           => true,
          'x'               => true,
          'y'               => true,
          'xml:space'       => true,
        ),
        'g'        => array(
          'fill'            => true,
          'fill-rule'       => true,
          'id'              => true,
          'transform'       => true,
          'sketch:type'     => true,
          'stroke'          => true,
          'stroke-width'    => true,
          'strokewidth'     => true,
          'stroke-linecap'  => true,
          'stroke-linejoin' => true,
        ),
        'title'    => array(
          'title' => true
        ),
        'style'    => array(
          'type' => true
        ),
        'circle'   => array(
          'cx' => true,
          'cy' => true,
          'r'  => true,
        ),
        'path'     => array(
          'd'               => true,
          'id'              => true,
          'transform'       => true,
          'fill'            => true,
          'class'           => true,
          'sketch:type'     => true,
          'stroke'          => true,
          'stroke-width'    => true,
          'strokewidth'     => true,
          'stroke-linecap'  => true,
          'stroke-linejoin' => true,
        ),
        'line'     => array(
          'id' => true,
          'x1' => true,
          'y1' => true,
          'x2' => true,
          'y2' => true
        ),
        'polygon'  => array(
          'points' => true
        ),
        'polyline' => array(
          'id'     => true,
          'points' => true
        ),
        'rect'     => array(
          'x'         => true,
          'y'         => true,
          'width'     => true,
          'height'    => true,
          'transform' => true,
        ),
      );

      $tags = array_merge( $tags, $svg );
    }

    return $tags;
  }

  public function addSvgToMedia( $mimes ) {
    $mimes['svg'] = 'image/svg+xml';

    return $mimes;
  }

  /**
   * Removes the width and height attributes of <img> tags for SVG
   *
   * Without this filter, the width and height are set to "1" since
   * WordPress core can't seem to figure out an SVG file's dimensions.
   *
   * For SVG:s, returns an array with file url, width and height set
   * to null, and false for 'is_intermediate'.
   *
   * @wp-hook image_downsize
   *
   * @param mixed $out Value to be filtered
   * @param int $id Attachment ID for image.
   *
   * @return bool|array False if not in admin or not SVG. Array otherwise.
   */
  public function fixSvgSize( $out, $id ) {
    $url = wp_get_attachment_url( $id );
    $ext = pathinfo( $url, PATHINFO_EXTENSION );

    if ( 'svg' !== $ext || is_admin() ) {
      return false;
    }

    return array( $url, null, null, false );
  }

  public function addSectionSettings( array $sections ): array {
    $settings = [
      'start_grid_svg' => array(
        'title' => 'SVG',
        'type'  => 'startGrid',
      ),
      'svg_enable'     => array(
        'id'       => 'svg_enable',
        'title'    => esc_html__( 'Enable SVG support', 'jetexir' ),
        'type'     => 'toggle',
        'value'    => 1,
        'default'  => true,
        'desc'     => esc_html__( 'Allows upload SVG Files into your Media library', 'jetexir' ),
        'sanitize' => 'bool'
      ),
      'end_grid_svg'   => array(
        'type' => 'endGrid',
      )
    ];

    $sections[ self::sectionID ] = array(
      'title'    => esc_html__( 'Media', 'jetexir' ),
      'desc'     => esc_html__( 'Media Settings', 'jetexir' ),
      'settings' => $settings
    );

    return $sections;
  }
}
