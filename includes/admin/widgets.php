<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

/**
 * Replace wp-planet.ir to News and Events widget
 *
 * @author             Morteza Geransayeh
 * @author             HamidReza Yazdani
 * @package            WP-Parsidate
 * @subpackage         Core/General
 */

if ( ! function_exists( 'wpp_dashboard_primary_link' ) ) {
	/**
	 * Widget primary link
	 *
	 * @return          string
	 * @author          Morteza Geransayeh
	 * @author          HamidReza Yazdani
	 */
	function wpp_dashboard_primary_link() {
		return 'https://wp-parsi.com/';
	}

	add_filter( 'dashboard_primary_link', 'wpp_dashboard_primary_link', 999, 1 );
}

if ( ! function_exists( 'wpp_dashboard_primary_feed' ) ) {
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
}

if ( ! function_exists( 'wpp_dashboard_secondary_link' ) ) {
	/**
	 * Widget secondary link
	 *
	 * @return          string
	 * @author          Morteza Geransayeh
	 */
	function wpp_dashboard_secondary_link() {
		return 'https://wp-planet.ir/';
	}

	add_filter( 'dashboard_secondary_link', 'wpp_dashboard_secondary_link', 999, 1 );
}

if ( ! function_exists( 'wpp_dashboard_secondary_feed' ) ) {
	/**
	 * Widget secondary feed
	 *
	 * @return          string
	 * @author          Morteza Geransayeh
	 */
	function wpp_dashboard_secondary_feed() {
		return 'https://wp-planet.ir/feed';
	}

	add_filter( 'dashboard_secondary_feed', 'wpp_dashboard_secondary_feed', 999, 1 );
}

if ( ! function_exists( 'wpp_remove_wp_dashboard_events_news' ) ) {
	/**
	 * Remove the default WP events and news widget
	 *
	 * @return void
	 * @author HamidReza Yazdani
	 * @sicne 5.1.0
	 *
	 */
	function wpp_remove_wp_dashboard_events_news() {
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
	}

	add_action( 'wp_dashboard_setup', 'wpp_remove_wp_dashboard_events_news' );
}

if ( ! function_exists( 'wpp_add_our_dashboard_primary_widget' ) ) {
	/**
	 * Add Parsi-Date events and news widget
	 *
	 * @return void
	 * @author HamidReza Yazdani
	 * @sicne 5.1.0
	 *
	 */
	function wpp_add_our_dashboard_primary_widget() {
		add_meta_box(
			'wpp_dashboard_primary',
			__( 'WordPress Events and News' ),
			'wpp_dashboard_primary_widget_content',
            'dashboard',
            'normal',
            'high',
		);
	}

	add_action( 'admin_init', 'wpp_add_our_dashboard_primary_widget',1 );
}

if ( ! function_exists( 'wpp_dashboard_primary_widget_content' ) ) {
	/**
	 * Put our content into the new widget
	 *
	 * @return void
	 * @author HamidReza Yazdani
	 * @sicne 5.1.0
	 *
	 */
	function wpp_dashboard_primary_widget_content() {
		?>
        <div id="sponsorship-guide">
            <div class="question">
                <span class="dashicons dashicons-info-outline"></span>
                <span><?php esc_html_e( 'What is this?', 'wp-parsidate' ); ?></span>
            </div>
            <ul>
                <li><a href="https://wp-parsi.com/donate/" target="_blank"><span
                                class="dashicons dashicons-external"></span>&nbsp;<?php esc_html_e( 'Why are you showing me this?', 'wp-parsidate' ); ?>
                    </a></li>
                <li><a href="https://wp-parsi.com/sponser/" target="_blank"><span
                                class="dashicons dashicons-external"></span>&nbsp;<?php esc_html_e( 'How can I become a sponsor?', 'wp-parsidate' ); ?>
                    </a></li>
            </ul>
        </div>
        <div id="wpp_sponsorship_placeholder">
            <img src="<?php echo WP_PARSI_URL; ?>assets/images/icon.svg" alt="<?php esc_html_e( 'Loading Sponsors', 'wp-parsidate' ); ?>">
        </div>
        <div id="wpp_sponsorship" class="keen-slider">

        </div>
		<?php
		wp_dashboard_events_news();
	}
}

if ( ! function_exists( 'wpp_enqueue_admin_dashboard_assets' ) ) {
	/**
	 * Enqueue our assets to WP admin dashboard
	 *
	 * @param $hook
	 *
	 * @return void
	 * @author HamidReza Yazdani
	 * @sicne 5.1.0
	 *
	 */
	function wpp_enqueue_admin_dashboard_assets( $hook ) {
		// Check if we are on the admin dashboard page
		if ( $hook != 'index.php' ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || wpp_is_active( 'dev_mode' ) ? '' : '.min';

		wp_enqueue_style( 'keen-slider', WP_PARSI_URL . "assets/css/keen-slider$suffix.css", false, '1.0.0' );
		wp_enqueue_style( 'wpp_dashboard', WP_PARSI_URL . "assets/css/dashboard$suffix.css", false, '1.0.0' );
		wp_enqueue_script( 'keen-slider', WP_PARSI_URL . "assets/js/keen-slider.js", array(), '1.6.0', true );
		wp_enqueue_script( 'wpp_dashboard', WP_PARSI_URL . "assets/js/dashboard$suffix.js", array( 'jquery', 'keen-slider' ), '1.0.0', true );
	}

	add_action( 'admin_enqueue_scripts', 'wpp_enqueue_admin_dashboard_assets' );
}

if ( ! function_exists( 'wpp_fetch_sponsorship_slides_callback' ) ) {
	/**
	 * Fetch the sponsors banners
	 *
	 * @sicne 5.1.0
	 * @return void
	 */
	function wpp_fetch_sponsorship_slides_callback() {
		$sponsors_cache = get_transient( 'wpp_sponsors_cache' );

		if ( $sponsors_cache ) {
			wp_send_json_success( json_decode( $sponsors_cache, true ) );
		}

		$response = wp_remote_get( 'https://wp-parsi.com/wp-json/sponsorship/v1/sponsors/' );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( 'Error fetching slides' );
		}

		$slides = wp_remote_retrieve_body( $response );

		set_transient( 'wpp_sponsors_cache', $slides, WEEK_IN_SECONDS );
		wp_send_json_success( json_decode( $slides, true ) );
	}

	add_action( 'wp_ajax_fetch_sponsorship_slides', 'wpp_fetch_sponsorship_slides_callback' );
}

if ( ! function_exists( 'wpp_force_events_and_news_widget_to_top' ) ) {
	/**
	 * Force the widget to the top
	 *
	 * @sicne 5.1.0
	 */
	function wpp_force_events_and_news_widget_to_top() {
		global $wp_meta_boxes;

		$dashboard  = $wp_meta_boxes['dashboard']['normal']['core'];
		$wpp_widget = array( 'wpp_dashboard_primary' => $dashboard['wpp_dashboard_primary'] );

		unset( $dashboard['wpp_dashboard_primary'] );
/*
		$sorted_dashboard                             = array_merge( $wpp_widget, $dashboard );
		$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;*/
	}

	//add_action( 'wp_dashboard_setup', 'wpp_force_events_and_news_widget_to_top', PHP_INT_MAX );
}
