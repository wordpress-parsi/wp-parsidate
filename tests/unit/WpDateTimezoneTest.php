<?php

namespace WPParsidate\Tests\Unit;

use DateTimeImmutable;
use DateTimeZone;
use ReflectionClass;
use WP_UnitTestCase;
use WPParsidate\App\Core\FixDates;
use WPParsidate\Helper\Number;
use WPParsidate\Settings\Settings;

/**
 * Regression coverage for wp_date() Unix timestamps and historical offsets.
 * Uses the actual plugin class under the real WordPress PHPUnit bootstrap.
 */
class WpDateTimezoneTest extends WP_UnitTestCase {

  /**
   * @return array<string, array{string, int}>
   */
  public function unix_timestamp_cases(): array {
    return array(
      'Tehran U'  => array( 'Asia/Tehran', 1700000000 ),
      'Berlin U'  => array( 'Europe/Berlin', 1700000000 ),
      'New York U' => array( 'America/New_York', 1700000000 ),
      'UTC U control' => array( 'UTC', 1700000000 ),
    );
  }

  /** @dataProvider unix_timestamp_cases */
  public function test_unix_timestamp_is_timezone_independent( string $zone, int $timestamp ): void {
    $actual = $this->runPluginDateFilter( 'U', $timestamp, $zone );
    $this->assertSame( (string) $timestamp, $actual );
  }

  /**
   * @return array<string, array{string, int}>
   */
  public function historical_timezone_cases(): array {
    return array(
      'Berlin summer 2024' => array( 'Europe/Berlin', 1719873000 ),
      'Berlin winter 2024' => array( 'Europe/Berlin', 1704234600 ),
      'Tehran summer DST 2021' => array( 'Asia/Tehran', 1625169000 ),
      'Tehran no DST 2024' => array( 'Asia/Tehran', 1719863400 ),
      'Unix epoch zero Berlin' => array( 'Europe/Berlin', 0 ),
    );
  }

  /** @dataProvider historical_timezone_cases */
  public function test_jalali_datetime_uses_offset_at_requested_instant( string $zone, int $timestamp ): void {
    $timezone = new DateTimeZone( $zone );
    $offsetAtTarget = $timezone->getOffset( new DateTimeImmutable( '@' . $timestamp ) );
    $reference = parsidate( 'Y-m-d H:i', $timestamp + $offsetAtTarget, Settings::get( 'conv_dates' ) );
    $actual = $this->runPluginDateFilter( 'Y-m-d H:i', $timestamp, $zone );
    $this->assertSame( $reference, $actual );
  }

  /**
   * @return array<string, array{string, int, string, string}>
   */
  public function mixed_unix_format_cases(): array {
    return array(
      'Tehran suffix U' => array( 'Asia/Tehran', 1700000000, 'Y-m-d U', 'suffix' ),
      'Berlin prefix U' => array( 'Europe/Berlin', 1700000000, 'U Y-m-d', 'prefix' ),
      'New York repeated U' => array( 'America/New_York', 1700000000, 'Y-m-d U U', 'double' ),
      'Tehran negative epoch U' => array( 'Asia/Tehran', -1, 'Y-m-d U', 'suffix' ),
    );
  }

  /** @dataProvider mixed_unix_format_cases */
  public function test_mixed_unix_tokens_keep_absolute_timestamp( string $zone, int $timestamp, string $format, string $position ): void {
    $timezone = new DateTimeZone( $zone );
    $localGregorian = ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( $timezone )->format( 'Y-m-d' );
    $jalali = Number::toEnglish( parsidate( 'Y-m-d', $localGregorian, Settings::get( 'conv_dates' ) ) );
    $unix = (string) $timestamp;

    if ( 'prefix' === $position ) {
      $expected = $unix . ' ' . $jalali;
    } elseif ( 'double' === $position ) {
      $expected = $jalali . ' ' . $unix . ' ' . $unix;
    } else {
      $expected = $jalali . ' ' . $unix;
    }

    $actual = Number::toEnglish( $this->runPluginDateFilter( $format, $timestamp, $zone ) );
    $this->assertSame( $expected, $actual );
  }

  private function runPluginDateFilter( string $format, int $timestamp, string $zone ): string {
    // The plugin hook-deactivator inspects the real wp_date() call stack.
    // Preserve any existing callbacks, and register only the plugin filter under test.
    $existingHook = $GLOBALS['wp_filter']['wp_date'] ?? null;
    unset( $GLOBALS['wp_filter']['wp_date'] );

    $reflection = new ReflectionClass( FixDates::class );
    $filter = $reflection->newInstanceWithoutConstructor();
    $callback = array( $filter, 'fixWpDate' );
    add_filter( 'wp_date', $callback, 10, 4 );

    try {
      return wp_date( $format, $timestamp, new DateTimeZone( $zone ) );
    } finally {
      remove_filter( 'wp_date', $callback, 10 );
      if ( null !== $existingHook ) {
        $GLOBALS['wp_filter']['wp_date'] = $existingHook;
      } else {
        unset( $GLOBALS['wp_filter']['wp_date'] );
      }
    }
  }
}