<?php

namespace WooAssistant\Plugins;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Helper\Cache;
use WooAssistant\Helper\DebugTrait;
use WooAssistant\Helper\Notice;
use WooAssistant\Helper\Sanitizing;
use WooAssistant\Helper\Validating;

defined( 'ABSPATH' ) || exit;

class Plugins {
	public const tab = 'plugins';

	public function __construct() {
		add_filter( 'woo_assistant_menus', [ $this, 'addMenu' ] );
		add_filter( 'woo_assistant_plugins_settings', [ $this, 'settings' ] );
		add_filter( 'woo_assistant_plugins_tab_display_notice', '__return_false' );
		add_filter( 'woo_assistant_plugins_tab_content_display_notice', '__return_true' );
		add_filter( 'woo_assistant_plugins_settings_display_reset_button', '__return_false' );
		add_filter( 'woo_assistant_settings_submit_button_title', [ $this, 'changeSubmitButtonTitle' ], 10, 2 );
		add_filter( 'woo_assistant_save_settings_success_message', [ $this, 'saveMessage' ], 10, 2 );
		add_action( 'woo_assistant_notice', [ $this, 'addRefreshNotice' ] );
	}

	public function addRefreshNotice( $tab ): void {
		if ( $tab === self::tab && Cache::get( 'settings_saved' ) ) {
			Notice::add( self::tab, 'For initial plugins hook, page refreshed.', 'warning' );
			?>
            <script>
                setTimeout(function () {
                    window.location.reload(true);
                }, 5000)
            </script>
			<?php
		}
	}

	public function addMenu( $menus ) {
		$menus[ self::tab ] = __( 'Plugins', 'woo-assistant' );

		return $menus;
	}

	public function saveMessage( $message, $tab ) {
		if ( $tab === self::tab ) {
			$message = __( 'Plugins settings saved.', 'woo-assistant' );
		}

		return $message;
	}

