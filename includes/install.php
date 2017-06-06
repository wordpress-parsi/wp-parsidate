<?php
/**
 * Plugin installer helper
 *
 * @author              Mobin Ghasempoor
 * @package             WP-Parsidate
 * @subpackage          Core/Install
 */

register_activation_hook(WP_PARSI_ROOT, 'wpp_install');

/**
 * Copys files from plugin languages folder to global languages folder
 *
 * @since               1.0
 * @return              void
 */
function wpp_install()
{
	update_option('wpp_settings', array());
}