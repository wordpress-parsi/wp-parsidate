<?php

namespace WPParsidate\Tests\Unit;

use WP_UnitTestCase;

/**
 * A tag without a changelog entry for its version publishes an empty release
 * note on WordPress.org, so the readme must have one before the deploy runs.
 */
class ReleaseTest extends WP_UnitTestCase {

  public function test_changelog_has_an_entry_for_the_released_version(): void {
    // readme.txt is checked out with CRLF on Windows.
    $readme = str_replace( "\r\n", "\n", (string) file_get_contents( dirname( WP_PARSI_ROOT ) . '/readme.txt' ) );

    $this->assertSame( 1, preg_match( '/^==\s*Changelog\s*==$/m', $readme, $section, PREG_OFFSET_CAPTURE ) );

    $this->assertSame(
      1,
      // "6.6" and "6.6.0" name the same release.
      preg_match( '/^=\s*' . preg_quote( WP_PARSI_VER, '/' ) . '(?:\.0)?\s*=$/m', substr( $readme, $section[0][1] ) ),
      'readme.txt has no changelog entry for ' . WP_PARSI_VER
    );
  }
}
