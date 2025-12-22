<?php

namespace WPParsidate\Admin;

defined( 'ABSPATH' ) || exit;

class AdminConvert {
  public const tab = 'convert';
  public const icon = '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" xmlns="http://www.w3.org/2000/svg" transform="matrix(1, 0, 0, 1, 0, 0)rotate(90)"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M10 22C6.22876 22 4.34315 22 3.17157 20.8284C2 19.6569 2 18.7712 2 15" stroke="#3c3c3c" stroke-width="1.5" stroke-linecap="round"></path> <path d="M22 15C22 18.7712 22 19.6569 20.8284 20.8284C19.6569 22 17.7712 22 14 22" stroke="#3c3c3c" stroke-width="1.5" stroke-linecap="round"></path> <path d="M14 2C17.7712 2 19.6569 2 20.8284 3.17157C22 4.34315 22 5.22876 22 9" stroke="#3c3c3c" stroke-width="1.5" stroke-linecap="round"></path> <path d="M10 2C6.22876 2 4.34315 2 3.17157 3.17157C2 4.34315 2 5.22876 2 9" stroke="#3c3c3c" stroke-width="1.5" stroke-linecap="round"></path> <path d="M2 12H22" stroke="#3c3c3c" stroke-width="1.5" stroke-linecap="round"></path> </g></svg>';

  private static ?array $settings = null;

  public function __construct() {
    add_filter( 'wp_parsidate_menus', [ $this, 'addMenu' ] );
    add_filter( 'wp_parsidate_' . self::tab . '_settings', [ $this, 'settings' ] );
    add_filter( 'wp_parsidate_settings', [ $this, 'allSettings' ] );
  }

  public function addMenu( $menus ) {
    $menus[ self::tab ] = array(
      'title' => esc_html__( 'Convert', 'wp-parsidate' ),
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
        'title'    => esc_html__( 'Convert settings', 'wp-parsidate' ),
        'desc'     => esc_html__( 'Convert Persian letters and numbers', 'wp-parsidate' ),
        'settings' => apply_filters( 'wp_parsidate_' . self::tab . '_settings_options', [] )
      );
    }

    return self::$settings;
  }
}
