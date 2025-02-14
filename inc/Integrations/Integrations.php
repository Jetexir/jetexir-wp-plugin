<?php

namespace WooAssistant\Integrations;

defined( 'ABSPATH' ) || exit;

class Integrations {
	public function __construct() {
		new ACF();
	}
}