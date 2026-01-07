<?php

namespace Jetexir\App\Order;

defined( 'ABSPATH' ) || exit;

class Order {
  public function __construct() {
    new OrderStatus();
    new OrderNumber();
  }
}
