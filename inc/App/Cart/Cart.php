<?php

namespace WooAssistant\App\Cart;

defined( 'ABSPATH' ) || exit;

class Cart {
	public function __construct() {
		new FlyCart();
	}
}