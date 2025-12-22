<?php

namespace WPParsidate\Admin;

defined( 'ABSPATH' ) || exit;

class AdminTools {
  public const tab = 'tools';
  public const icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><g stroke="#3c3c3c" stroke-width="1.5"><path d="M2 14c0-3.771 0-5.657 1.172-6.828S6.229 6 10 6h4c3.771 0 5.657 0 6.828 1.172S22 10.229 22 14s0 5.657-1.172 6.828S17.771 22 14 22h-4c-3.771 0-5.657 0-6.828-1.172S2 17.771 2 14ZM16 6c0-1.886 0-2.828-.586-3.414S13.886 2 12 2s-2.828 0-3.414.586S8 4.114 8 6"/><path stroke-linejoin="round" d="M10 15H6c-.471 0-.707 0-.854.146C5 15.293 5 15.53 5 16v1c0 .471 0 .707.146.854C5.293 18 5.53 18 6 18h4c.471 0 .707 0 .854-.146C11 17.707 11 17.47 11 17v-1c0-.471 0-.707-.146-.854C10.707 15 10.47 15 10 15Z"/><path stroke-linecap="round" d="M6 6.5V15m0 6.5v-3M18 6.5v15"/></g></svg>';

  private static ?array $settings = null;

  public function __construct() {
    add_filter( 'wp_parsidate_menus', [ $this, 'addMenu' ] );
    add_filter( 'wp_parsidate_' . self::tab . '_settings', [ $this, 'settings' ] );
    add_filter( 'wp_parsidate_settings', [ $this, 'allSettings' ] );
  }

  public function addMenu( $menus ) {
    $menus[ self::tab ] = array(
      'title' => esc_html__( 'Tools', 'wp-parsidate' ),
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
        'title'    => esc_html__( 'Tools settings', 'wp-parsidate' ),
        'desc'     => esc_html__( 'Advanced tools', 'wp-parsidate' ),
        'settings' => apply_filters( 'wp_parsidate_' . self::tab . '_settings_options', [] )
      );
    }

    return self::$settings;
  }
}
