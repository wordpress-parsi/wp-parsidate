<?php
/**
 * Plugin Name: WP-Parsidate
 * Version: 2.3.4
 * Plugin URI: http://forum.wp-parsi.com/
 * Description: Persian package for WordPress, Adds full RTL and Shamsi (Jalali) support for: posts, comments, pages, archives, search, categories, permalinks and all admin sections and TinyMce editor, lists, quick editor. This package has Jalali archive widget.
 * Author: WP-Parsi Team
 * Author URI: http://wp-parsi.com/
 * Text Domain: wp-parsidate
 * Domain Path: parsi-languages
 *
 * WordPress Parsi Package, Adds Persian language & Jalali date support to your blog
 *
 * Developers:
 *              Mobin Ghasempoor ( Senior programmer & Founder )
 *              Morteza Geransayeh ( Senior programmer & Manager )
 *              Ehsaan ( Programmer )
 *              Farhan Nisi ( Programmer )
 *                Parsa Kafi ( Programmer )
 *                Saeed Fard ( Analyst )
 *
 * @author              Mobin Ghasempoor
 * @author              Morteza Geransayeh
 * @author              Farhan Nisi
 * @author              Ehsaan
 * @link                http://wp-parsi.com/
 * @version             2.2
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v2.0
 * @package             WP-Parsidate
 * @subpackage          Core
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // No direct access allowed

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
		add_action( 'wp_dashboard_setup', 'wpp_add_dashboard_widgets' );
	}

	/**
	 * Sets up constants for plugin
	 *
	 * @since           2.0
	 * @return          void
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
			define( 'WP_PARSI_VER', '2.2' );
		}
	}

	/**
	 * Sets up global variables
	 *
	 * @since           2.0
	 * @return          void
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
	 * @since          2.0
	 * @return         void
	 */
	public function include_files() {
		require_once( WP_PARSI_DIR . 'includes/settings.php' );
		global $wpp_settings;
		$wpp_settings = wp_parsi_get_settings();

		$files = array(
			'parsidate',
			'general',
			'fixes-archive',
			'fixes-permalinks',
			'fixes-dates',
			'fixes-misc',
			'admin/styles-fix',
			'admin/lists-fix',
			'admin/widgets',
			'fixes-calendar',
			'fixes-archives',
			'plugins/woocommerce',
			'plugins/edd',
			'widget/widget_archive',
			'widget/widget_calendar'
		);

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
	 * @since           2.0
	 * @return          WP_Parsidate Instance of WP_Parsidate
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
	 * @param           array $links
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
	 * @since           2.0
	 * @return          boolean
	 */
	public function register_widget() {
		register_widget( 'parsidate_archive' );
		register_widget( 'parsidate_calendar' );

		return true;
	}
}

return WP_Parsidate::get_instance();
