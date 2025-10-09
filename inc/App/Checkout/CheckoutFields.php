<?php

namespace AssistantForWooCommerce\App\Checkout;

defined( 'ABSPATH' ) || exit;

use AssistantForWooCommerce\Addons\Addon;
use AssistantForWooCommerce\Helper\Helper;
use AssistantForWooCommerce\Helper\HTML;
use AssistantForWooCommerce\Helper\Notice;
use AssistantForWooCommerce\Helper\Param;
use AssistantForWooCommerce\Helper\Sanitizing;
use AssistantForWooCommerce\Helper\Templates;
use AssistantForWooCommerce\Helper\WooCommerce;
use AssistantForWooCommerce\Interfaces\AddonInterface;
use AssistantForWooCommerce\Providers\UI\DataTableUI;

class CheckoutFields extends Addon implements AddonInterface {
	public string $addonID = 'checkout-fields';

	public string $currentTab = 'checkout';

	private array $checkoutSections = [ 'billing', 'shipping', 'order' ];

	public function initAction(): void {
		add_action( 'assistant_for_woocommerce_data_table_ui_action', [ $this, 'dataTableActions' ], 10, 3 );

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

			echo '<p class="form-field form-field-wide"><strong>' . esc_html( $field['label'] ) . ':</strong> ' . esc_html( $value ) . '</p>';
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

				$fieldsOutput .= '<tr><th>' . $field['label'] . '</th><td>' . esc_html( $value ) . '</td></tr>';
			}

