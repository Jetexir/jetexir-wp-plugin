<?php

namespace WooAssistant\App\Order;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Addons\Addon;
use WooAssistant\Admin\AdminPages;
use WooAssistant\Helper\HTML;
use WooAssistant\Helper\Notice;
use WooAssistant\Helper\Param;
use WooAssistant\Helper\Sanitizing;
use WooAssistant\Helper\Templates;
use WooAssistant\Interfaces\AddonInterface;
use WooAssistant\Providers\UI\DataTableUI;
use WooAssistant\Settings\Settings;

class OrderStatus extends Addon implements AddonInterface {
	public string $addonID = 'order-status';

	public string $currentTab = 'order';
	private const sectionID = 'order-status';

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

	public function getStatuses( $addPrefix = false ): array {
		$entries  = $this->getOrderStatuses();
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
		$entries = Settings::get( self::orderStatusDataTableId, [], self::sectionID );
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
			$statuses   = Settings::get( self::orderStatusDataTableId, [], self::sectionID );

			foreach ( $statuses as $statusIndex => $status ) {
				if ( in_array( $statusIndex, $rowIDs, true ) ) {
					if ( $bulkAction === 'bulk_delete' ) {
						unset( $statuses[ $statusIndex ] );

					} elseif ( $bulkAction === 'bulk_enable' ) {
						$statuses[ $statusIndex ]['is_active'] = true;

					} elseif ( $bulkAction === 'bulk_disable' ) {
						$statuses[ $statusIndex ]['is_active'] = false;
					}
				}
			}

			$statuses = array_values( $statuses );
			Settings::save( self::orderStatusDataTableId, $statuses, self::sectionID );
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
					'message' => Notice::addAndDisplay( self::sectionID, array(
						array(
							'type'    => 'error',
							'message' => $errorMessage
						)
					), false ),
				], 403 );
			}

			$formData = array_map( 'trim', $formData );

			if ( $entry !== false ) {
				$entries           = Settings::get( self::orderStatusDataTableId, [], self::sectionID );
				$entries[ $index ] = $formData;
				Settings::save( self::orderStatusDataTableId, $entries, self::sectionID );
				$successMessage = __( 'The order status was successfully saved.', 'woo-assistant' );

			} else {
				Settings::addToArray( self::orderStatusDataTableId, $formData, self::sectionID, true );
				$successMessage = __( 'Order status added successfully.', 'woo-assistant' );
			}

			$dataTable = $this->getDataTable();

			wp_send_json_success( [
				'table'     => $dataTable->renderHTML( Templates::getPath( 'data-table/data_table_table.php' ) ),
				'row_count' => $dataTable->getRowCount(),
				'message'   => Notice::addAndDisplay( self::sectionID, array(
					array(
						'type'    => 'success',
						'message' => $successMessage,
					)
				), false )
			] );

		} elseif ( $action === 'delete' ) {
			if ( Settings::deleteFromArray( self::orderStatusDataTableId, $index, self::sectionID ) ) {
				$dataTable = $this->getDataTable();

				wp_send_json_success( [
					'table'     => $dataTable->renderHTML( Templates::getPath( 'data-table/data_table_table.php' ) ),
					'row_count' => $dataTable->getRowCount(),
					'message'   => Notice::addAndDisplay( self::sectionID, array(
						array(
							'type'    => 'success',
							'message' => __( 'Order status removed!', 'woo-assistant' ),
						)
					), false ),
				] );

			} else {
				wp_send_json_error( [
					'error'   => 'required-field',
					'message' => Notice::addAndDisplay( self::sectionID, array(
						array(
							'type'    => 'error',
							'message' => __( 'Selected item not found!', 'woo-assistant' ),
						)
					), false ),
				], 403 );
			}
		}
	}

	private function getByIndex( $index ) {
		$entries = Settings::get( self::orderStatusDataTableId, [], self::sectionID );
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
				'attributes'    => $slugAttributes
			)
		);
	}

	private function getDataTable(): DataTableUI {
		$dataTable = new DataTableUI();
		$dataTable->setID( self::orderStatusDataTableId )
		          ->setRows( Settings::get( self::orderStatusDataTableId, [], self::sectionID ) )
		          ->setIdField( $dataTable::ROW_INDEX )
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
		$styles  = '';
		if ( AdminPages::isSettingPage() ) {
			$styles .= '.order-status { display: inline-flex; line-height: 2.5em; color: #454545; background: #e5e5e5; border-radius: 4px; border-bottom: 1px solid rgba(0,0,0,.05); margin: -.25em 0; cursor: inherit !important; white-space: nowrap; max-width: 100%; }.order-status > span { margin: 0 1em; overflow: hidden; text-overflow: ellipsis; }';
		}

		foreach ( $statuses as $status ) {
			$styles .= '.order-status.status-' . $status['slug'] . ' {background-color: ' . $status['bg_color'] . '; color: ' . $status['text_color'] . '; }';
			$styles .= '.wc-action-button-' . $status['slug'] . ' {background-color: ' . $status['bg_color'] . ' !important; color: ' . $status['text_color'] . ' !important; }';
			$styles .= '.wc-action-button-edit.' . $status['slug'] . ' {background-color: ' . $status['bg_color'] . ' !important; color: ' . $status['text_color'] . ' !important; }';
			if ( ! empty( $status['row_bg_color'] ) ) {
				$styles .= '.wp-list-table.wc-orders-list-table tr.status-' . $status['slug'] . ' {background-color: ' . $status['row_bg_color'] . ';}';
			}
		}

		wp_register_style( $styleID, false );
		wp_enqueue_style( $styleID );
		wp_add_inline_style( $styleID, $styles );
	}

	public function addSectionSettings( $sections ): array {
		$dataTable = $this->getDataTable();

		$sections[ self::sectionID ] = array(
			'title'    => __( 'Order Status', 'woo-assistant' ),
			'desc'     => __( 'Custom order status', 'woo-assistant' ),
			'settings' => [
				'data_table_ui'            => array(
					'id'         => self::orderStatusDataTableId,
					'type'       => 'dataTable',
					'data_table' => $dataTable->render()
				),
				'product_call_start_grid'  => array(
					'id'    => 'product_social_share_start_grid_2',
					'title' => __( 'Call for Price', 'woo-assistant' ),
					'type'  => 'startgrid',
				),
				'product_call_empty_price' => array(
					'id'       => 'product_call_empty_price',
					'title'    => __( 'Empty price', 'woo-assistant' ),
					'desc'     => __( 'Display custom text for product with empty price', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => true,
					'sanitize' => 'bool'
				),
				'product_call_end_grid'    => array(
					'type' => 'endgrid',
				),
			]
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
			'more_info_link' => 'https://parsa.ws'
		);
	}
}