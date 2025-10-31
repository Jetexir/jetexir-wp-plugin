<?php

namespace AssistantForWooCommerce\App\WordPress;

defined( 'ABSPATH' ) || exit;

class WordPress {
  public function __construct() {
    new Media();
  }
}
