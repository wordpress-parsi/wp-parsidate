<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

/**
 * Plugin installer helper
 *
 * @author              Mobin Ghasempoor
 * @package             WP-Parsidate
 * @subpackage          Core/Install
 */

/**
 * Copys files from plugin languages folder to global languages folder
 *
 * @return              void
 * @since               1.0
 */
function wpp_install() {
	update_option( 'wpp_settings', array() );
}

register_activation_hook( WP_PARSI_ROOT, 'wpp_install' );
