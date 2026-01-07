<?php

namespace Jetexir\Integrations;

defined( 'ABSPATH' ) || exit;

class Integrations {
  public function __construct() {
    new WooDeveloperFeed();
  }
}
