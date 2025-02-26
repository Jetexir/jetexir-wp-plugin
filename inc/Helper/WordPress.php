<?php

namespace WooAssistant\Helper;

class WordPress {
	public static function blogInfo( $show = '', $filter = 'raw' ) {
		return get_bloginfo( $show, $filter );
	}

	public static function isPage( $page ): bool {
		return is_page( $page );
	}
}