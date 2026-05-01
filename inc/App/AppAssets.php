<?php
/**
 * Plugin assets
 *
 * Used for fix admin and editor style
 */

namespace WPParsidate\App;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Core\Names;
use WPParsidate\Helper\Assets;
use WPParsidate\Settings\Settings;

class AppAssets {
  public function __construct() {
    add_action( 'admin_enqueue_scripts', array( $this, 'adminEnqueueScripts' ) );
    add_filter( 'admin_init', [ $this, 'fixTinyMceFont' ], 9999999 );
    add_action( 'admin_print_styles-plugin-editor.php', [ $this, 'fixCodeEditor' ] );
    add_action( 'admin_print_styles-theme-editor.php', [ $this, 'fixCodeEditor' ] );
    add_action( 'wpp_jalali_datepicker_enqueued', [ $this, 'localizeMonthsName' ] );

    if ( Settings::get( 'persian_date', false ) && version_compare( get_bloginfo( 'version' ), '5.0.0', '>=' ) ) {
      add_action( 'enqueue_block_editor_assets', [ $this, 'blockEditorAssets' ] );
    }
  }

  /**
   * Enqueue Gutenberg Jalali Calendar assets for backend editor.
   *
   * @uses {wp-plugins}
   * @uses {wp-i18n} to internationalize the block's text.
   * @uses {wp-compose}
   * @uses {wp-components}
   * @uses {wp-element} for WP Element abstraction — structure of blocks.
   * @uses {wp-editor} for WP editor styles.
   * @uses {wp-edit-post} to internationalize the block's text.
   * @uses {wp-data}
   * @uses {wp-date}
   * @since 3.0.0
   * @author              Alireza Dabiri Nejad / Alirdn
   */
  public function blockEditorAssets(): void {
    $pluginVersion = Assets::getVersion();

    wp_enqueue_script( 'wpp_gutenberg_jalali_calendar_editor_scripts',
      Assets::url( 'js-admin/gutenberg-jalali-calendar.build.js' ),
      array(
        'wp-plugins',
        'wp-i18n',
        'wp-compose',
        'wp-components',
        'wp-element',
        'wp-editor',
        'wp-edit-post',
        'wp-data',
        'wp-date'
      ),
      $pluginVersion,
      [ 'in_footer' => true ]
    );

    wp_enqueue_style(
      'wpp_gutenberg_jalali_calendar_editor_styles',
      Assets::url( 'css-admin/gutenberg-jalali-calendar.build.css' ),
      array( 'wp-edit-blocks' ), $pluginVersion
    );
  }

  /**
   * Localize name of months after date picker enqueued
   *
   * @since 4.0.1
   */
  public function localizeMonthsName(): void {
    $months_name = Names::getMonths();

    // Remove first item (null string) from name of months array
    array_shift( $months_name );

    wp_localize_script( 'wpp_jalali_datepicker', 'WPP_I18N',
      array(
        'months' => $months_name
      )
    );
  }

  /**
   * Fixes themes and plugins RTL style, they should be LTR
   *
   * @return              void
   * @since               2.0
   */
  public function fixCodeEditor(): void {
    $pluginVersion = Assets::getVersion();
    $debugName     = WP_PARSI_DEBUG_MODE ? '' : '.min';

    wp_enqueue_style( 'functions', Assets::url( 'css-admin/admin-fix' . $debugName . '.css' )
      , false, $pluginVersion );
  }

  /**
   * Fixes TinyMCE font
   *
   * @return              void
   * @since               2.0
   */
  public function fixTinyMceFont(): void {
    if ( Settings::get( 'enable_fonts', false ) ) {
      $pluginVersion = Assets::getVersion();
      $debugName     = WP_PARSI_DEBUG_MODE ? '' : '.min';

      add_editor_style( Assets::url( 'css-admin/tinymce-editor' . $debugName . '.css?v=' . $pluginVersion ) );
    }
  }

  public function adminEnqueueScripts(): void {
    $pluginVersion = Assets::getVersion();
    $debugName     = WP_PARSI_DEBUG_MODE ? '' : '.min';

    if ( Settings::get( 'enable_fonts', false ) ) {
      wp_enqueue_style( WP_PARSI_KEY_SLUG . '-vazir-font',
        Assets::url( 'css-admin/vazir-font' . $debugName . '.css' ), false, $pluginVersion );
    }

    wp_enqueue_script( WP_PARSI_KEY_SLUG . '-admin', Assets::url( 'js-admin/admin' . $debugName . '.js' ), false,
      $pluginVersion, [ 'in_footer' => true ] );
    wp_localize_script(
      WP_PARSI_KEY_SLUG . '-admin',
      'WPP_I18N',
      array( 'months' => Names::getMonths() )
    );
  }
}
