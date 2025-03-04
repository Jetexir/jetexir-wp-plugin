<?php

namespace WooAssistant\Admin;

defined( 'ABSPATH' ) || exit;

class Admin {
	public function __construct() {
		new AdminAssets();
		new AdminPages();
		new AdminSettings();

		new AdminDashboard();
		new AdminTools();
		new AdminProduct();
		new AdminGlobal();
	}
}