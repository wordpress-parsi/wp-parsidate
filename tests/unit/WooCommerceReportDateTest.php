<?php

namespace WPParsidate\Tests\Unit;

use ReflectionClass;
use WP_UnitTestCase;
use WPParsidate\App\Integration\WooCommerce;

/**
 * Regression tests for the report range dates that reach the legacy
 * WooCommerce report SQL through woocommerce_reports_get_order_report_query.
 *
 * The values arrive from the query string, so they must never be written into
 * the report query unless they are a real Jalali or Gregorian Y-m-d date.
 */
class WooCommerceReportDateTest extends WP_UnitTestCase {

  /**
   * @var WooCommerce
   */
  private $addon;

  public function set_up(): void {
    parent::set_up();

    // The methods under test do not need the add-on bootstrap.
    $this->addon = ( new ReflectionClass( WooCommerce::class ) )->newInstanceWithoutConstructor();
  }

  public function tear_down(): void {
    unset( $_GET['page'], $_GET['start_date'], $_GET['end_date'] );

    parent::tear_down();
  }

  public function test_sql_fragment_in_report_dates_is_not_written_to_the_query(): void {
    $_GET['start_date'] = "1' AND 99=99 AND SLEEP(1)=0 AND '1'='1' --";
    $_GET['end_date']   = '1405-10-11';

    $where = "WHERE posts.post_type IN ('shop_order') AND posts.post_date >= '2026-11-19 00:00:00' AND posts.post_date < '2026-11-20 00:00:00'";

    $result = $this->addon->fixOrderReportQueryDate( array( 'where' => $where ) );

    $this->assertSame( $where, $result['where'] );
  }

  public function test_report_dates_that_are_not_dates_are_dropped_from_the_request(): void {
    $_GET['page']       = 'wc-reports';
    $_GET['start_date'] = "1' OR 1=1 --";
    $_GET['end_date']   = '1405-10-11';

    $this->addon->changeReportDates();

    $this->assertArrayNotHasKey( 'start_date', $_GET );
    $this->assertSame( '2027-01-01', $_GET['end_date'] );
  }

  public function test_date_followed_by_an_sql_fragment_is_rejected(): void {
    $_GET['page']       = 'wc-reports';
    $_GET['start_date'] = "1405-10-11' OR 1=1 --";
    $_GET['end_date']   = '1405-10-12';

    $this->addon->changeReportDates();

    $this->assertArrayNotHasKey( 'start_date', $_GET );
  }

  public function test_report_date_with_a_null_byte_is_rejected_without_a_fatal_error(): void {
    // date_parse_from_format() throws a ValueError for a null byte on PHP 8.
    $_GET['page']       = 'wc-reports';
    $_GET['start_date'] = "1405-10-11\0' OR 1=1 --";
    $_GET['end_date']   = '1405-10-12';

    $this->addon->changeReportDates();

    $this->assertArrayNotHasKey( 'start_date', $_GET );
  }

  public function test_report_dates_with_slash_separators_are_accepted(): void {
    $_GET['page']       = 'wc-reports';
    $_GET['start_date'] = '1405/10/11';
    $_GET['end_date']   = '1405/10/12';

    $this->addon->changeReportDates();

    $this->assertSame( '2027-01-01', $_GET['start_date'] );
    $this->assertSame( '2027-01-02', $_GET['end_date'] );
  }

  public function test_jalali_report_dates_are_converted_in_the_report_query(): void {
    $_GET['start_date'] = '1405-10-11';
    $_GET['end_date']   = '1405-10-12';

    $where = "AND posts.post_date >= '1405-10-11 00:00:00' AND posts.post_date < '1405-10-13 00:00:00'";

    $result = $this->addon->fixOrderReportQueryDate( array( 'where' => $where ) );

    // The end date is inclusive, so WooCommerce compares with "< end + 1 day".
    $this->assertSame(
      "AND posts.post_date >= '2027-01-01 00:00:00' AND posts.post_date < '2027-01-03 00:00:00'",
      $result['where']
    );
  }

  public function test_gregorian_report_dates_are_kept_as_they_are(): void {
    $_GET['start_date'] = '2026-11-01';
    $_GET['end_date']   = '2026-11-02';

    $where = "AND posts.post_date >= '1405-08-10 00:00:00' AND posts.post_date < '1405-08-11 00:00:00'";

    $result = $this->addon->fixOrderReportQueryDate( array( 'where' => $where ) );

    $this->assertSame(
      "AND posts.post_date >= '2026-11-01 00:00:00' AND posts.post_date < '2026-11-03 00:00:00'",
      $result['where']
    );
  }

  public function test_report_query_without_a_where_clause_is_untouched(): void {
    $_GET['start_date'] = '1405-10-11';
    $_GET['end_date']   = '1405-10-12';

    $this->assertSame( array( 'join' => '' ), $this->addon->fixOrderReportQueryDate( array( 'join' => '' ) ) );
  }
}
