<?php

namespace Jetexir\App\Order;

defined( 'ABSPATH' ) || exit;

use Jetexir\Addons\Addon;
use Jetexir\Helper\Helper;
use Jetexir\Helper\Notice;
use Jetexir\Helper\Param;
use Jetexir\Helper\WooCommerce;
use Jetexir\Interfaces\AddonInterface;

class OrderNumber extends Addon implements AddonInterface {
  public string $addonID = 'order-number';

  public string $currentTab = 'order';

  public function initAction(): void {
    if ( $this->getSetting( 'order_number_format', '' ) ) {
      // Update all order numbers
      add_action( 'jetexir_save_settings_success', [ $this, 'updateOrderNumbers' ], 999999, 3 );

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
      $where      = $wpdb->prepare( "`%s`.id in (SELECT order_id FROM `%s` WHERE meta_key = %s AND meta_value LIKE %s)",
        $orderTable,
        $metaTable,
        '_jetexir_order_number',
        '%' . $wpdb->esc_like( $searchTerm ) . '%' );
    }

    return $where;
  }

  public function hposAddOrderNumberSearchFilter( $options ): array {
    return Helper::arrayInsertAfter( $options, 1, [ 'order_number' => esc_html__( 'Order Number', 'jetexir' ) ] );
  }

  public function searchByMetaOrderNumber( $metaKeys ) {
    $metaKeys[] = '_jetexir_order_number';

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
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        'meta_query' => [
          [
            'key'        => '_jetexir_order_number',
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
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
        'meta_key'       => '_jetexir_order_number',
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
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

    $orderNumber = WooCommerce::getOrderMeta( $order->get_id(), '_jetexir_order_number' );
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
    if ( $reset || empty( WooCommerce::getOrderMeta( $orderID, '_jetexir_order_number' ) ) ) {
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

      WooCommerce::updateOrderMeta( $orderID, '_jetexir_order_number', $orderNumber );
    }
  }

  public function updateOrderNumbers( $tab, $section, $options ): void {
    if ( $tab === $this->currentTab && $section === $this->addonID && Param::post( JETEXIR_INPUT_PREFIX . 'order_number_update' ) && $this->getSetting( 'order_number_format', '' ) ) {
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

      /* translators: %d: Order number updated count */
      Notice::add( $tab, sprintf( esc_html__( '%d Order numbers updated.', 'jetexir' ), $updateCount ), 'info' );
    }
  }

  public function addSectionSettings( $sections ): array {
    $sections[ $this->addonID ] = array(
      'title'        => esc_html__( 'Number', 'jetexir' ),
      'desc'         => esc_html__( 'Custom order number', 'jetexir' ),
      'settings_key' => $this->addonID,
      'settings'     => array(
        'order_number_start_grid'  => array(
          'title' => esc_html__( 'Order number', 'jetexir' ),
          'type'  => 'startgrid',
        ),
        'order_number_format'      => array(
          'id'                => 'order_number_format',
          'title'             => esc_html__( 'Order number format', 'jetexir' ),
          'type'              => 'select',
          'options'           => array(
            'sequential'             => esc_html__( 'Sequential number', 'jetexir' ),
            'prefix_sequential'      => esc_html__( 'Prefix + Sequential number', 'jetexir' ),
            'date_sequential'        => esc_html__( 'Date + Sequential number', 'jetexir' ),
            'prefix_date_sequential' => esc_html__( 'Prefix + Date + Sequential number', 'jetexir' ),
            'hash_crc32'             => esc_html__( 'Pseudorandom - crc32 Hash (max 10 digits)', 'jetexir' ),
          ),
          'option_none'       => esc_html__( 'Order ID', 'jetexir' ),
          'option_none_value' => '',
          'default'           => '',
          'sanitize'          => 'text'
        ),
        'order_number_length'      => array(
          'id'         => 'order_number_length',
          'title'      => esc_html__( 'Order number length', 'jetexir' ),
          'desc'       => esc_html__( 'Minimum length of number (zeros add to the left). This changes the order number length for all orders. Set to 5 for 00001, leave as 0 to disable.', 'jetexir' ),
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
          'title'   => esc_html__( 'Prefix', 'jetexir' ),
          'desc'    => esc_html__( 'Prefix will be appended at the beginning of the order number.', 'jetexir' ),
          'type'    => 'text',
          'default' => 'afw',
        ),
        'order_number_date_format' => array(
          'id'      => 'order_number_date_format',
          'title'   => esc_html__( 'Date format', 'jetexir' ),
          'desc'    => esc_html__( 'Date will be appended at the beginning of the order number or after prefix.', 'jetexir' ),
          'type'    => 'text',
          'default' => 'Ymd',
        ),
        'order_number_start'       => array(
          'id'         => 'order_number_start',
          'title'      => esc_html__( 'Start Number', 'jetexir' ),
          'desc'       => esc_html__( 'Use in "Sequential Number" methods', 'jetexir' ),
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
          'title'      => esc_html__( 'Next Number', 'jetexir' ),
          'desc'       => esc_html__( 'Use in "Sequential Number" methods', 'jetexir' ),
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
          'title'    => esc_html__( 'Order tracking by custom number', 'jetexir' ),
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
          'title' => esc_html__( 'Apply for all orders', 'jetexir' ),
          'type'  => 'startgrid',
        ),
        'order_number_notice'            => array(
          'id'      => 'order_number_notice',
          'notices' => array(
            array(
              'message' => esc_html__( 'Update all order numbers based on the new settings.', 'jetexir' ),
              'type'    => 'error',
            )
          ),
          'type'    => 'notice',
        ),
        'order_number_update'            => [
          'id'       => 'order_number_update',
          'title'    => esc_html__( 'Update all order numbers', 'jetexir' ),
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
    $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><g fill="#873eff"><path d="M8.38 19.75a.75.75 0 0 1-.54-.22L5.34 17a.75.75 0 0 1 0-1.06.77.77 0 0 1 1.07 0l2 2 2-2a.77.77 0 0 1 1.07 0 .75.75 0 0 1 0 1.06l-2.5 2.5a.74.74 0 0 1-.6.25"/><path d="M8.38 19.75a.76.76 0 0 1-.76-.75V5a.76.76 0 0 1 .76-.75.75.75 0 0 1 .74.75v14a.75.75 0 0 1-.74.75M17.12 11.25a.75.75 0 0 1-.74-.75V6.44l-.38.22a.754.754 0 1 1-.73-1.32l.65-.34a1.19 1.19 0 0 1 1.22-.11 1.29 1.29 0 0 1 .74 1.18v4.43a.76.76 0 0 1-.76.75M16.62 17.25A2.25 2.25 0 1 1 18.88 15a2.24 2.24 0 0 1-2.26 2.25m0-3a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5"/><path d="M16.11 19.25h-.49a.75.75 0 1 1 0-1.5h.49a1.28 1.28 0 0 0 1.25-1.19V15a.75.75 0 0 1 .74-.75.76.76 0 0 1 .76.75v1.64a2.79 2.79 0 0 1-2.75 2.61"/></g></svg>';

    return array(
      'id'             => $this->addonID,
      'title'          => esc_html__( 'Order Number', 'jetexir' ),
      'desc'           => esc_html__( 'Add custom order numbers to your WooCommerce store.', 'jetexir' ),
      'tags'           => [ esc_html__( 'Order', 'jetexir' ) ],
      'cat'            => 'order',
      'icon'           => $icon,
      'more_info_link' => '{jetexir_website}',
      'settings_key'   => $this->addonID,
    );
  }
}
