<?php
/**
 * ParsiDate Archive Block
 *
 * Register the Gutenberg block version of the archive widget
 */

namespace WPParsidate\Block;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Core\Archive;

class ArchiveBlock extends Block {
  protected string $editorScript = 'wp-parsidate-archive-editor-script';

  protected string $blockJsonPath = 'blocks/archive/block.json';

  protected string $scriptBaseName = 'archive-block';

  protected string $dataVar = 'wppArchiveBlockData';

  protected function renderContent( $attributes ): string {
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
}
