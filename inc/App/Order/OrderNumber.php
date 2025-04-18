<?php

namespace WooAssistant\App\Order;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Addons\Addon;
use WooAssistant\Helper\Helper;
use WooAssistant\Helper\Notice;
use WooAssistant\Helper\Param;
use WooAssistant\Helper\WooCommerce;
use WooAssistant\Interfaces\AddonInterface;
use WooAssistant\Settings\Settings;

class OrderNumber extends Addon implements AddonInterface {
	public string $addonID = 'order-number';

	public string $currentTab = 'order';

	public function initAction(): void {
		add_action( 'woo_assistant_save_settings_success', [ $this, 'updateOrderNumbers' ], 10, 3 );

		// Update order number
		add_action( 'woocommerce_new_order', [ $this, 'updateOrderNumber' ] );
		add_action( 'woocommerce_update_order', [ $this, 'updateOrderNumber' ] );
		add_action( 'woocommerce_process_shop_order_meta', [ $this, 'updateOrderNumber' ] );
		add_action( 'woocommerce_order_status_changed', [ $this, 'updateOrderNumber' ] );
		add_action( 'woocommerce_api_create_order', [ $this, 'updateOrderNumber' ] );

		// Get order number
		add_filter( 'woocommerce_order_number', [ $this, 'getOrderNumber' ], PHP_INT_MAX, 2 );

		// Order number search
		add_filter( 'woocommerce_shop_order_search_fields', [ $this, 'searchByMetaOrderNumber' ] );
		add_filter( 'woocommerce_order_table_search_query_meta_keys', [ $this, 'searchByMetaOrderNumber' ] );
		add_filter( 'woocommerce_hpos_admin_search_filters', [ $this, 'hposAddOrderNumberSearchFilter' ] );
		add_filter( 'woocommerce_hpos_generate_where_for_search_filter', [
			$this,
			'hposWhereOrderNumberSearchFilter'
		], 10, 4 );

		// Order number tracking
		if ( Settings::get( 'order_number_tracking', true, $this->addonID ) ) {
			remove_filter( 'woocommerce_shortcode_order_tracking_order_id', 'wc_sanitize_order_id' );
			add_filter( 'woocommerce_shortcode_order_tracking_order_id', [ $this, 'setOrderTrackingID' ], PHP_INT_MAX );
		}
	}

	public function hposWhereOrderNumberSearchFilter( $where, $searchTerm, $searchFilter, $query ) {
		global $wpdb;

		if ( $searchFilter === 'order_number' ) {
			$orderTable = $query->get_table_name( 'orders' );
			$metaTable  = $query->get_table_name( 'meta' );
			$where      = $wpdb->prepare( "`$orderTable`.id in (SELECT order_id FROM `$metaTable` WHERE meta_key = %s AND meta_value LIKE %s)",
				'_wa_order_number',
				'%' . $wpdb->esc_like( $searchTerm ) . '%' );
		}

		return $where;
	}

	public function hposAddOrderNumberSearchFilter( $options ): array {
		return Helper::arrayInsertAfter( $options, 1, [ 'order_number' => __( 'Order Number', 'woo-assistant' ) ] );
	}

	public function searchByMetaOrderNumber( $metaKeys ) {
		$metaKeys[] = '_wa_order_number';

		return $metaKeys;
	}

	public function setOrderTrackingID( $orderID ) {
		global $post;
		$postTemp = $post;

		if ( WooCommerce::hposEnabled() ) {
			$orders = wc_get_orders( array(
				'type'       => 'shop_order',
				'limit'      => 1,
				'return'     => 'ids',
				'meta_query' => [
					[
						'key'        => '_wa_order_number',
						'value'      => $orderID,
						'comparison' => '='
					],
				],
			) );
		} else {
			$orders = get_posts( array(
				'post_type'      => 'shop_order',
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'meta_key'       => '_wa_order_number',
				'meta_value'     => $orderID,
			) );
		}

		$post     = $postTemp;
		$_orderID = $orders ? current( $orders ) : null;
		if ( ! is_null( $_orderID ) ) {
			return $_orderID;
		}

		return $orderID;
	}

	/**
	 * @param int $orderID
	 * @param \WC_Order $order
	 *
	 * @return mixed
	 */
	public function getOrderNumber( $orderID, $order ) {
		$orderNumber = WooCommerce::getOrderMeta( $order->get_id(), '_wa_order_number' );
		if ( ! empty( $orderNumber ) ) {
			return $orderNumber;
		}

		return $orderID;
	}

