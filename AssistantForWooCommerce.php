<?php
/**
 * Plugin Name:             Assistant for WooCommerce
 * Description:             Assistant for WooCommerce, First plugin you need for your WooCommerce store.
 * Version:                 1.0
 * Author:                  Parsa Kafi
 * Author URI:              https://parsa.ws
 * Text Domain:             assistant-for-woocommerce
 * Domain Path:             /i18n/languages/
 * Requires Plugins:        woocommerce
 * Requires at least:       6.7
 * Requires PHP:            7.4
 * License:                 GPLv3
 * License URI:             https://www.gnu.org/licenses/gpl-3.0.html
 */

namespace AssistantForWooCommerce;

defined( 'ABSPATH' ) || exit;

use AssistantForWooCommerce\Addons\Addons;
use AssistantForWooCommerce\Admin\Admin;
use AssistantForWooCommerce\App\App;
use AssistantForWooCommerce\AppHelper\AppHelper;
use AssistantForWooCommerce\Integrations\Integrations;
use AssistantForWooCommerce\Settings\Settings;
use AssistantForWooCommerce\Flow\Install;

final class AssistantForWooCommerce {
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
		define( 'ASSISTANTFORWOOCOMMERCE_PLUGIN_KEY','assistant_for_woocommerce' );
		define( 'ASSISTANTFORWOOCOMMERCE_PLUGIN_SLUG', 'assistant-for-woocommerce' );
		define( 'ASSISTANTFORWOOCOMMERCE_PLUGIN_KEYCAP', 'AssistantForWooCommerce' );
		define( 'ASSISTANTFORWOOCOMMERCE_PLUGIN_FILE_PATH', __FILE__ );
		define( 'ASSISTANTFORWOOCOMMERCE_PLUGIN_PATH', __DIR__ );
		define( 'ASSISTANTFORWOOCOMMERCE_PLUGIN_TEMPLATE_PATH', ASSISTANTFORWOOCOMMERCE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Templates' );
		define( 'ASSISTANTFORWOOCOMMERCE_PLUGIN_URL', plugins_url( '/', ASSISTANTFORWOOCOMMERCE_PLUGIN_FILE_PATH ) );
		define( 'ASSISTANTFORWOOCOMMERCE_INPUT_PREFIX', ASSISTANTFORWOOCOMMERCE_PLUGIN_KEY . '_' );
		define( 'ASSISTANTFORWOOCOMMERCE_CLASS_PREFIX', 'asfowoo-' );

		add_action( 'init', static function () {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$pluginData = get_plugin_data( ASSISTANTFORWOOCOMMERCE_PLUGIN_FILE_PATH );
			define( 'ASSISTANTFORWOOCOMMERCE_PLUGIN_VERSION', $pluginData['Version'] );
		}, 0 );
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
		define( 'ASSISTANTFORWOOCOMMERCE_DEBUG_MODE', Settings::get( 'debug_mode', false ) );

		new Admin();
		new Addons();
		new Integrations();
		new AppHelper();
		new App();
	}
}

new AssistantForWooCommerce();
register_activation_hook( __FILE__, array( Install::class, 'run' ) );