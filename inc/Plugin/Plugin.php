<?php

namespace AssistantForWooCommerce\Plugin;

class Plugin {
  public function __construct() {
    add_filter( 'plugin_action_links_' . plugin_basename( ASSISTANTFORWOOCOMMERCE_PLUGIN_FILE_PATH ),
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
    $links[] = '<a href="' . admin_url( 'admin.php?page=' . ASSISTANTFORWOOCOMMERCE_PLUGIN_SLUG ) . '">' .
               __( 'Dashboard', 'assistant-for-woocommerce' ) . '</a>';

    return $links;
  }
}
