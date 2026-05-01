<?php
/**
 * Dashboard Widget
 *
 * Add plugin dashboard widget to WP
 */

namespace WPParsidate\Widget;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Helper\{Assets, Cache, Param};

class DashboardWidget {
  public function __construct() {
    add_filter( 'dashboard_primary_link', [ $this, 'changeDashboardLink' ], 999 );
    add_filter( 'dashboard_primary_feed', [ $this, 'changeDashboardFeedUrl' ], 999 );
    add_filter( 'dashboard_secondary_link', [ $this, 'changeDashboardSecondaryLink' ], 999 );
    add_filter( 'dashboard_secondary_feed', [ $this, 'changeDashboardSecondaryFeedUrl' ], 999 );
    add_action( 'wp_dashboard_setup', [ $this, 'addDashboardWidget' ], 1 );
    add_action( 'wp_ajax_wpp-dashboard-widgets', [ $this, 'ajaxDashboardWidgets' ] );
    //@TODO Change ajax action to unique name
    add_action( 'wp_ajax_fetch_sponsorship_slides', [ $this, 'fetchSponsorshipSlides' ] );
    add_action( 'admin_enqueue_scripts', [ $this, 'enqueueScripts' ] );
  }

  /**
   * Fetch the sponsors banners
   *
   * @sicne 5.1.0
   * @return void
   */
  public function fetchSponsorshipSlides(): void {
    $sponsors_cache = Cache::get( 'sponsors_dashboard' );

    if ( $sponsors_cache ) {
      wp_send_json_success( json_decode( $sponsors_cache, true ) );
    }

    $response = wp_remote_get( 'https://wp-parsi.com/wp-json/sponsorship/v1/sponsors/' );

    if ( is_wp_error( $response ) ) {
      wp_send_json_error( 'Error fetching slides' );
    }

    $slides = wp_remote_retrieve_body( $response );

    Cache::set( 'sponsors_dashboard', $slides, DAY_IN_SECONDS );
    wp_send_json_success( json_decode( $slides, true ) );
  }

  /**
   * Handles dashboard widgets via AJAX.
   *
   * @since 5.1.0
   */
  public function ajaxDashboardWidgets(): void {
    require_once ABSPATH . 'wp-admin/includes/dashboard.php';

    $pagenow = Param::get( 'pagenow' );
    if ( 'dashboard-user' === $pagenow || 'dashboard-network' === $pagenow || 'dashboard' === $pagenow ) {
      set_current_screen( $pagenow );
    }

    switch ( Param::get( 'widget' ) ) {
      case 'wpp_dashboard_primary':
        $this->printDashboardWidgetPrimary();
        break;
    }
    wp_die();
  }

  /**
   * Add Parsi-Date events and news widget
   *
   * @return void
   * @author HamidReza Yazdani
   * @sicne 5.1.0
   */
  public function addDashboardWidget(): void {
    add_meta_box(
      'wpp_dashboard_primary',
      esc_html__( 'WP Parsi', 'wp-parsidate' ),
      [ $this, 'dashboardWidgetContent' ],
      'dashboard',
      'normal',
      'high'
    );
  }

  /**
   * Put our content into the new widget
   *
   * @return void
   * @author HamidReza Yazdani
   * @sicne 5.1.0
   *
   */
  public function dashboardWidgetContent(): void {
    ?>
    <div id="sponsorship-guide">
      <div class="question">
        <span class="dashicons dashicons-info-outline"></span>
        <span><?php esc_html_e( 'What is this?', 'wp-parsidate' ); ?></span>
      </div>
      <ul>
        <li>
          <a href="https://wp-parsidate.ir/donate" target="_blank">
            <span class="dashicons dashicons-external"></span>
            <?php esc_html_e( 'Why are you showing me this?', 'wp-parsidate' ); ?>
          </a>
        </li>
        <li>
          <a href="https://wp-parsidate.ir/sponser" target="_blank">
            <span class="dashicons dashicons-external"></span>
            <?php esc_html_e( 'How can I become a sponsor?', 'wp-parsidate' ); ?>
          </a>
        </li>
      </ul>
    </div>
    <div id="wpp_sponsorship_placeholder">
      <img src="<?php echo esc_url_raw( WP_PARSI_URL ); ?>assets/images/logo.svg"
           alt="<?php esc_html_e( 'Loading Sponsors', 'wp-parsidate' ); ?>">
    </div>
    <div id="wpp_sponsorship" class="keen-slider"></div>
    <?php
    $this->dashboardEventsNews();
  }

