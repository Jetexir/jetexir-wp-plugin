<?php

use AssistantForWooCommerce\Helper\WooCommerce;
use AssistantForWooCommerce\Settings\Settings;

defined( 'ABSPATH' ) or die();
?>

<div id="asfowoo-fly-cart-modal" class="asfowoo-modal asfowoo-fade" tabindex="-1"
     aria-labelledby="flyCartModalLabel" aria-hidden="true" style="--asfowoo-modal-border-width:0">
    <div class="asfowoo-modal-dialog">
        <div class="asfowoo-modal-content">
            <div class="asfowoo-modal-header">
                        <span class="asfowoo-modal-title"
                              id="flyCartModalLabel"><?php esc_html_e( 'Cart', 'assistant-for-woocommerce' ) ?></span>
                <button type="button" class="asfowoo-button asfowoo-button-close" data-asfowoo-dismiss="modal"
                        aria-label="<?php esc_html_e( 'Close', 'assistant-for-woocommerce' ) ?>"></button>
            </div>
            <div class="asfowoo-modal-body">
                <?php do_action( 'assistant_for_woocommerce_fly_cart_modal_body' ); ?>
            </div>

            <?php
            $cart     = Settings::get( 'fly_cart_cart_button_enable', true ) && ! WooCommerce::isCart();
            $checkout = Settings::get( 'fly_cart_checkout_button_enable', true ) && ! WooCommerce::isCheckout();

            if ( $cart || $checkout ) {
                echo '<div class="asfowoo-modal-footer">';

                if ( $cart ) {
                    echo '<a href="' . esc_url( wc_get_cart_url() ) . '" type="button" class="asfowoo-button asfowoo-button-secondary">' .
                         esc_html( Settings::get( 'fly_cart_cart_button', esc_html__( 'Cart', 'assistant-for-woocommerce' ) ) )
                         . '</a>';
                }

                if ( $checkout ) {
                    echo '<a href="' . esc_url( wc_get_checkout_url() ) . '" type="button" class="asfowoo-button asfowoo-button-primary">' .
                         esc_html( Settings::get( 'fly_cart_checkout_button', esc_html__( 'Checkout', 'assistant-for-woocommerce' ) ) )
                         . '</a>';
                }

                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>