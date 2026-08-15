<?php

use Jetexir\Helper\WooCommerce;
use Jetexir\Settings\Settings;

defined( 'ABSPATH' ) or die();
?>

<div id="jetexir-fly-cart-modal" class="jetexir-modal jetexir-fade" tabindex="-1"
     aria-labelledby="flyCartModalLabel" aria-hidden="true" style="--jetexir-modal-border-width:0">
  <div class="jetexir-modal-dialog">
    <div class="jetexir-modal-content">
      <div class="jetexir-modal-header">
                        <span class="jetexir-modal-title"
                              id="flyCartModalLabel"><?php esc_html_e( 'Cart', 'jetexir' ) ?></span>
        <button type="button" class="jetexir-button jetexir-button-close" data-jetexir-dismiss="modal"
                aria-label="<?php esc_html_e( 'Close', 'jetexir' ) ?>"></button>
      </div>
      <div class="jetexir-modal-body">
        <?php
        /**
         * Fires to display the fly cart modal body content.
         *
         * @since 1.0
         *
         */
        do_action( 'jetexir_fly_cart_modal_body' );
        ?>
      </div>

      <?php
      $cart     = Settings::get( 'fly_cart_cart_button_enable', true ) && ! WooCommerce::isCart();
      $checkout = Settings::get( 'fly_cart_checkout_button_enable', true ) && ! WooCommerce::isCheckout();

      if ( $cart || $checkout ) {
        echo '<div class="jetexir-modal-footer">';

        if ( $cart ) {
          echo '<a href="' . esc_url( wc_get_cart_url() ) . '" type="button" class="jetexir-button jetexir-button-secondary">' .
               esc_html( Settings::get( 'fly_cart_cart_button', esc_html__( 'Cart', 'jetexir' ) ) )
               . '</a>';
        }

        if ( $checkout ) {
          echo '<a href="' . esc_url( wc_get_checkout_url() ) . '" type="button" class="jetexir-button jetexir-button-primary">' .
               esc_html( Settings::get( 'fly_cart_checkout_button', esc_html__( 'Checkout', 'jetexir' ) ) )
               . '</a>';
        }

        echo '</div>';
      }
      ?>
    </div>
  </div>
</div>
