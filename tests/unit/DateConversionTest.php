<?php

namespace WPParsidate\Tests\Unit;

use WP_UnitTestCase;
use WPParsidate\Core\WPP_ParsiDate;

/**
 * Tests for Gregorian <-> Jalali (Shamsi) date conversion.
 */
class DateConversionTest extends WP_UnitTestCase {

  /**
   * @return array<string, array{string, string}>
   */
  public function gregorian_to_jalali_provider(): array {
    return array(
      'nowruz 1403'            => array( '2024-03-20', '1403-01-01' ),
      'nowruz 1404'            => array( '2025-03-21', '1404-01-01' ),
      'last day of leap 1403'  => array( '2025-03-20', '1403-12-30' ),
      'last day of common 1402' => array( '2024-03-19', '1402-12-29' ),
      'first of mehr'          => array( '2024-09-22', '1403-07-01' ),
      'unix epoch'             => array( '1970-01-01', '1348-10-11' ),
    );
  }

  /**
   * @dataProvider gregorian_to_jalali_provider
   */
  public function test_parsidate_converts_gregorian_to_jalali( string $gregorian, string $jalali ): void {
    $this->assertSame( $jalali, parsidate( 'Y-m-d', $gregorian, 'eng' ) );
  }

  /**
   * @dataProvider gregorian_to_jalali_provider
   */
  public function test_gregdate_converts_jalali_to_gregorian( string $gregorian, string $jalali ): void {
    $this->assertSame( $gregorian, gregdate( 'Y-m-d', $jalali ) );
  }

  public function test_parsidate_outputs_persian_digits_by_default(): void {
    $this->assertSame( '۱۴۰۳/۰۱/۰۱', parsidate( 'Y/m/d', '2024-03-20' ) );
  }

  public function test_parsidate_accepts_timestamps(): void {
    $this->assertSame( '1403-01-01', parsidate( 'Y-m-d', (string) strtotime( '2024-03-20' ), 'eng' ) );
  }

  public function test_gregdate_returns_input_when_not_a_date(): void {
    $this->assertSame( 'not a date', gregdate( 'Y-m-d', 'not a date' ) );
  }

  /**
   * @return array<string, array{int, bool}>
   */
  public function leap_year_provider(): array {
    return array(
      '1399 leap'     => array( 1399, true ),
      '1403 leap'     => array( 1403, true ),
      '1400 common'   => array( 1400, false ),
      '1402 common'   => array( 1402, false ),
      '1404 common'   => array( 1404, false ),
    );
  }

  /**
   * @dataProvider leap_year_provider
   */
  public function test_is_per_leap_year( int $year, bool $expected ): void {
    $this->assertSame( $expected, WPP_ParsiDate::getInstance()->IsPerLeapYear( $year ) );
  }
}
