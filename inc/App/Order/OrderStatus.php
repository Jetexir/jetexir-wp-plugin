<?php

namespace Jetexir\App\Order;

defined( 'ABSPATH' ) || exit;

use Jetexir\Addons\Addon;
use Jetexir\Admin\AdminPages;
use Jetexir\Helper\{Helper, Assets, HTML, Notice, Param, Sanitizing, Templates, WooCommerce};
use Jetexir\Interfaces\AddonInterface;
use Jetexir\Providers\UI\DataTableUI;
use Jetexir\Settings\Settings;

class OrderStatus extends Addon implements AddonInterface {
  public string $addonID = 'order-status';

  public string $currentTab = 'order';

  private const orderStatusDataTableId = 'order_status';

  public function initAction(): void {
    add_action( 'jetexir_data_table_ui_order_status_action', [
      $this,
      'dataTableActions'
    ], 10, 2 );

    // Register custom statuses
    add_action( 'woocommerce_register_shop_order_post_statuses', [ $this, 'wcRegisterStatuses' ] );
    add_filter( 'wc_order_statuses', [ $this, 'wcAddOrderStatuses' ] );

    // Add order with custom status to editable orders
    add_filter( 'wc_order_is_editable', [ $this, 'wcOrderIsEditable' ], 10, 2 );

    // Add custom status to paid statuses
    add_filter( 'woocommerce_order_is_paid_statuses', [ $this, 'wcOrderIsPaidStatuses' ] );

    // Order row actions
    add_filter( 'woocommerce_admin_order_actions', [ $this, 'wcAdminOrderActions' ], 10, 2 );

    // Order preview actions
    add_filter( 'woocommerce_admin_order_preview_actions', [ $this, 'wcAdminOrderPreviewActions' ], 10, 2 );

    // Add order bulk actions
    add_filter( 'bulk_actions-edit-shop_order', [ $this, 'wcAddOrderBulkActions' ] );
    add_filter( 'bulk_actions-woocommerce_page_wc-orders', [ $this, 'wcAddOrderBulkActions' ] );

    // Add order status to reports
    add_filter( 'woocommerce_reports_order_statuses', [ $this, 'wcReportsOrderStatuses' ] );

    // Change order status
    add_action( 'woocommerce_thankyou', [ $this, 'changeOrderStatus' ] );
  }

  public function changeOrderStatus( $orderId ): void {
    $changed = WooCommerce::getOrderMeta( $orderId, '_waos_changed' );

    if ( ! empty( $changed ) ) {
      return;
    }

    $order  = wc_get_order( $orderId );
    $status = Settings::get( 'order_status_payment_' . $order->get_payment_method(), false, $this->addonID );
    if ( ! $status ) {
      $status = Settings::get( 'order_status_default', false, $this->addonID );
    }

    if ( $status && array_key_exists( $status, WooCommerce::getOrderStatuses() ) ) {
      $order->update_status( $status );
      WooCommerce::updateOrderMeta( $orderId, '_waos_changed', current_time( 'mysql' ) );
    }
  }

  /**
   * @param mixed $statuses
   *
   * @return mixed
   */
  public function wcReportsOrderStatuses( $statuses ) {
    if ( is_array( $statuses ) && in_array( 'completed', $statuses, true ) ) {
      return array_merge( $statuses, array_keys( $this->getStatuses() ) );
    }

    return $statuses;
  }

  public function wcAddOrderBulkActions( $actions ) {
    foreach ( $this->getStatuses() as $slug => $title ) {
      /* translators: %s: Order status name */
      $actions[ 'mark_' . $slug ] = sprintf( esc_html__( 'Change status to %s', 'jetexir' ), $title );
    }

    return $actions;
  }

