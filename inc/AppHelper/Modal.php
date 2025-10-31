<?php

namespace AssistantForWooCommerce\AppHelper;

defined( 'ABSPATH' ) || exit;

class Modal {
  public function __construct() {
    add_action( 'wp_footer', [ $this, 'printModal' ] );
    add_action( 'admin_footer', [ $this, 'printAdminModal' ] );
  }

  public function printAdminModal(): void {
    do_action( 'assistant_for_woocommerce_admin_modals' );

    if ( apply_filters( 'assistant_for_woocommerce_admin_modal_overlay', true ) ) {
      echo '<div id="asfowoo-modal-overlay" class="asfowoo-modal-overlay"></div>';
    }
  }

  public function printModal(): void {
    do_action( 'assistant_for_woocommerce_site_modals' );

    if ( apply_filters( 'assistant_for_woocommerce_site_modal_overlay', false ) ) {
      echo '<div id="asfowoo-modal-overlay" class="asfowoo-modal-overlay"></div>';
    }
  }
}
