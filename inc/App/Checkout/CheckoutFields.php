<?php

namespace WooAssistant\App\Checkout;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Addons\Addon;
use WooAssistant\Helper\Helper;
use WooAssistant\Helper\HTML;
use WooAssistant\Helper\Notice;
use WooAssistant\Helper\Param;
use WooAssistant\Helper\Sanitizing;
use WooAssistant\Helper\Templates;
use WooAssistant\Helper\WooCommerce;
use WooAssistant\Interfaces\AddonInterface;
use WooAssistant\Providers\UI\DataTableUI;

class CheckoutFields extends Addon implements AddonInterface {
	public string $addonID = 'checkout-fields';

	public string $currentTab = 'checkout';

	public function initAction(): void {
		add_action( 'woo_assistant_data_table_ui_action', [ $this, 'dataTableActions' ], 10, 3 );

		if ( $this->getSetting( 'checkout_fields_type', 'classic' ) === 'classic' ) {
			add_filter( 'woocommerce_checkout_fields', [ $this, 'addCustomField' ] );
		}
	}

	public function addCustomField( $fields ): array {
		// Add the custom radio buttons
		$fields['billing']['billing_address_type'] = array(
			'label'    => __( 'Address Type' ),
			'type'     => 'radio',
			'class'    => array( 'form-row-wide', 'address-type' ),
			'required' => true,
			'priority' => 85,
			'options'  => array(
				'home' => __( 'Home (9AM-9PM)', 'woocommerce' ),
				'work' => __( 'Work (9AM-6PM)', 'woocommerce' ),
			),
		);

		return $fields;
	}

	public function dataTableActions( $dataTableID, $index, $action ): void {
		if ( ! in_array( $dataTableID, [
			'billing_fields_classic',
			'shipping_fields_classic',
			'additional_fields_classic'
		] ) ) {
			return;
		}

		if ( $action === 'bulk_action' ) {
			$bulkAction = Sanitizing::text( Param::post( 'bulk_action' ) );
			$rowIDs     = array_map( 'WooAssistant\Helper\Sanitizing::int', Sanitizing::array( Param::post( 'row_ids' ) ) );
			$entries    = $this->getSetting( $dataTableID, [] );

			foreach ( $entries as $entryIndex => $status ) {
				if ( in_array( $entryIndex, $rowIDs, true ) ) {
					if ( $bulkAction === 'bulk_delete' ) {
						unset( $entries[ $entryIndex ] );

					} elseif ( $bulkAction === 'bulk_enable' ) {
						$entries[ $entryIndex ]['is_active'] = true;

					} elseif ( $bulkAction === 'bulk_disable' ) {
						$entries[ $entryIndex ]['is_active'] = false;
					}
				}
			}

			$entries = array_values( $entries );
			$this->saveSetting( $dataTableID, $entries );
			$dataTable = $this->getDataTable( $dataTableID );

			wp_send_json_success( [
				'table'     => $dataTable->renderHTML( Templates::getPath( 'data-table/data_table_table.php' ) ),
				'row_count' => $dataTable->getRowCount(),
			] );

		} elseif ( $action === 'save_changes' ) {
			$rowOrders = Sanitizing::array( Param::post( 'row_orders' ) );
			$entries   = $this->getSetting( $dataTableID, [] );
			$entries   = Helper::reorderArray( $entries, $rowOrders );

			if ( is_array( $entries ) ) {
				$this->saveSetting( $dataTableID, $entries );
			}

			$dataTable = $this->getDataTable( $dataTableID );

			wp_send_json_success( [
				'table'     => $dataTable->renderHTML( Templates::getPath( 'data-table/data_table_table.php' ) ),
				'row_count' => $dataTable->getRowCount(),
			] );

		} elseif ( $action === 'add_form' || $action === 'edit' ) {
			$data = [];
			if ( $index >= 0 && $entry = $this->getByIndex( $index ) ) {
				$data = $entry;
			}

			$form = HTML::printFields( $this->getFields( $index, $data ), false );

			wp_send_json_success( [ 'content' => $form ] );

		} elseif ( $action === 'save_form' ) {
			$formData     = \WooAssistant\AppHelper\DataTableUI::getFormData( $this->getFields() );
			$errorMessage = '';
			$entry        = false;

			if ( empty( $formData['title'] ) ) {
				$errorMessage = sprintf( __( '%s field is empty!', 'woo-assistant' ), __( 'Title', 'woo-assistant' ) );
			} elseif ( empty( $formData['slug'] ) ) {
				$errorMessage = sprintf( __( '%s field is empty!', 'woo-assistant' ), __( 'Slug', 'woo-assistant' ) );
			}

			if ( $index >= 0 ) {
				$entry = $this->getByIndex( $dataTableID, $index );

				if ( $entry === false ) {
					$errorMessage = __( 'Order status not found!', 'woo-assistant' );
				}
			}

			if ( ! empty( $errorMessage ) ) {
				wp_send_json_error( [
					'error'   => 'required-field',
					'message' => Notice::addAndDisplay( $this->addonID, array(
						array(
							'type'    => 'error',
							'message' => $errorMessage
						)
					), false ),
				], 403 );
			}

			$formData = array_map( 'trim', $formData );

			if ( $entry !== false ) {
				$entries           = $this->getSetting( $dataTableID, [] );
				$entries[ $index ] = $formData;
				$this->saveSetting( $dataTableID, $entries );
				$successMessage = __( 'The order status was successfully saved.', 'woo-assistant' );

			} else {
				$this->addToArraySetting( $dataTableID, $formData, true );
				$successMessage = __( 'Order status added successfully.', 'woo-assistant' );
			}

			$dataTable = $this->getDataTable( $dataTableID );

			wp_send_json_success( [
				'table'     => $dataTable->renderHTML( Templates::getPath( 'data-table/data_table_table.php' ) ),
				'row_count' => $dataTable->getRowCount(),
				'message'   => Notice::addAndDisplay( $this->addonID, array(
					array(
						'type'    => 'success',
						'message' => $successMessage,
					)
				), false )
			] );

		} elseif ( $action === 'delete' ) {
			$entry = $this->getByIndex( $dataTableID, $index );

			if ( $this->deleteFromArraySetting( $dataTableID, $index ) ) {
				$dataTable = $this->getDataTable( $dataTableID );

				wp_send_json_success( [
					'table'     => $dataTable->renderHTML( Templates::getPath( 'data-table/data_table_table.php' ) ),
					'row_count' => $dataTable->getRowCount(),
					'message'   => Notice::addAndDisplay( $this->addonID, array(
						array(
							'type'    => 'success',
							'message' => __( 'Order status removed!', 'woo-assistant' ),
						)
					), false ),
				] );

			} else {
				wp_send_json_error( [
					'error'   => 'required-field',
					'message' => Notice::addAndDisplay( $this->addonID, array(
						array(
							'type'    => 'error',
							'message' => __( 'Selected item not found!', 'woo-assistant' ),
						)
					), false ),
				], 403 );
			}
		}
	}

