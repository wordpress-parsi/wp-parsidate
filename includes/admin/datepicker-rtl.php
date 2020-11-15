<?php
/**
 * RTL jQuery Datepicker
 *
 * @author              Morteza Geransayeh
 * @package             WP-Parsidate
 * @subpackage          Admin/jQuery
 */

/**
 * Fixes jQuery Datepicker RTL style and code
 *
 * @return              void
 * @since               3.0
 */

function wpp_enqueue_datepicker_css() {
	wp_enqueue_style( 'wp-parsi-datepicker', WP_PARSI_URL . 'assets/css/jquery-ui.css', false, WP_PARSI_VER, 'all' );
}

function wpp_enqueue_datepicker_scripts() {
	wp_register_script( 'wpp_ui_datepicker', WP_PARSI_URL . 'assets/js/jquery-ui.js', false, WP_PARSI_VER );
	wp_register_script( 'wpp_datepicker_fa', WP_PARSI_URL . 'assets/js/datepicker.js', false, WP_PARSI_VER );
	wp_enqueue_script( 'wpp_ui_datepicker' );
	wp_enqueue_script( 'wpp_datepicker_fa' );
}

add_action( 'admin_enqueue_scripts', 'wpp_enqueue_datepicker_css' );
add_action( 'admin_enqueue_scripts', 'wpp_enqueue_datepicker_scripts', 9999 );