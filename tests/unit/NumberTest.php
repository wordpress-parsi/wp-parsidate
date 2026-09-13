<?php

namespace WPParsidate\Tests\Unit;

use WP_UnitTestCase;
use WPParsidate\Helper\Number;

/**
 * Tests for the Persian/English digit helpers.
 */
class NumberTest extends WP_UnitTestCase {

  public function test_to_persian_converts_every_digit(): void {
    $this->assertSame( '۰۱۲۳۴۵۶۷۸۹', Number::toPersian( '0123456789' ) );
  }

  public function test_to_english_converts_every_digit(): void {
    $this->assertSame( '0123456789', Number::toEnglish( '۰۱۲۳۴۵۶۷۸۹' ) );
  }

  public function test_to_persian_and_to_english_are_inverse(): void {
    $input = 'Order #4021 total 1,250.75';

    $this->assertSame( $input, Number::toEnglish( Number::toPersian( $input ) ) );
  }

  public function test_fix_number_converts_digits_in_plain_text(): void {
    $this->assertSame( 'سال ۱۴۰۳', Number::fixNumber( 'سال 1403' ) );
  }

  public function test_fix_number_keeps_html_attributes_untouched(): void {
    $html = '<a href="https://example.com/page/12" class="c1">12</a>';

    $this->assertSame(
      '<a href="https://example.com/page/12" class="c1">۱۲</a>',
      Number::fixNumber( $html )
    );
  }

  public function test_fix_number_skips_code_and_pre_blocks(): void {
    $html = '<pre>echo 123;</pre><code>456</code><p>789</p>';

    $this->assertSame(
      '<pre>echo 123;</pre><code>456</code><p>۷۸۹</p>',
      Number::fixNumber( $html )
    );
  }

  public function test_global_helper_functions_are_available(): void {
    $this->assertSame( '۱۲', per_number( '12' ) );
    $this->assertSame( '12', eng_number( '۱۲' ) );
    $this->assertSame( '۱۲', fix_number( '12' ) );
  }
}
