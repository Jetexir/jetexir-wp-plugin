<?php

namespace Jetexir\AppHelper;

defined( 'ABSPATH' ) || exit;

class Modal {
  public function __construct() {
    add_action( 'wp_footer', [ $this, 'printModal' ] );
    add_action( 'admin_footer', [ $this, 'printAdminModal' ] );
  }

  public function printAdminModal(): void {
    do_action( 'jetexir_admin_modals' );

    if ( apply_filters( 'jetexir_admin_modal_overlay', true ) ) {
      echo '<div id="jetexir-modal-overlay" class="jetexir-modal-overlay"></div>';
    }
  }

  public function printModal(): void {
    do_action( 'jetexir_site_modals' );

    if ( apply_filters( 'jetexir_site_modal_overlay', false ) ) {
      echo '<div id="jetexir-modal-overlay" class="jetexir-modal-overlay"></div>';
    }
  }
}
