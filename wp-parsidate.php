<?php
defined( 'ABSPATH' ) || exit( 'No direct script access allowed' );

/**
 * Plugin Name: WP-Parsidate
 * Version: 5.1.8
 * Plugin URI: https://wp-parsi.com/support/
 * Description: Persian package for WordPress, Adds full RTL and Shamsi (Jalali) support for: posts, comments, pages, archives, search, categories, permalinks and all admin sections and TinyMce editor, lists, quick editor. This package has Jalali archive widget.
 * Author: WP-Parsi Team
 * Author URI: https://wp-parsi.com/
 * Text Domain: wp-parsidate
 * Domain Path: /languages
 * Requires at least: 5.3
 * Requires PHP: 7.4
 * WC tested up to: 9.8.5
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

/**
 * WP Parsidate main class
 */
final class WP_Parsidate {
	/**
	 * @var WP_Parsidate Class instance
	 */
	public static $instance = null;

	private function __construct() {
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		$this->define_const();
		$this->include_files();

		require_once( WP_PARSI_DIR . 'includes/settings.php' );

		if ( self::wpp_multilingual_is_active() && wpp_is_active( 'wpp_multilingual_support' ) ) {
			if ( ( defined( 'ICL_LANGUAGE_CODE' ) && 'fa_IR' !== ICL_LANGUAGE_CODE ) ||
			     ( function_exists( 'pll_current_language' ) && pll_current_language() !== 'fa' ) ) {
				return;
			}
		}

		WPP_ParsiDate::getInstance();

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'parsi_settings_link' ) );
		add_action( 'widgets_init', array( $this, 'register_widget' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'wpp_load_vazir_font_in_admin_area' ) );
		add_action( 'wpp_jalali_datepicker_enqueued', array( $this, 'wpp_localize_months_name' ) );

        do_action('wpp_init');
	}

	/**
	 * Sets up constants for plugin
	 *
	 * @return          void
	 * @since           2.0
	 */
	private function define_const() {
		if ( ! defined( 'WP_PARSI_ROOT' ) ) {
			define( 'WP_PARSI_ROOT', __FILE__ );
		}

		if ( ! defined( 'WP_PARSI_DIR' ) ) {
			define( 'WP_PARSI_DIR', plugin_dir_path( WP_PARSI_ROOT ) );
		}

		if ( ! defined( 'WP_PARSI_URL' ) ) {
			define( 'WP_PARSI_URL', plugin_dir_url( WP_PARSI_ROOT ) );
		}

		if ( ! defined( 'WP_PARSI_VER' ) ) {
			define( 'WP_PARSI_VER', '5.1.8' );
    }
	}

	/**
	 * Includes files for plugin
	 *
	 * @return         void
	 * @since          2.0
	 */
	public function include_files() {
		require_once( WP_PARSI_DIR . 'includes/settings.php' );

		global $wpp_settings;

		$wpp_settings = wp_parsi_get_settings();
		$files        = array(
			'parsidate',
			'general',
			'tools',
			'fixes-archive',
			'fixes-permalinks',
			'fixes-dates',
			'fixes-misc',
			'admin/styles-fix',
			'admin/gutenberg-jalali-calendar',
			'admin/lists-fix',
			'admin/widgets',
			'fixes-calendar',
			'fixes-archives',
			'widget/widget_archive',
			'widget/widget_calendar'
		);

		if ( class_exists( 'WooCommerce' ) ) {
			$files[] = 'plugins/woocommerce';
		}

		if ( class_exists( 'Easy_Digital_Downloads' ) ) {
			$files[] = 'plugins/edd';
		}

		if ( class_exists( 'ACF' ) ) {
			$files[] = 'plugins/acf';
		}

		if ( class_exists( '\Elementor\Core\Editor\Editor' ) ) {
			$files[] = 'plugins/elementor';
		}

        if ( class_exists( '\RankMath' ) ) {
            $files[] = 'plugins/rank-math';
        }

		$files[] = 'plugins/disable';

		foreach ( $files as $file ) {
			require_once( WP_PARSI_DIR . 'includes/' . $file . '.php' );
		}
	}

	public function load_plugin_textdomain() {
		if ( get_locale() === 'fa_IR' ) {
			load_textdomain( 'wp-parsidate', WP_PARSI_DIR . 'languages/fa_IR.mo' );
		}
	}

	/**
	 * Localize name of months after date picker enqueued
	 *
	 * @since 4.0.1
	 */
	public function wpp_localize_months_name() {
		global $wpp_months_name;

		$months_name = $wpp_months_name;

		// Remove first item (null string) from name of months array
		array_shift( $months_name );

		wp_localize_script( 'wpp_jalali_datepicker', 'WPP_I18N',
			array(
				'months' => $months_name
			)
		);
	}

	/**
	 * Returns an instance of WP_Parsidate class, makes instance if not exists
	 *
	 * @return          WP_Parsidate Instance of WP_Parsidate
	 * @since           2.0
	 */
	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new WP_Parsidate();
		}

		return self::$instance;
	}

	/**
	 * Add Setting Link To Install Plugin
	 *
	 * @param array $links
	 *
	 * @return          array
	 */
	public static function parsi_settings_link( $links ) {
		$settings_link = array( '<a href="' . menu_page_url( 'wp-parsi-settings', false ) . '">' . __( 'settings', 'wp-parsidate' ) . '</a>' );

		return array_merge( $links, $settings_link );
	}

	/**
	 * Register Plugin Widgets
	 *
	 * @return          boolean
	 * @since           2.0
	 */
	public function register_widget() {
		register_widget( 'parsidate_archive' );
		register_widget( 'parsidate_calendar' );

		return true;
	}

	/**
	 * Load Vazir font in admin area
	 *
	 * @since           4.0.0
	 */
	public function wpp_load_vazir_font_in_admin_area() {
		if ( get_locale() !== 'fa_IR' ) {
			return;
		}

		if ( wpp_is_active( 'enable_fonts' ) ) {
			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || wpp_is_active( 'dev_mode' ) ? '' : '.min';

			wp_enqueue_style( 'wpp-vazir-font', WP_PARSI_URL . "assets/css/vazir-font$suffix.css", null, WP_PARSI_VER, 'all' );
			add_action( 'admin_head', array( $this, 'wpp_preload_vazir_fonts' ) );
		}
	}

	/**
	 * Preload vazir font to achieve to high performance
	 *
	 * @since           4.0.0
	 */
	public function wpp_preload_vazir_fonts() {
		echo '<link rel="preload" href="' . WP_PARSI_URL . 'assets/fonts/Vazirmatn-Regular.woff2" as="font" type="font/woff2" crossorigin>' . PHP_EOL .
		     '<link rel="preload" href="' . WP_PARSI_URL . 'assets/fonts/Vazirmatn-Bold.woff2" as="font" type="font/woff2" crossorigin>' . PHP_EOL;
	}

	/**
	 * Check the given plugin is installed and activated
	 *
	 * Since 5.0.1
	 */
	public static function is_plugin_activated( $plugin_file ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $plugin_file );
	}

	/**
	 * Checks WPML or PolyLang plugins is active
	 *
	 * Since 4.0.1
	 */
	public static function wpp_multilingual_is_active() {
		$polylang_activated  = self::is_plugin_activated( 'polylang/polylang.php' );
		$sitepress_activated = self::is_plugin_activated( 'sitepress-multilingual-cms/sitepress.php' );

		return $polylang_activated || $sitepress_activated;
	}
}

return WP_Parsidate::get_instance();
