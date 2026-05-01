<?php

namespace WPParsidate\App\Convert;

class Convert {
  public function __construct() {
    new FixNumbers();
    new FixArabic();
    new FixPermalink();

    add_filter( 'wp_parsidate_convert_settings_options', [ $this, 'settings' ] );
  }

  public function settings(): array {
    return array(
      // Numbers
      'start_grid_persian_numbers' => array(
        'title' => esc_html__( 'Convert numbers to Persian', 'wp-parsidate' ),
        'type'  => 'startGrid',
      ),
      'conv_number_format_i18n'    => array(
        'id'       => 'conv_number_format_i18n',
        'title'    => esc_html__( 'Numbers', 'wp-parsidate' ),
        'desc'     => esc_html__( 'Used in menus, comments, API, media, etc.', 'wp-parsidate' ),
        'type'     => 'toggle',
        'default'  => false,
        'sanitize' => 'bool'
      ),
      'conv_page_title'            => array(
        'id'       => 'conv_page_title',
        'title'    => esc_html__( 'Page title', 'wp-parsidate' ),
        'type'     => 'toggle',
        'default'  => false,
        'sanitize' => 'bool'
      ),
      'conv_title'                 => array(
        'id'       => 'conv_title',
        'title'    => esc_html__( 'Post title', 'wp-parsidate' ),
        'type'     => 'toggle',
        'default'  => false,
        'sanitize' => 'bool'
      ),
      'conv_contents'              => array(
        'id'       => 'conv_contents',
        'title'    => esc_html__( 'Post content', 'wp-parsidate' ),
        'type'     => 'toggle',
        'default'  => false,
        'sanitize' => 'bool'
      ),
      'conv_excerpt'               => array(
        'id'       => 'conv_excerpt',
        'title'    => esc_html__( 'Post excerpt', 'wp-parsidate' ),
        'type'     => 'toggle',
        'default'  => false,
        'sanitize' => 'bool'
      ),
      'conv_comments'              => array(
        'id'       => 'conv_comments',
        'title'    => esc_html__( 'Comments text', 'wp-parsidate' ),
        'type'     => 'toggle',
        'default'  => false,
        'sanitize' => 'bool'
      ),
      'conv_comment_count'         => array(
        'id'       => 'conv_comment_count',
        'title'    => esc_html__( 'Comments count', 'wp-parsidate' ),
        'type'     => 'toggle',
        'default'  => false,
        'sanitize' => 'bool'
      ),
      'conv_dates'                 => array(
        'id'       => 'conv_dates',
        'title'    => esc_html__( 'Dates', 'wp-parsidate' ),
        'type'     => 'toggle',
        'default'  => false,
        'sanitize' => 'bool'
      ),
      'conv_cats'                  => array(
        'id'       => 'conv_cats',
        'title'    => esc_html__( 'Categories', 'wp-parsidate' ),
        'type'     => 'toggle',
        'default'  => false,
        'sanitize' => 'bool'
      ),
      'end_grid_persian_numbers'   => array(
        'type' => 'endGrid',
      ),

      // Letters
      'start_grid_letters'         => array(
        'title' => esc_html__( 'Letters', 'wp-parsidate' ),
        'type'  => 'startGrid',
      ),
      'conv_arabic'                => array(
        'id'       => 'conv_arabic',
        'title'    => esc_html__( 'Fix arabic characters', 'wp-parsidate' ),
        'type'     => 'toggle',
        'default'  => false,
        'desc'     => esc_html__( 'Fixes arabic characters caused by wrong keyboard layouts', 'wp-parsidate' ),
        'sanitize' => 'bool'
      ),
      'end_grid_letters'           => array(
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
    );
  }
}
