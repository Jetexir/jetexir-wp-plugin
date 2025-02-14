<?php

namespace WooAssistant\Admin;

defined( 'ABSPATH' ) || exit;

use WooAssistant\Helper\Notice;
use WooAssistant\Interfaces\AdminTabInterface;

class AdminDashboard implements AdminTabInterface {
	public const tab = 'dashboard';

	public function __construct() {
		add_action( 'woo_assistant_dashboard_tab_content', [ $this, 'content' ] );
		add_action( 'woo_assistant_admin_init', [ $this, 'notice' ] );
	}

	public function notice(): void {

	}

	public function content(): void {
		echo 'dashboard';
	}

	public function settings(): array {
		return [];
	}
}