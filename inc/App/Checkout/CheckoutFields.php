<?php

namespace Jetexir\App\Checkout;

defined( 'ABSPATH' ) || exit;

use Jetexir\Addons\Addon;
use Jetexir\Helper\{Helper, HTML, Notice, Param, Sanitizing, Templates, WooCommerce};
use Jetexir\Interfaces\AddonInterface;
use Jetexir\Providers\UI\DataTableUI;

class CheckoutFields extends Addon implements AddonInterface {
  public string $addonID = 'checkout-fields';

  public string $currentTab = 'checkout';

  private array $checkoutSections = [ 'billing', 'shipping', 'order' ];

  public function initAction(): void {
    add_action( 'jetexir_data_table_ui_action', [ $this, 'dataTableActions' ], 10, 3 );

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
        $output .= '<tr><td colspan="2">' . $this->getSectionLabel( $section ) . '</td></tr>' . $fieldsOutput;
      }
    }

    if ( ! empty( $output ) ) {
      echo '<table class="woocommerce-table shop_table order_details has-background jetexir-checkout-fields-order-meta">' . wp_kses_post( $output ) . '</table>';
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
        $output .= '<tr><td colspan="2">' . $this->getSectionLabel( $section ) . '</td></tr>' . $fieldsOutput;
      }
    }

    if ( ! empty( $output ) ) {
      echo wp_kses_post( '<table style="color:#525252;border:1px solid #e5e5e5;width:100%;vertical-align:middle;margin-bottom: 30px;">' . $output . '</table>' );
    }
  }

  private function getSectionLabel( $section ) {
    $sections = array(
      'billing'  => esc_html__( 'Billing', 'jetexir' ),
      'shipping' => esc_html__( 'Shipping', 'jetexir' ),
      'order'    => esc_html__( 'Order', 'jetexir' ),
    );

    return $sections[ $section ] ?? ucfirst( $section );
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
      $rowIDs     = array_map( 'Jetexir\Helper\Sanitizing::int', Sanitizing::array( Param::post( 'row_ids' ) ) );
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
      $formData           = \Jetexir\AppHelper\DataTableUI::getFormData( $this->getDataTableUiFields() );
      $formData['custom'] = true;
      $errorMessage       = '';
      $entry              = false;

      if ( $index >= 0 ) {
        $entry = $this->getByIndex( $dataTableID, $index );

        if ( $entry === false ) {
          $errorMessage = esc_html__( 'Field not found!', 'jetexir' );
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
        $errorMessage = sprintf( esc_html__( '%s field is empty!', 'jetexir' ), esc_html__( 'Name', 'jetexir' ) );
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
        $successMessage = esc_html__( 'The field was successfully saved.', 'jetexir' );

      } else {
        $this->addToArraySetting( $dataTableID, $formData );
        $successMessage = esc_html__( 'Field added successfully.', 'jetexir' );
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
              'message' => esc_html__( 'Order status removed!', 'jetexir' ),
            )
          ), false ),
        ] );

      } else {
        wp_send_json_error( [
          'error'   => 'required-field',
          'message' => Notice::addAndDisplay( $this->addonID, array(
            array(
              'type'    => 'error',
              'message' => esc_html__( 'Selected item not found!', 'jetexir' ),
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
      'text'     => esc_html__( 'Text', 'jetexir' ),
      'number'   => esc_html__( 'Number', 'jetexir' ),
      'password' => esc_html__( 'Password', 'jetexir' ),
      'email'    => esc_html__( 'Email', 'jetexir' ),
      'phone'    => esc_html__( 'Phone', 'jetexir' ),
      'url'      => esc_html__( 'URL', 'jetexir' ),
      'hidden'   => esc_html__( 'Hidden', 'jetexir' ),
      'textarea' => esc_html__( 'Textarea', 'jetexir' ),
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
        'title'         => esc_html__( 'Type', 'jetexir' ),
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
        'title'         => esc_html__( 'Name', 'jetexir' ),
        'desc'          => esc_html__( 'Use english alphabetic characters', 'jetexir' ),
        'type'          => 'text',
        'required_text' => true,
        'setting_value' => $data['name'] ?? '',
        'attributes'    => $displayAttributes,
        'sanitize'      => 'title',
      ),
      array(
        'id'            => 'label',
        'title'         => esc_html__( 'Label', 'jetexir' ),
        'type'          => 'text',
        'setting_value' => $data['label'] ?? '',
      ),
      array(
        'id'            => 'placeholder',
        'title'         => esc_html__( 'Placeholder', 'jetexir' ),
        'type'          => 'text',
        'setting_value' => $data['placeholder'] ?? '',
      ),
      array(
        'id'            => 'default',
        'title'         => esc_html__( 'Default Value', 'jetexir' ),
        'type'          => 'text',
        'setting_value' => $data['default'] ?? '',
      ),
      array(
        'id'            => 'class',
        'title'         => esc_html__( 'CSS Class', 'jetexir' ),
        'desc'          => esc_html__( 'Separate with space', 'jetexir' ),
        'type'          => 'text',
        'setting_value' => is_array( $data['class'] ) ? implode( ' ', $data['class'] ) : 'form-row-wide',
      ),
      array(
        'id'    => 'validate_start_grid',
        'title' => esc_html__( 'Validation', 'jetexir' ),
        'type'  => 'startgrid',
      ),
      array(
        'id'               => 'validate',
        'type'             => 'checkboxInline',
        'default'          => [],
        'options'          => array(
          'number'   => esc_html__( 'Number', 'jetexir' ),
          'email'    => esc_html__( 'Email', 'jetexir' ),
          'url'      => esc_html__( 'URL', 'jetexir' ),
          'phone'    => esc_html__( 'Phone', 'jetexir' ),
          'postcode' => esc_html__( 'Postcode', 'jetexir' ),
          'state'    => esc_html__( 'State', 'jetexir' ),
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
        'title' => esc_html__( 'Display', 'jetexir' ),
        'type'  => 'startgrid',
      ),
      array(
        'id'            => 'required',
        'title'         => esc_html__( 'Required', 'jetexir' ),
        'type'          => 'toggle',
        'value'         => 1,
        'default'       => true,
        'sanitize'      => 'bool',
        'setting_value' => $data['required'] ?? true,
      ),
      array(
        'id'            => 'display_in_email',
        'title'         => esc_html__( 'Display in Emails', 'jetexir' ),
        'type'          => 'toggle',
        'value'         => 1,
        'default'       => true,
        'sanitize'      => 'bool',
        'attributes'    => $displayAttributes,
        'setting_value' => $data['display_in_email'] ?? true,
      ),
      array(
        'id'            => 'display_in_order',
        'title'         => esc_html__( 'Display in Order Detail Pages', 'jetexir' ),
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
              ->modalAddTitle( esc_html__( 'Add new field', 'jetexir' ) )
              ->modalEditTitle( esc_html__( 'Edit field', 'jetexir' ) )
              ->addNewButton( esc_html__( 'Add new', 'jetexir' ) )
              ->sortable( true )
              ->displayBottomBulkAction( true )
              ->addAction( 'edit', '<i class="jetexir-icon-edit"></i>', $dataTable::ACTION_EDIT )
              ->addAction( 'delete', '<i class="jetexir-icon-trash"></i>', $dataTable::ACTION_DELETE )
              ->addAction( 'bulk_enable', esc_html__( 'Enable', 'jetexir' ), $dataTable::ACTION_NONE, [], $dataTable::ACTION_BULK )
              ->addAction( 'bulk_disable', esc_html__( 'Disable', 'jetexir' ), $dataTable::ACTION_NONE, [], $dataTable::ACTION_BULK )
              ->addAction( 'bulk_delete', esc_html__( 'Delete', 'jetexir' ), $dataTable::ACTION_DELETE, [], $dataTable::ACTION_BULK )
              ->addColumn( esc_html__( 'Name', 'jetexir' ), 'name' )
              ->addColumn( esc_html__( 'Label', 'jetexir' ), 'label', null, [ 'hide_on_mobile' => true ] )
              ->addColumn( esc_html__( 'Status', 'jetexir' ), $dataTable::ACTIVE_FIELD );

    if ( $id === 'billing_fields_classic' ) {
      $dataTable->setTitle( esc_html__( 'Billing Fields', 'jetexir' ) );
    } elseif ( $id === 'shipping_fields_classic' ) {
      $dataTable->setTitle( esc_html__( 'Shipping Fields', 'jetexir' ) );
    } elseif ( $id === 'order_fields_classic' ) {
      $dataTable->setTitle( esc_html__( 'Order Fields', 'jetexir' ) );
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
        'title' => esc_html__( 'Checkout Fields', 'jetexir' ),
        'type'  => 'startGrid',
      ),
      'checkout_fields_type'            => array(
        'id'        => 'checkout_fields_type',
        'title'     => esc_html__( 'Checkout page type', 'jetexir' ),
        'type'      => 'radioInline',
        'default'   => $type,
        'options'   => array(
          'classic' => esc_html__( 'Classic', 'jetexir' ),
          'blocks'  => esc_html__( 'Blocks', 'jetexir' ),
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
              'message' => esc_html__( 'Block type is not currently supported.', 'jetexir' ),
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
      'title'        => esc_html__( 'Fields', 'jetexir' ),
      'desc'         => esc_html__( 'Checkout fields manager', 'jetexir' ),
      'settings_key' => $this->info()['settings_key'],
      'settings'     => $settings
    );


    return $sections;
  }

  public function info(): array {
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><g stroke="#873eff" stroke-width="1.32"><path d="M4 7c0-1.886 0-2.828.586-3.414S6.114 3 8 3h8c1.886 0 2.828 0 3.414.586S20 5.114 20 7v8c0 2.828 0 4.243-.879 5.121C18.243 21 16.828 21 14 21h-4c-2.828 0-4.243 0-5.121-.879C4 19.243 4 17.828 4 15z"/><path stroke-linecap="round" d="M15 18v3m-6-3v3M9 8h6M9 12h6"/></g></svg>';

    return array(
      'id'             => $this->addonID,
      'title'          => esc_html__( 'Checkout Fields', 'jetexir' ),
      'desc'           => esc_html__( 'Customize the checkout fields in WooCommerce.', 'jetexir' ),
      'tags'           => [ esc_html__( 'Checkout', 'jetexir' ) ],
      'cat'            => 'checkout',
      'icon'           => $icon,
      'more_info_link' => '{jetexir_website}/addons/checkout-fields',
      'settings_key'   => $this->addonID,
    );
  }
}
