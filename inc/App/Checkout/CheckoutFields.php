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

	private array $checkoutSections = [ 'billing', 'shipping', 'order' ];

	public function initAction(): void {
		add_action( 'woo_assistant_data_table_ui_action', [ $this, 'dataTableActions' ], 10, 3 );

		if ( $this->getSetting( 'checkout_fields_type', 'classic' ) === 'classic' ) {
			add_filter( 'woocommerce_checkout_fields', [ $this, 'addCustomField' ], 0 );
			add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'saveCustomField' ], 10, 2 );

			// Add field value to order email
			add_action( 'woocommerce_email_after_order_table', [ $this, 'addCustomFieldToEmail' ] );

			// Add field value to user order detail
			add_action( 'woocommerce_order_details_after_order_table', [ $this, 'addCustomFieldToOrderDetails' ] );

			// Display field value in admin order detail
			add_action( 'woocommerce_admin_order_data_after_billing_address', [ $this, 'addCustomFieldToOrder' ] );
			add_action( 'woocommerce_admin_order_data_after_shipping_address', [ $this, 'addCustomFieldToOrder' ] );
			add_action( 'woocommerce_admin_order_data_after_order_details', [ $this, 'addCustomFieldToOrder' ] );
		}
	}

	public function addCustomFieldToOrder( $order ): void {
		$orderID = $order->get_id();
		$action  = current_action();
		$section = 'billing';

		if ( $action === 'woocommerce_admin_order_data_after_shipping_address' ) {
			$section = 'shipping';
		} elseif ( $action === 'woocommerce_admin_order_data_after_order_details' ) {
			$section = 'order';
		}

		$fields = $this->getRows( $section . '_fields_classic' );

		foreach ( $fields as $field ) {
			if ( ! isset( $field['custom'] ) || ! $field['custom'] ) {
				continue;
			}
			$value = WooCommerce::getOrderMeta( $orderID, '_' . $field['name'] );

			if ( is_array( $value ) ) {
				$value = implode( ', ', $value );
			}

			echo '<p class="form-field form-field-wide"><strong>' . $field['label'] . ':</strong> ' . $value . '</p>';
		}
	}

	public function addCustomFieldToOrderDetails( $order ): void {
		$orderID = $order->get_id();
		$output  = '';

		foreach ( $this->checkoutSections as $section ) {
			$fieldsOutput = '';
			$fields       = $this->getRows( $section . '_fields_classic' );

			foreach ( $fields as $field ) {
				if ( ! isset( $field['custom'], $field['display_in_order'] ) || ! $field['custom'] || ! $field['display_in_order'] ) {
					continue;
				}
				$value = WooCommerce::getOrderMeta( $orderID, '_' . $field['name'] );

				if ( is_array( $value ) ) {
					$value = implode( ', ', $value );
				}

				$fieldsOutput .= '<tr><th>' . $field['label'] . '</th><td>' . $value . '</td></tr>';
			}

			if ( ! empty( $fieldsOutput ) ) {
				$output .= '<tr><td colspan="2">' . __( ucfirst( $section ), 'woo-assistant' ) . '</td></tr>' . $fieldsOutput;
			}
		}

		if ( ! empty( $output ) ) {
			echo '<table class="woocommerce-table shop_table order_details has-background wa-checkout-fields-order-meta">' . $output . '</table>';
		}
	}

	public function addCustomFieldToEmail( $order ): void {
		$orderID = $order->get_id();
		$output  = '';
		foreach ( $this->checkoutSections as $section ) {
			$fieldsOutput = '';
			$fields       = $this->getRows( $section . '_fields_classic' );

			foreach ( $fields as $field ) {
				if ( ! isset( $field['custom'], $field['display_in_email'] ) || ! $field['custom'] || ! $field['display_in_email'] ) {
					continue;
				}
				$value = WooCommerce::getOrderMeta( $orderID, '_' . $field['name'] );

				if ( is_array( $value ) ) {
					$value = implode( ', ', $value );
				}

				$fieldsOutput .= '<tr><th>' . $field['label'] . '</th><td>' . $value . '</td></tr>';
			}

			if ( ! empty( $fieldsOutput ) ) {
				$output .= '<tr><td colspan="2">' . __( ucfirst( $section ), 'woo-assistant' ) . '</td></tr>' . $fieldsOutput;
			}
		}

		if ( ! empty( $output ) ) {
			echo '<table style="color:#525252;border:1px solid #e5e5e5;width:100%;vertical-align:middle;margin-bottom: 30px;">' . $output . '</table>';
		}
	}

	public function saveCustomField( $orderID, $data ): void {
		$shipToDifferentAddress = $data['ship_to_different_address'] ?? false;

		foreach ( $this->checkoutSections as $section ) {
			if ( $section === 'shipping' && ( ! $shipToDifferentAddress || ! WC()->cart->needs_shipping_address() ) ) {
				continue;
			}

			$fields = $this->getRows( $section . '_fields_classic' );

			foreach ( $fields as $field ) {
				if ( ! isset( $field['custom'], $data[ $field['name'] ] ) || ! $field['custom'] ) {
					continue;
				}

				if ( $field['type'] === 'textarea' ) {
					$value = Sanitizing::textarea( $data[ $field['name'] ] );
				} else {
					$value = Sanitizing::clean( $data[ $field['name'] ] );
				}

				WooCommerce::updateOrderMeta( $orderID, '_' . $field['name'], $value );
			}
		}
	}

	public function addCustomField( $fields ): array {
		foreach ( $this->checkoutSections as $section ) {
			if ( isset( $fields[ $section ] ) ) {
				$fields[ $section ] = $this->getCheckoutFields( $this->getRows( $section . '_fields_classic' ), $fields[ $section ] );
			}
		}

		return $fields;
	}

	private function getCheckoutFields( $checkoutFieldRows, $fields ): array {
		$checkoutFields = [];
		$fieldIndex     = 120;
		$fieldPriority  = 10;

		foreach ( $checkoutFieldRows as $field ) {
			if ( ! $field['is_active'] ) {
				continue;
			}

			$class      = $field['class'] ?? [];
			$labelClass = $field['label_class'] ?? [];
			$priority   = $fieldPriority;
			$index      = $fieldIndex;
			$default    = '';
			$validate   = array_filter( $field['validate'] );

			if ( array_key_exists( $field['name'], $fields ) ) {
				$checkoutField = $fields[ $field['name'] ];
				$priority      = $checkoutField['priority'] ?? $priority;
				$index         = $checkoutField['index'] ?? $index;
				$default       = $checkoutField['default'] ?? $default;

				if ( isset( $checkoutField['index'] ) ) {
					$fieldIndex = $checkoutField['index'];
				}
				if ( isset( $checkoutField['priority'] ) ) {
					$fieldPriority = $checkoutField['priority'];
				}
				if ( is_array( $checkoutField['class'] ) ) {
					$class = array_merge( $class, $checkoutField['class'] );
					$class = array_unique( $class );
				}

				if ( is_array( $checkoutField['label_class'] ) ) {
					$labelClass = array_merge( $labelClass, $checkoutField['label_class'] );
					$labelClass = array_unique( $labelClass );
				}
			}

			$checkoutFields[ $field['name'] ] = array(
				'type'         => $field['type'] ?? 'text',
				'label'        => $field['label'] ?? '',
				'placeholder'  => $field['placeholder'] ?? '',
				'autocomplete' => $field['autocomplete'] ?? '',
				'required'     => $field['required'] ?? false,
				'class'        => $class,
				'label_class'  => $labelClass,
				'priority'     => $priority,
				'index'        => $index,
				'default'      => $default,
				'validate'     => $validate
			);

			$fieldIndex    += 100;
			$fieldPriority += 10;
		}

		return $checkoutFields;
	}

	public function dataTableActions( $dataTableID, $index, $action ): void {
		if ( ! in_array( $dataTableID, [
			'billing_fields_classic',
			'shipping_fields_classic',
			'order_fields_classic'
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
			if ( $index >= 0 && $entry = $this->getByIndex( $dataTableID, $index ) ) {
				$data = $entry;
			}

			$form = HTML::printFields( $this->getDataTableUiFields( $index, $data ), false );

			wp_send_json_success( [ 'content' => $form ] );

		} elseif ( $action === 'save_form' ) {
			$formData           = \WooAssistant\AppHelper\DataTableUI::getFormData( $this->getDataTableUiFields() );
			$formData['custom'] = true;
			$errorMessage       = '';
			$entry              = false;

			if ( $index >= 0 ) {
				$entry = $this->getByIndex( $dataTableID, $index );

				if ( $entry === false ) {
					$errorMessage = __( 'Field not found!', 'woo-assistant' );
				}
			}

			if ( $entry ) {
				$formData['custom'] = $entry['custom'] || $this->checkFieldIsCustom( $dataTableID, $entry['name'] );

				if ( ! $entry['custom'] ) {
					$formData['type']             = $entry['type'];
					$formData['name']             = $entry['name'];
					$formData['display_in_email'] = $entry['display_in_email'];
					$formData['display_in_order'] = $entry['display_in_order'];
				}
			}

			if ( empty( $errorMessage ) && empty( $formData['name'] ) ) {
				$errorMessage = sprintf( __( '%s field is empty!', 'woo-assistant' ), __( 'Name', 'woo-assistant' ) );
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

			$formData['class'] = $formData['class'] ?? '';

			if ( ! empty( $formData['class'] ) ) {
				$class             = explode( ' ', $formData['class'] );
				$class             = array_map( 'trim', $class );
				$class             = array_filter( $class );
				$formData['class'] = array_values( $class );
			} else {
				$formData['class'] = [];
			}

			if ( is_array( $formData['validate'] ) ) {
				$formData['validate'] = array_filter( $formData['validate'] );
			}

			if ( is_array( $entry ) ) {
				$entries           = $this->getSetting( $dataTableID, [] );
				$entries[ $index ] = $formData;
				$this->saveSetting( $dataTableID, $entries );
				$successMessage = __( 'The field was successfully saved.', 'woo-assistant' );

			} else {
				$this->addToArraySetting( $dataTableID, $formData );
				$successMessage = __( 'Field added successfully.', 'woo-assistant' );
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

	private function getDataTableUiFields( $index = - 1, $data = [] ): array {
		$types          = array(
			'text'     => __( 'Text', 'woo-assistant' ),
			'number'   => __( 'Number', 'woo-assistant' ),
			'password' => __( 'Password', 'woo-assistant' ),
			'email'    => __( 'Email', 'woo-assistant' ),
			'phone'    => __( 'Phone', 'woo-assistant' ),
			'url'      => __( 'URL', 'woo-assistant' ),
			'hidden'   => __( 'Hidden', 'woo-assistant' ),
			'textarea' => __( 'Textarea', 'woo-assistant' ),
		);
		$typeAttributes = $displayAttributes = [];
		if ( ( isset( $data['type'] ) && ! array_key_exists( $data['type'], $types ) ) || ( isset( $data['custom'] ) && $data['custom'] === false ) ) {
			$typeAttributes = array(
				'disabled' => 'disabled',
			);
		}

		if ( isset( $data['custom'] ) && $data['custom'] === false ) {
			$displayAttributes = array(
				'disabled' => 'disabled',
			);
		}

		return array(
			array(
				'id'            => 'row_id',
				'type'          => 'hidden',
				'save'          => false,
				'setting_value' => $index
			),
			array(
				'id'            => 'type',
				'title'         => __( 'Type', 'woo-assistant' ),
				'type'          => 'select',
				'options'       => $types,
				'attributes'    => $typeAttributes,
				'setting_value' => $data['type'] ?? '',
				'required_text' => true,
				'default'       => 'text',
				'sanitize'      => 'text'
			),
			array(
				'id'            => 'name',
				'title'         => __( 'Name', 'woo-assistant' ),
				'desc'          => __( 'Use english alphabetic characters', 'woo-assistant' ),
				'type'          => 'text',
				'required_text' => true,
				'setting_value' => $data['name'] ?? '',
				'attributes'    => $displayAttributes,
				'sanitize'      => 'title',
			),
			array(
				'id'            => 'label',
				'title'         => __( 'Label', 'woo-assistant' ),
				'type'          => 'text',
				'setting_value' => $data['label'] ?? '',
			),
			array(
				'id'            => 'placeholder',
				'title'         => __( 'Placeholder', 'woo-assistant' ),
				'type'          => 'text',
				'setting_value' => $data['placeholder'] ?? '',
			),
			array(
				'id'            => 'default',
				'title'         => __( 'Default Value', 'woo-assistant' ),
				'type'          => 'text',
				'setting_value' => $data['default'] ?? '',
			),
			array(
				'id'            => 'class',
				'title'         => __( 'CSS Class', 'woo-assistant' ),
				'desc'          => __( 'Separated with space', 'woo-assistant' ),
				'type'          => 'text',
				'setting_value' => is_array( $data['class'] ) ? implode( ' ', $data['class'] ) : 'form-row-wide',
			),
			array(
				'id'    => 'validate_start_grid',
				'title' => __( 'Validation', 'woo-assistant' ),
				'type'  => 'startgrid',
			),
			array(
				'id'               => 'validate',
				'type'             => 'checkboxInline',
				'default'          => [],
				'options'          => array(
					'number'   => __( 'Number', 'woo-assistant' ),
					'email'    => __( 'Email', 'woo-assistant' ),
					'url'      => __( 'URL', 'woo-assistant' ),
					'phone'    => __( 'Phone', 'woo-assistant' ),
					'postcode' => __( 'Postcode', 'woo-assistant' ),
					'state'    => __( 'State', 'woo-assistant' ),
				),
				'not_equal'        => true,
				'sanitize'         => 'array',
				'sanitize_options' => 'text',
				'setting_value'    => $data['validate'] ?? [],
			),
			array(
				'type' => 'endgrid',
			),
			array(
				'id'    => 'display_start_grid',
				'title' => __( 'Display', 'woo-assistant' ),
				'type'  => 'startgrid',
			),
			array(
				'id'            => 'required',
				'title'         => __( 'Required', 'woo-assistant' ),
				'type'          => 'toggle',
				'value'         => 1,
				'default'       => true,
				'sanitize'      => 'bool',
				'setting_value' => $data['required'] ?? true,
			),
			array(
				'id'            => 'display_in_email',
				'title'         => __( 'Display in Emails', 'woo-assistant' ),
				'type'          => 'toggle',
				'value'         => 1,
				'default'       => true,
				'sanitize'      => 'bool',
				'attributes'    => $displayAttributes,
				'setting_value' => $data['display_in_email'] ?? true,
			),
			array(
				'id'            => 'display_in_order',
				'title'         => __( 'Display in Order Detail Pages', 'woo-assistant' ),
				'type'          => 'toggle',
				'value'         => 1,
				'default'       => true,
				'sanitize'      => 'bool',
				'attributes'    => $displayAttributes,
				'setting_value' => $data['display_in_order'] ?? true,
			),
			array(
				'type' => 'endgrid',
			),
		);
	}

	private function getDataTable( $id ): DataTableUI {
		$dataTable = new DataTableUI();
		$dataTable->setID( $id )
		          ->setRows( $this->getRows( $id ) )
		          ->setIdField( $dataTable::ROW_INDEX )
		          ->modalAddTitle( __( 'Add new field', 'woo-assistant' ) )
		          ->modalEditTitle( __( 'Edit field', 'woo-assistant' ) )
		          ->addNewButton( __( 'Add new', 'woo-assistant' ) )
		          ->sortable( true )
		          ->displayBottomBulkAction( true )
		          ->addAction( 'edit', '<i class="wa-icon-edit"></i>', $dataTable::ACTION_EDIT )
		          ->addAction( 'delete', '<i class="wa-icon-trash"></i>', $dataTable::ACTION_DELETE )
		          ->addAction( 'bulk_enable', __( 'Enable', 'woo-assistant' ), $dataTable::ACTION_NONE, [], $dataTable::ACTION_BULK )
		          ->addAction( 'bulk_disable', __( 'Disable', 'woo-assistant' ), $dataTable::ACTION_NONE, [], $dataTable::ACTION_BULK )
		          ->addAction( 'bulk_delete', __( 'Delete', 'woo-assistant' ), $dataTable::ACTION_DELETE, [], $dataTable::ACTION_BULK )
		          ->addColumn( __( 'Name', 'woo-assistant' ), 'name' )
		          ->addColumn( __( 'Label', 'woo-assistant' ), 'label' )
		          ->addColumn( __( 'Status', 'woo-assistant' ), $dataTable::ACTIVE_FIELD );

		if ( $id === 'billing_fields_classic' ) {
			$dataTable->setTitle( __( 'Billing Fields', 'woo-assistant' ) );
		} elseif ( $id === 'shipping_fields_classic' ) {
			$dataTable->setTitle( __( 'Shipping Fields', 'woo-assistant' ) );
		} elseif ( $id === 'order_fields_classic' ) {
			$dataTable->setTitle( __( 'Order Fields', 'woo-assistant' ) );
		}

		return $dataTable;
	}

	private function checkFieldIsCustom( $id, $field ): bool {
		$IDs    = explode( '_', $id );
		$fields = $this->getWooFields( $IDs[0] );

		return ! array_key_exists( $field, $fields );
	}

	private function getWooFields( $type ): array {
		$fields = array();

		if ( empty( $fields ) ) {
			if ( $type === 'billing' || $type === 'shipping' || $type === 'order' ) {
				$fields = WooCommerce::getCheckoutFields( $type );
			}

			foreach ( $fields as $key => $value ) {
				$fields[ $key ]['is_active']        = true;
				$fields[ $key ]['custom']           = false;
				$fields[ $key ]['display_in_email'] = true;
				$fields[ $key ]['display_in_order'] = true;
			}
		}

		return $fields;
	}

	private function getRows( $id ): array {
		$rows = $this->getSetting( $id, [] );
		if ( ! empty( $rows ) ) {
			return $rows;
		}

		$rows   = [];
		$IDs    = explode( '_', $id );
		$fields = $this->getWooFields( $IDs[0] );

		foreach ( $fields as $name => $field ) {
			$rows[] = array(
				'is_active'        => $field['is_active'] ?? true,
				'name'             => $name,
				'label'            => $field['label'] ?? '',
				'placeholder'      => $field['placeholder'] ?? '',
				'type'             => $field['type'] ?? 'text',
				'autocomplete'     => $field['autocomplete'] ?? '',
				'required'         => $field['required'] ?? false,
				'class'            => $field['class'] ?? [],
				'validate'         => $field['validate'] ?? [],
				'custom'           => $field['custom'] ?? true,
				'display_in_email' => $field['display_in_email'] ?? false,
				'display_in_order' => $field['display_in_order'] ?? false,
			);
		}

		$this->saveSetting( $id, $rows );

		return $rows;
	}

	public function addSectionSettings( $sections ): array {
		$type = ! $this->getSetting( 'checkout_fields_type', false ) && WooCommerce::hasBlockInPage( wc_get_page_id( 'checkout' ), 'woocommerce/checkout' ) ? 'blocks' : 'classic';

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
			$fields = array(
				'block_type_notice' => array(
					'id'      => 'order_number_notice',
					'notices' => array(
						array(
							'message' => __( 'Block type is not currently supported.', 'woo-assistant' ),
							'type'    => 'warning',
						)
					),
					'type'    => 'notice',
				),
			);

		} else {
			$fields = array(
				'billing_fields_classic_dtu'  => array(
					'id'         => 'billing_fields_classic',
					'type'       => 'dataTable',
					'data_table' => $this->getDataTable( 'billing_fields_classic' )->render()
				),
				'shipping_fields_classic_dtu' => array(
					'id'         => 'shipping_fields_classic',
					'type'       => 'dataTable',
					'data_table' => $this->getDataTable( 'shipping_fields_classic' )->render()
				),
				'order_fields_classic_dtu'    => array(
					'id'         => 'order_fields_classic',
					'type'       => 'dataTable',
					'data_table' => $this->getDataTable( 'order_fields_classic' )->render()
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