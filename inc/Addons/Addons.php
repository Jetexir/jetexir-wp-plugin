<?php

namespace WooAssistant\Addons;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Admin\AdminPages;
use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Cache;
use WooAssistant\Helper\Notice;
use WooAssistant\Helper\Param;
use WooAssistant\Helper\Sanitizing;
use WooAssistant\Helper\Validating;
use WooAssistant\Helper\WordPress;

class Addons {
	public const tab = 'addons';
	public const icon = '<svg fill="#873eff" viewBox="-1.6 -1.6 19.20 19.20" id="puzzle-16px" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"><path transform="translate(-1.6, -1.6), scale(0.6)" d="M16,26.067016359222563C19.01738168054809,25.872397068992516,22.262239430627645,26.517240315137514,24.60181605099669,24.60181605099669C27.111898440059782,22.546797774978174,28.145281087067747,19.24049670110675,28.296260704139346,16C28.455347682104016,12.585493874718981,27.663918047661323,9.155468547682407,25.442529248674575,6.557470751325425C23.042874131554072,3.750983787341677,19.68332835017655,1.3586644697408636,16,1.6190603700907609C12.450863972879969,1.8699694347691473,10.324639773941016,5.292883807362616,7.804411840049186,7.804411840049182C5.2767992788350275,10.323299008106623,1.883980163995068,12.445487433187566,1.5692449778710547,15.999999999999998C1.2381447568526107,19.73933372680073,3.1332333196882076,23.640132127311034,6.212055537857966,25.78794446214203C8.994928292718487,27.729299956421816,12.613919183695263,26.285416520322205,16,26.067016359222563" fill="#fff" strokewidth="0"></path></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path id="Path_56" data-name="Path 56" d="M-8.5,16h-4a.5.5,0,0,1-.5-.5v-1A1.5,1.5,0,0,0-14.5,13,1.5,1.5,0,0,0-16,14.5v1a.5.5,0,0,1-.5.5h-4a.5.5,0,0,1-.5-.5V3.5a.5.5,0,0,1,.5-.5H-17V2.5A2.5,2.5,0,0,1-14.5,0,2.5,2.5,0,0,1-12,2.5V3h3.5a.5.5,0,0,1,.5.5V7h.5A2.5,2.5,0,0,1-5,9.5,2.5,2.5,0,0,1-7.5,12H-8v3.5A.5.5,0,0,1-8.5,16ZM-12,15h3V11.5a.5.5,0,0,1,.5-.5h1A1.5,1.5,0,0,0-6,9.5,1.5,1.5,0,0,0-7.5,8h-1A.5.5,0,0,1-9,7.5V4h-3.5a.5.5,0,0,1-.5-.5v-1A1.5,1.5,0,0,0-14.5,1,1.5,1.5,0,0,0-16,2.5v1a.5.5,0,0,1-.5.5H-20V15h3v-.5A2.5,2.5,0,0,1-14.5,12,2.5,2.5,0,0,1-12,14.5Z" transform="translate(21)"></path> </g></svg>';

	public function __construct() {
		add_filter( 'woo_assistant_menus', [ $this, 'addMenu' ] );
		add_filter( 'woo_assistant_' . self::tab . '_settings', [ $this, 'settings' ] );
		add_filter( 'woo_assistant_' . self::tab . '_tab_display_notice', '__return_false' );
		add_filter( 'woo_assistant_' . self::tab . '_tab_content_display_notice', '__return_true' );
		add_filter( 'woo_assistant_' . self::tab . '_settings_display_reset_button', '__return_false' );
		add_filter( 'woo_assistant_settings_submit_button_title', [ $this, 'changeSubmitButtonTitle' ], 10, 2 );
		add_filter( 'woo_assistant_save_settings_success_message', [ $this, 'saveMessage' ], 10, 2 );
		add_filter( 'woo_assistant_dashboard_custom_links', [ $this, 'addDashboardLink' ] );
		add_action( 'woo_assistant_notice', [ $this, 'addRefreshNotice' ] );
		add_action( 'admin_init', [ $this, 'flushRewriteRules' ] );
	}

	public function addDashboardLink( $links ) {
		$links[] = [
			'title' => __( 'Addons', 'woo-assistant' ),
			'desc'  => __( 'Woo Assistant Addons', 'woo-assistant' ),
			'link'  => AdminPages::link( [
				'tab' => self::tab
			] ),
			'icon'  => self::icon,
			'type'  => 'addons'
		];

		return $links;
	}

	public function flushRewriteRules(): void {
		if ( AdminPages::isSettingPage() && Param::get( 'tab' ) === self::tab && Param::get( 'addons-refreshed' ) === '1' ) {
			flush_rewrite_rules();
			wp_safe_redirect( AdminPages::link( [ 'tab' => self::tab ] ) );
			exit();
		}
	}

	public function addRefreshNotice( $tab ): void {
		if ( $tab === self::tab && Cache::get( 'settings_saved' ) ) {
			Notice::add( self::tab, 'For initial addons hook, page refreshed.', 'warning' );
			$url = AdminPages::link( [ 'tab' => self::tab, 'addons-refreshed' => true ] );
			?>
            <script>
                setTimeout(function () {
                    // window.location.reload(true);
                    window.location.href = '<?php echo $url ?>';
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
		foreach ( array_keys( $addonCats ) as $addonCat ) {
			$addonList[ $addonCat ] = array();
		}

		foreach ( $addons as $addon ) {
			$cat = empty( $addon['cat'] ) || ! array_key_exists( $addon['cat'], $addonCats ) ? 'other' : $addon['cat'];

			if ( empty( $addon['id'] ) || empty( $addon['title'] ) || isset( $addonList[ $cat ][ $addon['id'] ] ) ) {
				continue;
			}

			$tags                 = is_array( $addon['tags'] ) ? $addon['tags'] : [];
			$icon                 = ! empty( $addon['icon'] ) && Assets::isSvgImageString( $addon['icon'] ) ? Assets::setSvgDimensions( $addon['icon'], 50 ) : '';
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
						$pluginSlug = WordPress::pluginPathToSlug( $requirePluginPath );

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

			if ( empty( $icon ) && empty( $image ) ) {
				$icon = self::icon;
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

		foreach ( $addonList as $cat => $addons ) {
			if ( empty( $addons ) ) {
				unset( $addonList[ $cat ] );
			}
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

		return array(
			'title'    => __( 'Addons', 'woo-assistant' ),
			'desc'     => __( 'Woo Assistant integrates with WooCommerce to help you further enhance your website. You can enable or disable these integrations below.', 'woo-assistant' ),
			'settings' => $elementList
		);
	}

	public static function getAddonCats(): ?array {
		$cats = Cache::get( 'addon_cats', false );
		if ( is_array( $cats ) ) {
			return $cats;
		}

		$defaultCats = array(
			'recommended'    => __( 'Recommended', 'woo-assistant' ),
			'product'        => __( 'Product', 'woo-assistant' ),
			'cart'           => __( 'Cart', 'woo-assistant' ),
			'checkout'       => __( 'Checkout', 'woo-assistant' ),
			'order'          => __( 'Order', 'woo-assistant' ),
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

		$cats = array_merge( $defaultCats, $cats, [ 'other' => __( 'Other addons', 'woo-assistant' ) ] );
		Cache::set( 'addon_cats', $cats );

		return $cats;
	}

	public function changeSubmitButtonTitle( $title, $tab ) {
		if ( $tab === self::tab ) {
			$title = __( 'Save active addons', 'woo-assistant' );
		}

		return $title;
	}
}