<?php

namespace Jetexir\App\Checkout;

defined( 'ABSPATH' ) || exit;

class Checkout {
  public function __construct() {
    new CheckoutFields();
  }
}
