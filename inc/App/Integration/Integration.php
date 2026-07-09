<?php

namespace WPParsidate\App\Integration;

class Integration {
  public function __construct() {
    // E-commerce
    new WooCommerce();
    new EDD();
    new BulkyBulkEditProductsWooCommerce();

    // Customizations
    new ACF();
    new JetEngine();

    // SEO
    new RankMath();
    new SchemaPro();

    // Page Builder
    new Elementor();

    // Security
    new LimitLoginAttempts();
  }
}
