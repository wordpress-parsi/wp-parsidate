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
 * Copys files from plugin languages folder to global languages folder
 *
 * @since               1.0
 * @return              void
 */
function wpp_install() {
    
    /*if ( ! is_dir( WP_CONTENT_DIR . '/languages' ) )
        mkdir( WP_CONTENT_DIR . '/languages/' );

    $source         =   WP_PARSI_DIR . 'languages/*';
    $destination    =   WP_CONTENT_DIR . '/languages/';
    $files          =   glob( $source );

    foreach( $files as $file )
        @ copy( $file, $destination . basename( $file ) );*/

    update_option( 'wpp_settings', array() );
}