			if ( ! empty( $fieldsOutput ) ) {
				$sectionLabel = ucfirst( $section );
				// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
				$output .= '<tr><td colspan="2">' . esc_html__( $sectionLabel, 'assistant-for-woocommerce' ) . '</td></tr>' . $fieldsOutput;
			}
		}

		if ( ! empty( $output ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<table class="woocommerce-table shop_table order_details has-background asfowoo-checkout-fields-order-meta">' . $output . '</table>';
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

				$fieldsOutput .= '<tr><th>' . $field['label'] . '</th><td>' . esc_html( $value ) . '</td></tr>';
			}

			if ( ! empty( $fieldsOutput ) ) {
				$sectionLabel = ucfirst( $section );
				// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
				$output .= '<tr><td colspan="2">' . esc_html__( $sectionLabel, 'assistant-for-woocommerce' ) . '</td></tr>' . $fieldsOutput;
			}
		}

		if ( ! empty( $output ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
			$rowIDs     = array_map( 'AssistantForWooCommerce\Helper\Sanitizing::int', Sanitizing::array( Param::post( 'row_ids' ) ) );
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
			$formData           = \AssistantForWooCommerce\AppHelper\DataTableUI::getFormData( $this->getDataTableUiFields() );
			$formData['custom'] = true;
			$errorMessage       = '';
			$entry              = false;

			if ( $index >= 0 ) {
				$entry = $this->getByIndex( $dataTableID, $index );

				if ( $entry === false ) {
					$errorMessage = esc_html__( 'Field not found!', 'assistant-for-woocommerce' );
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
				/* translators: %s: Field name */
				$errorMessage = sprintf( esc_html__( '%s field is empty!', 'assistant-for-woocommerce' ), esc_html__( 'Name', 'assistant-for-woocommerce' ) );
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
				$successMessage = esc_html__( 'The field was successfully saved.', 'assistant-for-woocommerce' );

			} else {
				$this->addToArraySetting( $dataTableID, $formData );
				$successMessage = esc_html__( 'Field added successfully.', 'assistant-for-woocommerce' );
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
							'message' => esc_html__( 'Order status removed!', 'assistant-for-woocommerce' ),
						)
					), false ),
				] );

			} else {
				wp_send_json_error( [
					'error'   => 'required-field',
					'message' => Notice::addAndDisplay( $this->addonID, array(
						array(
							'type'    => 'error',
							'message' => esc_html__( 'Selected item not found!', 'assistant-for-woocommerce' ),
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
			'text'     => esc_html__( 'Text', 'assistant-for-woocommerce' ),
			'number'   => esc_html__( 'Number', 'assistant-for-woocommerce' ),
			'password' => esc_html__( 'Password', 'assistant-for-woocommerce' ),
			'email'    => esc_html__( 'Email', 'assistant-for-woocommerce' ),
			'phone'    => esc_html__( 'Phone', 'assistant-for-woocommerce' ),
			'url'      => esc_html__( 'URL', 'assistant-for-woocommerce' ),
			'hidden'   => esc_html__( 'Hidden', 'assistant-for-woocommerce' ),
			'textarea' => esc_html__( 'Textarea', 'assistant-for-woocommerce' ),
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
				'title'         => esc_html__( 'Type', 'assistant-for-woocommerce' ),
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
				'title'         => esc_html__( 'Name', 'assistant-for-woocommerce' ),
				'desc'          => esc_html__( 'Use english alphabetic characters', 'assistant-for-woocommerce' ),
				'type'          => 'text',
				'required_text' => true,
				'setting_value' => $data['name'] ?? '',
				'attributes'    => $displayAttributes,
				'sanitize'      => 'title',
			),
			array(
				'id'            => 'label',
				'title'         => esc_html__( 'Label', 'assistant-for-woocommerce' ),
				'type'          => 'text',
				'setting_value' => $data['label'] ?? '',
			),
			array(
				'id'            => 'placeholder',
				'title'         => esc_html__( 'Placeholder', 'assistant-for-woocommerce' ),
				'type'          => 'text',
				'setting_value' => $data['placeholder'] ?? '',
			),
			array(
				'id'            => 'default',
				'title'         => esc_html__( 'Default Value', 'assistant-for-woocommerce' ),
				'type'          => 'text',
				'setting_value' => $data['default'] ?? '',
			),
			array(
				'id'            => 'class',
				'title'         => esc_html__( 'CSS Class', 'assistant-for-woocommerce' ),
				'desc'          => esc_html__( 'Separate with space', 'assistant-for-woocommerce' ),
				'type'          => 'text',
				'setting_value' => is_array( $data['class'] ) ? implode( ' ', $data['class'] ) : 'form-row-wide',
			),
			array(
				'id'    => 'validate_start_grid',
				'title' => esc_html__( 'Validation', 'assistant-for-woocommerce' ),
				'type'  => 'startgrid',
			),
			array(
				'id'               => 'validate',
				'type'             => 'checkboxInline',
				'default'          => [],
				'options'          => array(
					'number'   => esc_html__( 'Number', 'assistant-for-woocommerce' ),
					'email'    => esc_html__( 'Email', 'assistant-for-woocommerce' ),
					'url'      => esc_html__( 'URL', 'assistant-for-woocommerce' ),
					'phone'    => esc_html__( 'Phone', 'assistant-for-woocommerce' ),
					'postcode' => esc_html__( 'Postcode', 'assistant-for-woocommerce' ),
					'state'    => esc_html__( 'State', 'assistant-for-woocommerce' ),
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
				'title' => esc_html__( 'Display', 'assistant-for-woocommerce' ),
				'type'  => 'startgrid',
			),
			array(
				'id'            => 'required',
				'title'         => esc_html__( 'Required', 'assistant-for-woocommerce' ),
				'type'          => 'toggle',
				'value'         => 1,
				'default'       => true,
				'sanitize'      => 'bool',
				'setting_value' => $data['required'] ?? true,
			),
			array(
				'id'            => 'display_in_email',
				'title'         => esc_html__( 'Display in Emails', 'assistant-for-woocommerce' ),
				'type'          => 'toggle',
				'value'         => 1,
				'default'       => true,
				'sanitize'      => 'bool',
				'attributes'    => $displayAttributes,
				'setting_value' => $data['display_in_email'] ?? true,
			),
			array(
				'id'            => 'display_in_order',
				'title'         => esc_html__( 'Display in Order Detail Pages', 'assistant-for-woocommerce' ),
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
			array(
				'type' => 'space',
				'size' => 30
			),
		);
	}

	private function getDataTable( $id ): DataTableUI {
		$dataTable = new DataTableUI();
		$dataTable->setID( $id )
		          ->setRows( $this->getRows( $id ) )
		          ->setIdField( $dataTable::ROW_INDEX )
		          ->modalAddTitle( esc_html__( 'Add new field', 'assistant-for-woocommerce' ) )
		          ->modalEditTitle( esc_html__( 'Edit field', 'assistant-for-woocommerce' ) )
		          ->addNewButton( esc_html__( 'Add new', 'assistant-for-woocommerce' ) )
		          ->sortable( true )
		          ->displayBottomBulkAction( true )
		          ->addAction( 'edit', '<i class="asfowoo-icon-edit"></i>', $dataTable::ACTION_EDIT )
		          ->addAction( 'delete', '<i class="asfowoo-icon-trash"></i>', $dataTable::ACTION_DELETE )
		          ->addAction( 'bulk_enable', esc_html__( 'Enable', 'assistant-for-woocommerce' ), $dataTable::ACTION_NONE, [], $dataTable::ACTION_BULK )
		          ->addAction( 'bulk_disable', esc_html__( 'Disable', 'assistant-for-woocommerce' ), $dataTable::ACTION_NONE, [], $dataTable::ACTION_BULK )
		          ->addAction( 'bulk_delete', esc_html__( 'Delete', 'assistant-for-woocommerce' ), $dataTable::ACTION_DELETE, [], $dataTable::ACTION_BULK )
		          ->addColumn( esc_html__( 'Name', 'assistant-for-woocommerce' ), 'name' )
		          ->addColumn( esc_html__( 'Label', 'assistant-for-woocommerce' ), 'label', null, [ 'hide_on_mobile' => true ] )
		          ->addColumn( esc_html__( 'Status', 'assistant-for-woocommerce' ), $dataTable::ACTIVE_FIELD );

		if ( $id === 'billing_fields_classic' ) {
			$dataTable->setTitle( esc_html__( 'Billing Fields', 'assistant-for-woocommerce' ) );
		} elseif ( $id === 'shipping_fields_classic' ) {
			$dataTable->setTitle( esc_html__( 'Shipping Fields', 'assistant-for-woocommerce' ) );
		} elseif ( $id === 'order_fields_classic' ) {
			$dataTable->setTitle( esc_html__( 'Order Fields', 'assistant-for-woocommerce' ) );
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
				'title' => esc_html__( 'Checkout Fields', 'assistant-for-woocommerce' ),
				'type'  => 'startGrid',
			),
			'checkout_fields_type'            => array(
				'id'        => 'checkout_fields_type',
				'title'     => esc_html__( 'Checkout page type', 'assistant-for-woocommerce' ),
				'type'      => 'radioInline',
				'default'   => $type,
				'options'   => array(
					'classic' => esc_html__( 'Classic', 'assistant-for-woocommerce' ),
					'blocks'  => esc_html__( 'Blocks', 'assistant-for-woocommerce' ),
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
							'message' => esc_html__( 'Block type is not currently supported.', 'assistant-for-woocommerce' ),
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
			'title'        => esc_html__( 'Fields', 'assistant-for-woocommerce' ),
			'desc'         => esc_html__( 'Checkout fields manager', 'assistant-for-woocommerce' ),
			'settings_key' => $this->info()['settings_key'],
			'settings'     => $settings
		);


		return $sections;
	}

	public function info(): array {
		$icon = '<svg width="256px" height="256px" viewBox="-2.4 -2.4 28.80 28.80" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0" transform="translate(0,0), scale(1)"><path transform="translate(-2.4, -2.4), scale(0.8999999999999999)" d="M16,31.118223652243614C18.152340584517972,31.67212167636631,20.3737306262037,30.11009412618828,22.1550344819258,28.781060798359448C23.82173917732638,27.537530066238393,25.34918552325176,25.841981259149446,25.794292839780876,23.81068790264672C26.227741553158616,21.832599789571955,24.303175086352326,19.959091525954726,24.52911156391847,17.946714056809956C24.71982270045602,16.24808266086459,26.610854687309175,15.175246343679273,26.951874547014675,13.500306102304322C27.35630352917396,11.513928013996251,27.52724188615165,9.332615277137513,26.655573589924998,7.502463618756671C25.758730067161327,5.619454144167198,23.941261979036856,4.31192618350015,22.104763261772277,3.32332846592114C20.23213581097401,2.315282445343249,18.12480548920616,1.676552231137154,16,1.7665076763614227C13.90444070342058,1.8552249591787247,11.416345064965485,2.1661365855932164,10.139189310913611,3.829901202852252C8.735873384984354,5.658016040834937,10.34120059994184,8.580285882682007,9.222740997663644,10.595316296073044C8.361241984489142,12.14740338899712,5.860273556260619,12.018590238908855,4.8026486966857025,13.444277634564468C3.6357739230456176,15.017234852831635,2.7824721801655947,17.0191092471035,3.0146990173923616,18.963810212272193C3.2477979238560297,20.91581401076948,4.560568110060803,22.653607159846587,6.054915513438965,23.930940228219647C7.474880571867372,25.14469301486393,9.68832705956999,24.868242095987444,11.202350299584765,25.96242225617109C13.129202061457452,27.354952379708312,13.697645283595032,30.525719966990717,16,31.118223652243614" fill="#fff" strokewidth="0"></path></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.048"></g><g id="SVGRepo_iconCarrier"> <path d="M4 7C4 5.11438 4 4.17157 4.58579 3.58579C5.17157 3 6.11438 3 8 3H16C17.8856 3 18.8284 3 19.4142 3.58579C20 4.17157 20 5.11438 20 7V15C20 17.8284 20 19.2426 19.1213 20.1213C18.2426 21 16.8284 21 14 21H10C7.17157 21 5.75736 21 4.87868 20.1213C4 19.2426 4 17.8284 4 15V7Z" stroke="#873eff" stroke-width="2"></path> <path d="M15 18L15 21M9 18L9 21" stroke="#873eff" stroke-width="2" stroke-linecap="round"></path> <path d="M9 8L15 8" stroke="#873eff" stroke-width="2" stroke-linecap="round"></path> <path d="M9 12L15 12" stroke="#873eff" stroke-width="2" stroke-linecap="round"></path> </g></svg>';

		return array(
			'id'             => $this->addonID,
			'title'          => esc_html__( 'Checkout Fields', 'assistant-for-woocommerce' ),
			'desc'           => esc_html__( 'Customize the checkout fields in WooCommerce.', 'assistant-for-woocommerce' ),
			'tags'           => [ esc_html__( 'Checkout', 'assistant-for-woocommerce' ) ],
			'cat'            => 'checkout',
			'icon'           => $icon,
			'more_info_link' => 'https://parsa.ws',
			'settings_key'   => $this->addonID,
		);
	}
}