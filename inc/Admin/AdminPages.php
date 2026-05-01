<?php

namespace WPParsidate\Admin;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Helper\Assets;
use WPParsidate\Helper\Cache;
use WPParsidate\Helper\Notice;
use WPParsidate\Helper\Param;

class AdminPages {
  public function __construct() {
    add_action( 'admin_init', [ $this, 'init' ] );
    add_action( 'wp_parsidate_admin_init', [ $this, 'checkSubmitForm' ], 15 );
    add_action( 'admin_menu', array( $this, 'adminMenuInit' ), 0 );
    add_action( 'admin_menu', array( $this, 'addMenu' ), PHP_INT_MAX );
    add_action( 'wp_parsidate_notice', [ $this, 'displayNotices' ] );
    add_action( 'wp_parsidate_header', [ $this, 'pageHeader' ] );
    add_action( 'wp_parsidate_content', [ $this, 'pageContent' ] );
    add_action( 'wp_parsidate_footer', [ $this, 'pageFooter' ] );
    add_action( 'admin_footer', [ $this, 'flushRewriteRules' ] );
  }

  public function flushRewriteRules(): void {
    if ( Cache::get( 'settings_saved' ) ) {
      flush_rewrite_rules();
    }
  }

  public function checkSubmitForm(): void {
    $tab = self::getActiveTab();
    if ( isset( $_POST['_form_nonce'] ) && check_admin_referer( 'settings_submit_' . $tab, '_form_nonce' ) ) {
      do_action( 'wp_parsidate_submit_settings_form', $tab );
    }
  }

  public function pageHeader( $currentTab ): void {
    AdminSettings::headerSettings( $currentTab, AdminSettings::getSettings( $currentTab ) );
  }

  public function pageContent( $currentTab ): void {
    $settings = AdminSettings::getSettings( $currentTab );

    if ( $settings && apply_filters( 'wp_parsidate_display_tab_settings', true, $currentTab ) ) {
      AdminSettings::printPage( $currentTab, $settings );
    }
  }

  public function pageFooter( $currentTab ): void {
    $settings = AdminSettings::getSettings( $currentTab );
    if ( empty( $settings ) ) {
      return;
    }

    $currentSection = AdminSettings::getActiveSection( $settings );
    $currentSection = $currentSection ?: null;
    AdminSettings::footerSettings( $currentTab, $currentSection );
  }

  public function init(): void {
    if ( self::isSettingPage() ) {
      do_action( 'wp_parsidate_admin_init', self::getActiveTab() );
    }
  }

  public function adminMenuInit(): void {
    if ( self::isSettingPage() ) {
      do_action( 'wp_parsidate_admin_init_menu', self::getActiveTab() );
    }
  }

  public function displayNotices( $tab ): void {
    if ( apply_filters( 'wp_parsidate_' . $tab . '_tab_display_notice', true ) ) {
      Notice::display( '*' );
      Notice::display( $tab );
    }
  }

