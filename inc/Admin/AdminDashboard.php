<?php

namespace WPParsidate\Admin;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Addons\Addons;
use WPParsidate\Helper\Assets;
use WPParsidate\Helper\FeedReader;
use WPParsidate\Helper\Notice;
use WPParsidate\Helper\Templates;
use WPParsidate\Helper\User;

class AdminDashboard {
  public const tab = 'dashboard';
  public const icon = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M15.5777 3.38197L17.5777 4.43152C19.7294 5.56066 20.8052 6.12523 21.4026 7.13974C22 8.15425 22 9.41667 22 11.9415V12.0585C22 14.5833 22 15.8458 21.4026 16.8603C20.8052 17.8748 19.7294 18.4393 17.5777 19.5685L15.5777 20.618C13.8221 21.5393 12.9443 22 12 22C11.0557 22 10.1779 21.5393 8.42229 20.618L6.42229 19.5685C4.27063 18.4393 3.19479 17.8748 2.5974 16.8603C2 15.8458 2 14.5833 2 12.0585V11.9415C2 9.41667 2 8.15425 2.5974 7.13974C3.19479 6.12523 4.27063 5.56066 6.42229 4.43152L8.42229 3.38197C10.1779 2.46066 11.0557 2 12 2C12.9443 2 13.8221 2.46066 15.5777 3.38197Z" stroke="#3c3c3c" stroke-width="1.5" stroke-linecap="round"></path> <path d="M21 7.5L12 12M12 12L3 7.5M12 12V21.5" stroke="#3c3c3c" stroke-width="1.5" stroke-linecap="round"></path> </g></svg>';

  public function __construct() {
    add_action( 'wp_parsidate_dashboard_tab_content', [ $this, 'content' ] );
    add_action( 'wp_parsidate_admin_init', [ $this, 'notice' ] );
    add_filter( 'wp_parsidate_menus', [ $this, 'addMenu' ] );
  }

  public function addMenu( $menus ) {
    $menus[ self::tab ] = array(
      'title' => esc_html__( 'Dashboard', 'wp-parsidate' ),
      'icon'  => self::icon
    );

    return $menus;
  }

  public function notice(): void {
    Notice::add( self::tab, esc_html__( 'Welcome to WP Parsi!', 'wp-parsidate' ), 'default' );
  }

  public function content(): void {
    $dashboardTypeLinks = array(
      'addons' => $this->getAddons(),
      'custom' => apply_filters( 'wp_parsidate_dashboard_custom_links', [] )
    );

    if ( empty( $dashboardTypeLinks['addons'] ) ) {
      $message = '<strong>' . esc_html__( 'Hello',
          'wp-parsidate' ) . '، ' . User::getData( 'display_name' ) . '!</strong>';
      $message .= '<p>' . esc_html__( 'WP Parsi is here to help you integrate Jalali date with your site, go to the Addons tab and activate the required addons.',
          'wp-parsidate' ) . '</p>';

      echo '<div class="wppd-dashboard-welcome">' . wp_kses( $message, [ 'strong' => [], 'p' => [] ] ) . '</div>';
    }

    echo '<div class="wppd-dashboard-links-wrap">';
    foreach ( $dashboardTypeLinks as $dashboardLinks ) {
      foreach ( $dashboardLinks as $link ) {
        $icon = ! empty( $link['icon'] ) && Assets::isSvgImageString( $link['icon'] ) ? Assets::setSvgDimensions( $link['icon'],
          50 ) : '';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<a href="' . esc_url_raw( $link['link'] ) . '" title="' . esc_html( $link['desc'] ) . '" class="wppd-link-type-' . esc_html( $link['type'] ) . '">' . $icon . '<span>' . esc_html( $link['title'] ) . '</span></a>';
      }
    }
    echo '</div>';

    echo '<div class="wppd-dashboard-feed-news"><strong class="wppd-dashboard-feed-head">' .
         esc_html__( 'WP Parsi news', 'wp-parsidate' ) . '</strong>';
    $feedReader = new FeedReader( [ 'url' => 'https://wp-parsi.com/parsidate/feed/' ] );
    $feedItems  = $feedReader->read()->getFeedLinks();
    $feedNone   = $feedReader->setEmptyFeedDesc( esc_html__( 'WP-Parsi website is not available.', 'wp-parsidate' ) );
    Templates::load(
      Templates::getPath( 'feed-reader/feed_list.php' ),
      array( 'items' => $feedItems, 'none' => $feedNone )
    );
    echo '</div>';
  }

  private function getAddons(): array {
    $addons    = apply_filters( 'wp_parsidate_dashboard_addon_links', [] );
    $addonCats = Addons::getAddonCats();
    $addonList = array();
    foreach ( array_keys( $addonCats ) as $addonCat ) {
      if ( ! empty( $addons[ $addonCat ] ) ) {
        $addonList[] = $addons[ $addonCat ];
      }
    }

    return array_merge( [], ...$addonList );
  }
}
