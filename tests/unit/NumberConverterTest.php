<?php

namespace WPParsidate\Tests\Unit;

use WP_UnitTestCase;
use WPParsidate\Helper\NumberConverter;

/**
 * Tests for the post-content digit converter.
 */
class NumberConverterTest extends WP_UnitTestCase {

  public function test_converts_digits_in_visible_text(): void {
    $this->assertSame(
      '<p>سال ۱۴۰۳ و ۱۲.۵</p>',
      NumberConverter::convertContent( '<p>سال 1403 و 12.5</p>' )
    );
  }

  public function test_converts_plain_text_without_tags(): void {
    $this->assertSame( 'سال ۱۴۰۳', NumberConverter::convertContent( 'سال 1403' ) );
  }

  public function test_keeps_tags_and_attributes_byte_for_byte(): void {
    $html = '<a href="https://example.com/page/12" title="قیمت 12">قیمت 12</a>';

    $this->assertSame(
      '<a href="https://example.com/page/12" title="قیمت 12">قیمت ۱۲</a>',
      NumberConverter::convertContent( $html )
    );
  }

  public function test_keeps_escaped_attribute_markup_escaped(): void {
    $payload = '&amp;{&lt;/title&gt;&lt;img src=x onerror=alert(1)&gt;}';
    $html    = '<b title="' . $payload . '">سال 1403</b>';

    $this->assertSame(
      '<b title="' . $payload . '">سال ۱۴۰۳</b>',
      NumberConverter::convertContent( $html )
    );
  }

  public function test_keeps_digits_after_a_gt_inside_attributes_and_comments(): void {
    // The tag matcher ended a tag at the first ">", so the rest of this
    // attribute value and comment was treated as text and converted.
    $html = '<a title="سال 12 > قیمت 13">سال 20</a><!-- سال 30 > قیمت 31 -->';

    $this->assertSame(
      '<a title="سال 12 > قیمت 13">سال ۲۰</a><!-- سال 30 > قیمت 31 -->',
      NumberConverter::convertContent( $html )
    );
  }

  public function test_converts_digits_after_a_bare_less_than(): void {
    // "<" before a space or a digit is a comparison in text, not a tag.
    $this->assertSame(
      '<p>سال ۱۲ < ۱۳ و ۱۴ <۱۵</p>',
      NumberConverter::convertContent( '<p>سال 12 < 13 و 14 <15</p>' )
    );
  }

  public function test_leaves_a_title_element_and_its_attribute_untouched(): void {
    // A raw-text element that wp_kses 'post' allows. The DOMDocument
    // round-trip used to write the attribute back unescaped, ending <title>
    // early and turning the rest of the attribute into live markup.
    $payload = '&amp;{&lt;/title&gt;&lt;img src=x onerror=alert(1)&gt;}';
    $html    = '<title>سال 1403 <b title="' . $payload . '">قیمت 12</b></title><p>سال 1403</p>';

    $converted = NumberConverter::convertContent( $html );

    $this->assertSame(
      '<title>سال 1403 <b title="' . $payload . '">قیمت 12</b></title><p>سال ۱۴۰۳</p>',
      $converted
    );
  }

  public function test_protects_code_and_pre_blocks(): void {
    $html = '<pre>echo 123;</pre><code>456</code><p>سال 789</p>';

    $this->assertSame(
      '<pre>echo 123;</pre><code>456</code><p>سال ۷۸۹</p>',
      NumberConverter::convertContent( $html )
    );
  }

  public function test_skips_text_segments_without_persian_text(): void {
    $html = '<p>سال 1403</p><p>Page 45</p>';

    $this->assertSame(
      '<p>سال ۱۴۰۳</p><p>Page 45</p>',
      NumberConverter::convertContent( $html )
    );
  }

  public function test_keeps_numeric_character_references(): void {
    $this->assertSame(
      '<p>سال ۱۴۰۳ &#8220;۱۲&#8221; &#1114111; &#x1F600;</p>',
      NumberConverter::convertContent( '<p>سال 1403 &#8220;12&#8221; &#1114111; &#x1F600;</p>' )
    );
  }

  public function test_skips_content_without_persian_text(): void {
    $html = '<p>Year 2024</p>';

    $this->assertSame( $html, NumberConverter::convertContent( $html ) );
  }

  public function test_returns_empty_content_unchanged(): void {
    $this->assertSame( '', NumberConverter::convertContent( '' ) );
  }
}
