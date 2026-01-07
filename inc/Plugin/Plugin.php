<?php

namespace Jetexir\Plugin;

class Plugin {
  public function __construct() {
    add_filter( 'plugin_action_links_' . plugin_basename( JETEXIR_PLUGIN_FILE_PATH ),
      [ $this, 'pluginActionLink' ] );
  }

  /**
   * Add dashboard link to admin plugins
   *
   * @param array $links
   *
   * @return  array
   */
  public static function pluginActionLink( $links ): array {
    $links[] = '<a href="' . admin_url( 'admin.php?page=' . JETEXIR_PLUGIN_SLUG ) . '">' .
               __( 'Dashboard', 'jetexir' ) . '</a>';

    return $links;
  }
}
