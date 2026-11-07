<?php

namespace WPParsidate\Tests\Unit;

use WP_UnitTestCase;
use WPParsidate\Helper\Date;

/**
 * Jalali date validation for supported formats; preserve existing custom-format behavior.
 */
class DateValidationTest extends WP_UnitTestCase {

  /**
   * @return array<string, array{string, string, bool, string|null}>
   */
  public function jalali_date_validation_provider(): array {
    return array(
      'valid Farvardin day 1'           => array( '1403-01-01', 'Y-m-d', true, 'jalali' ),
      'valid Ordibehesht day 29'       => array( '1403-02-29', 'Y-m-d', true, 'jalali' ),
      'valid Ordibehesht day 30'       => array( '1403-02-30', 'Y-m-d', true, 'jalali' ),
      'valid Ordibehesht day 31'       => array( '1403-02-31', 'Y-m-d', true, 'jalali' ),
      'valid Tir day 31'               => array( '1403-04-31', 'Y-m-d', true, 'jalali' ),
      'valid Shahrivar day 31'         => array( '1403-06-31', 'Y-m-d', true, 'jalali' ),
      'valid Mehr day 30'             => array( '1403-07-30', 'Y-m-d', true, 'jalali' ),
      'invalid Mehr day 31'           => array( '1403-07-31', 'Y-m-d', false, null ),
      'valid leap Esfand day 30'      => array( '1403-12-30', 'Y-m-d', true, 'jalali' ),
      'invalid common 1402 Esfand 30' => array( '1402-12-30', 'Y-m-d', false, null ),
      'invalid common 1404 Esfand 30' => array( '1404-12-30', 'Y-m-d', false, null ),
      'valid common Esfand day 29'    => array( '1404-12-29', 'Y-m-d', true, 'jalali' ),
      'valid Jalali datetime'         => array( '1403-02-31T23:59', 'Y-m-d\TH:i', true, 'jalali' ),
      'invalid Jalali hour and minute' => array( '1403-02-31T25:99', 'Y-m-d\TH:i', false, null ),
      'valid Jalali datetime offset'  => array( '1403-02-31T23:59:59+03:30', 'Y-m-d\TH:i:sP', true, 'jalali' ),
      'invalid Jalali datetime offset'=> array( '1403-02-31T25:99:59+03:30', 'Y-m-d\TH:i:sP', false, null ),
      'invalid Jalali timezone hour 25'   => array( '1403-02-31T23:59:59+25:00', 'Y-m-d\TH:i:sP', false, null ),
      'invalid Jalali timezone minute 99' => array( '1403-02-31T23:59:59+03:99', 'Y-m-d\TH:i:sP', false, null ),
      'invalid Jalali timezone hour -25'  => array( '1403-02-31T23:59:59-25:00', 'Y-m-d\TH:i:sP', false, null ),
      'invalid Jalali timezone O 2500'   => array( '1403-02-31T23:59:59+2500', 'Y-m-d\TH:i:sO', false, null ),
      'invalid Jalali short-hour offset' => array( '1403-02-31T23:59:59+3:99', 'Y-m-d\TH:i:sP', false, null ),
      'invalid Jalali colonless P offset' => array( '1403-02-31T23:59:59+2500', 'Y-m-d\TH:i:sP', false, null ),
      'valid Jalali canonical O offset'   => array( '1403-02-31T23:59:59+0330', 'Y-m-d\TH:i:sO', true, 'jalali' ),
      'invalid Jalali short O offset'     => array( '1403-02-31T23:59:59+250', 'Y-m-d\TH:i:sO', false, null ),
      'unsupported custom offset format keeps baseline rejection' => array( '1403-02-31 +03:30 23:59', 'Y-m-d P H:i', false, null ),
      'unsupported suffix format keeps baseline rejection'        => array( '1403-02-31T23:59:59+03:30!', 'Y-m-d\TH:i:sP\!', false, null ),
      'invalid Jalali time seconds'   => array( '1403-02-31T23:59:60+03:30', 'Y-m-d\TH:i:sP', false, null ),
      'valid Gregorian leap day'      => array( '2024-02-29', 'Y-m-d', true, 'gregorian' ),
      'invalid Gregorian leap day'    => array( '2025-02-29', 'Y-m-d', false, null ),
      'valid Gregorian datetime'      => array( '2024-02-29T23:59', 'Y-m-d\TH:i', true, 'gregorian' ),
      'invalid Gregorian datetime'    => array( '2024-02-29T25:99', 'Y-m-d\TH:i', false, null ),
    );
  }

  /**
   * @dataProvider jalali_date_validation_provider
   */
  public function test_jalali_date_validation( string $input, string $format, bool $expectedValid, ?string $expectedType ): void {
    $actual = Date::isDateString( $input, $format );
    $this->assertSame( $expectedValid, $actual['status'] );
    $this->assertSame( $expectedType, $actual['type'] );
    $this->assertSame( $expectedValid ? $input : '', $actual['value'] );
  }

  public function test_public_date_validator_wrapper(): void {
    $expected = array( 'status' => true, 'type' => 'jalali', 'value' => '1403-02-31' );
    $this->assertSame( $expected, wpp_date_is( '1403-02-31', 'Y-m-d' ) );
  }

  public function test_persian_digits_in_valid_jalali_date(): void {
    // Persian digits for 1403-02-31, represented in hex to avoid WinPS encoding surprises.
    $input = hex2bin( 'DBB1DBB4DBB0DBB32DDBB0DBB22DDBB3DBB1' );
    $this->assertSame(
      array( 'status' => true, 'type' => 'jalali', 'value' => '1403-02-31' ),
      Date::isDateString( $input, 'Y-m-d' )
    );
  }
}
