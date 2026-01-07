<?php

namespace Jetexir\App\General;

defined( 'ABSPATH' ) || exit;

class General {
  public function __construct() {
    new Styles();
    new Debug();
  }
}
