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
	return 'https://wp-planet.ir/';
}

add_filter( 'dashboard_secondary_link', 'wpp_dashboard_secondary_link', 999, 1 );

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
			__( 'WP Parsidate' ),
			'wpp_dashboard_primary_widget_content',
			'dashboard',
			'normal',
			'high'
		);
	}

	add_action( 'wp_dashboard_setup', 'wpp_add_our_dashboard_primary_widget', 1 );
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
                <li>
					<a href="https://wp-parsidate.ir/donate" target="_blank"><span
                                class="dashicons dashicons-external"></span>&nbsp;<?php esc_html_e( 'Why are you showing me this?', 'wp-parsidate' ); ?>
                    </a>
				</li>
                <li>
					<a href="https://wp-parsidate.ir/sponser" target="_blank"><span
                                class="dashicons dashicons-external"></span>&nbsp;<?php esc_html_e( 'How can I become a sponsor?', 'wp-parsidate' ); ?>
                    </a>
				</li>
            </ul>
        </div>
        <div id="wpp_sponsorship_placeholder">
            <img src="<?php echo WP_PARSI_URL; ?>assets/images/icon.svg" alt="<?php esc_html_e( 'Loading Sponsors', 'wp-parsidate' ); ?>">
        </div>
        <div id="wpp_sponsorship" class="keen-slider">

        </div>
		<?php
		wpp_dashboard_events_news();
	}
}

if ( ! function_exists( 'wpp_dashboard_events_news' ) ) {
	/**
	 * Renders the Events and News dashboard widget.
	 *
	 * @since 4.8.0
	 */
	function wpp_dashboard_events_news() {
		?>

        <div class="wordpress-news hide-if-no-js">
			<?php wpp_dashboard_primary(); ?>
        </div>

        <p class="community-events-footer">
			<?php
			printf(
				'<a href="%1$s" target="_blank">%2$s <span class="screen-reader-text"> %3$s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a>',
				'https://wp-parsi.com/',
				__( 'WordPress Parsi' ),
				/* translators: Hidden accessibility text. */
				__( '(opens in a new tab)' )
			);
			?>

            |

			<?php
			printf(
				'<a href="%1$s" target="_blank">%2$s <span class="screen-reader-text"> %3$s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a>',
				'https://wp-parsi.com/about/',
				__( 'About' ),
				/* translators: Hidden accessibility text. */
				__( '(opens in a new tab)' )
			);
			?>

            |

			<?php
			printf(
				'<a href="%1$s" target="_blank">%2$s <span class="screen-reader-text"> %3$s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a>',
				/* translators: If a Rosetta site exists (e.g. https://es.wordpress.org/news/), then use that. Otherwise, leave untranslated. */
				esc_url( _x( 'https://wp-parsi.com/sponser/', 'Sponsership plans' ) ),
				__( 'Sponser' ),
				/* translators: Hidden accessibility text. */
				__( '(opens in a new tab)' )
			);
			?>
        </p>

		<?php
	}
}

if ( ! function_exists( 'wpp_dashboard_primary' ) ) {
	/**
	 * 'WordPress Events and News' dashboard widget.
	 *
	 * @since 2.7.0
	 * @since 4.8.0 Removed popular plugins feed.
	 */
	function wpp_dashboard_primary() {
		$feeds = array(
			'news'   => array(

				/**
				 * Filters the primary link URL for the 'WordPress Events and News' dashboard widget.
				 *
				 * @param string $link The widget's primary link URL.
				 *
				 * @since 2.5.0
				 *
				 */
				'link'         => 'https://wp-parsi.com/',

				/**
				 * Filters the primary feed URL for the 'WordPress Events and News' dashboard widget.
				 *
				 * @param string $url The widget's primary feed URL.
				 *
				 * @since 2.3.0
				 *
				 */
				'url'          => 'https://wp-parsi.com/parsidate/feed/',

				/**
				 * Filters the primary link title for the 'WordPress Events and News' dashboard widget.
				 *
				 * @param string $title Title attribute for the widget's primary link.
				 *
				 * @since 2.3.0
				 *
				 */
				'title'        => apply_filters( 'dashboard_primary_title', __( 'Parsidate' ) ),
				'items'        => 5,
				'show_summary' => 0,
				'show_author'  => 0,
				'show_date'    => 0,
			),
			//'planet' => array(

				/**
				 * Filters the secondary link URL for the 'WordPress Events and News' dashboard widget.
				 *
				 * @param string $link The widget's secondary link URL.
				 *
				 * @since 2.3.0
				 *
				 */
				//'link'         => __( 'https://wp-planet.ir/' ),

				/**
				 * Filters the secondary feed URL for the 'WordPress Events and News' dashboard widget.
				 *
				 * @param string $url The widget's secondary feed URL.
				 *
				 * @since 2.3.0
				 *
				 */
				//'url'          => __( 'https://wp-planet.ir/feed' ),

				/**
				 * Filters the secondary link title for the 'WordPress Events and News' dashboard widget.
				 *
				 * @param string $title Title attribute for the widget's secondary link.
				 *
				 * @since 2.3.0
				 *
				 */
				//'title'        => apply_filters( 'dashboard_secondary_title', __( 'Other WordPress News' ) ),

				/**
				 * Filters the number of secondary link items for the 'WordPress Events and News' dashboard widget.
				 *
				 * @param string $items How many items to show in the secondary feed.
				 *
				 * @since 4.4.0
				 *
				 */
			//	'items'        => apply_filters( 'dashboard_secondary_items', 3 ),
			//	'show_summary' => 0,
			//	'show_author'  => 0,
			//	'show_date'    => 0,
			//),
		);

		wp_dashboard_cached_rss_widget( 'wpp_dashboard_primary', 'wpp_dashboard_primary_output', $feeds );
	}
}

