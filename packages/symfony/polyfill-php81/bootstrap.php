<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use WPParsidate\Dependencies\Symfony\Polyfill\Php81 as p;

if (\PHP_VERSION_ID >= 80100) {
    return;
}

if (defined('MYSQLI_REFRESH_SLAVE') && !defined('WPPARSIDATE_DEPENDENCIES_MYSQLI_REFRESH_REPLICA')) {
    define('WPPARSIDATE_DEPENDENCIES_MYSQLI_REFRESH_REPLICA', 64);
}

if (\extension_loaded('curl') && !defined('WPPARSIDATE_DEPENDENCIES_CURLOPT_ISSUERCERT_BLOB') && curl_version()['version_number'] >= 0x074700) {
    define('WPPARSIDATE_DEPENDENCIES_CURLOPT_ISSUERCERT_BLOB', 40295);
}

if (!function_exists('array_is_list')) {
    function array_is_list(array $array): bool { return p\Php81::array_is_list($array); }
}

if (!function_exists('enum_exists')) {
    function enum_exists(string $enum, bool $autoload = true): bool { return $autoload && class_exists($enum) && false; }
}
