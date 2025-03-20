<?php

namespace WooAssistant\Helper;

class Nonce {
	public static function create( $action = WOOASSISTANT_PLUGIN_KEY ) {
		return wp_create_nonce( $action );
	}

	public static function verify( $nonce = null, $action = WOOASSISTANT_PLUGIN_KEY ) {
		$nonce = is_null( $nonce ) && isset( $_POST['nonce'] ) ? Sanitizing::text( wp_unslash( Param::post( 'nonce' ) ) ) : $nonce;
		if ( is_null( $nonce ) ) {
			return false;
		}

		return wp_verify_nonce( $nonce, $action );
	}
}