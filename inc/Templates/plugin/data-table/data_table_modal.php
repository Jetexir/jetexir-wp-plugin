<?php

use Jetexir\Helper\HTML;

defined( 'ABSPATH' ) or die();
?>

<div id="jetexir-data-table-ui-modal"
     class="jetexir-data-table-ui-modal jetexir-modal jetexir-modal-large jetexir-fade jetexir-wrap"
     tabindex="-1"
     aria-labelledby="waDataTableUiModalLabel" aria-hidden="true"
     style="--jetexir-modal-border-width:0; --jetexir-modal-bg-color:white; --jetexir-modal-border-radius: 10px; --jetexir-modal-font-size: 14px">
  <div class="jetexir-modal-dialog">
    <div class="jetexir-modal-content">
      <div class="jetexir-modal-header">
                        <span class="jetexir-modal-title"
                              id="waDataTableUiModalLabel"><?php esc_html_e( 'Add new', 'jetexir' ) ?></span>
        <button type="button" class="jetexir-button jetexir-button-close" data-jetexir-dismiss="modal"
                aria-label="<?php esc_html_e( 'Close', 'jetexir' ) ?>"></button>
      </div>
      <div class="jetexir-modal-message"></div>
      <form class="jetexir-modal-body" onsubmit="return false;">
        <div class="jetexir-loader-wrap">
          <div class="jetexir-loader"></div>
        </div>
      </form>

      <div class="jetexir-modal-footer">
        <?php
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo HTML::toggle( array(
          'type'          => 'toggle',
          'title'         => esc_html__( 'Active', 'jetexir' ),
          'id'            => 'dtu-row-active',
          'value'         => 1,
          'setting_value' => 1
        ) );
        ?>
        <div class="jetexir-modal-buttons">
          <button class="jetexir-button jetexir-button-secondary" data-jetexir-dismiss="modal" type="button">
            <?php esc_html_e( 'Close', 'jetexir' ) ?>
          </button>
          <button class="jetexir-button jetexir-button-primary" type="button">
            <?php esc_html_e( 'Add', 'jetexir' ) ?>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
