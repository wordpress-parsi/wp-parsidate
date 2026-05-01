<?php

namespace WPParsidate\Admin;

defined( 'ABSPATH' ) || exit;

class AdminCore {
  public const tab = 'core';
  public const icon = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M20.9423 3.05768C23.4117 5.52701 21.4099 11.5324 16.4712 16.4711C11.5326 21.4097 5.5272 23.4115 3.05787 20.9422C0.588547 18.4728 2.59033 12.4675 7.52899 7.5288C12.4676 2.59014 18.473 0.588345 20.9423 3.05768ZM3.05768 3.05782C0.588349 5.52715 2.59013 11.5325 7.52879 16.4712C12.4674 21.4099 18.4728 23.4117 20.9421 20.9423C23.4115 18.473 21.4097 12.4676 16.471 7.52894C11.5324 2.59028 5.527 0.588485 3.05768 3.05782Z" stroke="#3c3c3c" stroke-width="1.5"></path> <path d="M14.5 12C14.5 13.3807 13.3807 14.5 12 14.5C10.6193 14.5 9.5 13.3807 9.5 12C9.5 10.6193 10.6193 9.5 12 9.5C13.3807 9.5 14.5 10.6193 14.5 12Z" stroke="#3c3c3c" stroke-width="1.5"></path> </g></svg>';

  private static ?array $settings = null;

  public function __construct() {
    add_filter( 'wp_parsidate_menus', [ $this, 'addMenu' ] );
    add_filter( 'wp_parsidate_' . self::tab . '_settings', [ $this, 'settings' ] );
    add_filter( 'wp_parsidate_settings', [ $this, 'allSettings' ] );
  }

  public function addMenu( $menus ) {
    $menus[ self::tab ] = array(
      'title' => esc_html__( 'Core', 'wp-parsidate' ),
      'icon'  => self::icon
    );

    return $menus;
  }

  public function allSettings( $settings ): array {
    $settings[ self::tab ] = $this->settings();

    return $settings;
  }

  public function settings(): array {
    if ( self::$settings === null ) {
      self::$settings = array(
        'title'    => esc_html__( 'Core settings', 'wp-parsidate' ),
        'desc'     => esc_html__( 'Global plugin settings', 'wp-parsidate' ),
        'settings' => apply_filters( 'wp_parsidate_' . self::tab . '_settings_options', [] )
      );
    }

    return self::$settings;
  }
}
