<?php

namespace WPParsidate\App\Integration;

class Integration {
  public function __construct() {
    // Recommended
    new ParsigateDemo();
    new TapChatDemo();

    // E-commerce
    new WooCommerce();
    new EDD();
    new BulkyBulkEditProductsWooCommerce();

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
    new Brizy();

    // Form
    new Formello();

    // Security
    new LimitLoginAttempts();

    // Tools
    new Booked();
  }
}
