<?php
/**
 * Tools settings
 *
 * Add some tools to WP
 */

namespace WPParsidate\App\Tools;

class Tools {
  public function __construct() {
    new HookDeactivator();
    new Other();
  }
}