	private function getByIndex( $key, $index ) {
		$entries = $this->getSetting( $key, [] );
		if ( is_array( $entries ) && ! empty( $entries ) && isset( $entries[ $index ] ) ) {
			return $entries[ $index ];
		}

		return false;
	}

	private function getFields( $index = - 1, $data = [] ): array {
		return array(
			array(
				'id'            => 'row_id',
				'type'          => 'hidden',
				'save'          => false,
				'setting_value' => $index
			),
			array(
				'id'            => 'text_color',
				'title'         => __( 'Text color', 'woo-assistant' ),
				'type'          => 'wpColorPicker',
				'setting_value' => $data['text_color'] ?? '#333',
				'sanitize'      => 'color'
			),
			array(
				'id'            => 'bg_color',
				'title'         => __( 'Background color', 'woo-assistant' ),
				'type'          => 'wpColorPicker',
				'setting_value' => $data['bg_color'] ?? '#ebe5ff',
				'sanitize'      => 'color'
			),
			array(
				'id'            => 'row_bg_color',
				'title'         => __( 'Row background color', 'woo-assistant' ),
				'type'          => 'wpColorPicker',
				'setting_value' => $data['row_bg_color'] ?? '',
				'sanitize'      => 'color'
			),
			array(
				'id'            => 'title',
				'title'         => __( 'Title', 'woo-assistant' ),
				'placeholder'   => __( 'Status title', 'woo-assistant' ),
				'type'          => 'text',
				'setting_value' => $data['title'] ?? '',
			),
			array(
				'id'            => 'slug',
				'title'         => __( 'Slug', 'woo-assistant' ),
				'desc'          => __( 'Use english alphabetic characters', 'woo-assistant' ),
				'placeholder'   => __( 'Status slug', 'woo-assistant' ),
				'type'          => 'text',
				'setting_value' => $data['slug'] ?? '',
				'sanitize'      => 'title',
			)
		);
	}

