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

  public function test_validator_leaves_pods_display_formats_to_pods(): void {
    $addon = ( new \ReflectionClass( Pods::class ) )->newInstanceWithoutConstructor();

    // m/d/Y is the Pods default; rejecting it blocked every save with a date field.
    $this->assertTrue( $addon->validateDateField( true, '11/26/2026' ) );
    $this->assertTrue( $addon->validateDateField( true, '' ) );
    $this->assertTrue( $addon->validateDateField( true, '1405/09/05' ) );
    $this->assertFalse( $addon->validateDateField( true, '1405-13-01' ) );
  }

  public function test_validator_keeps_errors_from_other_filters(): void {
    $addon = ( new \ReflectionClass( Pods::class ) )->newInstanceWithoutConstructor();

    $this->assertSame( 'Field is required', $addon->validateDateField( 'Field is required', '1405-09-05' ) );
    $this->assertSame( [ 'error' ], $addon->validateDateField( [ 'error' ], '1405-09-05' ) );
  }

  public function test_non_array_pieces_and_non_string_values_pass_through(): void {
    $addon = ( new \ReflectionClass( Pods::class ) )->newInstanceWithoutConstructor();

    $this->assertFalse( $addon->preparePodItemDates( false ) );
    $this->assertNull( Pods::toJalaliDisplay( null ) );
  }
}
