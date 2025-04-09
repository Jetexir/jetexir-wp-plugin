<?php

use WooAssistant\Providers\UI\AbstractDataTableUI;

defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
	return;
}

if ( $args['has_bulk_action'] && isset( $args[ $args['bulk_action_position'] . '_bulk_action' ] ) && $args[ $args['bulk_action_position'] . '_bulk_action' ] ) {
	?>
    <div class="wa-dtu-bulk-actions wa-dtu-bulk-actions-<?php echo $args['bulk_action_position'] ?>">
        <label for="bulk-action-selector-<?php echo $args['bulk_action_position'] ?>"
               class="screen-reader-text"><?php _e( 'Select bulk action' ) ?></label>
        <select name="action" id="bulk-action-selector-<?php echo $args['bulk_action_position'] ?>">
            <option value="">---</option>
			<?php
			foreach ( $args['actions'] as $key => $action ) {
				if ( $action['flag'] === AbstractDataTableUI::ACTION_BULK ) {
					echo '<option value="' . $key . '" data-action-type="' . $action['type'] . '">' . $action['title'] . '</option>';
				}
			}
			?>
        </select>
        <button class="wa-button wa-button-secondary" type="button"><?php _e( 'Apply' ) ?></button>
    </div>
<?php }