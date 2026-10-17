<?php
/**
 * PHPUnit bootstrap file.
 *
 * Boots the WordPress test suite (installed by bin/install-wp-tests.sh) and
 * loads WP-Parsidate as a regular plugin so tests run against real WordPress
 * functions instead of mocks.
 *
 * @package WP-Parsidate
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
  echo "Could not find {$_tests_dir}/includes/functions.php. Have you run bin/install-wp-tests.sh?" . PHP_EOL;
  exit( 1 );
}

// Forward the PHPUnit Polyfills path when provided (e.g. by setup-php in CI).
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
  define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

require_once "{$_tests_dir}/includes/functions.php";

/**
 * Load the plugin being tested.
 */
function _wp_parsidate_manually_load_plugin(): void {
  require dirname( __DIR__ ) . '/wp-parsidate.php';
}

tests_add_filter( 'muplugins_loaded', '_wp_parsidate_manually_load_plugin' );

require "{$_tests_dir}/includes/bootstrap.php";
