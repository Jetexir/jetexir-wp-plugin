<?php

namespace WooAssistant\Helper;

class Strip {
	public static function removeHtmlComments( $html ) {
		return preg_replace( '~<!--(.*?)-->~s', '', $html );
	}

	public static function removeHtmlDoctype( $html ) {
		return preg_replace( '/^<!DOCTYPE.+?>/', '', $html );
	}
}