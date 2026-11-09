<?php
/**
 * Local PHPUnit runner.
 *
 * PHPUnit is installed in the plugin's vendor directory. The plugin's Composer
 * autoloader loads inc/Functions/Core.php and inc/Functions/Old.php, which
 * guard against direct access with `defined( 'ABSPATH' ) || exit;`. Defining
 * ABSPATH before PHPUnit boots keeps those files from terminating the process.
 *
 * The WordPress test suite config (wp-tests-config.php) defines ABSPATH
 * conditionally, so it reuses the value set here. The path is derived from the
 * plugin location, which must be:
 *   <wordpress-root>/wp-content/plugins/wp-parsidate/bin/phpunit.php
 *
 * Usage:
 *   php bin/phpunit.php --testdox
 *   composer test
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', rtrim( dirname( __DIR__, 4 ), '/\\' ) . '/' );
}

require dirname( __DIR__ ) . '/vendor/bin/phpunit';
