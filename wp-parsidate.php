<?php
defined( 'ABSPATH' ) || exit( 'No direct script access allowed' );

/**
 * Plugin Name: WP-Parsidate
 * Version: 4.0.1
 * Plugin URI: https://wp-parsi.com/support/
 * Description: Persian package for WordPress, Adds full RTL and Shamsi (Jalali) support for: posts, comments, pages, archives, search, categories, permalinks and all admin sections and TinyMce editor, lists, quick editor. This package has Jalali archive widget.
 * Author: WP-Parsi Team
 * Author URI: https://wp-parsi.com/
 * Text Domain: wp-parsidate
 * Domain Path: parsi-languages
 * License: GPL3
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
 *              Mobin Ghasempoor ( Senior programmer & Founder )
 *              Morteza Geransayeh ( Senior programmer & Manager )
 *              HamidReza Yazdani ( Programmer )
 *              Parsa Kafi ( Programmer )
 *              Alireza Dabiri Nejad ( Programmer )
 *              Ehsaan ( Programmer )
 *              Saeed Fard ( Analyst & Contributor )
 *              Farhan Nisi ( Contributor )
 *              Mostafa Soufi ( Contributor )
 *              Ali Aghdam ( Contributor )
 *              Kamran Khorsandi ( Contributor )
 *              Mehrshad Darzi ( Contributor )
 *              Nasser Rafie ( Contributor )
 *              Milad Nazari ( Contributor )
 *
 * @author              Mobin Ghasempoor
 * @author              Morteza Geransayeh
 * @link                https://wp-parsi.com/
 * @version             4.0.0
 * @license             http://www.gnu.org/licenses/gpl-3.0.html GNU Public License v3.0
 * @package             WP-Parsidate
 * @subpackage          Core
 */

/**
 *
 */
final class WP_Parsidate {
	/**
	 * @var WP_Parsidate Class instance
	 */
	public static $instance = null;

	private function __construct() {
		$this->define_const();
		$this->setup_vars();
		$this->include_files();
		
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'parsi_settings_link' ) );
		add_action( 'widgets_init', array( $this, 'register_widget' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'wpp_load_vazir_font_in_admin_area' ) );
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
			define( 'WP_PARSI_VER', '4.0.0' );
		}
	}

	/**
	 * Sets up global variables
	 *
	 * @return          void
	 * @since           2.0
	 */
	private function setup_vars() {
		global $persian_month_names;
		$persian_month_names = array(
			'',
			'فروردین',
			'اردیبهشت',
			'خرداد',
			'تیر',
			'مرداد',
			'شهریور',
			'مهر',
			'آبان',
			'آذر',
			'دی',
			'بهمن',
			'اسفند'
		);
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

		$files[] = 'plugins/disable';

		foreach ( $files as $file ) {
			require_once( WP_PARSI_DIR . 'includes/' . $file . '.php' );
		}

		if ( get_locale() == 'fa_IR' ) {
			load_textdomain( 'wp-parsidate', WP_PARSI_DIR . 'languages/fa_IR.mo' );
		}
	}

	/**
	 * Returns an instance of WP_Parsidate class, makes instance if not exists
	 *
	 * @return          WP_Parsidate Instance of WP_Parsidate
	 * @since           2.0
	 */
	public static function get_instance() {
		if ( self::$instance == null ) {
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
	 * Load vazir font in admin area
	 *
	 * @since           4.0.0
	 */
	public function wpp_load_vazir_font_in_admin_area() {
		if ( get_locale() !== 'fa_IR' ) {
			return;
		}

		if( wpp_is_active( 'enable_fonts' ) ){
			
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || wpp_is_active( 'dev_mode' ) ? '' : '.min';

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
		echo '<link rel="preload" href="' . WP_PARSI_URL . 'assets/fonts/Vazir-Regular.woff2" as="font" type="font/woff2" crossorigin>' . PHP_EOL .
		     '<link rel="preload" href="' . WP_PARSI_URL . 'assets/fonts/Vazir-Bold.woff2" as="font" type="font/woff2" crossorigin>' . PHP_EOL;
	}
}

return WP_Parsidate::get_instance();
