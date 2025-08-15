<?php

use WooAssistant\Providers\UI\AbstractDataTableUI;

defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) || ! isset( $args[ $args['bulk_action_position'] . '_bulk_action' ] ) || ! $args[ $args['bulk_action_position'] . '_bulk_action' ] ) {
    return;
}
?>
<div class="wa-dtu-actions wa-dtu-actions-<?php echo esc_html( $args['bulk_action_position'] ) ?>">
    <?php
    if ( $args['has_bulk_action'] ) {
        ?>
        <div class="wa-dtu-bulk-actions wa-dtu-bulk-actions-<?php echo esc_html( $args['bulk_action_position'] ) ?>">
            <label for="bulk-action-selector-<?php echo esc_html( $args['bulk_action_position'] ) ?>"
                   class="screen-reader-text"><?php esc_html_e( 'Select bulk action', 'woo-assistant' ) ?></label>
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
            <button class="wa-button wa-button-secondary"
                    type="button"><?php esc_html_e( 'Apply', 'woo-assistant' ) ?></button>
        </div>
    <?php }

    if ( $args['sortable'] ) {
        echo '<button class="wa-button wa-button-secondary wa-dtu-save-changes" type="button" disabled>' . esc_html__( 'Save changes', 'woo-assistant' ) . '</button>';
    }
    ?>
</div>
