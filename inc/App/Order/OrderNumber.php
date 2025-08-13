<?php

namespace WooAssistant\App\Order;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Addons\Addon;
use WooAssistant\Helper\Helper;
use WooAssistant\Helper\Notice;
use WooAssistant\Helper\Param;
use WooAssistant\Helper\WooCommerce;
use WooAssistant\Interfaces\AddonInterface;

class OrderNumber extends Addon implements AddonInterface {
	public string $addonID = 'order-number';

	public string $currentTab = 'order';

	public function initAction(): void {
		if ( $this->getSetting( 'order_number_format', '' ) ) {
			// Update all order numbers
			add_action( 'woo_assistant_save_settings_success', [ $this, 'updateOrderNumbers' ], 999999, 3 );

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
			if ( $this->getSetting( 'order_number_tracking', true ) ) {
				remove_filter( 'woocommerce_shortcode_order_tracking_order_id', 'wc_sanitize_order_id' );
				add_filter( 'woocommerce_shortcode_order_tracking_order_id', [
					$this,
					'setOrderTrackingID'
				], PHP_INT_MAX );
			}
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
		if ( in_array( $order->get_status(), [ 'draft', 'checkout-draft' ] ) ) {
			return $orderID;
		}

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
			$format = $this->getSetting( 'order_number_format', '' );

			if ( empty( $format ) ) {
				return;
			}

			if ( $format === 'hash_crc32' ) {
				$orderNumber = sprintf( '%u', crc32( $orderID ) );

			} else {
				$number     = $nextNumber = (int) $this->getSetting( 'order_number_next', 1 );
				$length     = (int) $this->getSetting( 'order_number_length', 0 );
				$prefix     = $this->getSetting( 'order_number_prefix', '' );
				$dateFormat = $this->getSetting( 'order_number_date_format', '' );
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

				$this->saveSetting( 'order_number_next', $nextNumber + 1 );
			}

			WooCommerce::updateOrderMeta( $orderID, '_wa_order_number', $orderNumber );
		}
	}

