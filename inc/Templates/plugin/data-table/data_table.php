<?php

use WooAssistant\Helper\Templates;

defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
	return;
}

?>

<div id="<?php echo $args['component_id'] ?>"
     class="wa-data-table-ui <?php echo $args['sortable'] ? 'wa-data-table-sortable' : '' ?>"
     data-id="<?php echo ! empty( $args['id'] ) ? $args['id'] : str_replace( 'wa-datatable-', '', $args['component_id'] ) ?>">
    <div class="wa-loader-wrap" style="display: none">
        <div class="wa-loader"></div>
    </div>
	<?php
	if ( ! empty( $args['title'] ) || ! empty( $args['row_count'] ) || ! empty( $args['add_new_button'] ) ) {
		echo '<div class="wa-dtu-head">';
		if ( ! empty( $args['title'] ) || ! empty( $args['row_count'] ) ) {
			echo '<div class="wa-dtu-title-wrap">';

			if ( ! empty( $args['row_count'] ) ) {
				echo '<span class="wa-badge wa-dtu-row-count">' . $args['row_count'] . '</span>';
			}
			if ( ! empty( $args['title'] ) ) {
				echo '<div class="wa-dtu-title">';
				echo '<span>' . esc_html( $args['title'] ) . '</span>';
				if ( ! empty( $args['description'] ) ) {
					echo '<span class="wa-dtu-description">' . esc_html( $args['description'] ) . '</span>';
				}
				echo '</div>';
			}
			echo '</div>';
		}

		if ( ! empty( $args['add_new_button'] ) ) {
			echo '<button class="wa-button wa-button-primary wa-dtu-add-new" data-modal-title="' . $args['modal_add_title'] . '" data-primary-button-text="' . $args['modal_add_button'] . '" data-display-active-field="' . ( (int) $args['display_active_field'] ) . '" data-active-field="' . ( (int) $args['active_field'] ) . '" data-wa-toggle="modal" data-wa-target="#wa-data-table-ui-modal" type="button">' . $args['add_new_button'] . '</button>';
		}
		echo '</div>';
	}
	?>

    <div class="wa-dtu-body">
		<?php
		Templates::load( Templates::getPath( 'data-table/data_table_bulk_action.php' ), array_merge( $args, [ 'bulk_action_position' => 'top' ] ), false );
		Templates::load( Templates::getPath( 'data-table/data_table_table.php' ), $args );

		if ( $args['sortable'] ) {

		}

		Templates::load( Templates::getPath( 'data-table/data_table_bulk_action.php' ), array_merge( $args, [ 'bulk_action_position' => 'bottom' ] ), false );
		?>
    </div>
</div>
