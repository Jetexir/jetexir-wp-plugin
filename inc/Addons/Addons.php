<?php

namespace WooAssistant\Addons;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Cache;
use WooAssistant\Helper\DebugTrait;
use WooAssistant\Helper\Notice;
use WooAssistant\Helper\Sanitizing;
use WooAssistant\Helper\Validating;

defined( 'ABSPATH' ) || exit;

class Addons {
	public const tab = 'addons';

	public function __construct() {
		add_filter( 'woo_assistant_menus', [ $this, 'addMenu' ] );
		add_filter( 'woo_assistant_' . self::tab . '_settings', [ $this, 'settings' ] );
		add_filter( 'woo_assistant_' . self::tab . '_tab_display_notice', '__return_false' );
		add_filter( 'woo_assistant_' . self::tab . '_tab_content_display_notice', '__return_true' );
		add_filter( 'woo_assistant_' . self::tab . '_settings_display_reset_button', '__return_false' );
		add_filter( 'woo_assistant_settings_submit_button_title', [ $this, 'changeSubmitButtonTitle' ], 10, 2 );
		add_filter( 'woo_assistant_save_settings_success_message', [ $this, 'saveMessage' ], 10, 2 );
		add_action( 'woo_assistant_notice', [ $this, 'addRefreshNotice' ] );
	}

