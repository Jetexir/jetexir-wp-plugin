<?php

namespace WooAssistant\AppHelper;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Admin\AdminPages;
use WooAssistant\Admin\AdminSettings;
use WooAssistant\Helper\HTML;
use WooAssistant\Helper\Nonce;
use WooAssistant\Helper\Param;
use WooAssistant\Helper\Sanitizing;
use WooAssistant\Helper\Templates;

class DataTableUI {
	public function __construct() {
		add_action( 'wp_ajax_woo_assistant_data_table_ui_action', [ $this, 'doAction' ] );
		add_action( 'woo_assistant_admin_modals', [ $this, 'printModal' ] );
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
				$value = $postedData[ WOOASSISTANT_INPUT_PREFIX . $field['id'] ] ?? false;
			} else {
				$default = AdminSettings::getSettingDefault( $field );
				$value   = $postedData[ WOOASSISTANT_INPUT_PREFIX . $field['id'] ] ?? $default;
			}

			$value = AdminSettings::sanitizeSetting( $value, $field );

			if ( is_array( $value ) ) {
				$value = AdminSettings::sanitizeOptionsSetting( $value, $field );
			}

			if ( $field['type'] === 'colorpalette' ) {
				$value = array_values( $value );
			}

			$value                = apply_filters( 'woo_assistant_dtu_value_before_save', $value, $field );
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
		if ( Nonce::verify() && current_user_can( 'manage_options' ) ) {
			$dataTableID = Sanitizing::text( Param::post( 'data_table_id' ) );
			$rowID       = Sanitizing::text( Param::post( 'row_id' ) );
			$rowAction   = Sanitizing::text( Param::post( 'row_action' ) );

			do_action( 'woo_assistant_data_table_ui_' . $dataTableID . '_action', $rowID, $rowAction );
			do_action( 'woo_assistant_data_table_ui_action', $dataTableID, $rowID, $rowAction );

			wp_send_json_error( [
				'error'   => 'not-action',
				'message' => __( 'Not registered action for this data table:', 'wc-assistant' ) . ' ' . $dataTableID,
			], 403 );
		}

		wp_send_json_error( [
			'error'   => 'nonce-invalid',
			'message' => __( 'Security code is not valid, page will be refreshed.', 'wc-assistant' ),
			'refresh' => true
		], 403 );
	}
}