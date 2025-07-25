<?php

namespace WooAssistant\App\GlobalSettings;

defined( 'ABSPATH' ) || exit;

class GlobalSettings {
	public function __construct() {
		new Styles();
		new Debug();
	}
}