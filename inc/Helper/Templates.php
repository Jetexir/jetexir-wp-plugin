<?php

namespace Jetexir\Helper;

class Templates {
  public static function load( $file, $args = [], $loadOnce = false, $echo = true ) {
    if ( ! $echo ) {
      ob_start();
    }

    if ( file_exists( $file ) ) {
      load_template( $file, $loadOnce, $args );
    }

    if ( ! $echo ) {
      return ob_get_clean();
    }
  }

  public static function getPath( $template, $dir = 'plugin' ): string {
    $path = Assets::pathCorrection( JETEXIR_PLUGIN_PATH . '/inc/Templates/' . $dir . '/' . $template );

    /**
     * Filters the template file path.
     *
     * @param string $path Template file path.
     * @param string $template Template name.
     * @param string $dir Template directory.
     *
     * @return string Template file path.
     *
     * @since 1.0
     *
     */
    return (string) apply_filters( 'jetexir_template_path', $path, $template, $dir );
  }
}
