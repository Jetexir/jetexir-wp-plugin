<?php

namespace WooAssistant\AppHelper;

class Modal {
	public function __construct() {
		add_action( 'wp_footer', [ $this, 'printModal' ] );
		add_action( 'wp_footer', [ $this, 'printOverlay' ] );
	}

	public function printModal(): void {
		do_action( 'woo_assistant_site_modals' );
	}

	public function printOverlay(): void {
		if ( apply_filters( 'woo_assistant_modal_overlay', false ) ) {
			echo '<div id="wa-modal-overlay" class="wa-modal-overlay"></div>';
		}
	}
}