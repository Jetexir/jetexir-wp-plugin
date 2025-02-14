<?php

namespace WooAssistant\Helper;

defined( 'ABSPATH' ) || exit;

class Assets {
	public static function url( $path ): string {
		return WOOASSISTANT_PLUGIN_URL . 'assets/' . $path;
	}
}