if ( ! function_exists( 'wpp_dashboard_primary_output' ) ) {
	/**
	 * Displays the WordPress events and news feeds.
	 *
	 * @param string $widget_id Widget ID.
	 * @param array $feeds Array of RSS feeds.
	 *
	 * @since 3.8.0
	 * @since 4.8.0 Removed popular plugins feed.
	 *
	 */
	function wpp_dashboard_primary_output( $widget_id, $feeds ) {
		foreach ( $feeds as $type => $args ) {
			$args['type'] = $type;
			echo '<div class="rss-widget">';
			wp_widget_rss_output( $args['url'], $args );
			echo '</div>';
		}
	}
}

if ( ! function_exists( 'wpp_ajax_dashboard_widgets' ) ) {
	/**
	 * Handles dashboard widgets via AJAX.
	 *
	 * @since 5.1.0
	 */
	function wpp_ajax_dashboard_widgets() {
		require_once ABSPATH . 'wp-admin/includes/dashboard.php';

		$pagenow = $_GET['pagenow'];
		if ( 'dashboard-user' === $pagenow || 'dashboard-network' === $pagenow || 'dashboard' === $pagenow ) {
			set_current_screen( $pagenow );
		}

		switch ( $_GET['widget'] ) {
			case 'wpp_dashboard_primary':
				wpp_dashboard_primary();
				break;
		}
		wp_die();
	}

	add_action( 'wp_ajax_wpp-dashboard-widgets', 'wpp_ajax_dashboard_widgets' );
}

if ( ! function_exists( 'get_mocked_sponsors' ) ) {
	/**
	 * Get mocked sponsors
	 *
	 * @return array
	 * @author Mohammad Zarei
	 * @sicne 5.1.3
	 *
	 */
	function get_mocked_sponsors() {
		$sponsors = array();
		$all_sponsors = array(
			array(
				'image_url' => WP_PARSI_URL . 'assets/images/sponsors/mediana.jpg',
				'image_alt' => __( 'Mediana', 'wp-parsidate' ),
				'link'      => 'https://app.mediana.ir/register?utm_source=parsi_date&utm_medium=banner&utm_campaign=plugin_referral',
				'end_date'  => '2025-11-21',
			),
			array(
				'image_url' => WP_PARSI_URL . 'assets/images/sponsors/seven.jpg',
				'image_alt' => __( 'Seven', 'wp-parsidate' ),
				'link'      => 'https://7ho.st/hosting/woocommerce?utm_source=wp-parsidate&utm_medium=banner&utm_campaign=sponsorship-parsidate',
				'end_date'  => '2025-11-21',
			),
		);
		$today = date( 'Y-m-d' );
		foreach ( $all_sponsors as $sponsor ) {
			if ( strtotime( $sponsor['end_date'] ) > strtotime( $today ) ) {
				$sponsors[] = $sponsor;
			}
		}

		return $sponsors;
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
		if ( $hook !== 'index.php' ) {
			return;
		}

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || wpp_is_active( 'dev_mode' ) ? '' : '.min';

		wp_enqueue_style( 'keen-slider', WP_PARSI_URL . "assets/css/keen-slider$suffix.css", false, '6.8.6' );
		wp_enqueue_style( 'wpp_dashboard', WP_PARSI_URL . "assets/css/dashboard$suffix.css", false, WP_PARSI_VER );
		wp_enqueue_script( 'keen-slider', WP_PARSI_URL . "assets/js/keen-slider.min.js", array(), '6.8.6', true );
		wp_enqueue_script( 'wpp_dashboard', WP_PARSI_URL . "assets/js/dashboard$suffix.js", array( 'jquery', 'keen-slider' ), WP_PARSI_VER, true );

		wp_localize_script( 'wpp_dashboard', 'sponsors', get_mocked_sponsors() );
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

		set_transient( 'wpp_sponsors_cache', $slides, DAY_IN_SECONDS );
		wp_send_json_success( json_decode( $slides, true ) );
	}

	add_action( 'wp_ajax_fetch_sponsorship_slides', 'wpp_fetch_sponsorship_slides_callback' );
}
