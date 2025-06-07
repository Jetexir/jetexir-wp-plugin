<?php

namespace WooAssistant\Helper;

class Templates {
	public static function load( $file, $args = [], $loadOnce = false ): void {
		if ( file_exists( $file ) ) {
			load_template( $file, $loadOnce, $args );
		}
	}

	public static function getPath( $template, $dir = 'plugin' ): string {
		$path = Assets::pathCorrection( WOOASSISTANT_PLUGIN_PATH . '/inc/Templates/' . $dir . '/' . $template );

		return apply_filters( 'woo_assistant_template_path', $path, $template, $dir );
	}
}