  public function addMenu(): void {
    $icon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0NTAiIGhlaWdodD0iNDUwIj48ZyBjbGFzcz0ibGF5ZXIiPjx0aXRsZT5MYXllciAxPC90aXRsZT48cGF0aCBmaWxsPSIjZmZmIiBkPSJNMjA3IC42Yy0yOC43IDIuNi01Ny4xIDEwLjYtODEuNyAyMi45LTQ1LjggMjIuOS03OC45IDU2LTEwMS45IDEwMS45LTM0IDY4LTMwLjUgMTUwLjIgOS4yIDIxNS44IDM1LjkgNTkuMyA5Ny45IDk4LjkgMTY3LjMgMTA2LjggMTMuOSAxLjYgNDkuNi44IDYxLjYtMS41IDI4LjUtNS4yIDUxLjktMTMuNiA3NC41LTI2LjUgNTguNi0zMy43IDk3LjItODguNSAxMTAuMS0xNTYuNSAyLjctMTQgMy43LTQ4LjIgMS45LTYzLjYtNC42LTQwLjMtMTkuMi03Ny4xLTQzLjgtMTEwLjMtOS40LTEyLjYtMzEuNi0zNC45LTQ0LjQtNDQuNEMzMjkuNiAyMi42IDI5NS40IDguMyAyNTggMi41IDI0Ny45IDEgMjE2LjMtLjIgMjA3IC42bTM4IDEzLjljNC43LjQgMTQgMS43IDIwLjggMy4xIDczLjYgMTQuNyAxMzMuNSA2Ny4yIDE1OC4xIDEzOC43IDguMyAyNC4xIDExLjcgNDcuOSAxMC44IDc1LjEtLjggMjMuNS00LjcgNDQtMTIuNCA2NC42LTIxLjggNTguNS02NyAxMDMuNy0xMjUuNSAxMjUuNC0yMC44IDcuNy00MC44IDExLjUtNjQuNCAxMi4zLTI3LjUuOS00OC41LTItNzMuNi0xMC4zLTc1LjktMjUuMi0xMzAuMS04OS44LTE0Mi40LTE2OS45LTIuMy0xNS4yLTIuMy00My4yIDAtNTkgNi43LTQ1IDI2LjItODQuNyA1Ny41LTExNi45IDMyLjUtMzMuNCA3Mi01NCAxMTguMS02MS42IDE1LjQtMi42IDMwLjQtMyA1My0xLjUiLz48cGF0aCBmaWxsPSIjZmZmIiBkPSJNMTk4LjUgMzMuNmMtMzIuMiA0LjMtNjIuNyAxNi44LTg5LjEgMzYuNUM4NC45IDg4LjQgNjYuMSAxMTEgNTIgMTM5Yy00LjcgOS40LTEyIDI3LjQtMTIgMjkuNyAwIDEuMiA3Ni4xLTEuNyAxMDAuNS0zLjggNDUuOC0zLjkgODUuNS0xMi4zIDEwNS4yLTIyLjIgMTMuNC02LjggMTUuNi0xMy4xIDgtMjMuMy02LjMtOC42LTIzLjUtMjMuOC00MC45LTM2LjMtMi43LTEuOS00LjgtNC00LjgtNC42IDAtLjcgMy40LTguNiA3LjYtMTcuNnM4LjktMTkuMiAxMC40LTIyLjdsMi45LTYuMi0xMC4yLjFjLTUuNi4xLTE0LjcuNy0yMC4yIDEuNSIvPjxwYXRoIGZpbGw9IiNmZmYiIGQ9Ik0yODcuMyA0My4yYy4zLjcgMi4yIDUgNC4yIDkuNSA3LjEgMTYuMiA3LjkgMjEgNy45IDQ2LjggMCAxOS41LS4zIDI0LjgtMi4yIDM0LjctOC4yIDQzLjYtMjguNCA3MS43LTY0LjYgOTAtMzYuNiAxOC40LTc4LjMgMjUuNC0xNjYuMyAyNy42LTE3LjIuNC0zMS41IDEuMS0zMS45IDEuNC0uMy40LjMgNS42IDEuNSAxMS41IDguNiA0Mi43IDM0LjUgODQuNCA2OS42IDExMS44IDIyLjIgMTcuMyA1MC42IDMwLjkgNzYuNiAzNi42IDE3IDMuNyA1MiA2LjUgNTQuMiA0LjMuMy0uMy0yLjktMy4yLTcuMi02LjQtMTItOC45LTI4LTIzLTM3LjYtMzMuMi05LjktMTAuNS0yMC45LTIzLjctMjAuMy0yNC4zLjItLjIgNC43IDEuMiA5LjggMy4xIDM3LjkgMTQuMyA4NC40IDIxLjUgMTA4LjMgMTcgMjUuNi00LjkgNTMuNS0yMC4xIDc1LjUtNDEuMSAxMS41LTEwLjkgMjcuMi0zMC44IDI3LjItMzQuMyAwLS41LTYuMi0xLjYtMTMuNy0yLjUtMzEuMi0zLjktNTgtMTEuMS03Mi42LTE5LjgtMTUtOC44LTI1LjgtMjMuNC0yOC44LTM4LjctMS40LTcuNy0uNy0yNC41IDEuNi0zNS4xIDctMzMgMzEtNzQuMyA1NC44LTk0LjIgNy43LTYuNSAxNy42LTEyIDI2LjEtMTQuNGw1LjgtMS43LTUuOC01LjVjLTE5LjktMTguNC0zOS42LTMxLjMtNjIuNi00MC43LTkuNi00LTEwLjItNC4xLTkuNS0yLjRNMTE4LjUgMjc1bDExLjEgMTEgMTAuOC0xMWM2LjktNy4xIDExLjUtMTEgMTIuOS0xMSAyLjggMCAyOS43IDI2LjYgMjkuNyAyOS40IDAgMi40LTI2LjYgMjkuNi0yOSAyOS42LS44IDAtNi41LTUuMS0xMi43LTExLjJMMTMwIDMwMC41bC0xMS4zIDExLjNjLTYuMiA2LjEtMTEuOSAxMS4yLTEyLjggMTEuMi0uOCAwLTcuOS02LjUtMTUuOC0xNC40LTEzLjktMTQuMS0xNC4yLTE0LjUtMTIuOS0xNyAyLjMtNC4zIDI2LTI3LjYgMjguMS0yNy42IDEuMiAwIDYuNSA0LjQgMTMuMiAxMW0yNy4zIDQzLjZjOS44IDkuMyAxNC4yIDE0LjIgMTQuMiAxNS43cy00LjcgNi45LTE0LjUgMTYuN0wxMzEgMzY1LjRsLTEzLjItMTIuOWMtMTcuNS0xNy4xLTE3LjQtMTctMTUuNC0yMCAzLjEtNC43IDI1LjktMjcuNSAyNy41LTI3LjUuOSAwIDggNi4xIDE1LjkgMTMuNiIvPjxwYXRoIGZpbGw9IiNmZmYiIGQ9Ik0zNDQuMiAxNjQuMWMtNy44IDMuOS0xNy40IDEzLjMtMTkuNyAxOS40LTQuOCAxMi4zIDMgMjEuMiAyNC40IDI3LjkgOC4yIDIuNiA1Mi41IDkuNiA1My42IDguNXMtMy43LTE2LjYtNy41LTI0LjNjLTcuOS0xNi4zLTE5LjMtMjcuOS0zMS42LTMyLjUtNy42LTIuOS0xMi0yLjctMTkuMiAxIi8+PC9nPjwvc3ZnPg==';

    add_menu_page( esc_html__( 'WP Parsi', 'wp-parsidate' ), esc_html__( 'WP Parsi', 'wp-parsidate' ), 'manage_options',
      WP_PARSI_KEY_SLUG,
      [ $this, 'mainPage' ], $icon );
  }

