<?php

namespace WPParsidate\App\Core;

use WPParsidate\Helper\WordPress;
use WPParsidate\Settings\Settings;

class ShamsiDate {
  private const sectionID = 'shamsi-date';

  public function __construct() {
    add_filter( 'wp_parsidate_core_settings_sections', [ $this, 'addSectionSettings' ] );
    add_action( 'init', [ $this, 'disableGutenbergBlocksWidget' ] );
  }

  /**
   * disable wp widget block that introduced in WordPress 5.8
   *
   * @since               4.0.0
   */
  public function disableGutenbergBlocksWidget(): void {
    if ( Settings::get( 'disable_widget_block', false ) ) {
      add_filter( 'gutenberg_use_widgets_block_editor', '__return_false' );
      add_filter( 'use_widgets_block_editor', '__return_false' );
    }
  }

  public function addSectionSettings( array $sections ): array {
    $settings = array(
      // Date
      'start_grid_date'      => array(
        'title' => esc_html__( 'Date', 'wp-parsidate' ),
        'type'  => 'startGrid',
      ),
      'persian_date'         => array(
        'id'       => 'persian_date',
        'title'    => esc_html__( 'Shamsi date', 'wp-parsidate' ),
        'type'     => 'toggle',
        'default'  => false,
        'desc'     => esc_html__( 'By enabling this, Dates will convert to Shamsi (Jalali) dates', 'wp-parsidate' ),
        'sanitize' => 'bool'
      ),
      'dual_date'            => array(
        'id'       => 'dual_date',
        'title'    => esc_html__( 'Dual date', 'wp-parsidate' ),
        'type'     => 'toggle',
        'default'  => false,
        'desc'     => esc_html__( 'By enabling this, Gregorian dates will be displayed beside Shamsi (Jalali) dates.',
          'wp-parsidate' ),
        'sanitize' => 'bool'
      ),
      'months_name_type'     => array(
        'id'       => 'months_name_type',
        'title'    => esc_html__( 'Months and week days name type', 'wp-parsidate' ),
        'type'     => 'select',
        'options'  => array(
          'persian' => esc_html__( 'Persian', 'wp-parsidate' ),
          'dari'    => esc_html__( 'Dari', 'wp-parsidate' ),
          'kurdish' => esc_html__( 'Kurdish', 'wp-parsidate' ),
          'pashto'  => esc_html__( 'Pashto', 'wp-parsidate' ),
        ),
        'default'  => 'persian',
        'sanitize' => 'text'
      ),
      'end_grid_date'        => array(
        'type' => 'endGrid',
      ),

      // Permalinks
      'start_grid_permalinks'      => array(
        'title' => esc_html__( 'Permalinks', 'wp-parsidate' ),
        'type'  => 'startGrid',
      ),
      'conv_permalinks'            => array(
        'id'       => 'conv_permalinks',
        'title'    => esc_html__( 'Fix permalinks dates', 'wp-parsidate' ),
        'type'     => 'toggle',
        'default'  => false,
        'desc'     => esc_html__( 'By enabling this, dates in permalinks converted to Shamsi (Jalali) date',
          'wp-parsidate' ),
        'sanitize' => 'bool'
      ),
      'end_grid_permalinks'        => array(
        'type' => 'endGrid',
      ),

      // Admin
      'start_grid_admin'     => array(
        'title' => esc_html__( 'Admin', 'wp-parsidate' ),
        'type'  => 'startGrid',
      ),
      'disable_widget_block' => array(
        'id'       => 'disable_widget_block',
        'title'    => esc_html__( 'Disable Widget Block', 'wp-parsidate' ),
        'type'     => 'toggle',
        'default'  => false,
        'desc'     => esc_html__( 'By enabling this, Widget Block Editor disabled', 'wp-parsidate' ),
        'sanitize' => 'bool'
      ),
      'enable_fonts'         => array(
        'id'       => 'enable_fonts',
        'title'    => esc_html__( 'Vazir Font', 'wp-parsidate' ),
        'type'     => 'toggle',
        'default'  => false,
        'desc'     => esc_html__( 'By enabling this option, the Vazir font will be enable in whole admin area.',
          'wp-parsidate' ),
        'sanitize' => 'bool'
      ),
      'end_grid_admin'       => array(
        'type' => 'endGrid',
      )
    );

    if ( WordPress::isMultilingualActive() ) {
      $settings = array_merge( $settings, array(
        'sep_multilingual'        => array(
          'type' => 'hr',
        ),
        'start_grid_multilingual' => array(
          'title' => esc_html__( 'Multilingual', 'wp-parsidate' ),
          'type'  => 'startGrid',
        ),
        'multilingual_support'    => array(
          'id'       => 'multilingual_support',
          'title'    => esc_html__( 'Multilingual compatibility', 'wp-parsidate' ),
          'type'     => 'toggle',
          'default'  => false,
          'desc'     => esc_html__( 'By enabling this, ParsiDate options only work in persian locale',
            'wp-parsidate' ),
          'sanitize' => 'bool'
        ),
        'end_grid_multilingual'   => array(
          'type' => 'endGrid',
        ),
      ) );
    }

    $sections[ self::sectionID ] = array(
      'title'    => esc_html__( 'Shamsi date', 'wp-parsidate' ),
      'desc'     => esc_html__( 'Shamsi date settings', 'wp-parsidate' ),
      'settings' => $settings
    );

    return $sections;
  }
}