	public function settings(): array {
		$plugins    = apply_filters( 'woo_assistant_plugins', array() );
		$pluginList = array();
		$pluginCats = self::getPluginCats();

		foreach ( $plugins as $plugin ) {
			$cat = empty( $plugin['cat'] ) || ! array_key_exists( $plugin['cat'], $pluginCats ) ? 'other' : $plugin['cat'];

			if ( empty( $plugin['id'] ) || empty( $plugin['title'] ) || isset( $pluginList[ $cat ][ $plugin['id'] ] ) ) {
				continue;
			}

			$tags                 = is_array( $plugin['tags'] ) ? $plugin['tags'] : [];
			$icon                 = ! empty( $plugin['icon'] ) && str_starts_with( $plugin['icon'], '<svg' ) !== false ? $plugin['icon'] : '';
			$image                = ! empty( $plugin['image'] ) && Validating::isUrl( $plugin['image'] ) ? $plugin['image'] : '';
			$imageLink            = ! empty( $plugin['image_link'] ) && Validating::isUrl( $plugin['image_link'] ) ? $plugin['image_link'] : '';
			$moreInfo             = ! empty( $plugin['more_info_link'] ) && Validating::isUrl( $plugin['more_info_link'] ) ? $plugin['more_info_link'] : '';
			$forceEnable          = Sanitizing::bool( $plugin['force_enable'] );
			$canActivate          = empty( $plugin['requires_plugins'] );
			$requirePluginsActive = 0;
			$actionLink           = '';
			$actionTitle          = __( 'Enable plugin', 'woo-assistant' );

			if ( ! $canActivate && ! empty( $plugin['requires_plugins'] ) && is_array( $plugin['requires_plugins'] ) ) {
				foreach ( $plugin['requires_plugins'] as $requirePluginPath => $requirePlugin ) {
					$fileExists = file_exists( WP_PLUGIN_DIR . '/' . $requirePluginPath );

					if (
						( $fileExists && is_plugin_active( $requirePluginPath ) ) ||
						( ! empty( $requirePlugin['function_check'] ) && function_exists( $requirePlugin['function_check'] ) ) ||
						( ! empty( $requirePlugin['class_check'] ) && class_exists( $requirePlugin['class_check'] ) )
					) {
						$requirePluginsActive ++;

					} elseif ( $fileExists ) {
						$actionLink  = wp_nonce_url(
							self_admin_url( 'plugins.php?action=activate&plugin=' . $requirePluginPath ),
							'activate-plugin_' . $requirePluginPath
						);
						$actionTitle = __( 'Activate required plugin', 'woo-assistant' );

					} elseif ( isset( $requirePlugin['is_wp_plugin'] ) && $requirePlugin['is_wp_plugin'] ) {
						$pluginSlug = self::convertToSlug( $requirePluginPath );

						$actionLink  = wp_nonce_url(
							self_admin_url( 'update.php?action=install-plugin&plugin=' . $pluginSlug ),
							'install-plugin_' . $pluginSlug
						);
						$actionTitle = __( 'Install required plugin', 'woo-assistant' );

					} elseif ( ! empty( $requirePlugin['plugin_link'] ) && Validating::isUrl( $requirePlugin['plugin_link'] ) ) {
						$actionLink  = $requirePlugin['plugin_link'];
						$actionTitle = isset( $requirePlugin['is_free'] ) && $requirePlugin['is_free'] ? __( 'Download required plugin', 'woo-assistant' ) : __( 'Buy required plugin', 'woo-assistant' );

					}

					if ( ! empty( $actionLink ) ) {
						break;
					}
				}

				if ( $requirePluginsActive > 0 && $requirePluginsActive === count( $plugin['requires_plugins'] ) ) {
					$canActivate = true;
				}
			}

			$pluginList[ $cat ][ $plugin['id'] ] = array(
				'id'                   => 'internal_plugin_' . $plugin['id'],
				'title'                => $plugin['title'],
				'desc'                 => wp_trim_words( $plugin['desc'] ?? '', 20, '' ),
				'value'                => 1,
				'default'              => 0,
				'image'                => $image,
				'image_link'           => $imageLink,
				'icon'                 => $icon,
				'tags'                 => $tags,
				'cat'                  => $cat,
				'more_info_link'       => $moreInfo,
				'can_activate'         => $canActivate,
				'action_link'          => $actionLink,
				'action_link_external' => Validating::isExternalLink( $actionLink ),
				'action_title'         => $actionTitle,
				'force_enable'         => $forceEnable
			);
		}

		$elementList = array();
		if ( count( $pluginList ) ) {
			$lastKey = array_key_last( $pluginList );
			foreach ( $pluginList as $cat => $plugins ) {
				if ( ! is_array( $plugins ) || empty( $plugins ) ) {
					continue;
				}

				$elementList[ $cat . '_startplugins' ] = array(
					'type'  => 'startplugins',
					'title' => $pluginCats[ $cat ],
				);

				foreach ( $plugins as $pluginId => $pluginOptions ) {
					$elementList[ $pluginId . '_plugin' ] = array_merge(
						$pluginOptions, [
							'type' => 'plugin',
							'name' => 'active_plugins[' . $pluginId . ']'
						]
					);
				}

				$elementList[ $cat . '_endplugins' ] = array(
					'type' => 'endplugins'
				);

				if ( $cat !== $lastKey ) {
					$elementList[ $cat . '_sep' ] = array(
						'type' => 'hr'
					);
				}
			}
		}

		//DebugTrait::dd( $elementList );

		return array(
			'title'    => __( 'Plugins', 'woo-assistant' ),
			'desc'     => __( 'Woo Assistant can integrate with other products, to help you further improve your website. You can enable or disable these integrations below.', 'woo-assistant' ),
			'settings' => $elementList
		);
	}

	public static function getPluginCats(): ?array {
		$defaultCats = array(
			'recommended'    => __( 'Recommended', 'woo-assistant' ),
			'marketing'      => __( 'Marketing', 'woo-assistant' ),
			'payments'       => __( 'Payments', 'woo-assistant' ),
			'merchandising'  => __( 'Merchandising', 'woo-assistant' ),
			'shipping'       => __( 'Shipping', 'woo-assistant' ),
			'customizations' => __( 'Customizations', 'woo-assistant' ),
			'conversion'     => __( 'Conversion', 'woo-assistant' ),
			'seo'            => __( 'SEO', 'woo-assistant' ),
		);

		$cats = apply_filters( 'woo_assistant_plugin_cats', array() );
		$cats = is_array( $cats ) ? $cats : [];

		return array_merge( $defaultCats, $cats, [ 'other' => __( 'Other plugins', 'woo-assistant' ) ] );
	}

	public function changeSubmitButtonTitle( $title, $tab ) {
		if ( $tab === self::tab ) {
			$title = __( 'Save active plugins', 'woo-assistant' );
		}

		return $title;
	}

	/**
	 * Converts a plugin filepath to a slug.
	 *
	 * @param string $pluginFile The plugin's filepath, relative to the plugins directory.
	 *
	 * @return string The plugin's slug.
	 */
	protected static function convertToSlug( $pluginFile ): string {
		if ( 'hello.php' === $pluginFile ) {
			return 'hello-dolly';
		}

		return str_contains( $pluginFile, '/' ) ? dirname( $pluginFile ) : str_replace( '.php', '', $pluginFile );
	}
}