	public function updateOrderNumber( $orderID ): void {
		$order = wc_get_order( $orderID );
		if ( in_array( $order->get_status(), [ 'draft', 'checkout-draft' ] ) ) {
			return;
		}

		$this->setOrderNumber( $orderID );
	}

	public function setOrderNumber( $orderID, $reset = false ): void {
		if ( $reset || empty( WooCommerce::getOrderMeta( $orderID, '_wa_order_number' ) ) ) {
			$format = Settings::get( 'order_number_format', '', $this->addonID );

			if ( empty( $format ) ) {
				return;
			}

			if ( $format === 'hash_crc32' ) {
				$orderNumber = sprintf( '%u', crc32( $orderID ) );

			} else {
				$number     = $nextNumber = (int) Settings::get( 'order_number_next', 1, $this->addonID );
				$length     = (int) Settings::get( 'order_number_length', 0, $this->addonID );
				$prefix     = Settings::get( 'order_number_prefix', '', $this->addonID );
				$dateFormat = Settings::get( 'order_number_date_format', '', $this->addonID );
				$orderDate  = false;

				if ( $length > 0 && strlen( $nextNumber ) < $length ) {
					$padding = $length - strlen( $nextNumber );

					if ( $padding > 0 ) {
						$number = str_repeat( '0', $padding ) . $nextNumber;
					}
				}

				if ( $dateFormat && in_array( $format, [ 'date_sequential', 'prefix_date_sequential' ] ) ) {
					$order = wc_get_order( $orderID );
					if ( $order ) {
						$_orderDate = $order->get_date_created();
						if ( ! is_null( $_orderDate ) ) {
							$orderDate = $_orderDate->format( $dateFormat );
						}
					}
				}

				if ( $format === 'prefix_sequential' ) {
					$orderNumber = $prefix . $number;
				} elseif ( $orderDate && $format === 'date_sequential' ) {
					$orderNumber = $orderDate . $number;
				} elseif ( $orderDate && $format === 'prefix_date_sequential' ) {
					$orderNumber = $prefix . $orderDate . $number;
				} else {
					$orderNumber = $number;
				}

				Settings::save( 'order_number_next', $nextNumber + 1, $this->addonID );
			}

			WooCommerce::updateOrderMeta( $orderID, '_wa_order_number', $orderNumber );
		}
	}

	public function updateOrderNumbers( $tab, $section, $options ): void {
		if ( $tab === $this->currentTab && $section === $this->addonID && Param::post( WOOASSISTANT_INPUT_PREFIX . 'order_number_update' ) && Settings::get( 'order_number_format', '', $this->addonID ) ) {
			$limit       = 100;
			$updateCount = $offset = 0;
			$start       = (int) Settings::get( 'order_number_start', 1, $this->addonID );
			Settings::save( 'order_number_next', $start, $this->addonID );

			while ( true ) {
				$orders = wc_get_orders( array(
					'type'    => 'shop_order',
					'status'  => 'any',
					'limit'   => $limit,
					'offset'  => $offset,
					'return'  => 'ids',
					'orderby' => 'date',
					'order'   => 'ASC'
				) );
				if ( empty( $orders ) ) {
					break;
				}

				foreach ( $orders as $orderID ) {
					$order = wc_get_order( $orderID );
					if ( in_array( $order->get_status(), [ 'draft', 'checkout-draft' ] ) ) {
						continue;
					}

					$this->setOrderNumber( $orderID, true );
				}

				$updateCount += count( $orders );

				$offset += $limit;
			}

			Notice::add( $tab, sprintf( __( '%d Order numbers updated.', 'woo-assistant' ), $updateCount ), 'info' );
		}
	}

