<?php

use Jetexir\Helper\Templates;

defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
  return;
}

?>

<div id="<?php echo esc_html( $args['component_id'] ) ?>"
     class="jetexir-data-table-ui <?php echo $args['sortable'] ? 'jetexir-data-table-sortable' : '' ?>"
     data-id="<?php echo esc_html( ! empty( $args['id'] ) ? $args['id'] : str_replace( 'jetexir-datatable-', '', $args['component_id'] ) ) ?>">
  <div class="jetexir-loader-wrap" style="display: none">
    <div class="jetexir-loader"></div>
  </div>
  <?php
  if ( ! empty( $args['title'] ) || ! empty( $args['row_count'] ) || ! empty( $args['add_new_button'] ) ) {
    echo '<div class="jetexir-dtu-head">';
    if ( ! empty( $args['title'] ) || ! empty( $args['row_count'] ) ) {
      echo '<div class="jetexir-dtu-title-wrap">';

      if ( ! empty( $args['row_count'] ) ) {
        echo '<span class="jetexir-badge jetexir-dtu-row-count">' . esc_html( $args['row_count'] ) . '</span>';
      }
      if ( ! empty( $args['title'] ) ) {
        echo '<div class="jetexir-dtu-title">';
        echo '<span>' . esc_html( $args['title'] ) . '</span>';
        if ( ! empty( $args['description'] ) ) {
          echo '<span class="jetexir-dtu-description">' . esc_html( $args['description'] ) . '</span>';
        }
        echo '</div>';
      }
      echo '</div>';
    }

    if ( ! empty( $args['add_new_button'] ) ) {
      echo '<button class="jetexir-button jetexir-button-primary jetexir-dtu-add-new" data-modal-title="' . esc_html( $args['modal_add_title'] ) . '" data-primary-button-text="' . esc_html( $args['modal_add_button'] ) . '" data-display-active-field="' . ( (int) $args['display_active_field'] ) . '" data-active-field="' . ( (int) $args['active_field'] ) . '" data-jetexir-toggle="modal" data-jetexir-target="#jetexir-data-table-ui-modal" type="button">' . esc_html( $args['add_new_button'] ) . '</button>';
    }
    echo '</div>';
  }
  ?>

  <div class="jetexir-dtu-body">
    <?php
    Templates::load( Templates::getPath( 'data-table/data_table_bulk_action.php' ), array_merge( $args, [ 'bulk_action_position' => 'top' ] ) );
    Templates::load( Templates::getPath( 'data-table/data_table_table.php' ), $args );
    Templates::load( Templates::getPath( 'data-table/data_table_bulk_action.php' ), array_merge( $args, [ 'bulk_action_position' => 'bottom' ] ) );
    ?>
  </div>
</div>
