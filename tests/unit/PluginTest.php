<?php

namespace WPParsidate\Tests\Unit;

use WP_UnitTestCase;

/**
 * Smoke tests: plugin boots inside WordPress without errors.
 */
class PluginTest extends WP_UnitTestCase {

  public function test_plugin_constants_are_defined(): void {
    $this->assertTrue( defined( 'WP_PARSI_ROOT' ) );
    $this->assertTrue( defined( 'WP_PARSI_DIR' ) );
    $this->assertTrue( defined( 'WP_PARSI_URL' ) );
  }

  public function test_plugin_version_matches_main_file_header(): void {
    $header = get_file_data( WP_PARSI_ROOT, array( 'Version' => 'Version' ) );

    $this->assertNotEmpty( $header['Version'] );
    $this->assertSame( $header['Version'], WP_PARSI_VER );
  }

  public function test_plugin_version_matches_readme_stable_tag(): void {
    $readme = file_get_contents( dirname( WP_PARSI_ROOT ) . '/readme.txt' );

    $this->assertSame( 1, preg_match( '/^Stable tag:\s*(.+)$/m', $readme, $m ) );
    $this->assertSame( trim( $m[1] ), WP_PARSI_VER );
  }

  public function test_svg_sanitizer_strips_scripts(): void {
    $dirty = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><circle r="1"/></svg>';
    $clean = \WPParsidate\Helper\Sanitizing::svg( $dirty );

    $this->assertStringNotContainsString( '<script', $clean );
    $this->assertStringContainsString( '<circle', $clean );
  }
}