	public function addSectionSettings( $sections ): array {
		$sections[ $this->addonID ] = array(
			'title'      => __( 'Number', 'woo-assistant' ),
			'desc'       => __( 'Custom order number', 'woo-assistant' ),
			'options_id' => $this->addonID,
			'settings'   => array(
				'order_number_start_grid'  => array(
					'title' => __( 'Order number', 'woo-assistant' ),
					'type'  => 'startgrid',
				),
				'order_number_format'      => array(
					'id'                => 'order_number_format',
					'title'             => __( 'Order numbers format', 'woo-assistant' ),
					'type'              => 'select',
					'options'           => array(
						'sequential'             => __( 'Sequential number', 'woo-assistant' ),
						'prefix_sequential'      => __( 'Prefix + Sequential number', 'woo-assistant' ),
						'date_sequential'        => __( 'Date + Sequential number', 'woo-assistant' ),
						'prefix_date_sequential' => __( 'Prefix + Date + Sequential number', 'woo-assistant' ),
						'hash_crc32'             => __( 'Pseudorandom - crc32 Hash (max 10 digits)', 'woo-assistant' ),
					),
					'option_none'       => __( 'Order ID', 'woo-assistant' ),
					'option_none_value' => '',
					'default'           => '',
					'sanitize'          => 'text'
				),
				'order_number_length'      => array(
					'id'         => 'order_number_length',
					'title'      => __( 'Order number length', 'woo-assistant' ),
					'desc'       => __( 'Minimum length of number (zeros add to the left). This changes the order number length for all orders. Set to 5 for 00001, leave as 0 to disable.', 'woo-assistant' ),
					'type'       => 'number',
					'default'    => 0,
					'attributes' => array(
						'placeholder' => 0,
						'step'        => 1,
						'min'         => 0,
						'max'         => 20,
					),
					'sanitize'   => 'int'
				),
				'order_number_prefix'      => array(
					'id'      => 'order_number_prefix',
					'title'   => __( 'Prefix', 'woo-assistant' ),
					'desc'    => __( 'Prefix will be appended at the beginning of the order number.', 'woo-assistant' ),
					'type'    => 'text',
					'default' => 'wa',
				),
				'order_number_date_format' => array(
					'id'      => 'order_number_date_format',
					'title'   => __( 'Date format', 'woo-assistant' ),
					'desc'    => __( 'Date will be appended at the beginning of the order number or after prefix.', 'woo-assistant' ),
					'type'    => 'text',
					'default' => 'Ymd',
				),
				'order_number_start'       => array(
					'id'         => 'order_number_start',
					'title'      => __( 'Start Number', 'woo-assistant' ),
					'desc'       => __( 'Use in "Sequential Number" methods', 'woo-assistant' ),
					'type'       => 'number',
					'default'    => 1,
					'attributes' => array(
						'placeholder' => 1,
						'step'        => 1,
						'min'         => 1,
					),
					'sanitize'   => 'int'
				),
				'order_number_next'        => array(
					'id'         => 'order_number_next',
					'title'      => __( 'Next Number', 'woo-assistant' ),
					'desc'       => __( 'Use in "Sequential Number" methods', 'woo-assistant' ),
					'type'       => 'number',
					'default'    => 1,
					'save'       => false,
					'attributes' => array(
						'disabled' => true,
					),
					'sanitize'   => 'int'
				),
				'order_number_tracking'    => [
					'id'       => 'order_number_tracking',
					'title'    => __( 'Order tracking by custom number', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => true,
					'sanitize' => 'bool'
				],
				'order_number_end_grid'    => array(
					'type' => 'endgrid',
				),

				'order_number_hr'                => array(
					'type' => 'hr',
				),
				'order_number_update_start_grid' => array(
					'title' => __( 'Apply for all orders', 'woo-assistant' ),
					'type'  => 'startgrid',
				),
				'order_number_notice'            => array(
					'id'      => 'order_number_notice',
					'notices' => array(
						array(
							'message' => __( 'Update all order numbers based on the new settings.', 'woo-assistant' ),
							'type'    => 'error',
						)
					),
					'type'    => 'notice',
				),
				'order_number_update'            => [
					'id'       => 'order_number_update',
					'title'    => __( 'Update all order numbers', 'woo-assistant' ),
					'type'     => 'toggle',
					'value'    => 1,
					'default'  => false,
					'save'     => false,
					'sanitize' => 'bool'
				],
				'order_number_update_end_grid'   => array(
					'type' => 'endgrid',
				),
			)
		);

		return $sections;
	}

	public function info(): array {
		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Order Number', 'woo-assistant' ),
			'desc'           => __( 'Add custom order numbers to WooCommerce.', 'woo-assistant' ),
			'tags'           => [ __( 'Order', 'woo-assistant' ) ],
			'cat'            => 'order',
			'more_info_link' => 'https://parsa.ws'
		);
	}
}