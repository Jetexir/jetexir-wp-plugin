<?php

use WooAssistant\Helper\HTML;

defined( 'ABSPATH' ) or die();
?>

<div id="wa-data-table-ui-modal"
     class="wa-data-table-ui-modal wa-modal wa-modal-large wa-fade woo-assistant-wrap"
     tabindex="-1"
     aria-labelledby="waDataTableUiModalLabel" aria-hidden="true"
     style="--wa-modal-border-width:0; --wa-modal-bg-color:white; --wa-modal-border-radius: 10px; --wa-modal-font-size: 14px">
    <div class="wa-modal-dialog">
        <div class="wa-modal-content">
            <div class="wa-modal-header">
                        <span class="wa-modal-title"
                              id="waDataTableUiModalLabel"><?php esc_html_e( 'Add new', 'woo-assistant' ) ?></span>
                <button type="button" class="wa-button wa-button-close" data-wa-dismiss="modal"
                        aria-label="<?php esc_html_e( 'Close', 'woo-assistant' ) ?>"></button>
            </div>
            <div class="wa-modal-message"></div>
            <form class="wa-modal-body">
                <div class="wa-loader-wrap">
                    <div class="wa-loader"></div>
                </div>
            </form>

            <div class="wa-modal-footer">
                <?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo HTML::toggle( array(
                        'type'          => 'toggle',
                        'title'         => esc_html__( 'Active', 'woo-assistant' ),
                        'id'            => 'dtu-row-active',
                        'value'         => 1,
                        'setting_value' => 1
                ) );
                ?>
                <div class="wa-modal-buttons">
                    <button class="wa-button wa-button-secondary" data-wa-dismiss="modal" type="button">
                        <?php esc_html_e( 'Close', 'woo-assistant' ) ?>
                    </button>
                    <button class="wa-button wa-button-primary" type="button">
                        <?php esc_html_e( 'Add', 'woo-assistant' ) ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>