	public function addRefreshNotice( $tab ): void {
		if ( $tab === self::tab && Cache::get( 'settings_saved' ) ) {
			Notice::add( self::tab, 'For initial addons hook, page refreshed.', 'warning' );
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
		$menus[ self::tab ] = __( 'Addons', 'woo-assistant' );

		return $menus;
	}

	public function saveMessage( $message, $tab ) {
		if ( $tab === self::tab ) {
			$message = __( 'Addons settings saved.', 'woo-assistant' );
		}

		return $message;
	}

	public function settings(): array {
		$addons    = apply_filters( 'woo_assistant_addons', array() );
		$addonList = array();
		$addonCats = self::getAddonCats();

		foreach ( $addons as $addon ) {
			$cat = empty( $addon['cat'] ) || ! array_key_exists( $addon['cat'], $addonCats ) ? 'other' : $addon['cat'];

			if ( empty( $addon['id'] ) || empty( $addon['title'] ) || isset( $addonList[ $cat ][ $addon['id'] ] ) ) {
				continue;
			}

			$tags                 = is_array( $addon['tags'] ) ? $addon['tags'] : [];
			$icon                 = ! empty( $addon['icon'] ) && str_starts_with( $addon['icon'], '<svg' ) !== false ? Assets::setSvgDimensions( $addon['icon'], 50 ) : '';
			$image                = ! empty( $addon['image'] ) && Validating::isUrl( $addon['image'] ) ? $addon['image'] : '';
			$imageLink            = ! empty( $addon['image_link'] ) && Validating::isUrl( $addon['image_link'] ) ? $addon['image_link'] : '';
			$moreInfo             = ! empty( $addon['more_info_link'] ) && Validating::isUrl( $addon['more_info_link'] ) ? $addon['more_info_link'] : '';
			$forceEnable          = Sanitizing::bool( $addon['force_enable'] );
			$canActivate          = empty( $addon['requires_plugins'] );
			$requirePluginsActive = 0;
			$actionLink           = '';
			$actionTitle          = __( 'Enable addon', 'woo-assistant' );

			if ( ! $canActivate && ! empty( $addon['requires_plugins'] ) && is_array( $addon['requires_plugins'] ) ) {
				foreach ( $addon['requires_plugins'] as $requirePluginPath => $requirePlugin ) {
					$fileExists = file_exists( WP_PLUGIN_DIR . '/' . $requirePluginPath );

					if (
						( $fileExists && is_plugin_active( $requirePluginPath ) ) ||
						( ! empty( $requirePlugin['function_check'] ) && function_exists( $requirePlugin['function_check'] ) ) ||
						( ! empty( $requirePlugin['class_check'] ) && class_exists( $requirePlugin['class_check'] ) )
					) {
						$requirePluginsActive ++;

					} elseif ( $fileExists ) {
						$actionLink  = wp_nonce_url(
							self_admin_url( 'addons.php?action=activate&addon=' . $requirePluginPath ),
							'activate-plugin_' . $requirePluginPath
						);
						$actionTitle = __( 'Activate required addon', 'woo-assistant' );

					} elseif ( isset( $requirePlugin['is_wp_plugin'] ) && $requirePlugin['is_wp_plugin'] ) {
						$pluginSlug = self::convertToSlug( $requirePluginPath );

						$actionLink  = wp_nonce_url(
							self_admin_url( 'update.php?action=install-addon&addon=' . $pluginSlug ),
							'install-plugin_' . $pluginSlug
						);
						$actionTitle = __( 'Install required addon', 'woo-assistant' );

					} elseif ( ! empty( $requirePlugin['plugin_link'] ) && Validating::isUrl( $requirePlugin['plugin_link'] ) ) {
						$actionLink  = $requirePlugin['plugin_link'];
						$actionTitle = isset( $requirePlugin['is_free'] ) && $requirePlugin['is_free'] ? __( 'Download required addon', 'woo-assistant' ) : __( 'Buy required addon', 'woo-assistant' );

					}

					if ( ! empty( $actionLink ) ) {
						break;
					}
				}

				if ( $requirePluginsActive > 0 && $requirePluginsActive === count( $addon['requires_plugins'] ) ) {
					$canActivate = true;
				}
			}

			$addonList[ $cat ][ $addon['id'] ] = array(
				'id'                   => 'internal_addon_' . $addon['id'],
				'title'                => $addon['title'],
				'desc'                 => wp_trim_words( $addon['desc'] ?? '', 20, '' ),
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
		if ( count( $addonList ) ) {
			$lastKey = array_key_last( $addonList );
			foreach ( $addonList as $cat => $addons ) {
				if ( ! is_array( $addons ) || empty( $addons ) ) {
					continue;
				}

				$elementList[ $cat . '_startaddons' ] = array(
					'type'  => 'startaddons',
					'title' => $addonCats[ $cat ],
				);

				foreach ( $addons as $addonID => $pluginOptions ) {
					$elementList[ $addonID . '_plugin' ] = array_merge(
						$pluginOptions, [
							'type' => 'addon',
							'name' => 'active_plugins[' . $addonID . ']'
						]
					);
				}

				$elementList[ $cat . '_endaddons' ] = array(
					'type' => 'endaddons'
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
			'title'    => __( 'Addons', 'woo-assistant' ),
			'desc'     => __( 'Woo Assistant can integrate with other products, to help you further improve your website. You can enable or disable these integrations below.', 'woo-assistant' ),
			'settings' => $elementList
		);
	}

	public static function getAddonCats(): ?array {
		$defaultCats = array(
			'recommended'    => __( 'Recommended', 'woo-assistant' ),
			'marketing'      => __( 'Marketing', 'woo-assistant' ),
			'payments'       => __( 'Payments', 'woo-assistant' ),
			'merchandising'  => __( 'Merchandising', 'woo-assistant' ),
			'shipping'       => __( 'Shipping', 'woo-assistant' ),
			'customizations' => __( 'Customizations', 'woo-assistant' ),
			'conversion'     => __( 'Conversion', 'woo-assistant' ),
			'seo'            => __( 'SEO', 'woo-assistant' ),
			'utility'        => __( 'Utility', 'woo-assistant' ),
		);

		$cats = apply_filters( 'woo_assistant_addon_cats', array() );
		$cats = is_array( $cats ) ? $cats : [];

		return array_merge( $defaultCats, $cats, [ 'other' => __( 'Other addons', 'woo-assistant' ) ] );
	}

	public function changeSubmitButtonTitle( $title, $tab ) {
		if ( $tab === self::tab ) {
			$title = __( 'Save active addons', 'woo-assistant' );
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