<?php

namespace WooAssistant\AppHelper;

defined( 'ABSPATH' ) || exit;

class AppHelper {
	public function __construct() {
		new FlyIcons();
		new Modal();
		new DataTableUI();
	}
}