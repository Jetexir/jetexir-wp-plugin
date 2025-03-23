<?php

namespace WooAssistant\Helper;

class Templates {
	public static function getPath( $file, $dir = 'woocommerce' ): string {
		return self::pathCorrection( WOOASSISTANT_PLUGIN_PATH . '/inc/Templates/' . $dir . '/' . $file );
	}

	public static function pathCorrection( $path ): string {
		return str_replace( [ '/', '\\' ], DIRECTORY_SEPARATOR, $path );
	}
}