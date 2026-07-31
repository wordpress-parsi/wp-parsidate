<?php
/**
 * ParsiDate Calendar Block
 *
 * Register the Gutenberg block version of the calendar widget
 */

namespace WPParsidate\Widget;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Core\Calendar;
use WPParsidate\Helper\Assets;
use WPParsidate\Helper\Posts;
use WPParsidate\Settings\Settings;

class CalendarBlock {
  private const EDITOR_SCRIPT = 'wp-parsidate-calendar-editor-script';

  public function __construct() {
    add_action( 'init', array( $this, 'registerBlock' ) );
  }

  public function registerBlock(): void {
    if ( ! function_exists( 'register_block_type' ) ) {
      return;
    }

    $debugName = WP_PARSI_DEBUG_MODE ? '' : '.min';

    wp_register_script(
      self::EDITOR_SCRIPT,
      Assets::url( 'js-admin/calendar-block' . $debugName . '.js' ),
      array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
      Assets::getVersion(),
      array( 'in_footer' => true )
    );

    register_block_type( Assets::path( 'blocks/calendar/block.json' ), array(
      'render_callback' => array( $this, 'renderBlock' ),
    ) );

    add_action( 'enqueue_block_editor_assets', array( $this, 'editorAssets' ) );
  }

  public function editorAssets(): void {
    wp_add_inline_script(
      self::EDITOR_SCRIPT,
      'window.wppCalendarBlockData = ' . wp_json_encode( array(
        'postTypes'      => self::postTypeOptions(),
        'convPermalinks' => (bool) Settings::get( 'conv_permalinks', false ),
      ) ) . ';',
      'before'
    );
  }

  public function renderBlock( $attributes ): string {
    if ( ! Settings::get( 'conv_permalinks', false ) ) {
      return '';
    }

    $title    = $attributes['title'] ?? '';
    $postType = $attributes['postType'] ?? 'post';
    $theme    = $attributes['theme'] ?? 'simple';
    $blockId  = 'wp-parsidate-calendar-block-' . md5( $postType . $theme );

    ob_start();

    echo '<div ' . get_block_wrapper_attributes() . '>';

    if ( ! empty( $title ) ) {
      echo '<h2 class="wp-block-wp-parsidate-calendar__title">' . esc_html( $title ) . '</h2>';
    }

    do_action( 'wp_parsidate_calendar_block_start', $title, $postType, $theme, $blockId );
    Calendar::printCalendar( $postType, $theme );
    do_action( 'wp_parsidate_calendar_block_end', $title, $postType, $theme, $blockId );

    echo '</div>';

    return ob_get_clean();
  }

  private static function postTypeOptions(): array {
    $options = array();

    foreach ( Posts::getTypes() as $name => $label ) {
      $options[] = array( 'label' => $label, 'value' => $name );
    }

    return $options;
  }
}
