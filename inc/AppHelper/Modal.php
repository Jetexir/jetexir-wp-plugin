<?php

namespace Jetexir\AppHelper;

defined( 'ABSPATH' ) || exit;

class Modal {
  public function __construct() {
    add_action( 'wp_footer', [ $this, 'printModal' ] );
    add_action( 'admin_footer', [ $this, 'printAdminModal' ] );
  }

  public function printAdminModal(): void {
    /**
     * Fires to display admin modals.
     *
     * @since 1.0
     *
     */
    do_action( 'jetexir_admin_modals' );

    /**
     * Filters whether to display the admin modal overlay.
     *
     * @param bool $display Whether to display the overlay.
     *
     * @return bool Whether to display the overlay.
     *
     * @since 1.0
     *
     */
    $displayOverlay = (bool) apply_filters( 'jetexir_admin_modal_overlay', true );

    if ( $displayOverlay ) {
      echo '<div id="jetexir-modal-overlay" class="jetexir-modal-overlay"></div>';
    }
  }

  public function printModal(): void {
    /**
     * Fires to display site modals.
     *
     * @since 1.0
     *
     */
    do_action( 'jetexir_site_modals' );

    /**
     * Filters whether to display the site modal overlay.
     *
     * @param bool $display Whether to display the overlay.
     *
     * @return bool Whether to display the overlay.
     *
     * @since 1.0
     *
     */
    $displayOverlay = (bool) apply_filters( 'jetexir_site_modal_overlay', false );

    if ( $displayOverlay ) {
      echo '<div id="jetexir-modal-overlay" class="jetexir-modal-overlay"></div>';
    }
  }
}
