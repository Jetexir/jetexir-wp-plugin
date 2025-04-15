<?php

namespace WooAssistant\Admin;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Helper\Cache;
use WooAssistant\Helper\DebugTrait;
use WooAssistant\Helper\Notice;
use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Param;

defined( 'ABSPATH' ) || die();

class AdminPages {
	use DebugTrait;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'addMenu' ) );
		add_action( 'admin_init', [ $this, 'init' ] );
		add_action( 'woo_assistant_notice', [ $this, 'displayNotices' ] );
		add_action( 'woo_assistant_content', [ $this, 'content' ] );
		add_action( 'woo_assistant_admin_init', [ $this, 'checkSubmitForm' ], 999999 );
		add_action( 'admin_footer', [ $this, 'flushRewriteRules' ] );
	}

	public function flushRewriteRules(): void {
		if ( Cache::get( 'settings_saved' ) ) {
			flush_rewrite_rules();
		}
	}

	public function checkSubmitForm(): void {
		$tab = self::getActiveTab();
		if ( isset( $_POST['_form_nonce'] ) && check_admin_referer( 'settings_submit_' . $tab, '_form_nonce' ) ) {
			do_action( 'woo_assistant_submit_settings_form', $tab );
		}
	}

	public function content( $currentTab ): void {
		$settings = AdminSettings::getSettings( $currentTab );

		if ( $settings && apply_filters( 'woo_assistant_display_tab_settings', true, $currentTab ) ) {
			AdminSettings::printPage( $currentTab, $settings );
		}
	}

	public function init(): void {
		if ( self::isSettingPage() ) {
			do_action( 'woo_assistant_admin_init', self::getActiveTab() );
		}
	}

	public function displayNotices( $tab ): void {
		if ( apply_filters( 'woo_assistant_' . $tab . '_tab_display_notice', true ) ) {
			Notice::display( '*' );
			Notice::display( $tab );
		}
	}

	public function addMenu(): void {
		$icon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1OCIgaGVpZ2h0PSIyNiIgZmlsbD0ibm9uZSI+PHBhdGggZmlsbD0iI2ZmZiIgZD0iTTExLjczMiAyNC44NzNjMi42ODUgMCA0Ljg0LTEuMzIxIDYuNDYzLTQuMzZsMy42MTMtNi43Mzd2NS43MTRjMCAzLjM2OCAyLjE4NyA1LjM4MyA1LjU2NyA1LjM4MyAyLjY1MiAwIDQuNjA3LTEuMTU2IDYuNDk2LTQuMzZMNDIuMTkgNi41MWMxLjgyMy0zLjA3MS41My01LjM4My0zLjQ4LTUuMzgzLTIuMTU0IDAtMy41NDYuNjk0LTQuODA2IDMuMDM5bC01LjczMyAxMC43MzNWNS4zNTRjMC0yLjg0LTEuMzU5LTQuMjI3LTMuODc4LTQuMjI3LTEuOTg4IDAtMy41OC44NTktNC44MDUgMy4yMzdsLTUuNDAzIDEwLjUzNVY1LjQ1NGMwLTMuMDM5LTEuMjU5LTQuMzI3LTQuMzA4LTQuMzI3aC02LjIzQzEuMTkyIDEuMTI3IDAgMi4yMTcgMCA0LjIzMnMxLjI2IDMuMTcgMy41NDYgMy4xN2gyLjU1MnYxMi4wNTVjMCAzLjQwMSAyLjI4NyA1LjQxNiA1LjYzNCA1LjQxNk01Ni41OCA4LjIxOWwxLjM2NyAxMi43NWMuNDU1IDMuNjQzLTIuMSA0LjA5OC0zLjkxIDQuMDk4LTIuMjk0IDAtMy41MzItMS4yNzctMy41MzItMy44OTFWMTIuMzlsLTUuMjIxIDkuODhjLTEuMTQ3IDIuMTU5LTIuNDE1IDIuNzk3LTQuMzc3IDIuNzk3LTMuNjUyIDAtNC44My0yLjEyOC0zLjE3LTQuOTU1bDcuNTc3LTEyLjg5QzQ3LjAzNCA0LjI3NCA0OC4wOS45MzMgNTAuNTA1LjkzM2MzLjA3OSAwIDUuNjIgMy42NDMgNi4wNzUgNy4yODYiLz48L3N2Zz4=';

		add_menu_page( __( 'Woo Assistant', 'woo-assistant' ), __( 'Woo Assistant', 'woo-assistant' ), 'manage_options', WOOASSISTANT_PLUGIN_SLUG,
			[ $this, 'mainPage' ], $icon, '55.5' );
	}

	public function mainPage(): void {
		$logo       = Assets::url( 'images/woo-assistant-logo.svg' );
		$currentTab = self::getActiveTab();
		?>
        <div class="wrap ">
            <div class="woo-assistant-wrap woo-assistant-wrapper">
                <div class="wa-sidebar">
                    <img src="<?php echo $logo ?>" alt="Logo" class="wa-logo">
                    <div class="menu-items">
						<?php
						do_action( 'woo_assistant_start_menus' );
						$menus    = self::getMenus();
						$addonSep = false;
						echo self::menuItem( __( 'Dashboard', 'woo-assistant' ), 'dashboard' );
						foreach ( $menus as $tab => $title ) {
							if ( ! $addonSep && ! in_array( $tab, self::defaultTabs(), true ) ) {
								echo '<hr>';
								$addonSep = true;
							}

							echo self::menuItem( $title, $tab );
						}
						do_action( 'woo_assistant_end_menus' );
						?>
                    </div>
                </div>
                <div class="wa-content">
					<?php
					// Display notice
					do_action( 'woo_assistant_notice', $currentTab );
					do_action( 'woo_assistant_' . $currentTab . '_tab_notice' );

					// Display tab content
					do_action( 'woo_assistant_' . $currentTab . '_tab_content' );
					do_action( 'woo_assistant_content', $currentTab );
					?>
                </div>
            </div>
        </div>
		<?php
	}

	public static function getMenus(): array {
		return apply_filters( 'woo_assistant_menus', [] );

		/*$settings = AdminSettings::defaultSettings();
		return array_map( static function ( $setting ) {
			return $setting['title'];
		}, $settings );*/
	}

	public static function menuItem( $title, $tab, $link = null ): string {
		$current = self::getActiveTab();
		$link    = empty( $link ) ? self::link( [ 'tab' => $tab ] ) : $link;

		return '<a href="' . $link . '" class="menu-item' . ( $current === $tab ? ' menu-item-current' : '' ) . '">' . $title . '</a>';
	}

	private static function getActiveTab(): string {
		$default = 'dashboard';
		$current = strtolower( Param::get( 'tab', $default ) );
		$tabs    = array_merge( self::defaultTabs(), array_keys( self::getMenus() ) );

		return in_array( $current, $tabs, true ) ? $current : $default;
	}

	private static function defaultTabs(): array {
		return [ 'dashboard', 'product', 'order', 'cart', 'tools', 'addons', 'global' ];

		/*return array(
			'dashboard' => __( 'Dashboard', 'woo-assistant' ),
			'tools'     => __( 'Tools', 'woo-assistant' ),
			'plugins'   => __( 'Plugins', 'woo-assistant' ),
		);*/
	}

	public static function isSettingPage(): bool {
		return Param::get( 'page' ) === WOOASSISTANT_PLUGIN_SLUG;
	}

	public static function link( $query ): ?string {
		$query = is_array( $query ) ? $query : array();
		$data  = array_merge( array( 'page' => WOOASSISTANT_PLUGIN_SLUG ), $query );
		$query = http_build_query( $data );

		return admin_url( 'admin.php?' . $query );
	}
}