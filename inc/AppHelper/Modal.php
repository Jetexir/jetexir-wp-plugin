<?php

namespace WooAssistant\AppHelper;

class Modal {
	public function __construct() {
		add_action( 'wp_footer', [ $this, 'printModal' ] );
		add_action( 'admin_footer', [ $this, 'printAdminModal' ] );
	}

	public function printAdminModal(): void {
		do_action( 'woo_assistant_admin_modals' );

		if ( apply_filters( 'woo_assistant_admin_modal_overlay', true ) ) {
			echo '<div id="wa-modal-overlay" class="wa-modal-overlay"></div>';
		}
	}

	public function printModal(): void {
		do_action( 'woo_assistant_site_modals' );

		if ( apply_filters( 'woo_assistant_site_modal_overlay', false ) ) {
			echo '<div id="wa-modal-overlay" class="wa-modal-overlay"></div>';
		}
	}
}