<?php

namespace WooAssistant\App;

defined( 'ABSPATH' ) || exit;

class App {
	public function __construct() {
		new ProductQuantity();
	}
}