  /**
   * @param array $actions
   * @param \WC_Order $order
   *
   * @return array
   */
  public function wcAdminOrderPreviewActions( $actions, $order ): array {
    $statusActions = [];

    foreach ( $this->getStatuses() as $slug => $title ) {
      if ( ! $order->has_status( $slug ) ) {
        $statusActions[ $slug ] = array(
          'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=' . $slug . '&order_id=' . $order->get_id() ), 'woocommerce-mark-order-status' ),
          'name'   => $title,
          /* translators: %s: Order status name */
          'title'  => sprintf( esc_html__( 'Change order status to %s', 'jetexir' ), $title ),
          'action' => $slug,
        );
      }
    }

    if ( ! empty( $statusActions ) ) {
      if ( ! empty( $actions['status']['actions'] ) && is_array( $actions['status']['actions'] ) ) {
        $actions['status']['actions'] = array_merge( $actions['status']['actions'], $statusActions );
      } else {
        $actions['status'] = array(
          'group'   => esc_html__( 'Change status:', 'jetexir' ) . ' ',
          'actions' => $statusActions,
        );
      }
    }

    return $actions;
  }

  /**
   * @param array $actions
   * @param \WC_Order $order
   *
   * @return array
   */
  public function wcAdminOrderActions( $actions, $order ): array {
    foreach ( $this->getStatuses() as $slug => $title ) {
      if ( ! $order->has_status( array( $slug ) ) ) {
        $actions[ $slug ] = array(
          'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=' . $slug . '&order_id=' . $order->get_id() ), 'woocommerce-mark-order-status' ),
          'name'   => $title,
          /* translators: %s: Order status name */
          'title'  => sprintf( esc_html__( 'Change order status to %s', 'jetexir' ), $title ),
          'action' => 'edit ' . $slug,
        );
      }
    }

    return $actions;
  }

  public function wcOrderIsPaidStatuses( $statuses ): array {
    return array_merge( $statuses, array_keys( $this->getStatuses( true ) ) );
  }

  public function wcOrderIsEditable( $isEditable, $order ): bool {
    return array_key_exists( 'wc-' . $order->get_status(), $this->getStatuses( true ) ) ? true : $isEditable;
  }

  public function wcAddOrderStatuses( $statuses ) {
    $entries = $this->getStatuses( true );
    foreach ( $entries as $slug => $title ) {
      $statuses[ $slug ] = $title;
    }

    return $statuses;
  }

  public function wcRegisterStatuses( $statuses ) {
    foreach ( $this->getStatuses( true ) as $slug => $title ) {
      $statuses[ $slug ] = array(
        'label'                     => $title,
        'public'                    => false,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => array(
          0          => $title . ' <span class="count">(%s)</span>',
          1          => $title . ' <span class="count">(%s)</span>',
          'singular' => $title . ' <span class="count">(%s)</span>',
          'plural'   => $title . ' <span class="count">(%s)</span>',
          'context'  => null,
          'domain'   => 'jetexir',
        )
      );
    }

    return $statuses;
  }

  public function getStatuses( $addPrefix = false, $all = false ): array {
    $entries  = $this->getOrderStatuses( $all );
    $statuses = [];
    $prefix   = $addPrefix ? 'wc-' : '';
    foreach ( $entries as $status ) {
      $statuses[ $prefix . $status['slug'] ] = $status['title'];
    }

    return $statuses;
  }

  /**
   * Get order statuses
   *
   * @param bool $all All items
   *
   * @return array
   */
  public function getOrderStatuses( bool $all = false ): array {
    $entries = Settings::get( self::orderStatusDataTableId, [], $this->addonID );
    $entries = is_array( $entries ) ? $entries : [];

    if ( ! empty( $entries ) ) {
      $entries = array_filter( $entries, static function ( $entry ) use ( $all ) {
        return $all || $entry['is_active'];
      } );
    }

    return $entries;
  }

  public function dataTableActions( $index, $action ): void {
    if ( $action === 'bulk_action' ) {
      $bulkAction = Sanitizing::text( Param::post( 'bulk_action' ) );
      $rowIDs     = array_map( 'Jetexir\Helper\Sanitizing::int', Sanitizing::array( Param::post( 'row_ids' ) ) );
      $statuses   = Settings::get( self::orderStatusDataTableId, [], $this->addonID );

      foreach ( $statuses as $statusIndex => $status ) {
        if ( in_array( $statusIndex, $rowIDs, true ) ) {
          if ( $bulkAction === 'bulk_delete' ) {
            $this->deleteActions( $status['slug'] );
            unset( $statuses[ $statusIndex ] );

          } elseif ( $bulkAction === 'bulk_enable' ) {
            $statuses[ $statusIndex ]['is_active'] = true;

          } elseif ( $bulkAction === 'bulk_disable' ) {
            $statuses[ $statusIndex ]['is_active'] = false;
          }
        }
      }

      $statuses = array_values( $statuses );
      Settings::save( self::orderStatusDataTableId, $statuses, $this->addonID );
      $dataTable = $this->getDataTable();

      wp_send_json_success( [
        'table'     => $dataTable->renderHTML( Templates::getPath( 'data-table/data_table_table.php' ) ),
        'row_count' => $dataTable->getRowCount(),
      ] );

    } elseif ( $action === 'save_changes' ) {
      $rowOrders = Sanitizing::array( Param::post( 'row_orders' ) );
      $entries   = $this->getSetting( self::orderStatusDataTableId, [] );
      $entries   = Helper::reorderArray( $entries, $rowOrders );

      if ( is_array( $entries ) ) {
        $this->saveSetting( self::orderStatusDataTableId, $entries );
      }

      $dataTable = $this->getDataTable();

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
      $formData     = \Jetexir\AppHelper\DataTableUI::getFormData( $this->getFields() );
      $errorMessage = '';
      $entry        = false;

      if ( empty( $formData['title'] ) ) {
        /* translators: %s: Title */
        $errorMessage = sprintf( esc_html__( '%s field is empty!', 'jetexir' ), esc_html__( 'Title', 'jetexir' ) );

      } elseif ( empty( $formData['slug'] ) ) {
        /* translators: %s: Slug */
        $errorMessage = sprintf( esc_html__( '%s field is empty!', 'jetexir' ), esc_html__( 'Slug', 'jetexir' ) );

      } elseif ( $index >= 0 ) {
        $entry = $this->getByIndex( $index );

        if ( $entry === false ) {
          $errorMessage = esc_html__( 'Order status not found!', 'jetexir' );
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
        $entries           = Settings::get( self::orderStatusDataTableId, [], $this->addonID );
        $entries[ $index ] = $formData;
        Settings::save( self::orderStatusDataTableId, $entries, $this->addonID );
        $successMessage = esc_html__( 'The order status was successfully saved.', 'jetexir' );

      } else {
        Settings::addToArray( self::orderStatusDataTableId, $formData, $this->addonID, true );
        $successMessage = esc_html__( 'Order status added successfully.', 'jetexir' );
      }

      $dataTable = $this->getDataTable();

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
      $entry = $this->getByIndex( $index );
      if ( $entry ) {
        $this->deleteActions( $entry['slug'] );
      }

      if ( Settings::deleteFromArray( self::orderStatusDataTableId, $index, $this->addonID ) ) {
        $dataTable = $this->getDataTable();

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

  private function deleteActions( $status ): void {
    $fallbackStatus = Settings::get( 'order_status_fallback_delete', 'on-hold', $this->addonID );
    if ( $fallbackStatus ) {
      WooCommerce::changeOrdersStatus( $status, $fallbackStatus );
    }
  }

  private function getByIndex( $index ) {
    $entries = Settings::get( self::orderStatusDataTableId, [], $this->addonID );
    if ( is_array( $entries ) && ! empty( $entries ) && isset( $entries[ $index ] ) ) {
      return $entries[ $index ];
    }

    return false;
  }

  private function getFields( $index = - 1, $data = [] ): array {
    $slugAttributes = isset( $data['slug'] ) ? array( 'disabled' => 'disabled' ) : array();

    return array(
      array(
        'id'            => 'row_id',
        'type'          => 'hidden',
        'save'          => false,
        'setting_value' => $index
      ),
      array(
        'id'            => 'text_color',
        'title'         => esc_html__( 'Text color', 'jetexir' ),
        'type'          => 'wpColorPicker',
        'setting_value' => $data['text_color'] ?? '#333',
        'sanitize'      => 'color'
      ),
      array(
        'id'            => 'bg_color',
        'title'         => esc_html__( 'Background color', 'jetexir' ),
        'type'          => 'wpColorPicker',
        'setting_value' => $data['bg_color'] ?? '#ebe5ff',
        'sanitize'      => 'color'
      ),
      array(
        'id'            => 'row_bg_color',
        'title'         => esc_html__( 'Row background color', 'jetexir' ),
        'type'          => 'wpColorPicker',
        'setting_value' => $data['row_bg_color'] ?? '',
        'sanitize'      => 'color'
      ),
      array(
        'id'            => 'title',
        'title'         => esc_html__( 'Title', 'jetexir' ),
        'placeholder'   => esc_html__( 'Status title', 'jetexir' ),
        'type'          => 'text',
        'setting_value' => $data['title'] ?? '',
      ),
      array(
        'id'            => 'slug',
        'title'         => esc_html__( 'Slug', 'jetexir' ),
        'desc'          => esc_html__( 'Use english alphabetic characters', 'jetexir' ),
        'placeholder'   => esc_html__( 'Status slug', 'jetexir' ),
        'type'          => 'text',
        'setting_value' => $data['slug'] ?? '',
        'sanitize'      => 'title',
        //'attributes'    => $slugAttributes
      )
    );
  }

  private function getDataTable(): DataTableUI {
    $dataTable = new DataTableUI();
    $dataTable->setID( self::orderStatusDataTableId )
              ->setRows( Settings::get( self::orderStatusDataTableId, [], $this->addonID ) )
              ->setIdField( $dataTable::ROW_INDEX )
              ->sortable( true )
              ->setTitle( esc_html__( 'Custom Order Status', 'jetexir' ) )
              ->modalAddTitle( esc_html__( 'Add new order status', 'jetexir' ) )
              ->modalEditTitle( esc_html__( 'Edit order status', 'jetexir' ) )
              ->addNewButton( esc_html__( 'Add new', 'jetexir' ) )
              ->addAction( 'edit', '<i class="jetexir-icon-edit"></i>', $dataTable::ACTION_EDIT )
              ->addAction( 'delete', '<i class="jetexir-icon-trash"></i>', $dataTable::ACTION_DELETE )
              ->addAction( 'bulk_enable', esc_html__( 'Enable', 'jetexir' ), $dataTable::ACTION_NONE, [], $dataTable::ACTION_BULK )
              ->addAction( 'bulk_disable', esc_html__( 'Disable', 'jetexir' ), $dataTable::ACTION_NONE, [], $dataTable::ACTION_BULK )
              ->addAction( 'bulk_delete', esc_html__( 'Delete', 'jetexir' ), $dataTable::ACTION_DELETE, [], $dataTable::ACTION_BULK )
              ->addColumn( esc_html__( 'Title', 'jetexir' ), 'title', function ( $entry ) {
                return '<mark class="order-status status-' . $entry['slug'] . '"><span>' . $entry['title'] . '</span></mark>';
              }, [ 'is_html' => true ] )
              ->addColumn( esc_html__( 'Slug', 'jetexir' ), 'slug', null, [ 'hide_on_mobile' => true ] )
              ->addColumn( esc_html__( 'Status', 'jetexir' ), $dataTable::ACTIVE_FIELD );

    return $dataTable;
  }

  public function adminEnqueueScriptsAction(): void {
    $statuses = $this->getOrderStatuses( true );
    if ( empty( $statuses ) ) {
      return;
    }

    $styleID = JETEXIR_PLUGIN_SLUG . '-' . $this->addonID;
    $styles  = '#order_data h2{display: inline-block;padding:10px !important;border-radius:5px;}';
    if ( AdminPages::isSettingPage() ) {
      $styles .= '.order-status { display: inline-flex; line-height: 1; color: #454545; background: #e5e5e5; border-radius: 4px; border-bottom: 1px solid rgba(0,0,0,.05); margin: -.25em 0; cursor: inherit !important; white-space: nowrap; max-width: 100%; padding: 5px 7px;}.order-status > span { margin: 0 1em; overflow: hidden; text-overflow: ellipsis; }';
    }

    foreach ( $statuses as $status ) {
      $styles .= '.order-status.status-' . $status['slug'] . ' {background-color: ' . $status['bg_color'] . '; color: ' . $status['text_color'] . '; }';
      $styles .= '.wc-action-button-' . $status['slug'] . ' {background-color: ' . $status['bg_color'] . ' !important; color: ' . $status['text_color'] . ' !important; }';
      $styles .= '.wc-action-button-edit.' . $status['slug'] . ' {background-color: ' . $status['bg_color'] . ' !important; color: ' . $status['text_color'] . ' !important; }';
      if ( ! empty( $status['row_bg_color'] ) ) {
        $styles .= '.wp-list-table.wc-orders-list-table tr.status-' . $status['slug'] . ' {background-color: ' . $status['row_bg_color'] . ';}';
        $styles .= 'body.wc-order-status-' . $status['slug'] . ' #order_data h2 {background-color: ' . $status['row_bg_color'] . ';}';
      }
    }

    $customStatuses = array_keys( $this->getStatuses( false, true ) );
    $wcStatuses     = WooCommerce::getOrderStatuses();
    foreach ( $wcStatuses as $slug => $title ) {
      if ( ! in_array( $slug, $customStatuses, true ) && $color = Settings::get( 'order_status_wc_color_' . $slug, false, $this->addonID ) ) {
        $styles .= '.wp-list-table.wc-orders-list-table tr.status-' . $slug . ' {background-color: ' . $color . ';}';
        $styles .= 'body.wc-order-status-' . $slug . ' #order_data h2 {background-color: ' . $color . ';}';
      }
    }

    wp_register_style( $styleID, false, [], Assets::getVersion() );
    wp_enqueue_style( $styleID );
    wp_add_inline_style( $styleID, esc_html( $styles ) );
  }

  public function addSectionSettings( $sections ): array {
    $dataTable       = $this->getDataTable();
    $paymentGateways = WooCommerce::getPaymentGateways();
    $customStatuses  = array_keys( $this->getStatuses( false, true ) );
    $wcStatuses      = WooCommerce::getOrderStatuses();
    $statusColors    = [];
    foreach ( $wcStatuses as $slug => $title ) {
      if ( ! in_array( $slug, $customStatuses, true ) ) {
        $statusColors[ 'order_status_wc_color_' . $slug ] = array(
          'id'       => 'order_status_wc_color_' . $slug,
          'title'    => $title,
          'type'     => 'wpColorPicker',
          'default'  => '',
          'sanitize' => 'color'
        );
      }
    }

    $settings = [
      'data_table_ui'                => array(
        'id'         => self::orderStatusDataTableId,
        'type'       => 'dataTable',
        'data_table' => $dataTable->render()
      ),
      'order_status_start_grid'      => array(
        'id'    => 'order_status_start_grid',
        'title' => esc_html__( 'Order status', 'jetexir' ),
        'type'  => 'startgrid',
      ),
      'order_status_default'         => array(
        'id'                => 'order_status_default',
        'title'             => esc_html__( 'Default order status', 'jetexir' ),
        'type'              => 'orderStatusSelect',
        'default'           => 0,
        'option_none'       => esc_html__( 'No changes', 'jetexir' ),
        'option_none_value' => '',
        'sanitize'          => 'text'
      ),
      'order_status_fallback_delete' => array(
        'id'       => 'order_status_fallback_delete',
        'title'    => esc_html__( 'Fallback delete order status', 'jetexir' ),
        'type'     => 'orderStatusSelect',
        'default'  => 'on-hold',
        'sanitize' => 'text'
      ),
    ];

    $paymentGatewayOptions = [];
    foreach ( $paymentGateways as $gatewayID => $gatewayTitle ) {
      $paymentGatewayOptions[ 'order_status_payment_' . $gatewayID ] = array(
        'id'                => 'order_status_payment_' . $gatewayID,
        /* translators: %s: Payment gateway title */
        'title'             => sprintf( esc_html__( 'Default order status for "%s" method', 'jetexir' ), $gatewayTitle ),
        'option_none'       => esc_html__( 'No changes', 'jetexir' ),
        'option_none_value' => '',
        'type'              => 'orderStatusSelect',
        'sanitize'          => 'text'
      );
    }

    $settings = array_merge( $settings, $paymentGatewayOptions, array(
      'order_status_end_grid' => array(
        'type' => 'endgrid',
      )
    ) );

    if ( ! empty( $statusColors ) ) {
      $statusColors = array_merge(
        array(
          'order_status_row_colors_start_grid' => array(
            'id'    => 'order_status_row_colors_start_grid',
            'title' => esc_html__( 'Orders row background color', 'jetexir' ),
            'type'  => 'startgrid',
          )
        ),
        $statusColors,
        array(
          'order_status_row_colors_end_grid' => array(
            'type' => 'endgrid',
          ),
          'order_status_space'               => array(
            'type' => 'space',
            'size' => 150
          ),
        )
      );
      $settings     = array_merge( $settings, $statusColors );
    }

    $sections[ $this->addonID ] = array(
      'title'        => esc_html__( 'Status', 'jetexir' ),
      'desc'         => esc_html__( 'Custom Order Status', 'jetexir' ),
      'settings_key' => $this->addonID,
      'settings'     => $settings
    );

    return $sections;
  }

  public function info(): array {
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="#873eff" class="icon" viewBox="0 0 1024 1024"><g><path d="M959.018 208.158c.23-2.721.34-5.45.34-8.172 0-74.93-60.96-135.89-135.89-135.89-1.54 0-3.036.06-6.522.213l-611.757-.043c-1.768-.085-3.563-.17-5.424-.17-74.812 0-135.67 60.84-135.67 135.712l.188 10.952h-.306l.391 594.972-.162 20.382c0 74.03 60.22 134.25 134.24 134.25 1.668 0 7.007-.239 7.1-.239l608.934.085c2.985.357 6.216.468 9.55.468 35.815 0 69.514-13.954 94.879-39.302 25.373-25.34 39.344-58.987 39.344-94.794l-.145-12.015h.918l-.008-606.41zm-757.655 693.82-2.585-.203c-42.524 0-76.146-34.863-76.537-79.309V332.671H900.79l.46 485.186-.885 2.865c-.535 1.837-.8 3.58-.8 5.17 0 40.382-31.555 73.766-71.852 76.002l-10.816.621v-.527l-615.533-.01zM900.78 274.424H122.3l-.375-65.934.85-2.924c.52-1.82.782-3.63.782-5.247 0-42.236 34.727-76.665 78.179-76.809l.45-.068 618.177.018 2.662.203c42.329 0 76.767 34.439 76.767 76.768 0 1.326.196 2.687.655 4.532l.332.884v68.577z"/><path d="M697.67 471.435c-7.882 0-15.314 3.078-20.918 8.682l-223.43 223.439L346.599 596.84c-5.544-5.603-12.95-8.69-20.842-8.69s-15.323 3.078-20.918 8.665c-5.578 5.518-8.674 12.9-8.7 20.79-.017 7.908 3.07 15.357 8.69 20.994l127.55 127.558c5.57 5.56 13.01 8.622 20.943 8.622 7.925 0 15.364-3.06 20.934-8.63l244.247-244.247c5.578-5.511 8.674-12.883 8.7-20.783.017-7.942-3.079-15.408-8.682-20.986-5.552-5.612-12.958-8.698-20.85-8.698z"/></g></svg>';

    return array(
      'id'             => $this->addonID,
      'title'          => esc_html__( 'Order Status', 'jetexir' ),
      'desc'           => esc_html__( 'Add custom order statuses to your WooCommerce store.', 'jetexir' ),
      'tags'           => [ esc_html__( 'Order', 'jetexir' ) ],
      'cat'            => 'order',
      'icon'           => $icon,
      'more_info_link' => '{jetexir_website}/addons/custom-order-statuses',
      'settings_key'   => $this->addonID,
    );
  }
}
