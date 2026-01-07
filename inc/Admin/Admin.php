<?php

namespace Jetexir\Admin;

defined( 'ABSPATH' ) || exit;

class Admin {
  public function __construct() {
    new AdminAssets();
    new AdminSettings();
    new AdminPages();

    new AdminDashboard();
    new AdminTools();
    new AdminProduct();
    new AdminCart();
    new AdminCheckout();
    new AdminOrder();
    new AdminWordPress();
    new AdminGeneral();
  }
}
