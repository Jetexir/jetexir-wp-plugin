<?php

namespace WooAssistant\Admin;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Addons\Addons;
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
		$addonList = $this->getAddons();
		if ( empty( $addonList ) ) {
			$message = '<strong>' . __( 'Hello', 'woo-assistant' ) . ', ' . User::getData( 'display_name' ) . '!</strong>';
			$message .= '<p>' . __( 'Woo Assistant is here to help you sell more in your store. To get started, go to the Addons tab and activate the required addons.', 'woo-assistant' ) . '</p>';

			echo '<div class="wa-dashboard-welcome">' . $message . '</div>';

		} else {
			echo '<div class="wa-dashboard-addons-wrap">';
			foreach ( $addonList as $addon ) {
				echo '<a href="' . $addon['link'] . '" title="' . $addon['desc'] . '">' . $addon['icon'] . '<span>' . $addon['title'] . '</span></a>';
			}
			echo '</div>';
		}
	}

	private function getAddons(): array {
		$addons    = apply_filters( 'woo_assistant_dashboard_addons', [] );
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