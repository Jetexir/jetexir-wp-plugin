<?php

namespace WooAssistant\App\GeneralSettings;

defined( 'ABSPATH' ) || exit;

class GeneralSettings {
	public function __construct() {
		new Styles();
		new Debug();
	}
}