	public function updateOrderNumbers( $tab, $section, $options ): void {
		if ( $tab === $this->currentTab && $section === $this->addonID && Param::post( WOOASSISTANT_INPUT_PREFIX . 'order_number_update' ) && $this->getSetting( 'order_number_format', '' ) ) {
			$limit       = 100;
			$updateCount = $offset = 0;
			$start       = (int) $this->getSetting( 'order_number_start', 1 );
			$this->saveSetting( 'order_number_next', $start );

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

					if ( is_bool( $order ) || in_array( $order->get_status(), [ 'draft', 'checkout-draft' ] ) ) {
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
			'title'        => __( 'Number', 'woo-assistant' ),
			'desc'         => __( 'Custom order number', 'woo-assistant' ),
			'settings_key' => $this->addonID,
			'settings'     => array(
				'order_number_start_grid'  => array(
					'title' => __( 'Order number', 'woo-assistant' ),
					'type'  => 'startgrid',
				),
				'order_number_format'      => array(
					'id'                => 'order_number_format',
					'title'             => __( 'Order number format', 'woo-assistant' ),
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
		$icon = '<svg fill="#873eff" viewBox="-2.4 -2.4 28.80 28.80" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"><path transform="translate(-2.4, -2.4), scale(0.8999999999999999)" d="M16,28.756961747072637C18.342116156261483,28.800612717858325,20.79431054232827,30.17089737856416,22.913035507950163,29.171688683284692C25.02859553827587,28.173972598381397,25.92162917308144,25.664784989920697,26.979787993488834,23.57880044089343C27.94299214989713,21.680003257448902,28.319974862674268,19.627857064674174,28.8058838884048,17.554915818644105C29.343779197846253,15.26019537933774,31.09205937550239,12.779293359815254,30.000963277110294,10.69013502149739C28.872250188514084,8.528950168866844,25.4974902668516,8.943880143872322,23.545568677875252,7.482813645010561C21.80502122943586,6.1799664502480995,21.348695191193194,3.355678411634581,19.28568389584161,2.6694564151778284C17.236684278710673,1.9878951405619811,14.901258591161545,2.894417705502044,13.03933560212359,3.9881123543646417C11.340440121388227,4.986044549840232,10.809538134637716,7.170344516438108,9.356981379933798,8.50158205932585C7.936300074829726,9.80360642684793,5.73561261628025,10.10780536899081,4.6355280004668735,11.690024273676322C3.459168754799676,13.381946704406854,3.2762700490885863,15.52413528337243,3.0502359096519207,17.572386038110466C2.7803067757647972,20.018399519029803,2.007764279686789,22.684743791104662,3.183650174359281,24.84648754300276C4.360457812042172,27.009925823428343,6.8653128781879555,28.197776345830203,9.220896416495657,28.91650272170169C11.41470295982177,29.58586833773953,13.70674668980123,28.714221453009912,16,28.756961747072637" fill="#fff" strokewidth="0"></path></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#873eff" stroke-width="0.24000000000000005"> <path d="M19.5134329,15.9821968 C19.5091986,15.9890656 19.5047785,15.9958679 19.5001715,16.0025976 L16.9125836,19.7824443 C16.7565939,20.0103079 16.4454193,20.0685733 16.2175557,19.9125836 C15.9896921,19.7565939 15.9314267,19.4454193 16.0874164,19.2175557 L17.6070397,16.9977498 C17.5715461,16.9992449 17.5358612,17 17.5,17 C16.1192881,17 15,15.8807119 15,14.5 C15,13.1192881 16.1192881,12 17.5,12 C18.8807119,12 20,13.1192881 20,14.5 C20,15.0548368 19.819255,15.5674584 19.5134329,15.9821968 L19.5134329,15.9821968 Z M18,10 L18.5,10 C18.7761424,10 19,10.2238576 19,10.5 C19,10.7761424 18.7761424,11 18.5,11 L16.5,11 C16.2238576,11 16,10.7761424 16,10.5 C16,10.2238576 16.2238576,10 16.5,10 L17,10 L17,5.70710678 L16.8535534,5.85355339 C16.6582912,6.04881554 16.3417088,6.04881554 16.1464466,5.85355339 C15.9511845,5.65829124 15.9511845,5.34170876 16.1464466,5.14644661 L17.1464466,4.14644661 C17.461429,3.83146418 18,4.05454757 18,4.5 L18,10 L18,10 Z M8,5.70710678 L8,19.508331 C8,19.7844734 7.77614237,20.008331 7.5,20.008331 C7.22385763,20.008331 7,19.7844734 7,19.508331 L7,5.70710678 L4.85355339,7.85355339 C4.65829124,8.04881554 4.34170876,8.04881554 4.14644661,7.85355339 C3.95118446,7.65829124 3.95118446,7.34170876 4.14644661,7.14644661 L7.14644661,4.14644661 C7.34170876,3.95118446 7.65829124,3.95118446 7.85355339,4.14644661 L10.8535534,7.14644661 C11.0488155,7.34170876 11.0488155,7.65829124 10.8535534,7.85355339 C10.6582912,8.04881554 10.3417088,8.04881554 10.1464466,7.85355339 L8,5.70710678 Z M17.5,16 C18.3284271,16 19,15.3284271 19,14.5 C19,13.6715729 18.3284271,13 17.5,13 C16.6715729,13 16,13.6715729 16,14.5 C16,15.3284271 16.6715729,16 17.5,16 Z"></path> </g><g id="SVGRepo_iconCarrier"> <path d="M19.5134329,15.9821968 C19.5091986,15.9890656 19.5047785,15.9958679 19.5001715,16.0025976 L16.9125836,19.7824443 C16.7565939,20.0103079 16.4454193,20.0685733 16.2175557,19.9125836 C15.9896921,19.7565939 15.9314267,19.4454193 16.0874164,19.2175557 L17.6070397,16.9977498 C17.5715461,16.9992449 17.5358612,17 17.5,17 C16.1192881,17 15,15.8807119 15,14.5 C15,13.1192881 16.1192881,12 17.5,12 C18.8807119,12 20,13.1192881 20,14.5 C20,15.0548368 19.819255,15.5674584 19.5134329,15.9821968 L19.5134329,15.9821968 Z M18,10 L18.5,10 C18.7761424,10 19,10.2238576 19,10.5 C19,10.7761424 18.7761424,11 18.5,11 L16.5,11 C16.2238576,11 16,10.7761424 16,10.5 C16,10.2238576 16.2238576,10 16.5,10 L17,10 L17,5.70710678 L16.8535534,5.85355339 C16.6582912,6.04881554 16.3417088,6.04881554 16.1464466,5.85355339 C15.9511845,5.65829124 15.9511845,5.34170876 16.1464466,5.14644661 L17.1464466,4.14644661 C17.461429,3.83146418 18,4.05454757 18,4.5 L18,10 L18,10 Z M8,5.70710678 L8,19.508331 C8,19.7844734 7.77614237,20.008331 7.5,20.008331 C7.22385763,20.008331 7,19.7844734 7,19.508331 L7,5.70710678 L4.85355339,7.85355339 C4.65829124,8.04881554 4.34170876,8.04881554 4.14644661,7.85355339 C3.95118446,7.65829124 3.95118446,7.34170876 4.14644661,7.14644661 L7.14644661,4.14644661 C7.34170876,3.95118446 7.65829124,3.95118446 7.85355339,4.14644661 L10.8535534,7.14644661 C11.0488155,7.34170876 11.0488155,7.65829124 10.8535534,7.85355339 C10.6582912,8.04881554 10.3417088,8.04881554 10.1464466,7.85355339 L8,5.70710678 Z M17.5,16 C18.3284271,16 19,15.3284271 19,14.5 C19,13.6715729 18.3284271,13 17.5,13 C16.6715729,13 16,13.6715729 16,14.5 C16,15.3284271 16.6715729,16 17.5,16 Z"></path> </g></svg>';

		return array(
			'id'             => $this->addonID,
			'title'          => __( 'Order Number', 'woo-assistant' ),
			'desc'           => __( 'Add custom order numbers to your WooCommerce store.', 'woo-assistant' ),
			'tags'           => [ __( 'Order', 'woo-assistant' ) ],
			'cat'            => 'order',
			'icon'           => $icon,
			'more_info_link' => 'https://parsa.ws',
			'settings_key'   => $this->addonID,
		);
	}
}