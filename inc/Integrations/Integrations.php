<?php

namespace AssistantForWooCommerce\Integrations;

defined( 'ABSPATH' ) || exit;

class Integrations {
  public function __construct() {
    new WooDeveloperFeed();
  }
}
