<?php

use WooAssistant\Providers\UI\AbstractDataTableUI;

defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
	return;
}
?>

<table class="wa-dtu-table">
    <thead>
    <tr>
		<?php
		if ( $args['has_bulk_action'] ) {
			echo '<th class="wa-dtu-select-all-wrap check-column"><label class="wa-checkbox-wrap"><input type="checkbox" class="wa-dtu-select-all"><span class="wa-checkmark"></span></label></th>';
		}

		foreach ( $args['thead'] as $columnKey => $column ) {
			$addClass = [];
			$addAttr  = '';
			if ( $column['is_sortable'] ) {
				$addClass[] = 'is_sortable';

				if ( $column['order_by_field'] == $args['order_by'] ) {
					$addClass[] = 'active_order_field';
					$addAttr    .= ' data-order-type="' . ( $args['order_by_type'] === 'ASC' ? 'ASC' : 'DESC' ) . '"';
				}
			}
			$addClass = empty( $addClass ) ? '' : ' class="' . implode( ' ', $addClass ) . '"';

			echo '<th data-column="' . $column['field'] . '"' . $addClass . $addAttr . '>' . htmlspecialchars( $column['name'] ) . '</th>';
		}
		?>
        <th></th>
    </tr>
    </thead>
    <tbody>
	<?php
	if ( empty( $args['tbody'] ) ) {
		echo '<tr><td colspan="100%">' . __( 'No entries!', 'woo-assistant' ) . '</td></tr>';
	} else {
		foreach ( $args['tbody'] as $row ) {
			$rowId      = $row['id'];
			$attributes = '';
			foreach ( $args['attributes'] as $dataName => $dataValues ) {
				$attributes .= ' data-' . $dataName . '="' . $dataValues . '"';
			}
			?>
            <tr data-id="<?php echo $rowId ?>"<?php echo $row['attributes'] . ( ! $row['is_active'] ? ' data-disabled="true"' : '' ) ?>>
				<?php
				if ( $args['has_bulk_action'] ) {
					echo '<td class="check-column"><label class="wa-checkbox-wrap"><input type="checkbox" class="wa-dtu-row-select" value="' . $rowId . '"><span class="wa-checkmark"></span></label></td>';
				}

				foreach ( $row['data'] as $data ) {
					$attributes = ' data-column="' . $data['field'] . '" ';
					foreach ( $data['attributes'] as $dataName => $dataValues ) {
						$attributes .= ' data-' . $dataName . '="' . $dataValues . '"';
					}

					if ( $data['field'] === AbstractDataTableUI::ACTIVE_FIELD &&
					     in_array( $data['content'], [ '1', '0' ], true ) ) {
						$data['content'] = \WooAssistant\Helper\HTML::toggle( array(
							'id'            => $args['id'] . '_row_active_' . $rowId,
							'type'          => 'toggle',
							'value'         => 1,
							'default'       => true,
							'setting_value' => (bool) $data['content'],
							'attributes'    => [ 'disabled' => 'disabled' ],
							'wrap'          => false
						) );
					}

					echo '<td' . $attributes . '>' . $data['content'] . '</td>';
				}

				echo '<td class="wa-dtu-actions-wrap">';
				if ( ! empty( $args['actions'] ) ) {
					echo '<div class="wa-dtu-actions">';
					foreach ( $args['actions'] as $key => $action ) {
						if ( $action['flag'] === AbstractDataTableUI::ACTION_SINGLE ) {
							$attributes = [];
							if ( $action['type'] === AbstractDataTableUI::ACTION_EDIT ) {
								$attributes['data-modal-title']          = $args['modal_edit_title'];
								$attributes['data-primary-button-text']  = $args['modal_edit_button'];
								$attributes['data-display-active-field'] = (int) $args['display_active_field'];
								$attributes['data-active-field']         = (int) $row['is_active'];
								$attributes['data-wa-toggle']            = 'modal';
								$attributes['data-wa-target']            = '#wa-data-table-ui-modal';
							}
							$attributes = \WooAssistant\Helper\HTML::getAttributes( $action, $attributes );
							?>
                            <button class="wa-button wa-dtu-action" data-action="<?php echo $key ?>"
                                    data-action-type="<?php echo $action['type'] ?>"
                                    type="button" <?php echo $attributes ?>><?php echo $action['title'] ?></button>
						<?php }
					}
					echo '</div>';
				}
				echo '</td>';
				?>
            </tr>
			<?php
		}
	}
	?>
    </tbody>
</table>