<?php
/**
 * Plugin Name:             Woo Assistant
 * Description:             WooCommerce Assistant
 * Version:                 1.0
 * Author:                  Parsa Kafi
 * Author URI:              http://parsa.ws
 * Text Domain:             woo-assistant
 * Domain Path:             /i18n/languages/
 * Requires Plugins:        woocommerce
 * Requires at least:       6.6
 * Requires PHP:            7.4
 */

namespace WooAssistant;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Admin\Admin;
use WooAssistant\App\App;
use WooAssistant\Integrations\Integrations;
use WooAssistant\Plugins\Plugins;

final class WooAssistant {
	public function __construct() {
		$this->define();
		$this->include();
		$this->instance();
	}

	/**
	 * Define constant
	 *
	 * @return void
	 */
	private function define(): void {
		define( 'WOOASSISTANT_PLUGIN_KEY', 'woo_assistant' );
		define( 'WOOASSISTANT_PLUGIN_SLUG', 'woo-assistant' );
		define( 'WOOASSISTANT_PLUGIN_KEYCAP', 'WooAssistant' );
		define( 'WOOASSISTANT_PLUGIN_FILE_PATH', __FILE__ );
		define( 'WOOASSISTANT_PLUGIN_PATH', __DIR__ );
		define( 'WOOASSISTANT_PLUGIN_URL', plugins_url( '/', WOOASSISTANT_PLUGIN_FILE_PATH ) );
		define( 'WOOASSISTANT_INPUT_PREFIX', WOOASSISTANT_PLUGIN_KEY . '_' );

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		$pluginData = get_plugin_data( WOOASSISTANT_PLUGIN_FILE_PATH );
		define( 'WOOASSISTANT_PLUGIN_VERSION', $pluginData['Version'] );
	}

	/**
	 * Include required files
	 *
	 * @return void
	 */
	private function include(): void {
		require_once __DIR__ . '/vendor/autoload.php';
	}

	/**
	 * Instant classes
	 *
	 * @return void
	 */
	private function instance(): void {
		new Admin();
		new Plugins();
		new Integrations();
		new App();
	}
}

new WooAssistant();