<?php
/**
 * Plugin Name:             Jetexir
 * Description:             Jetexir, First plugin you need for your WooCommerce store.
 * Version:                 1.0.1
 * Author:                  Parsa Kafi
 * Author URI:              https://parsa.ws
 * Text Domain:             jetexir
 * Domain Path:             /i18n/languages/
 * Requires Plugins:        woocommerce
 * Requires at least:       6.7
 * Requires PHP:            7.4
 * License:                 GPLv3
 * License URI:             https://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Jetexir;

defined( 'ABSPATH' ) || exit;

use Jetexir\Addons\Addons;
use Jetexir\Admin\Admin;
use Jetexir\App\App;
use Jetexir\AppHelper\AppHelper;
use Jetexir\Integrations\Integrations;
use Jetexir\Plugin\{Plugin, Install};
use Jetexir\Settings\Settings;

final class Jetexir {
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
    define( 'JETEXIR_PLUGIN_KEY', 'jetexir' );
    define( 'JETEXIR_PLUGIN_SLUG', 'jetexir' );
    define( 'JETEXIR_PLUGIN_KEYCAP', 'Jetexir' );
    define( 'JETEXIR_PLUGIN_FILE_PATH', __FILE__ );
    define( 'JETEXIR_PLUGIN_PATH', __DIR__ );
    define( 'JETEXIR_PLUGIN_TEMPLATE_PATH', JETEXIR_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Templates' );
    define( 'JETEXIR_PLUGIN_URL', plugins_url( '/', JETEXIR_PLUGIN_FILE_PATH ) );
    define( 'JETEXIR_INPUT_PREFIX', JETEXIR_PLUGIN_KEY . '_' );
    define( 'JETEXIR_CLASS_PREFIX', 'jetexir-' );
    define( 'JETEXIR_WEBSITE', 'https://jetexir.ir' );

    add_action( 'init', static function () {
      if ( ! function_exists( 'get_plugin_data' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
      }
      $pluginData = get_plugin_data( JETEXIR_PLUGIN_FILE_PATH );
      define( 'JETEXIR_PLUGIN_VERSION', $pluginData['Version'] );
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
    define( 'JETEXIR_DEBUG_MODE', Settings::get( 'debug_mode', false ) );

    new Install();
    new Plugin();
    new Admin();
    new Addons();
    new Integrations();
    new AppHelper();
    new App();
  }
}

new Jetexir();
register_activation_hook( __FILE__, array( Install::class, 'update' ) );
