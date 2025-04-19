<?php

namespace WooAssistant\App\Order;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Addons\Addon;
use WooAssistant\Admin\AdminPages;
use WooAssistant\Helper\Helper;
use WooAssistant\Helper\HTML;
use WooAssistant\Helper\Notice;
use WooAssistant\Helper\Param;
use WooAssistant\Helper\Sanitizing;
use WooAssistant\Helper\Templates;
use WooAssistant\Helper\WooCommerce;
use WooAssistant\Interfaces\AddonInterface;
use WooAssistant\Providers\UI\DataTableUI;
use WooAssistant\Settings\Settings;

class OrderStatus extends Addon implements AddonInterface {
	public string $addonID = 'order-status';

	public string $currentTab = 'order';

	private const orderStatusDataTableId = 'order_status';

	public function initAction(): void {
		add_action( 'woo_assistant_data_table_ui_order_status_action', [ $this, 'dataTableActions' ], 10, 2 );

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
			$actions[ 'mark_' . $slug ] = sprintf( __( 'Change status to %s', 'woo-assistant' ), $title );
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
					'title'  => sprintf( __( 'Change order status to %s', 'woo-assistant' ), $title ),
					'action' => $slug,
				);
			}
		}

		if ( ! empty( $statusActions ) ) {
			if ( ! empty( $actions['status']['actions'] ) && is_array( $actions['status']['actions'] ) ) {
				$actions['status']['actions'] = array_merge( $actions['status']['actions'], $statusActions );
			} else {
				$actions['status'] = array(
					'group'   => __( 'Change status: ', 'woocommerce' ),
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
					'title'  => sprintf( __( 'Change order status to %s', 'woo-assistant' ), $title ),
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
				'label_count'               => _n_noop( "$title <span class='count'>(%s)</span>", "$title <span class='count'>(%s)</span>" )
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
			$rowIDs     = array_map( 'WooAssistant\Helper\Sanitizing::int', Sanitizing::array( Param::post( 'row_ids' ) ) );
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
			$formData     = \WooAssistant\AppHelper\DataTableUI::getFormData( $this->getFields() );
			$errorMessage = '';
			$entry        = false;

			if ( empty( $formData['title'] ) ) {
				$errorMessage = sprintf( __( '%s field is empty!', 'woo-assistant' ), __( 'Title', 'woo-assistant' ) );
			} elseif ( empty( $formData['slug'] ) ) {
				$errorMessage = sprintf( __( '%s field is empty!', 'woo-assistant' ), __( 'Slug', 'woo-assistant' ) );
			}

			if ( $index >= 0 ) {
				$entry = $this->getByIndex( $index );

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
				$entries           = Settings::get( self::orderStatusDataTableId, [], $this->addonID );
				$entries[ $index ] = $formData;
				Settings::save( self::orderStatusDataTableId, $entries, $this->addonID );
				$successMessage = __( 'The order status was successfully saved.', 'woo-assistant' );

			} else {
				Settings::addToArray( self::orderStatusDataTableId, $formData, $this->addonID, true );
				$successMessage = __( 'Order status added successfully.', 'woo-assistant' );
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
		          ->setTitle( __( 'Custom Order Status', 'woo-assistant' ) )
		          ->modalAddTitle( __( 'Add new order status', 'woo-assistant' ) )
		          ->modalEditTitle( __( 'Edit order status', 'woo-assistant' ) )
		          ->addNewButton( __( 'Add new', 'woo-assistant' ) )
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

		return $dataTable;
	}

	public function adminEnqueueScriptsAction(): void {
		$statuses = $this->getOrderStatuses( true );
		if ( empty( $statuses ) ) {
			return;
		}

		$styleID = WOOASSISTANT_PLUGIN_SLUG . '-' . $this->addonID;
		$styles  = '#order_data h2{display: inline-block;padding:10px !important;border-radius:5px;}';
		if ( AdminPages::isSettingPage() ) {
			$styles .= '.order-status { display: inline-flex; line-height: 2.5em; color: #454545; background: #e5e5e5; border-radius: 4px; border-bottom: 1px solid rgba(0,0,0,.05); margin: -.25em 0; cursor: inherit !important; white-space: nowrap; max-width: 100%; }.order-status > span { margin: 0 1em; overflow: hidden; text-overflow: ellipsis; }';
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

		wp_register_style( $styleID, false );
		wp_enqueue_style( $styleID );
		wp_add_inline_style( $styleID, $styles );
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
				'title' => __( 'Order status', 'woo-assistant' ),
				'type'  => 'startgrid',
			),
			'order_status_default'         => array(
				'id'                => 'order_status_default',
				'title'             => __( 'Default order status', 'woo-assistant' ),
				'type'              => 'orderStatusSelect',
				'default'           => 0,
				'option_none'       => 'No changes',
				'option_none_value' => '',
				'sanitize'          => 'text'
			),
			'order_status_fallback_delete' => array(
				'id'       => 'order_status_fallback_delete',
				'title'    => __( 'Fallback delete order status', 'woo-assistant' ),
				'type'     => 'orderStatusSelect',
				'default'  => 'on-hold',
				'sanitize' => 'text'
			),
		];

		$paymentGatewayOptions = [];
		foreach ( $paymentGateways as $gatewayID => $gatewayTitle ) {
			$paymentGatewayOptions[ 'order_status_payment_' . $gatewayID ] = array(
				'id'                => 'order_status_payment_' . $gatewayID,
				'title'             => sprintf( __( 'Default order status for "%s" method', 'woo-assistant' ), $gatewayTitle ),
				'option_none'       => 'No changes',
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
						'title' => __( 'Orders row background color', 'woo-assistant' ),
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
					),
				)
			);
			$settings     = array_merge( $settings, $statusColors );
		}

		$sections[ $this->addonID ] = array(
			'title'        => __( 'Status', 'woo-assistant' ),
			'desc'         => __( 'Custom order status', 'woo-assistant' ),
			'settings_key' => $this->addonID,
			'settings'     => $settings
		);

		return $sections;
	}

	public function info(): array {
		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Order Status', 'woo-assistant' ),
			'desc'           => __( 'Add custom order statuses to WooCommerce.', 'woo-assistant' ),
			'tags'           => [ __( 'Order', 'woo-assistant' ) ],
			'cat'            => 'order',
			'more_info_link' => 'https://parsa.ws',
			'settings_key'   => $this->addonID,
		);
	}
}