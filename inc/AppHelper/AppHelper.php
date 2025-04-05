<?php

namespace WooAssistant\AppHelper;

class AppHelper {
	public function __construct() {
		new FlyIcons();
		new Modal();
		new DataTableUI();
	}
}