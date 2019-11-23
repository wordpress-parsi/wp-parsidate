<?php
/**
 * Fix admin styles & TinyMCE editor
 *
 * @author              Morteza Geransayeh
 * @package             WP-Parsidate
 * @subpackage          Admin/Styles
 */

/**
 * Fixes themes and plugins RTL style, they should be LTR
 *
 * @return              void
 * @since               2.0
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
 * @return              void
 * @since               2.0
 */
function wpp_fix_tinymce_font()
{
    global $wpp_settings;

    add_editor_style(WP_PARSI_URL . 'assets/css/editor.css');
}

add_filter('init', 'wpp_fix_tinymce_font', 9);
