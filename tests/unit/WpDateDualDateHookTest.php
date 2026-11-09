<?php

namespace WPParsidate\Tests\Unit;

use DateTimeZone;
use ReflectionClass;
use WP_UnitTestCase;
use WPParsidate\App\Core\FixDates;
use WPParsidate\Helper\Number;

/**
 * Preserve the original caller format in Dual Date's public hook arguments.
 */
class WpDateDualDateHookTest extends WP_UnitTestCase {
  private function renderDualDate( string $format, int $timestamp, string $zone ): string {
    $originalHook = $GLOBALS['wp_filter']['wp_date'] ?? null;
    unset( $GLOBALS['wp_filter']['wp_date'] );

    $fixDates = ( new ReflectionClass( FixDates::class ) )->newInstanceWithoutConstructor();
    $callback = array( $fixDates, 'fixWpDate' );
    $enableDual = static function ( $value, $key ) {
      return 'dual_date' === $key ? true : $value;
    };
    add_filter( 'wp_date', $callback, 10, 4 );
    add_filter( 'wp_parsidate_get_setting', $enableDual, 999999, 2 );
    try {
      return wp_date( $format, $timestamp, new DateTimeZone( $zone ) );
    } finally {
      remove_filter( 'wp_parsidate_get_setting', $enableDual, 999999 );
      remove_filter( 'wp_date', $callback, 10 );
      if ( null === $originalHook ) {
        unset( $GLOBALS['wp_filter']['wp_date'] );
      } else {
        $GLOBALS['wp_filter']['wp_date'] = $originalHook;
      }
    }
  }

  public function test_separator_hook_receives_original_mixed_format(): void {
    $seen = null;
    $capture = static function ( $separator, $format ) use ( &$seen ) {
      $seen = $format;
      return $separator;
    };
    add_filter( 'wp_parsidate_dual_date_separator', $capture, 10, 2 );
    try {
      $this->renderDualDate( 'Y-m-d U', 1700000000, 'Asia/Tehran' );
    } finally {
      remove_filter( 'wp_parsidate_dual_date_separator', $capture, 10 );
    }
    $this->assertSame( 'Y-m-d U', $seen );
  }

  public function test_gregorian_hook_receives_original_mixed_format(): void {
    $seen = null;
    $capture = static function ( $date, $format ) use ( &$seen ) {
      $seen = $format;
      return $date;
    };
    add_filter( 'wp_parsidate_dual_date_gregorian', $capture, 10, 2 );
    try {
      $this->renderDualDate( 'Y-m-d U', 1700000000, 'Asia/Tehran' );
    } finally {
      remove_filter( 'wp_parsidate_dual_date_gregorian', $capture, 10 );
    }
    $this->assertSame( 'Y-m-d U', $seen );
  }

  public function test_mixed_format_keeps_both_correctly_formatted_dates(): void {
    $actual = Number::toEnglish( $this->renderDualDate( 'Y-m-d U', 1700000000, 'Asia/Tehran' ) );
    $this->assertSame( '1402-08-24 1700000000 - 2023-11-15 1700000000', $actual );
  }

  public function test_plain_date_format_remains_unchanged_in_hook(): void {
    $seen = null;
    $capture = static function ( $separator, $format ) use ( &$seen ) {
      $seen = $format;
      return $separator;
    };
    add_filter( 'wp_parsidate_dual_date_separator', $capture, 10, 2 );
    try {
      $this->renderDualDate( 'Y-m-d', 1700000000, 'Asia/Tehran' );
    } finally {
      remove_filter( 'wp_parsidate_dual_date_separator', $capture, 10 );
    }
    $this->assertSame( 'Y-m-d', $seen );
  }
}
