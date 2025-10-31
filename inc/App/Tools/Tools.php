<?php

namespace AssistantForWooCommerce\App\Tools;

defined( 'ABSPATH' ) || exit;

class Tools {
  public function __construct() {
    new AnnouncementBarTools();
    new CurrencySymbolTools();
  }
}
