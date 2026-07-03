<?php

namespace Jetexir\Helper;

use enshrined\svgSanitize\Sanitizer;

defined( 'ABSPATH' ) || exit;

class Sanitizing {
  public static function svg( $svg ) {
    $Sanitizer = new Sanitizer();
    $Sanitizer->removeXMLTag( true );
    // $Sanitizer->removeRemoteReferences( true );
    $Sanitizer->minify( true );

    $sanitized = $Sanitizer->sanitize( $svg );

    return $sanitized === false ? '' : $sanitized;
  }

  public static function clean( $value ) {
    return wc_clean( $value );
  }

  public static function jsonArray( $value ): array {
    $value = wp_unslash( htmlspecialchars_decode( $value ) );
    $value = str_replace( "'", '"', $value );
    if ( ! JSON::validate( $value ) ) {
      return [];
    }

    return self::array( JSON::decode( $value, true ) );
  }

  public static function array( $value ): array {
    return (array) $value;
  }

  public static function bool( $value ): bool {
    return wc_string_to_bool( $value );
  }

  public static function absint( $value ): int {
    return absint( $value );
  }

  public static function int( $value ): int {
    return (int) $value;
  }

  public static function float( $value ): float {
    return (float) $value;
  }

  public static function title( $value ): string {
    return sanitize_title( $value );
  }

  public static function color( $value ): string {
    return sanitize_hex_color( $value );
  }

  public static function colorNoHash( $value ): string {
    return sanitize_hex_color_no_hash( $value );
  }

  public static function text( $value ): string {
    return sanitize_text_field( $value );
  }

  public static function email( $value ): string {
    return sanitize_email( wp_unslash( $value ) );
  }

  public static function filename( $value ): string {
    return sanitize_file_name( $value );
  }

  public static function url( $value ): string {
    return sanitize_url( $value, array( 'http', 'https' ) );
  }

  public static function textarea( $value ): string {
    return sanitize_textarea_field( wp_unslash( $value ) );
  }

  public static function class( $value ): string {
    return sanitize_html_class( $value );
  }

  public static function mimeType( $value ): string {
    return sanitize_mime_type( $value );
  }

  public static function sqlOrderBy( $value ): string {
    return sanitize_sql_orderby( $value );
  }

  public static function username( $value ): string {
    return sanitize_user( $value );
  }

  public static function svgAllowedTags(): array {
    $kses_defaults = wp_kses_allowed_html( 'post' );

    $svg_args = array(
      'svg'   => array(
        'xml:space'       => true,
        'xmlns:x'         => true,
        'xmlns:i'         => true,
        'xmlns:graph'     => true,
        'class'           => true,
        'aria-hidden'     => true,
        'aria-labelledby' => true,
        'role'            => true,
        'xmlns'           => true,
        'width'           => true,
        'height'          => true,
        'viewbox'         => true, // <= Must be lower case!
        'fill'            => true,
        'x'               => true,
        'y'               => true,
        'style'           => true,
        'version'         => true,
        'transform'       => true,
      ),
      'g'     => array(
        'transform'       => true,
        'd'               => true,
        'id'              => true,
        'fill'            => true,
        'fill-rule'       => true,
        'stroke'          => true,
        'stroke-width'    => true,
        'stroke-linecap'  => true,
        'stroke-linejoin' => true,
      ),
      'title' => array( 'title' => true ),
      'path'  => array(
        'class'           => true,
        'transform'       => true,
        'd'               => true,
        'fill'            => true,
        'stroke'          => true,
        'stroke-width'    => true,
        'stroke-linecap'  => true,
        'stroke-linejoin' => true,
        'style'           => true,
      ),
    );

    $svg_args = array_map( '_wp_add_global_attributes', $svg_args );

    return array_merge( $kses_defaults, $svg_args );
  }
}
