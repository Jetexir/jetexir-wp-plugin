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
        if ( $args['sortable'] ) {
            echo '<th class="wa-dtu-sortable-column"><i class="wa-icon-move-vertical"></th>';
        }

        if ( $args['has_bulk_action'] ) {
            echo '<th class="wa-dtu-select-all-wrap check-column"><label class="wa-checkbox-wrap"><input type="checkbox" class="wa-dtu-select-all"><span class="wa-checkmark"></span></label></th>';
        }

        foreach ( $args['thead'] as $columnKey => $column ) {
            $addClass = [];
            $addAttr  = '';
            if ( $column['hide_on_mobile'] ) {
                $addClass[] = 'wa-dtu-col-hide-on-mobile';
            }
            if ( $column['is_sortable'] ) {
                $addClass[] = 'is_sortable';

                if ( $column['order_by_field'] == $args['order_by'] ) {
                    $addClass[] = 'active_order_field';
                    $addAttr    .= ' data-order-type="' . ( $args['order_by_type'] === 'ASC' ? 'ASC' : 'DESC' ) . '"';
                }
            }
            $addClass = empty( $addClass ) ? '' : ' class="' . implode( ' ', $addClass ) . '"';

            echo '<th data-column="' . esc_html( $column['field'] ) . '"' . wp_kses_post( $addClass ) . wp_kses_post( $addAttr ) . '>' . esc_html( $column['name'] ) . '</th>';
        }
        ?>
        <th></th>
    </tr>
    </thead>
    <tbody class="<?php echo $args['sortable'] ? 'ui-sortable' : '' ?>">
    <?php
    if ( empty( $args['tbody'] ) ) {
        echo '<tr><td colspan="100%">' . esc_html__( 'No entries!', 'woo-assistant' ) . '</td></tr>';
    } else {
        foreach ( $args['tbody'] as $index => $row ) {
            $rowId      = $row['id'];
            $attributes = '';
            if ( ! empty( $args['attributes'] ) && is_array( $args['attributes'] ) ) {
                foreach ( $args['attributes'] as $dataName => $dataValues ) {
                    $attributes .= ' data-' . $dataName . '="' . $dataValues . '"';
                }
            }
            ?>
            <tr data-id="<?php echo esc_html( $rowId ) ?>" <?php echo wp_kses_post( $attributes ) . ( ! $row['is_active'] ? ' data-disabled="true"' : '' ) ?>>
                <?php
                if ( $args['sortable'] ) {
                    echo '<td class="wa-dtu-sortable-column sort ui-sortable-handle"><i class="wa-icon-move-vertical"></i><input type="hidden" class="wa-dtu-row-order" name="order[' . esc_html( $rowId ) . ']" value="' . esc_html( $index ) . '" ></td>';
                }
                if ( $args['has_bulk_action'] ) {
                    echo '<td class="check-column"><label class="wa-checkbox-wrap"><input type="checkbox" class="wa-dtu-row-select" value="' . esc_html( $rowId ) . '"><span class="wa-checkmark"></span></label></td>';
                }

                foreach ( $row['data'] as $data ) {
                    $attributes = ' data-column="' . $data['field'] . '" ';
                    foreach ( $data['attributes'] as $dataName => $dataValues ) {
                        $attributes .= ' data-' . $dataName . '="' . $dataValues . '"';
                    }

                    $addClass = [];
                    if ( isset( $args['thead'][ $data['field'] ]['hide_on_mobile'] ) && $args['thead'][ $data['field'] ]['hide_on_mobile'] ) {
                        $addClass[] = 'wa-dtu-col-hide-on-mobile';
                    }
                    $addClass = empty( $addClass ) ? '' : ' class="' . implode( ' ', $addClass ) . '"';

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

                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo '<td ' . $addClass . ' ' . $attributes . '>' . $data['content'] . '</td>';
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
                            <button class="wa-button wa-dtu-action" data-action="<?php echo esc_html( $key ) ?>"
                                    data-action-type="<?php echo esc_html( $action['type'] ) ?>"
                                    type="button" <?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $attributes ?>>
                                <?php echo esc_html( $action['title'] ) ?>
                            </button>
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