  public function mainPage(): void {
    $logo         = Assets::url( 'images/logo.svg' );
    $currentTab   = self::getActiveTab();
    $wrapperClass = array(
      WP_PARSI_KEY_SLUG . '-wrap',
      WP_PARSI_KEY_SLUG . '-wrapper',
      WP_PARSI_KEY_SLUG . '-' . esc_html( $currentTab ),
    );
    ?>
    <div class="wrap">
      <div class="<?php echo esc_attr( implode( ' ', $wrapperClass ) ) ?>">
        <div class="wppd-sidebar" id="wppd-sidebar">
          <div class="wppd-sidebar-head">
            <div class="wppd-logo-wrap">
              <img src="<?php echo esc_url_raw( $logo ) ?>" alt="Logo" class="wppd-logo">
              <div>
                <span><?php esc_html_e( 'WordPress', 'wp-parsidate' ); ?></span>
                <span><?php esc_html_e( 'Parsi', 'wp-parsidate' ); ?></span>
              </div>
            </div>
            <a href="#" class="wppd-hide-sidebar" id="wppd-hide-sidebar">
              <svg viewBox="0 0 24 24" width="30px" height="30px" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                <g id="SVGRepo_iconCarrier">
                  <g id="Menu / Close_SM">
                    <path id="Vector" d="M16 16L12 12M12 12L8 8M12 12L16 8M12 12L8 16"
                          stroke="#3c3c3c" stroke-width="2" stroke-linecap="round"
                          stroke-linejoin="round"></path>
                  </g>
                </g>
              </svg>
            </a>
          </div>
          <div class="menu-items">
            <?php
            do_action( 'wp_parsidate_start_menus' );
            $menus    = self::getMenus();
            $addonSep = false;
            foreach ( $menus as $tab => $menu ) {
              if ( ! $addonSep && ! in_array( $tab, self::defaultTabs(), true ) ) {
                echo '<hr>';
                $addonSep = true;
              }

              // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
              echo self::menuItem( $tab, $menu );
            }
            do_action( 'wp_parsidate_end_menus' );
            ?>
          </div>
        </div>
        <div class="wppd-display-sidebar">
          <a href="#" id="wppd-display-sidebar">
            <svg xmlns="http://www.w3.org/2000/svg" width="30px" height="30px" fill="none"
                 viewBox="0 0 24 24">
              <path stroke="#3c3c3c" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M5 17h8m-8-5h14M5 7h8"/>
            </svg>
          </a>
        </div>
        <div class="wppd-content wppd-<?php echo esc_html( $currentTab ) ?>-content" id="wppd-content-wrap">
          <?php
          // Display tab header
          do_action( 'wp_parsidate_' . $currentTab . '_tab_header' );
          do_action( 'wp_parsidate_header', $currentTab );

          echo '<div class="wppd-content-body">';
          // Display notice
          do_action( 'wp_parsidate_notice', $currentTab );
          do_action( 'wp_parsidate_' . $currentTab . '_tab_notice' );

          // Display tab content
          do_action( 'wp_parsidate_' . $currentTab . '_tab_content' );
          do_action( 'wp_parsidate_content', $currentTab );
          echo '</div>';

          // Display tab footer
          do_action( 'wp_parsidate_' . $currentTab . '_tab_footer' );
          do_action( 'wp_parsidate_footer', $currentTab );
          ?>
        </div>
      </div>
    </div>
    <?php
  }

