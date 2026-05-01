<?php

namespace WPParsidate\Core;

defined( 'ABSPATH' ) || exit;

class Core {
  public function __construct() {
    new Posts();
  }
}
