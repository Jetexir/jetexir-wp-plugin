<?php

namespace WooAssistant\Admin;

defined( 'ABSPATH' ) || exit;

class Admin {
	public function __construct() {
		new AdminAssets();
		new AdminSettings();
		new AdminPages();

		new AdminDashboard();
		new AdminTools();
		new AdminProduct();
		new AdminOrder();
		new AdminCart();
		new AdminCheckout();
		new AdminGlobal();
	}
}