<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

/**
 * Replace wp-planet.ir to News and Events widget
 *
 * @author             Morteza Geransayeh
 * @package            WP-Parsidate
 * @subpackage         Core/General
 */

/**
 * Widget primary link
 *
 * @return          string
 * @author          Morteza Geransayeh
 */
function wpp_dashboard_primary_link() {
	return 'https://wp-parsi.com/';
}

add_filter( 'dashboard_primary_link', 'wpp_dashboard_primary_link', 999, 1 );

/**
 * Widget primary feed
 *
 * @return          string
 * @author          Morteza Geransayeh
 */
function wpp_dashboard_primary_feed() {
	return 'https://wp-parsi.com/feed/';
}

add_filter( 'dashboard_primary_feed', 'wpp_dashboard_primary_feed', 999, 1 );

/**
 * Widget secondary link
 *
 * @return          string
 * @author          Morteza Geransayeh
 */
function wpp_dashboard_secondary_link() {
	return 'http://wp-planet.ir/';
}

add_filter( 'dashboard_secondary_link', 'wpp_dashboard_secondary_link', 999, 1 );

/**
 * Widget secondary feed
 *
 * @return          string
 * @author          Morteza Geransayeh
 */
function wpp_dashboard_secondary_feed() {
	return 'http://wp-planet.ir/feed';
}

add_filter( 'dashboard_secondary_feed', 'wpp_dashboard_secondary_feed', 999, 1 );