<?php

/**
 * Plugin Name: WP-Parsidate
 * Version: 6.0
 * Plugin URI: https://wp-parsi.com/support/
 * Description: Persian package for WordPress, Adds full RTL and Shamsi (Jalali) support for: posts, comments, pages, archives, search, categories, permalinks and all admin sections and TinyMce editor, lists, quick editor. This package has Jalali archive widget.
 * Author: WP-Parsi Team
 * Author URI: https://wp-parsi.com/
 * Text Domain: wp-parsidate
 * Domain Path: /languages
 * Requires at least: 5.3
 * Requires PHP: 7.4
 * WC tested up to: 10.7.0
 * License: GPLv3
 *
 * WP-Parsidate is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * WP-Parsidate is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WP-Parsidate.
 *
 *
 * WordPress Parsi Package, Adds Persian language & Jalali date support to your blog
 *
 * Developers:
 *              Mobin Ghasempoor ( Developer & Founder )
 *              Morteza Geransayeh ( Developer & Founder )
 *              HamidReza Yazdani ( Developer )
 *              Saeed Fard ( Analyst & Developer )
 *              Parsa Kafi ( Developer )
 *
 * @author              Mobin Ghasempoor
 * @author              Morteza Geransayeh
 * @link                https://wp-parsi.com/
 * @version             5.1.6
 * @license             http://www.gnu.org/licenses/gpl-3.0.html GNU Public License v3.0
 * @package             WP-Parsidate
 * @subpackage          Core
 */

namespace WPParsidate;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Addons\Addons;
use WPParsidate\Admin\Admin;
use WPParsidate\App\App;
use WPParsidate\Core\Core;
use WPParsidate\Helper\WordPress;
use WPParsidate\Plugin\Install;
use WPParsidate\Plugin\Plugin;
use WPParsidate\Settings\Settings;
use WPParsidate\Widget\Widget;

final class WP_Parsidate {
  /**
   * @var WP_Parsidate|null Class instance
   */
  public static ?WP_Parsidate $instance = null;

  public function __construct() {
    $this->define();
    $this->include();
    $this->instance();
  }

  /**
   * Define constant
   *
   * @return void
   */
  private function define(): void {
    if ( ! defined( 'WP_PARSI_KEY' ) ) {
      define( 'WP_PARSI_KEY', 'wp_parsidate' );
    }

    if ( ! defined( 'WP_PARSI_KEY_SLUG' ) ) {
      define( 'WP_PARSI_KEY_SLUG', 'wp-parsidate' );
    }

    if ( ! defined( 'WP_PARSI_KEY_CAP' ) ) {
      define( 'WP_PARSI_KEY_CAP', 'WpParsiDate' );
    }

    if ( ! defined( 'WP_PARSI_ROOT' ) ) {
      define( 'WP_PARSI_ROOT', __FILE__ );
    }

    if ( ! defined( 'WP_PARSI_DIR' ) ) {
      define( 'WP_PARSI_DIR', plugin_dir_path( WP_PARSI_ROOT ) );
    }

    if ( ! defined( 'WP_PARSI_URL' ) ) {
      define( 'WP_PARSI_URL', plugin_dir_url( WP_PARSI_ROOT ) );
    }

    if ( ! defined( 'WP_PARSI_CLASS_PREFIX' ) ) {
      define( 'WP_PARSI_CLASS_PREFIX', 'wppd-' );
    }

    if ( ! defined( 'WP_PARSI_INPUT_PREFIX' ) ) {
      define( 'WP_PARSI_INPUT_PREFIX', 'wp_parsidate_' );
    }

    add_action( 'init', static function () {
      if ( ! function_exists( 'get_plugin_data' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
      }
      $pluginData = get_plugin_data( WP_PARSI_ROOT );
      define( 'WP_PARSI_VER', $pluginData['Version'] );
    }, 0 );
  }

  /**
   * Include required files
   *
   * @return void
   */
  private function include(): void {
    require_once __DIR__ . '/vendor/autoload.php';
  }

  /**
   * Instant classes
   *
   * @return void
   */
  private function instance(): void {
    if ( ! defined( 'WP_PARSI_DEBUG_MODE' ) ) {
      define( 'WP_PARSI_DEBUG_MODE', Settings::get( 'debug_mode', false ) );
    }

    new Admin();
    new Addons();

    if ( WordPress::isMultilingualActive() && Settings::get( 'multilingual_support', false ) ) {
      if (
        ( defined( 'ICL_LANGUAGE_CODE' ) && 'fa_IR' !== ICL_LANGUAGE_CODE ) ||
        ( function_exists( 'pll_current_language' ) && ( pll_current_language() !== false && pll_current_language() !== "fa" ) )
      ) {
        return;
      }
    }

    new Plugin();
    new App();
    new Core();
    new Widget();
  }

  /**
   * Returns an instance of WP_Parsidate class, makes instance if not exists
   *
   * @return          WP_Parsidate Instance of WP_Parsidate
   * @since           2.0
   */
  public static function getInstance(): ?self {
    if ( self::$instance === null ) {
      self::$instance = new WP_Parsidate();
    }

    return self::$instance;
  }
}

WP_Parsidate::getInstance();
register_activation_hook( __FILE__, array( Install::class, 'run' ) );
