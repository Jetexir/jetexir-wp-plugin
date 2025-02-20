<?php

namespace WooAssistant\App;

defined( 'ABSPATH' ) || exit;

class App {
	public function __construct() {
		new AppAssets();
		new ProductQuantity();
		new ProductTest();

		add_action( 'init', [ $this, 'init' ] );
	}

	public function init() {
		do_action( 'woo_assistant_plugins_load' );
		do_action( 'woo_assistant_init' );
	}
}