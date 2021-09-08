<?php

defined( 'ABSPATH' ) || exit( 'No direct script access allowed' );

global $wpp_settings, $dis_hook;

if ( isset( $wpp_settings['dis_input'] ) ) {
	$dis_hook = array();
	$lists    = explode( "\n", $wpp_settings['dis_input'] );

	foreach ( $lists as $list ) {
		$list = explode( ',', $list );

		if ( count( $list ) < 2 ) {
			continue;
		}

		$dis_hook[ $list[0] ][] = array( 'func' => $list[1], 'class' => ( isset( $list[2] ) ? $list[2] : '' ) );
	}
}

/**
 * @return bool
 */
function disable_wpp() {
	global $dis_hook;

	if ( wpp_is_feed() ) {
		return false;
	}

	$calls = debug_backtrace();
	unset( $calls[0], $calls[1], $calls[2] );

	foreach ( $calls as $i => $call ) {
		unset( $calls[ $i ] );

		if ( $call['function'] == 'apply_filters' && empty( $call['class'] ) ) {
			break;
		}
	}

	$func = $calls[ ++ $i ]['function'];

	if ( empty( $dis_hook[ $func ] ) ) {
		return true;
	}

	$hooks = $dis_hook[ $func ];

	if ( empty( $hooks ) ) {
		return true;
	}

	unset( $calls[ $i ] );

	foreach ( $calls as $i => $call ) {
		foreach ( $hooks as $hook ) {
			$hook['class'] = trim( $hook['class'] );

			if ( ( isset( $call['class'] ) && empty( $hook['class'] ) ) ||( ! isset( $call['class'] ) && ! empty( $hook['class'] ) ) ) {
				continue;
			}

			if ( ! empty( $hook['func'] ) && ( $call['function'] != trim( $hook['func'] ) ) ) {
				continue;
			}

			if ( ( ! isset( $call['class'] ) && empty( $hook['class'] ) ) ||$call['class'] == $hook['class'] ) {
				return false;
			}
		}
	}

	return true;
}


/**
 * @param $report_data
 *
 * @return mixed
 */
function wpp_woocommerce_admin_report_data( $report_data ) {
	$report_data['where'] = preg_replace_callback( "/posts.post_date\s.=?\s'([^']+)'/i", 'fix_date_woo_report', $report_data['where'] );

	return $report_data;
}

/**
 * @param $date
 *
 * @return array|mixed|string|string[]
 */
function fix_date_woo_report( $date ) {
	if ( empty( $_GET['start_date'] ) ||empty( $_GET['end_date'] ) ) {
		return $date[0];
	}

	if ( strpos( $date[0], '=' ) === false ) {
		if ( (int) $_GET['end_date'] > 1900 ) {
			return $date[0];
		}

		$dt = gregdate( 'Y-m-d', $_GET['end_date'] );
		$dt = date( 'Y-m-d', strtotime( "$dt +1 day" ) );
	} else {
		if ( (int) $_GET['start_date'] > 1900 ) {
			return $date[0];
		}

		$dt = gregdate( 'Y-m-d', $_GET['start_date'] );
	}

	return substr_replace( $date[0], $dt, - 20, 10 );
}

// add the filter
add_filter( 'woocommerce_reports_get_order_report_query', 'wpp_woocommerce_admin_report_data', 10, 1 );


/**
 * Makes EDD compatible with WP-Parsidate plugin
 *
 * @package                 WP-Parsidate
 * @subpackage              Plugins/EDD
 * @author                  Ehsaan
 */
class WPP_Disable {
	public static $instance = null;

	/**
	 * Hooks required tags
	 */
	private function __construct() {
		add_filter( 'wpp_plugins_compatibility_settings', array( $this, 'add_settings' ) );

		if ( ! wpp_is_active( 'dis_prices' )  ) {
			add_filter( 'dis_rial_currency_filter_after', 'per_number', 10, 2 );
		}

		if ( ! wpp_is_active( 'dis_rial_fix' )  ) {
			add_filter( 'dis_rial_currency_filter_after', array( $this, 'rial_fix' ), 10, 2 );
		}
	}

	/**
	 * Returns an instance of class
	 *
	 * @return          WPP_Disable
	 */
	public static function getInstance() {
		if ( self::$instance == null ) {
			self::$instance = new WPP_Disable();
		}

		return self::$instance;
	}

	/**
	 * RIAL fix for EDD
	 *
	 * @param  integer|string  $price  Price Number
	 * @param  $did
	 *
	 * @return string
	 */
	public function rial_fix( $price, $did ) {
		return str_replace( 'RIAL', 'ریال', $price );
	}

	/**
	 * Adds settings for toggle fixing
	 *
	 * @param array $old_settings Old settings
	 *
	 * @return          array New settings
	 */
	public function add_settings( $old_settings ) {
		$options  = array(
			'enable'  => __( 'Enable', 'wp-parsidate' ),
			'disable' => __( 'Disable', 'wp-parsidate' )
		);

		$settings = array(
			'dis'        => array(
				'id'   => 'dis',
				'name' => __( 'Hook deactivator', 'wp-parsidate' ),
				'type' => 'header'
			),
			'dis_prices' => array(
				'id'      => 'dis_input',
				'name'    => __( 'Hook list', 'wp-parsidate' ),
				'type'    => 'textarea',
				'options' => $options,
				'std'     => '',
				'desc'    => __( 'Enter hook,class,function to remove parsidate filter from it', 'wp-parsidate' )
			)
		);

		return array_merge( $old_settings, $settings );
	}
}

return WPP_Disable::getInstance();