  /**
   * Renders the Events and News dashboard widget.
   *
   * @since 4.8.0
   */
  public function dashboardEventsNews(): void {
    ?>

    <div class="wordpress-news hide-if-no-js">
      <?php $this->printDashboardWidgetPrimary(); ?>
    </div>

    <p class="community-events-footer">
      <?php
      printf(
        '<a href="%1$s" target="_blank">%2$s <span class="screen-reader-text"> %3$s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a>',
        'https://wp-parsi.com/',
        esc_html__( 'WordPress Parsi', 'wp-parsidate' ),
        /* translators: Hidden accessibility text. */
        esc_html__( '(opens in a new tab)', 'wp-parsidate' )
      );
      ?>

      |

      <?php
      printf(
        '<a href="%1$s" target="_blank">%2$s <span class="screen-reader-text"> %3$s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a>',
        'https://wp-parsi.com/about/',
        esc_html__( 'About', 'wp-parsidate' ),
        /* translators: Hidden accessibility text. */
        esc_html__( '(opens in a new tab)', 'wp-parsidate' )
      );
      ?>

      |

      <?php
      printf(
        '<a href="%1$s" target="_blank">%2$s <span class="screen-reader-text"> %3$s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a>',
        /* translators: If a Rosetta site exists (e.g. https://es.wordpress.org/news/), then use that. Otherwise, leave untranslated. */
        esc_url_raw( _x( 'https://wp-parsi.com/sponser/', 'Sponsorship plans', 'wp-parsidate' ) ),
        esc_html__( 'Sponsor', 'wp-parsidate' ),
        /* translators: Hidden accessibility text. */
        esc_html__( '(opens in a new tab)', 'wp-parsidate' )
      );
      ?>
    </p>
    <?php
  }

