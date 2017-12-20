<?php
/**
 * Replace wp-planet.ir to News and Events widget
 *
 * @author             Morteza Geransayeh
 * @package            WP-Parsidate
 * @subpackage         Core/General
 */

add_filter( 'dashboard_primary_link', 'wpp_dashboard_primary_link', 999, 1 );
add_filter( 'dashboard_primary_feed', 'wpp_dashboard_primary_feed', 999, 1 );
add_filter( 'dashboard_secondary_link', 'wpp_dashboard_secondary_link', 999, 1 );
add_filter( 'dashboard_secondary_feed', 'wpp_dashboard_secondary_feed', 999, 1 );

/**
 * Widget primary link
 *
 * @author          Morteza Geransayeh
 * @return          string
 */
function wpp_dashboard_primary_link(){
	return 'https://wp-parsi.com/';
}

/**
 * Widget primary feed
 *
 * @author          Morteza Geransayeh
 * @return          string
 */
function wpp_dashboard_primary_feed(){
	return 'https://wp-parsi.com/feed/';
}

/**
 * Widget secondary link
 *
 * @author          Morteza Geransayeh
 * @return          string
 */
function wpp_dashboard_secondary_link(){
	return 'http://wp-planet.ir/';
}

/**
 * Widget secondary feed
 *
 * @author          Morteza Geransayeh
 * @return          string
 */
function wpp_dashboard_secondary_feed(){
	return 'http://wp-planet.ir/feed';
}
