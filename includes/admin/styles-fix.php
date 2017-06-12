<?php
/**
 * Fix admin styles & TinyMCE editor
 *
 * @author              Mobin Ghasempoor
 * @package             WP-Parsidate
 * @subpackage          Admin/Styles
 */

/**
 * Fixes themes and plugins RTL style, they should be LTR
 *
 * @since               2.0
 * @return              void
 */
function wpp_fix_editor_rtl()
{
	wp_enqueue_style('functions', WP_PARSI_URL . 'assets/css/admin-fix.css', false, WP_PARSI_VER, 'all');
}

add_action('admin_print_styles-plugin-editor.php', 'wpp_fix_editor_rtl', 10);
add_action('admin_print_styles-theme-editor.php', 'wpp_fix_editor_rtl', 10);

/**
 * Fixes TinyMCE font
 *
 * @since               2.0
 * @return              void
 */
function wpp_fix_tinymce_font()
{
	global $wpp_settings;

	add_editor_style(WP_PARSI_URL . 'assets/css/editor.css');
	if (isset($wpp_settings['droidsans_editor']) && $wpp_settings['droidsans_editor'] != 'disable') {
		add_editor_style(WP_PARSI_URL . 'assets/css/editor-font.css');
	}
}

add_filter('init', 'wpp_fix_tinymce_font', 9);

/**
 * Style for whole Admin side
 *
 * @since                2.1.5
 * @return                void
 */
function wpp_enqueue_admin()
{
	global $wpp_settings;

	if (!isset($wpp_settings['droidsans_admin']) || $wpp_settings['droidsans_admin'] != 'disable') {
		wp_enqueue_style('wp-parsi-fonts', WP_PARSI_URL . 'assets/css/admin-fonts.css', false, WP_PARSI_VER, 'all');
		wp_enqueue_style('wp-parsi-admin', WP_PARSI_URL . 'assets/css/admin-styles.css', false, WP_PARSI_VER, 'all');
	}
}

add_action('admin_enqueue_scripts', 'wpp_enqueue_admin');