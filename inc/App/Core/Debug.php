<?php

namespace WPParsidate\App\Core;

use WPParsidate\Helper\Notice;
use WPParsidate\Settings\Settings;

class Debug {
  private const sectionID = 'debug';

  public function __construct() {
    add_filter( 'wp_parsidate_core_settings_sections', [ $this, 'addSectionSettings' ] );
    add_action( 'wp_parsidate_admin_init', [ $this, 'addNotice' ] );
  }

  public function addNotice( $tab ): void {
    if ( Settings::get( 'debug_mode', false ) ) {
      Notice::add( 'dashboard', esc_html__( 'Debug mode is enabled!', 'wp-parsidate' ), 'warning' );
    }
  }

  public function addSectionSettings( array $sections ): array {
    $settings = array(
      'debug_start_grid'  => array(
        'title' => esc_html__( 'Debug', 'wp-parsidate' ),
        'type'  => 'startGrid',
      ),
      'debug_mode'        => array(
        'id'       => 'debug_mode',
        'title'    => esc_html__( 'Debug Mode', 'wp-parsidate' ),
        'type'     => 'toggle',
        'default'  => false,
        'desc'     => esc_html__( 'By enabling this option, the uncompressed version of the JS and CSS files will be loaded.', 'wp-parsidate' ),
        'sanitize' => 'bool'
      ),
      'local_text_domain' => array(
        'id'       => 'local_text_domain',
        'title'    => esc_html__( 'Load translate file', 'wp-parsidate' ),
        'type'     => 'toggle',
        'default'  => false,
        'desc'     => esc_html__( 'Load translate file from plugin directory.', 'wp-parsidate' ),
        'sanitize' => 'bool'
      ),
      'debug_end_grid'    => array(
        'type' => 'endGrid',
      ),

      'features_start_grid'              => array(
        'title' => esc_html__( 'Features', 'wp-parsidate' ),
        'type'  => 'startGrid',
      ),
      'new_gutenberg_datepicker_enabled' => array(
        'id'       => 'new_gutenberg_datepicker_enabled',
        'title'    => esc_html__( 'New Gutenberg Datepicker', 'wp-parsidate' ),
        'type'     => 'toggle',
        'default'  => true,
        'desc'     => esc_html__( 'By enabling this option, New datepicker enabled in Gutenberg post editor', 'wp-parsidate' ),
        'sanitize' => 'bool'
      ),
      'features_end_grid'                => array(
        'type' => 'endGrid',
      ),
    );

    $sections[ self::sectionID ] = array(
      'title'    => esc_html__( 'Debug', 'wp-parsidate' ),
      'desc'     => esc_html__( 'Debug settings', 'wp-parsidate' ),
      'settings' => $settings
    );

    return $sections;
  }
}
