<?php
/**
 * ParsiDate Calendar Block
 *
 * Register the Gutenberg block version of the calendar widget
 */

namespace WPParsidate\Block;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Core\Calendar;
use WPParsidate\Helper\Assets;

class CalendarBlock extends Block {
  private const STYLE = 'wp-parsidate-calendar-style';

  protected string $editorScript = 'wp-parsidate-calendar-editor-script';

  protected string $blockJsonPath = 'blocks/calendar/block.json';

  protected string $scriptBaseName = 'calendar-block';

  protected string $dataVar = 'wppCalendarBlockData';

  public function registerBlock(): void {
    parent::registerBlock();

    wp_register_style(
      self::STYLE,
      Assets::url( 'css-admin/calendar-wdiget.min.css' ),
      array(),
      Assets::getVersion()
    );
  }

  protected function renderContent( $attributes ): string {
    $title    = $attributes['title'] ?? '';
    $postType = $attributes['postType'] ?? 'post';
    $theme    = $attributes['theme'] ?? 'simple';

    // Block attributes are saved by whoever can edit the post, so they must not
    // be trusted: only a registered post type and one of the known themes are
    // accepted, and anything else falls back to the default.
    $title    = is_string( $title ) ? $title : '';
    $postType = is_string( $postType ) ? sanitize_key( $postType ) : 'post';

    if ( ! post_type_exists( $postType ) ) {
      $postType = 'post';
    }

    $allowedThemes = array( 'none', 'simple', 'light', 'dark' );
    $theme         = is_string( $theme ) ? sanitize_key( $theme ) : 'simple';

    if ( ! in_array( $theme, $allowedThemes, true ) ) {
      $theme = 'simple';
    }

    $blockId = 'wp-parsidate-calendar-block-' . md5( $postType . $theme );

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
}
