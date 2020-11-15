<?php
/**
 * Plugin installer helper
 *
 * @author              Mobin Ghasempoor
 * @package             WP-Parsidate
 * @subpackage          Core/Install
 */

register_activation_hook( WP_PARSI_ROOT, 'wpp_install' );

/**
 * Setup initial settings for plugin
 *
 * @return              void
 * @since               1.0
 */
function wpp_install() {
	update_option( 'wpp_settings', array() );
}