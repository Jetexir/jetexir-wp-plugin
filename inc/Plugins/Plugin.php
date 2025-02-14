<?php

namespace WooAssistant\Plugins;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Helper\Cache;
use WooAssistant\Helper\DebugTrait;
use WooAssistant\Settings\Settings;

defined( 'ABSPATH' ) || exit;

abstract class Plugin {
	use DebugTrait;

	public string $pluginID;

	public function __construct() {
		add_filter( 'woo_assistant_plugins', [ $this, 'registerPlugin' ] );
		add_action( 'woo_assistant_admin_init', [ $this, 'registerMenu' ] );
		add_filter( 'woo_assistant_settings', [ $this, 'allSettings' ] );

		// Register WordPress hooks
		add_action( 'init', [ $this, 'registerInitAction' ] );
		add_action( 'admin_init', [ $this, 'registerAdminInitAction' ] );
		add_action( 'wp', [ $this, 'registerWpAction' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'registerWpEnqueueScriptsAction' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'registerAdminEnqueueScriptsAction' ] );
	}

	public function registerAdminEnqueueScriptsAction(): void {
		if ( method_exists( $this, 'adminEnqueueScriptsAction' ) && $this->isActivated() ) {
			$this->adminEnqueueScriptsAction();
		}
	}

	public function registerWpEnqueueScriptsAction(): void {
		if ( method_exists( $this, 'wpEnqueueScriptsAction' ) && $this->isActivated() ) {
			$this->wpEnqueueScriptsAction();
		}
	}

	public function registerAdminInitAction(): void {
		if ( method_exists( $this, 'adminInitAction' ) && $this->isActivated() ) {
			$this->adminInitAction();
		}
	}

	public function registerInitAction(): void {
		if ( method_exists( $this, 'initAction' ) && $this->isActivated() ) {
			$this->initAction();
		}
	}

	public function registerWpAction(): void {
		if ( method_exists( $this, 'wpAction' ) && $this->isActivated() ) {
			$this->wpAction();
		}
	}

	public function registerMenu(): void {
		if ( $this->getInfo( 'has_page', false ) && $this->isActivated() ) {
			add_filter( 'woo_assistant_menus', [ $this, 'addMenu' ] );

			if ( method_exists( $this, 'content' ) ) {
				add_action( 'woo_assistant_' . $this->pluginID . '_tab_content', [ $this, 'content' ] );
			}

			if ( method_exists( $this, 'settings' ) ) {
				add_filter( 'woo_assistant_' . $this->pluginID . '_settings', [ $this, 'settings' ] );
			}
		}
	}

	public function allSettings( $settings ): array {
		if ( method_exists( $this, 'settings' ) ) {
			$settings[ $this->pluginID ] = $this->settings();
		}

		return $settings;
	}

	public function addMenu( $menus ) {
		$plugin                   = $this->getInfo();
		$menus[ $this->pluginID ] = $plugin['menu_title'] ?? ( $plugin['name'] ?? $plugin['title'] );

		return $menus;
	}

	public function registerPlugin( $plugins ) {
		$plugins[] = $this->getInfo();

		return $plugins;
	}

	private function getInfo( $key = null, $default = null ) {
		$plugin = Cache::get( $this->pluginID . '_internal_plugin_info', false );

		if ( ! is_array( $plugin ) ) {
			$plugin = $this->info();
			Cache::set( $this->pluginID . '_internal_plugin_info', $plugin, 0 );
		}

		if ( $key !== null ) {
			return $plugin[ $key ] ?? $default;
		}

		return $plugin;
	}

	public function getSettings( $key = null, $default = null ) {
		return Settings::get( $key, $default, $this->pluginID );
	}

	public function isActivated(): bool {
		if ( ! $this->getInfo( 'force_enable', false ) && Settings::get( 'internal_plugin_' . $this->pluginID, false ) !== 1 ) {
			return false;
		}

		$requiresPlugins = $this->getInfo( 'requires_plugins', [] );
		$canActivate     = empty( $requiresPlugins );

		if ( ! $canActivate && ! empty( $requiresPlugins ) && is_array( $requiresPlugins ) ) {
			$requirePluginsActive = Cache::get( $this->pluginID . '_requires_plugins_count', false );

			if ( $requirePluginsActive === false ) {
				$requirePluginsActive = 0;
				foreach ( $requiresPlugins as $requirePluginPath => $requirePlugin ) {
					if (
						( file_exists( WP_PLUGIN_DIR . '/' . $requirePluginPath ) && is_plugin_active( $requirePluginPath ) ) ||
						( ! empty( $requirePlugin['function_check'] ) && function_exists( $requirePlugin['function_check'] ) ) ||
						( ! empty( $requirePlugin['class_check'] ) && class_exists( $requirePlugin['class_check'] ) )
					) {
						$requirePluginsActive ++;
					}
				}
				Cache::set( $this->pluginID . '_requires_plugins_count', $requirePluginsActive, 0 );
			}

			$canActivate = $requirePluginsActive > 0 && $requirePluginsActive === count( $requiresPlugins );
		}

		return $canActivate;
	}
}