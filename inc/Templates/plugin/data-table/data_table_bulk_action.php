<?php

use Jetexir\Providers\UI\AbstractDataTableUI;

defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) || ! isset( $args[ $args['bulk_action_position'] . '_bulk_action' ] ) || ! $args[ $args['bulk_action_position'] . '_bulk_action' ] ) {
  return;
}
?>
<div class="jetexir-dtu-actions jetexir-dtu-actions-<?php echo esc_html( $args['bulk_action_position'] ) ?>">
  <?php
  if ( $args['has_bulk_action'] ) {
    ?>
    <div
      class="jetexir-dtu-bulk-actions jetexir-dtu-bulk-actions-<?php echo esc_html( $args['bulk_action_position'] ) ?>">
      <label for="bulk-action-selector-<?php echo esc_html( $args['bulk_action_position'] ) ?>"
             class="screen-reader-text"><?php esc_html_e( 'Select bulk action', 'jetexir' ) ?></label>
      <select name="action" id="bulk-action-selector-<?php echo esc_html( $args['bulk_action_position'] ) ?>">
        <option value="">---</option>
        <?php
        foreach ( $args['actions'] as $key => $action ) {
          if ( $action['flag'] === AbstractDataTableUI::ACTION_BULK ) {
            echo '<option value="' . esc_html( $key ) . '" data-action-type="' . esc_html( $action['type'] ) . '">' . esc_html( $action['title'] ) . '</option>';
          }
        }
        ?>
      </select>
      <button class="jetexir-button jetexir-button-secondary"
              type="button"><?php esc_html_e( 'Apply', 'jetexir' ) ?></button>
    </div>
  <?php }

  if ( $args['sortable'] ) {
    echo '<button class="jetexir-button jetexir-button-secondary jetexir-dtu-save-changes" type="button" disabled>' . esc_html__( 'Save changes', 'jetexir' ) . '</button>';
  }
  ?>
</div>
