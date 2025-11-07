<?php

use AssistantForWooCommerce\Helper\HTML;

defined( 'ABSPATH' ) or die();
?>

<div id="asfowoo-data-table-ui-modal"
     class="asfowoo-data-table-ui-modal asfowoo-modal asfowoo-modal-large asfowoo-fade assistant-for-woocommerce-wrap"
     tabindex="-1"
     aria-labelledby="waDataTableUiModalLabel" aria-hidden="true"
     style="--asfowoo-modal-border-width:0; --asfowoo-modal-bg-color:white; --asfowoo-modal-border-radius: 10px; --asfowoo-modal-font-size: 14px">
  <div class="asfowoo-modal-dialog">
    <div class="asfowoo-modal-content">
      <div class="asfowoo-modal-header">
                        <span class="asfowoo-modal-title"
                              id="waDataTableUiModalLabel"><?php esc_html_e( 'Add new', 'assistant-for-woocommerce' ) ?></span>
        <button type="button" class="asfowoo-button asfowoo-button-close" data-asfowoo-dismiss="modal"
                aria-label="<?php esc_html_e( 'Close', 'assistant-for-woocommerce' ) ?>"></button>
      </div>
      <div class="asfowoo-modal-message"></div>
      <form class="asfowoo-modal-body" onsubmit="return false;">
        <div class="asfowoo-loader-wrap">
          <div class="asfowoo-loader"></div>
        </div>
      </form>

      <div class="asfowoo-modal-footer">
        <?php
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo HTML::toggle( array(
          'type'          => 'toggle',
          'title'         => esc_html__( 'Active', 'assistant-for-woocommerce' ),
          'id'            => 'dtu-row-active',
          'value'         => 1,
          'setting_value' => 1
        ) );
        ?>
        <div class="asfowoo-modal-buttons">
          <button class="asfowoo-button asfowoo-button-secondary" data-asfowoo-dismiss="modal" type="button">
            <?php esc_html_e( 'Close', 'assistant-for-woocommerce' ) ?>
          </button>
          <button class="asfowoo-button asfowoo-button-primary" type="button">
            <?php esc_html_e( 'Add', 'assistant-for-woocommerce' ) ?>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
