<?php

namespace WPParsidate\Tests\Unit;

use WP_UnitTestCase;
use WPParsidate\App\Integration\Pods;

class PodsTest extends WP_UnitTestCase {

  public function test_addon_converts_jalali_date_to_gregorian_storage(): void {
    $this->assertSame( '2026-10-06', Pods::toGregorianStorage( '1405-07-14', 'date' ) );
  }

  public function test_addon_preserves_gregorian_datetime_in_storage_format(): void {
    $this->assertSame( '2026-10-06 15:30:45', Pods::toGregorianStorage( '2026-10-06 15:30:45', 'datetime' ) );
  }

  public function test_addon_converts_jalali_datetime_to_gregorian_storage(): void {
    $this->assertSame( '2026-10-06 15:30:45', Pods::toGregorianStorage( '1405-07-14 15:30:45', 'datetime' ) );
  }

  public function test_addon_returns_invalid_date_unchanged(): void {
    $this->assertSame( '1405-13-01', Pods::toGregorianStorage( '1405-13-01', 'date' ) );
  }

  public function test_addon_converts_gregorian_value_to_jalali_display(): void {
    $this->assertSame( '1405-07-14', Pods::toJalaliDisplay( '2026-10-06', 'date' ) );
  }
}
