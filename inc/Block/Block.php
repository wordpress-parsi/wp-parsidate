<?php
/**
 * ParsiDate Base Block
 *
 * Shared registration and rendering logic for Gutenberg blocks
 */

namespace WPParsidate\Block;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Helper\Assets;
use WPParsidate\Helper\Posts;
use WPParsidate\Settings\Settings;

abstract class Block {
  protected string $editorScript = '';

  protected string $blockJsonPath = '';

  protected string $scriptBaseName = '';

  protected string $dataVar = '';

  protected array $deps = array(
    'wp-blocks',
    'wp-element',
    'wp-block-editor',
    'wp-components',
    'wp-i18n',
    'wp-server-side-render'
  );

  public function __construct() {
    add_action( 'init', array( $this, 'registerBlock' ) );
  }

  public function registerBlock(): void {
    if ( ! function_exists( 'register_block_type' ) ) {
      return;
    }

    $debugName = WP_PARSI_DEBUG_MODE ? '' : '.min';

    wp_register_script(
      $this->editorScript,
      Assets::url( 'js-admin/' . $this->scriptBaseName . $debugName . '.js' ),
      $this->deps,
      Assets::getVersion(),
      array( 'in_footer' => true )
    );

    register_block_type( Assets::path( $this->blockJsonPath ), array(
      'render_callback' => array( $this, 'renderBlock' ),
    ) );

    add_action( 'enqueue_block_editor_assets', array( $this, 'editorAssets' ) );
  }

  public function editorAssets(): void {
    wp_add_inline_script(
      $this->editorScript,
      'window.' . $this->dataVar . ' = ' . wp_json_encode( array(
        'postTypes' => $this->postTypeOptions(),
        'convPermalinks' => (bool) Settings::get( 'conv_permalinks', false ),
      ) ) . ';',
      'before'
    );
  }

  public function renderBlock( $attributes ): string {
    if ( ! Settings::get( 'conv_permalinks', false ) ) {
      return '';
    }

    return $this->renderContent( $attributes );
  }

  abstract protected function renderContent( $attributes ): string;

  protected function postTypeOptions(): array {
    $options = array();

    foreach ( Posts::getTypes() as $name => $label ) {
      $options[] = array( 'label' => $label, 'value' => $name );
    }

    return $options;
  }
}
