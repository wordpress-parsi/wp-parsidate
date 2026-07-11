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
    new UltimateMember();

    // SEO
    new RankMath();
    new SchemaPro();
    new MonsterInsights();

    // Page Builder
    new Elementor();

    // Security
    new LimitLoginAttempts();
  }
}
