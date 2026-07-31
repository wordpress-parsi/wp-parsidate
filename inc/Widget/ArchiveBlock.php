<?php
/**
 * ParsiDate Archive Block
 *
 * Register the Gutenberg block version of the archive widget
 */

namespace WPParsidate\Widget;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Core\Archive;
use WPParsidate\Helper\Assets;
use WPParsidate\Helper\Posts;
use WPParsidate\Settings\Settings;

class ArchiveBlock {
  private const EDITOR_SCRIPT = 'wp-parsidate-archive-editor-script';

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
      Assets::url( 'js-admin/archive-block' . $debugName . '.js' ),
      array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
      Assets::getVersion(),
      array( 'in_footer' => true )
    );

    register_block_type( Assets::path( 'blocks/archive/block.json' ), array(
      'render_callback' => array( $this, 'renderBlock' ),
    ) );

    add_action( 'enqueue_block_editor_assets', array( $this, 'editorAssets' ) );
  }

  public function editorAssets(): void {
    wp_add_inline_script(
      self::EDITOR_SCRIPT,
      'window.wppArchiveBlockData = ' . wp_json_encode( array(
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

    $title        = $attributes['title'] ?? esc_html__( 'Archive', 'wp-parsidate' );
    $postType     = $attributes['postType'] ?? 'post';
    $type         = $attributes['type'] ?? 'monthly';
    $displayCount = (bool) ( $attributes['displayCount'] ?? false );
    $isList       = (bool) ( $attributes['displaySelect'] ?? false );
    $blockId      = 'wp-parsidate-archive-block-' . md5( $postType . $type );

    ob_start();

    echo '<div ' . get_block_wrapper_attributes() . '>';

    if ( ! empty( $title ) ) {
      echo '<h2 class="wp-block-wp-parsidate-archive__title">' . esc_html( $title ) . '</h2>';
    }

    do_action( 'wp_parsidate_archive_block_start', $title, $postType, $type, $displayCount, $isList, $blockId );

    if ( $isList ) {
      echo "<select onchange='document.location.href=this.options[this.selectedIndex].value;'> <option value='0'>" . esc_attr( $title ) . '</option>';
    } else {
      echo '<ul>';
    }

    Archive::getPostTypeArchives( array(
      'type'            => $type,
      'format'          => $isList ? 'option' : 'html',
      'post_type'       => $postType,
      'show_post_count' => $displayCount,
    ) );

    echo $isList ? '</select>' : '</ul>';
    do_action( 'wp_parsidate_archive_block_end', $title, $postType, $type, $displayCount, $isList, $blockId );

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
