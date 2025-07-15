<?php

namespace WooAssistant\Admin;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Addons\Addons;
use WooAssistant\Helper\Assets;
use WooAssistant\Helper\Notice;
use WooAssistant\Helper\User;
use WooAssistant\Interfaces\AdminTabInterface;

class AdminDashboard implements AdminTabInterface {
	public const tab = 'dashboard';

	public function __construct() {
		add_action( 'woo_assistant_dashboard_tab_content', [ $this, 'content' ] );
		add_action( 'woo_assistant_admin_init', [ $this, 'notice' ] );
	}

	public function notice(): void {
		Notice::add( self::tab, __( 'Welcome to Woo Assistant!', 'woo-assistant' ), 'default' );
	}

	public function content(): void {
		$dashboardTypeLinks = array(
			'addons' => $this->getAddons(),
			'custom' => apply_filters( 'woo_assistant_dashboard_custom_links', [] )
		);

		if ( empty( $dashboardTypeLinks['addons'] ) ) {
			$message = '<strong>' . __( 'Hello', 'woo-assistant' ) . ', ' . User::getData( 'display_name' ) . '!</strong>';
			$message .= '<p>' . __( 'Woo Assistant is here to help you sell more in your store. To get started, go to the Addons tab and activate the required addons.', 'woo-assistant' ) . '</p>';

			echo '<div class="wa-dashboard-welcome">' . $message . '</div>';
		}

		echo '<div class="wa-dashboard-links-wrap">';
		foreach ( $dashboardTypeLinks as $dashboardLinks ) {
			foreach ( $dashboardLinks as $link ) {
				$icon = ! empty( $link['icon'] ) && Assets::isSvgImageString( $link['icon'] ) ? Assets::setSvgDimensions( $link['icon'], 50 ) : '';
				echo '<a href="' . $link['link'] . '" title="' . $link['desc'] . '" class="wa-link-type-' . $link['type'] . '">' . $icon . '<span>' . $link['title'] . '</span></a>';
			}
		}
		echo '</div>';
	}

	private function getAddons(): array {
		$addons    = apply_filters( 'woo_assistant_dashboard_addon_links', [] );
		$addonCats = Addons::getAddonCats();
		$addonList = array();
		foreach ( array_keys( $addonCats ) as $addonCat ) {
			if ( ! empty( $addons[ $addonCat ] ) ) {
				$addonList[] = $addons[ $addonCat ];
			}
		}

		return array_merge( [], ...$addonList );
	}
}