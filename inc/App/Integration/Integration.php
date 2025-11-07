<?php

namespace WPParsidate\App\Integration;

class Integration {
  public function __construct() {
    new WooCommerce();
    new EDD();
    new Elementor();
    new ACF();
    new RankMath();
    new HookDeactivator();
  }
}
