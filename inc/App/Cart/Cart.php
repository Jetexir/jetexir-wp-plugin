<?php

namespace Jetexir\App\Cart;

defined( 'ABSPATH' ) || exit;

class Cart {
  public function __construct() {
    new FlyCart();
    new MenuCart();
  }
}