  public static function getMenus(): array {
    return apply_filters( 'wp_parsidate_menus', [] );
  }

  public static function menuItem( $tab, $menu, $link = null ): string {
    $current = self::getActiveTab();
    $link    = $menu['link'] ?? $link;
    $link    = empty( $link ) ? self::link( [ 'tab' => $tab ] ) : $link;

    if ( ! is_array( $menu ) || ! isset( $menu['title'] ) ) {
      return '';
    }

    $icon = Assets::isSvgImageString( $menu['icon'] ) ? Assets::setSvgDimensions( $menu['icon'], 20 ) : '';

    return '<a href="' . esc_url_raw( $link ) . '" class="menu-item' . ( $current === $tab ? ' menu-item-current' : '' ) . '">' . $icon . '<span>' . esc_html( $menu['title'] ) . '</span></a>';
  }

  public static function getActiveTab(): string {
    $default = 'dashboard';
    $current = strtolower( Param::get( 'tab', $default ) );
    $tabs    = array_merge( self::defaultTabs(), array_keys( self::getMenus() ) );

    return in_array( $current, $tabs, true ) ? $current : $default;
  }

  private static function defaultTabs(): array {
    return [ 'dashboard', 'core', 'convert', 'tools', 'integration', 'addons', 'about' ];
  }

  public static function isSettingPage(): bool {
    return is_admin() && Param::get( 'page' ) === WP_PARSI_KEY_SLUG;
  }

  public static function link( $query ): ?string {
    $query = is_array( $query ) ? $query : array();
    $data  = array_merge( array( 'page' => WP_PARSI_KEY_SLUG ), $query );
    $query = http_build_query( $data );

    return admin_url( 'admin.php?' . $query );
  }
}
