<?php

namespace AssistantForWooCommerce\AppHelper;

defined( 'ABSPATH' ) || exit;

use AssistantForWooCommerce\Admin\AdminPages;
use AssistantForWooCommerce\Admin\AdminSettings;
use AssistantForWooCommerce\Helper\{HTML, Nonce, Param, Sanitizing, Templates, User};

class DataTableUI {
  public function __construct() {
    add_action( 'wp_ajax_assistant_for_woocommerce_data_table_ui_action', [ $this, 'doAction' ] );
    add_action( 'assistant_for_woocommerce_admin_modals', [ $this, 'printModal' ] );
  }

  public static function getFormData( $fields ): array {
    $postedData = Param::decodeSerialize( Param::post( 'form_data' ) );
    $data       = [
      'is_active' => Sanitizing::bool( Param::post( 'row_active' ) )
    ];

    foreach ( $fields as $field ) {
      if ( isset( $data[ $field['id'] ] ) ) {
        continue;
      }

      $field['type'] = strtolower( $field['type'] );

      if ( ( isset( $field['save'] ) && $field['save'] === false ) ||
           ! in_array( $field['type'], HTML::saveFields, true ) ) {
        continue;
      }

      if ( in_array( $field['type'], [ 'checkbox', 'checkboxinline', 'toggle' ] ) ) {
        $value = $postedData[ ASSISTANTFORWOOCOMMERCE_INPUT_PREFIX . $field['id'] ] ?? false;
      } else {
        $default = AdminSettings::getSettingDefault( $field );
        $value   = $postedData[ ASSISTANTFORWOOCOMMERCE_INPUT_PREFIX . $field['id'] ] ?? $default;
      }

      $value = AdminSettings::sanitizeSetting( $value, $field );

      if ( is_array( $value ) ) {
        $value = AdminSettings::sanitizeOptionsSetting( $value, $field );
      }

      if ( $field['type'] === 'colorpalette' ) {
        $value = array_values( $value );
      }

      $value                = apply_filters( 'assistant_for_woocommerce_dtu_value_before_save', $value, $field );
      $data[ $field['id'] ] = $value;
    }

    return $data;
  }

  public function printModal(): void {
    if ( ! AdminPages::isSettingPage() ) {
      return;
    }

    Templates::load( Templates::getPath( 'data-table/data_table_modal.php' ) );
  }

  public function doAction(): void {
    if ( Nonce::verify() && User::can( 'manage_options' ) ) {
      $dataTableID = Sanitizing::text( Param::post( 'data_table_id' ) );
      $rowID       = Sanitizing::text( Param::post( 'row_id' ) );
      $rowAction   = Sanitizing::text( Param::post( 'row_action' ) );

      do_action( 'assistant_for_woocommerce_data_table_ui_' . $dataTableID . '_action', $rowID, $rowAction );
      do_action( 'assistant_for_woocommerce_data_table_ui_action', $dataTableID, $rowID, $rowAction );

      wp_send_json_error( [
        'error'   => 'not-action',
        'message' => esc_html__( 'Not registered action for this data table:', 'assistant-for-woocommerce' ) . ' ' . $dataTableID,
      ], 403 );
    }

    wp_send_json_error( [
      'error'   => 'nonce-invalid',
      'message' => esc_html__( 'Security code is not valid, page will be refreshed.', 'assistant-for-woocommerce' ),
      'refresh' => true
    ], 403 );
  }
}
