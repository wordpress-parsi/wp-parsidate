<?php

namespace WPParsidate\Admin;

defined( 'ABSPATH' ) || exit;

class Admin {
  public function __construct() {
    new AdminAssets();
    new AdminSettings();
    new AdminPages();

    new AdminDashboard();
    new AdminCore();
    new AdminConvert();
    new AdminTools();
    new AdminIntegration();
    new AdminAbout();
  }
}
