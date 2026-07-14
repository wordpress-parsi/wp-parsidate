<?php

namespace WPParsidate\App\Integration;

class Integration {
  public function __construct() {
    // E-commerce
    new WooCommerce();
    new EDD();
    new BulkyBulkEditProductsWooCommerce();

    // Tools

    // Marketing

    // Customizations
    new ACF();
    new JetEngine();
    new UltimateMember();

    // SEO
    new RankMath();
    new SchemaPro();
    new MonsterInsights();

    // Utility

    // Page Builder
    new Elementor();
    new Formello();

    // Security
    new LimitLoginAttempts();
  }
}
