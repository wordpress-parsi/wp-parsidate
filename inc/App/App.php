<?php

namespace WPParsidate\App;

defined( 'ABSPATH' ) || exit;

use WPParsidate\App\Convert\Convert;
use WPParsidate\App\Core\Core;
use WPParsidate\App\Integration\Integration;
use WPParsidate\App\Tools\Tools;

class App {
  public function __construct() {
    new AppAssets();
    new Core();
    new Convert();
    new Tools();
    new Integration();

    add_action( 'init', [ $this, 'init' ], 0 );
  }

  public function init(): void {
    do_action( 'wp_parsidate_addons_load' );
    do_action( 'wp_parsidate_init' );
  }
}
