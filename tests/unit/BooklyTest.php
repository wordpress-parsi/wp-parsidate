<?php

namespace WPParsidate\Tests\Unit;

use WP_UnitTestCase;
use WPParsidate\App\Integration\Bookly as BooklyAddon;

class BooklyTest extends WP_UnitTestCase {

  public function test_addon_requires_only_bookly_free(): void {
    $addon       = new BooklyAddon();
    $requirements = $addon->info()['requires_plugins'];

    $this->assertSame(
      [ 'bookly-responsive-appointment-booking-tool/main.php' ],
      array_keys( $requirements )
    );
    $this->assertTrue( $requirements['bookly-responsive-appointment-booking-tool/main.php']['is_free'] );
    $this->assertEmpty( $requirements['bookly-responsive-appointment-booking-tool/main.php']['optional'] ?? false );
  }
}
