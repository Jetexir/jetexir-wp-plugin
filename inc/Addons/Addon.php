<?php

namespace WooAssistant\Addons;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Admin\AdminSettings;
use WooAssistant\Helper\Cache;
use WooAssistant\Helper\DebugTrait;
use WooAssistant\Settings\Settings;

defined( 'ABSPATH' ) || exit;

abstract class Addon {
	use DebugTrait;

	public string $addonID;

	public function __construct() {
		add_filter( 'woo_assistant_addons', [ $this, 'registerAddon' ] );
		add_action( 'woo_assistant_admin_init', [ $this, 'registerMenu' ] );
		add_filter( 'woo_assistant_settings', [ $this, 'allSettings' ] );

		if ( $this->addonID ) {
			add_filter( 'woo_assistant_' . $this->addonID . '_tab_display_notice', '__return_false' );
			add_filter( 'woo_assistant_' . $this->addonID . '_tab_content_display_notice', '__return_true' );
		}

		// Register WordPress hooks
		add_action( 'init', [ $this, 'registerInitAction' ] );
		add_action( 'admin_init', [ $this, 'registerAdminInitAction' ] );
		add_action( 'template_redirect', [ $this, 'registerTemplateRedirectAction' ] );
		add_action( 'wp', [ $this, 'registerWpAction' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'registerWpEnqueueScriptsAction' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'registerAdminEnqueueScriptsAction' ] );
		add_action( 'wp_footer', [ $this, 'registerWpFooterAction' ] );
		add_action( 'wp_body_open', [ $this, 'registerWpBodyOpenAction' ], 0 );

		add_filter( 'query_vars', [ $this, 'registerQueryVarsFilter' ] );
		add_filter( 'woocommerce_account_menu_items', [ $this, 'registerWooAccountMenuItemsFilter' ] );
	}

	public function registerQueryVarsFilter( $vars ) {
		if ( method_exists( $this, 'queryVarsFilter' ) && $this->isActivated() ) {
			return $this->queryVarsFilter( $vars );
		}

		return $vars;
	}

	public function registerWooAccountMenuItemsFilter( $items ) {
		if ( method_exists( $this, 'wooAccountMenuItemsFilter' ) && $this->isActivated() ) {
			return $this->wooAccountMenuItemsFilter( $items );
		}

		return $items;
	}

	public function registerWpFooterAction(): void {
		if ( method_exists( $this, 'wpFooterAction' ) && $this->isActivated() ) {
			$this->wpFooterAction();
		}
	}

	public function registerWpBodyOpenAction(): void {
		if ( method_exists( $this, 'wpBodyOpenAction' ) && $this->isActivated() ) {
			$this->wpBodyOpenAction();
		}
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

	public function registerTemplateRedirectAction(): void {
		if ( method_exists( $this, 'templateRedirectAction' ) && $this->isActivated() ) {
			$this->templateRedirectAction();
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

			if ( $this->getInfo( 'content_header', false ) ) {
				add_action( 'woo_assistant_' . $this->addonID . '_tab_content',
					[ $this, 'displayContentHeader' ], - 10 );
			}

			if ( method_exists( $this, 'content' ) ) {
				add_action( 'woo_assistant_' . $this->addonID . '_tab_content', [ $this, 'content' ] );
			}

			if ( method_exists( $this, 'settings' ) ) {
				add_filter( 'woo_assistant_' . $this->addonID . '_settings', [ $this, 'settings' ] );
			}
		}
	}

	public function displayContentHeader(): void {
		if ( $this->getInfo( 'content_header', false ) ) {
			AdminSettings::headerSettings( $this->addonID, $this->getInfo() );
		}
	}

	public function allSettings( $settings ): array {
		if ( method_exists( $this, 'settings' ) ) {
			$settings[ $this->addonID ] = $this->settings();
		}

		return $settings;
	}

	public function addMenu( $menus ) {
		$addon                   = $this->getInfo();
		$menus[ $this->addonID ] = $addon['menu_title'] ?? ( $addon['name'] ?? $addon['title'] );

		return $menus;
	}

	public function registerAddon( $addons ) {
		$addons[] = $this->getInfo();

		return $addons;
	}

	private function getInfo( $key = null, $default = null ) {
		$addon = Cache::get( $this->addonID . '_internal_addon_info', false );

		if ( ! is_array( $addon ) ) {
			$addon = $this->info();
			Cache::set( $this->addonID . '_internal_addon_info', $addon );
		}

		if ( $key !== null ) {
			return $addon[ $key ] ?? $default;
		}

		return $addon;
	}

	public function getSettingsKey() {
		return WOOASSISTANT_PLUGIN_KEY . '_' . $this->addonID;
	}

	public function getSettings( $key = null, $default = null ) {
		return Settings::get( $key, $default, $this->addonID );
	}

	public function isActivated(): bool {
		if ( ! $this->getInfo( 'force_enable', false ) && Settings::get( 'internal_addon_' . $this->addonID, false ) !== 1 ) {
			return false;
		}

		$requiresPlugins = $this->getInfo( 'requires_plugins', [] );
		$canActivate     = empty( $requiresPlugins );

		if ( ! $canActivate && ! empty( $requiresPlugins ) && is_array( $requiresPlugins ) ) {
			$requirePluginsActive = Cache::get( $this->addonID . '_requires_plugins_count', false );

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
				Cache::set( $this->addonID . '_requires_plugins_count', $requirePluginsActive );
			}

			$canActivate = $requirePluginsActive > 0 && $requirePluginsActive === count( $requiresPlugins );
		}

		return $canActivate;
	}
}