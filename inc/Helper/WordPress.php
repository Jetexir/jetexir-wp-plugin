<?php

namespace WooAssistant\Helper;

class WordPress {
	public static function isPage( $page ) {
		return is_page( $page );
	}
}