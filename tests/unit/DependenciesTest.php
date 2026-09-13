<?php

namespace WPParsidate\Tests\Unit;

use WP_UnitTestCase;

/**
 * Third-party dependencies are loaded from the prefixed packages/ directory.
 */
class DependenciesTest extends WP_UnitTestCase {

  public function test_prefixed_svg_sanitizer_class_is_used(): void {
    $this->assertTrue( class_exists( \WPParsidate\Dependencies\enshrined\svgSanitize\Sanitizer::class ) );
  }

  public function test_svg_sanitizer_is_loaded_from_packages_directory(): void {
    $reflection = new \ReflectionClass( \WPParsidate\Dependencies\enshrined\svgSanitize\Sanitizer::class );

    $this->assertStringStartsWith( dirname( WP_PARSI_ROOT ) . '/packages/', $reflection->getFileName() );
  }

  public function test_polyfilled_functions_are_available(): void {
    // Provided natively on newer PHP or by the bundled Symfony polyfills on PHP 7.4 - 8.2.
    $this->assertTrue( function_exists( 'str_contains' ) );
    $this->assertTrue( function_exists( 'str_starts_with' ) );
    $this->assertTrue( function_exists( 'array_is_list' ) );
    $this->assertTrue( function_exists( 'json_validate' ) );

    $this->assertTrue( str_contains( 'پارسی دیت', 'دیت' ) );
    $this->assertTrue( array_is_list( array( 1, 2, 3 ) ) );
    $this->assertTrue( json_validate( '{"a":1}' ) );
  }
}