	private function getDataTable( $id ): DataTableUI {
		$dataTable = new DataTableUI();
		$dataTable->setID( $id )
		          ->setRows( $this->getSetting( $id, [] ) )
		          ->setIdField( $dataTable::ROW_INDEX )
		          ->modalAddTitle( __( 'Add new field', 'woo-assistant' ) )
		          ->modalEditTitle( __( 'Edit field', 'woo-assistant' ) )
		          ->addNewButton( __( 'Add new', 'woo-assistant' ) )
		          ->sortable( true )
		          ->addAction( 'edit', '<i class="wa-icon-edit"></i>', $dataTable::ACTION_EDIT )
		          ->addAction( 'delete', '<i class="wa-icon-trash"></i>', $dataTable::ACTION_DELETE )
		          ->addAction( 'bulk_enable', __( 'Enable', 'woo-assistant' ), $dataTable::ACTION_NONE, [], $dataTable::ACTION_BULK )
		          ->addAction( 'bulk_disable', __( 'Disable', 'woo-assistant' ), $dataTable::ACTION_NONE, [], $dataTable::ACTION_BULK )
		          ->addAction( 'bulk_delete', __( 'Delete', 'woo-assistant' ), $dataTable::ACTION_DELETE, [], $dataTable::ACTION_BULK )
		          ->addColumn( __( 'Title', 'woo-assistant' ), 'title', function ( $entry ) {
			          return '<mark class="order-status status-' . $entry['slug'] . '"><span>' . $entry['title'] . '</span></mark>';
		          }, [ 'is_html' => true ] )
		          ->addColumn( __( 'Slug', 'woo-assistant' ), 'slug' )
		          ->addColumn( __( 'Status', 'woo-assistant' ), $dataTable::ACTIVE_FIELD );

		if ( $id === 'billing_fields_classic' ) {
			$dataTable->setTitle( __( 'Billing Fields', 'woo-assistant' ) );
		}

		return $dataTable;
	}

	private function getWooFields( $type ): array {
		$fields = array();

		if ( empty( $fields ) ) {
			if ( $type === 'billing' || $type === 'shipping' ) {
				$fields = WooCommerce::getCheckoutFields( $type );
			}

			foreach ( $fields as $key => $value ) {
				$fields[ $key ]['custom']        = 0;
				$fields[ $key ]['enabled']       = 1;
				$fields[ $key ]['show_in_email'] = 1;
				$fields[ $key ]['show_in_order'] = 1;
			}
		}

		return $fields;
	}

	public function addSectionSettings( $sections ): array {
		$type   = ! $this->getSetting( 'checkout_fields_type', false ) && WooCommerce::hasBlockInPage( wc_get_page_id( 'checkout' ), 'woocommerce/checkout' ) ? 'blocks' : 'classic';
		$fields = $this->getWooFields( 'billing' );
		//self::dd($fields);

		$settings = array(
			'checkout_fields_type_start_grid' => array(
				'id'    => 'checkout_fields_type_start_grid',
				'title' => __( 'Checkout Fields', 'woo-assistant' ),
				'type'  => 'startGrid',
			),
			'checkout_fields_type'            => array(
				'id'        => 'checkout_fields_type',
				'title'     => __( 'Checkout page type', 'woo-assistant' ),
				'type'      => 'radioInline',
				'default'   => $type,
				'options'   => array(
					'classic' => __( 'Classic', 'woo-assistant' ),
					'blocks'  => __( 'Blocks', 'woo-assistant' ),
				),
				'not_equal' => true,
				'sanitize'  => 'text'
			),
			'checkout_fields_type_end_grid'   => array(
				'type' => 'endGrid',
			),
		);

		if ( $this->getSetting( 'checkout_fields_type', $type ) === 'blocks' ) {
			$fields = array();

		} else {
			$fields = array(
				'billing_fields_classic_dtu' => array(
					'id'         => 'billing_fields_classic',
					'type'       => 'dataTable',
					'data_table' => $this->getDataTable( 'billing_fields_classic' )->render()
				),
			);
		}

		$settings = array_merge( $settings, $fields );

		$sections[ $this->addonID ] = array(
			'title'        => __( 'Fields', 'woo-assistant' ),
			'desc'         => __( 'Checkout fields manager', 'woo-assistant' ),
			'settings_key' => $this->info()['settings_key'],
			'settings'     => $settings
		);


		return $sections;
	}

	public function info(): array {
		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Checkout Fields', 'woo-assistant' ),
			'desc'           => __( 'Customize WooCommerce checkout fields', 'woo-assistant' ),
			'tags'           => [ __( 'Checkout', 'woo-assistant' ) ],
			'cat'            => 'checkout',
			'more_info_link' => 'https://parsa.ws',
			'settings_key'   => $this->addonID,
		);
	}
}