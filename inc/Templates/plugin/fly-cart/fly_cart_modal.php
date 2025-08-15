<?php

use WooAssistant\Helper\WooCommerce;
use WooAssistant\Settings\Settings;

defined( 'ABSPATH' ) or die();
?>

<div id="wa-fly-cart-modal" class="wa-modal wa-fade" tabindex="-1"
     aria-labelledby="flyCartModalLabel" aria-hidden="true" style="--wa-modal-border-width:0">
    <div class="wa-modal-dialog">
        <div class="wa-modal-content">
            <div class="wa-modal-header">
                        <span class="wa-modal-title"
                              id="flyCartModalLabel"><?php esc_html_e( 'Cart', 'wc-assistant' ) ?></span>
                <button type="button" class="wa-button wa-button-close" data-wa-dismiss="modal"
                        aria-label="<?php esc_html_e( 'Close', 'wc-assistant' ) ?>"></button>
            </div>
            <div class="wa-modal-body">
                <?php do_action( 'woo_assistant_fly_cart_modal_body' ); ?>
            </div>

            <?php
            $cart     = Settings::get( 'fly_cart_cart_button_enable', true ) && ! WooCommerce::isCart();
            $checkout = Settings::get( 'fly_cart_checkout_button_enable', true ) && ! WooCommerce::isCheckout();

            if ( $cart || $checkout ) {
                echo '<div class="wa-modal-footer">';

                if ( $cart ) {
                    echo '<a href="' . esc_url_raw( wc_get_cart_url() ) . '" type="button" class="wa-button wa-button-secondary">' .
                         esc_html( Settings::get( 'fly_cart_cart_button', __( 'Cart', 'wc-assistant' ) ) )
                         . '</a>';
                }

                if ( $checkout ) {
                    echo '<a href="' . esc_url_raw( wc_get_checkout_url() ) . '" type="button" class="wa-button wa-button-primary">' .
                         esc_html( Settings::get( 'fly_cart_checkout_button', __( 'Checkout', 'wc-assistant' ) ) )
                         . '</a>';
                }

                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>