  /**
   * 'WordPress Events and News' dashboard widget.
   *
   * @since 2.7.0
   * @since 4.8.0 Removed popular plugins feed.
   */
  public function printDashboardWidgetPrimary(): void {
    $feeds = array(
      'news' => array(

        /**
         * Filters the primary link URL for the 'WordPress Events and News' dashboard widget.
         *
         * @param  string  $link  The widget's primary link URL.
         *
         * @since 2.5.0
         *
         */
        'link'         => 'https://wp-parsi.com/',

        /**
         * Filters the primary feed URL for the 'WordPress Events and News' dashboard widget.
         *
         * @param  string  $url  The widget's primary feed URL.
         *
         * @since 2.3.0
         *
         */
        'url'          => 'https://wp-parsi.com/parsidate/feed/',

        /**
         * Filters the primary link title for the 'WordPress Events and News' dashboard widget.
         *
         * @param  string  $title  Title attribute for the widget's primary link.
         *
         * @since 2.3.0
         *
         */
        'title'        => apply_filters( 'dashboard_primary_title', esc_html__( 'ParsiDate', 'wp-parsidate' ) ),
        'items'        => 5,
        'show_summary' => 0,
        'show_author'  => 0,
        'show_date'    => 0,
      ),
      //'planet' => array(

      /**
       * Filters the secondary link URL for the 'WordPress Events and News' dashboard widget.
       *
       * @param  string  $link  The widget's secondary link URL.
       *
       * @since 2.3.0
       *
       */
      //'link'         => esc_html__( 'https://wp-planet.ir/' ),

      /**
       * Filters the secondary feed URL for the 'WordPress Events and News' dashboard widget.
       *
       * @param  string  $url  The widget's secondary feed URL.
       *
       * @since 2.3.0
       *
       */
      //'url'          => esc_html__( 'https://wp-planet.ir/feed' ),

      /**
       * Filters the secondary link title for the 'WordPress Events and News' dashboard widget.
       *
       * @param  string  $title  Title attribute for the widget's secondary link.
       *
       * @since 2.3.0
       *
       */
      //'title'        => apply_filters( 'dashboard_secondary_title', esc_html__( 'Other WordPress News' ) ),

      /**
       * Filters the number of secondary link items for the 'WordPress Events and News' dashboard widget.
       *
       * @param  string  $items  How many items to show in the secondary feed.
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

    wp_dashboard_cached_rss_widget( 'wpp_dashboard_primary',
      [ $this, 'printDashboardWidgetPrimaryOutput' ], $feeds );
  }

  /**
   * Displays the WordPress events and news feeds.
   *
   * @param  string  $widget_id  Widget ID.
   * @param  array  $feeds  Array of RSS feeds.
   *
   * @since 3.8.0
   * @since 4.8.0 Removed popular plugins feed.
   *
   */
  public function printDashboardWidgetPrimaryOutput( $widget_id, $feeds ): void {
    foreach ( $feeds as $type => $args ) {
      $args['type'] = $type;
      echo '<div class="rss-widget">';
      wp_widget_rss_output( $args['url'], $args );
      echo '</div>';
    }
  }

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
  public function enqueueScripts( $hook ): void {
    // Check if we are on the admin dashboard page
    if ( $hook !== 'index.php' ) {
      return;
    }

    $pluginVersion = Assets::getVersion();
    $debugName     = WP_PARSI_DEBUG_MODE ? '' : '.min';

    wp_enqueue_style( 'keen-slider', WP_PARSI_URL . "assets/css-admin/keen-slider$debugName.css", false, '6.8.6' );
    wp_enqueue_style( 'wpp_dashboard', WP_PARSI_URL . "assets/css-admin/dashboard$debugName.css", false,
      $pluginVersion );
    wp_enqueue_script( 'keen-slider', WP_PARSI_URL . "assets/js-admin/keen-slider.min.js", array(), '6.8.6', true );
    wp_enqueue_script( 'wpp_dashboard', WP_PARSI_URL . "assets/js-admin/dashboard$debugName.js",
      array( 'jquery', 'keen-slider' ), WP_PARSI_VER, true );

    wp_localize_script( 'wpp_dashboard', 'sponsors', $this->getMockedSponsors() );
  }

  /**
   * Get mocked sponsors
   *
   * @return array
   * @author Mohammad Zarei
   * @sicne 5.1.3
   *
   */
  private function getMockedSponsors(): array {
    $sponsors     = array();
    $all_sponsors = array(
      array(
        'image_url' => WP_PARSI_URL . 'assets/images/sponsors/files-ir.jpg',
        'image_alt' => esc_html__( 'Files.ir', 'wp-parsidate' ),
        'link'      => 'https://files.ir/?utm_source=wp-persian&utm_medium=WordPress&utm_campaign=Sponsored-Banner',
        'end_date'  => '2026-11-21',
      )
    );
    $today        = date( 'Y-m-d' );
    foreach ( $all_sponsors as $sponsor ) {
      if ( strtotime( $sponsor['end_date'] ) > strtotime( $today ) ) {
        $sponsors[] = $sponsor;
      }
    }

    return $sponsors;
  }

  /**
   * Widget secondary feed
   *
   * @return          string
   * @author          Morteza Geransayeh
   */
  public function changeDashboardSecondaryFeedUrl(): string {
    return 'https://wp-planet.ir/feed';
  }

  /**
   * Widget secondary link
   *
   * @return          string
   * @author          Morteza Geransayeh
   */
  public function changeDashboardSecondaryLink(): string {
    return 'https://wp-planet.ir/';
  }

  /**
   * Widget primary feed
   *
   * @return          string
   * @author          Morteza Geransayeh
   */
  public function changeDashboardFeedUrl(): string {
    return 'https://wp-parsi.com/feed/';
  }

  /**
   * Change login header url in wp-login.php & Widget primary link
   *
   * @return              string
   */
  public function changeDashboardLink(): string {
    return 'https://wp-parsi.com';
  }
}
