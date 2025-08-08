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
                              id="flyCartModalLabel"><?php _e( 'Cart', 'woo-assistant' ) ?></span>
                <button type="button" class="wa-button wa-button-close" data-wa-dismiss="modal"
                        aria-label="<?php _e( 'Close', 'woo-assistant' ) ?>"></button>
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
                    echo '<a href="' . wc_get_cart_url() . '" type="button" class="wa-button wa-button-secondary">' .
                         Settings::get( 'fly_cart_cart_button', __( 'Cart', 'woo-assistant' ) )
                         . '</a>';
                }

                if ( $checkout ) {
                    echo '<a href="' . wc_get_checkout_url() . '" type="button" class="wa-button wa-button-primary">' .
                         Settings::get( 'fly_cart_checkout_button', __( 'Checkout', 'woo-assistant' ) )
                         . '</a>';
                }

                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>