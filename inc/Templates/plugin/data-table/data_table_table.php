<?php

use AssistantForWooCommerce\Providers\UI\AbstractDataTableUI;

defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
    return;
}
?>

<table class="asfowoo-dtu-table">
    <thead>
    <tr>
        <?php
        if ( $args['sortable'] ) {
            echo '<th class="asfowoo-dtu-sortable-column"><i class="asfowoo-icon-move-vertical"></th>';
        }

        if ( $args['has_bulk_action'] ) {
            echo '<th class="asfowoo-dtu-select-all-wrap check-column"><label class="asfowoo-checkbox-wrap"><input type="checkbox" class="asfowoo-dtu-select-all"><span class="asfowoo-checkmark"></span></label></th>';
        }

        foreach ( $args['thead'] as $columnKey => $column ) {
            $addClass = [];
            $addAttr  = '';
            if ( $column['hide_on_mobile'] ) {
                $addClass[] = 'asfowoo-dtu-col-hide-on-mobile';
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
        echo '<tr><td colspan="100%">' . esc_html__( 'No entries!', 'assistant-for-woocommerce' ) . '</td></tr>';
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
                    echo '<td class="asfowoo-dtu-sortable-column sort ui-sortable-handle"><i class="asfowoo-icon-move-vertical"></i><input type="hidden" class="asfowoo-dtu-row-order" name="order[' . esc_html( $rowId ) . ']" value="' . esc_html( $index ) . '" ></td>';
                }
                if ( $args['has_bulk_action'] ) {
                    echo '<td class="check-column"><label class="asfowoo-checkbox-wrap"><input type="checkbox" class="asfowoo-dtu-row-select" value="' . esc_html( $rowId ) . '"><span class="asfowoo-checkmark"></span></label></td>';
                }

                foreach ( $row['data'] as $data ) {
                    $attributes = ' data-column="' . $data['field'] . '" ';
                    foreach ( $data['attributes'] as $dataName => $dataValues ) {
                        $attributes .= ' data-' . $dataName . '="' . $dataValues . '"';
                    }

                    $addClass = [];
                    if ( isset( $args['thead'][ $data['field'] ]['hide_on_mobile'] ) && $args['thead'][ $data['field'] ]['hide_on_mobile'] ) {
                        $addClass[] = 'asfowoo-dtu-col-hide-on-mobile';
                    }
                    $addClass = empty( $addClass ) ? '' : ' class="' . implode( ' ', $addClass ) . '"';

                    if ( $data['field'] === AbstractDataTableUI::ACTIVE_FIELD &&
                         in_array( $data['content'], [ '1', '0' ], true ) ) {
                        $data['content'] = \AssistantForWooCommerce\Helper\HTML::toggle( array(
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

                echo '<td class="asfowoo-dtu-actions-wrap">';
                if ( ! empty( $args['actions'] ) ) {
                    echo '<div class="asfowoo-dtu-actions">';
                    foreach ( $args['actions'] as $key => $action ) {
                        if ( $action['flag'] === AbstractDataTableUI::ACTION_SINGLE ) {
                            $attributes = [];
                            if ( $action['type'] === AbstractDataTableUI::ACTION_EDIT ) {
                                $attributes['data-modal-title']          = $args['modal_edit_title'];
                                $attributes['data-primary-button-text']  = $args['modal_edit_button'];
                                $attributes['data-display-active-field'] = (int) $args['display_active_field'];
                                $attributes['data-active-field']         = (int) $row['is_active'];
                                $attributes['data-asfowoo-toggle']       = 'modal';
                                $attributes['data-asfowoo-target']       = '#asfowoo-data-table-ui-modal';
                            }
                            $attributes = \AssistantForWooCommerce\Helper\HTML::getAttributes( $action, $attributes );
                            ?>
                            <button class="asfowoo-button asfowoo-dtu-action"
                                    data-action="<?php echo esc_html( $key ) ?>"
                                    data-action-type="<?php echo esc_html( $action['type'] ) ?>"
                                    type="button" <?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $attributes ?>>
                                <?php echo wp_kses_post( $action['title'] ) ?>
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