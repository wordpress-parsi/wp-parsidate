<?php
/**
 * Plugin assets
 *
 * Used for fix admin and editor style
 */

namespace WPParsidate\App;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Core\Names;
use WPParsidate\Helper\{Assets, Param};
use WPParsidate\Settings\Settings;

class AppAssets {
  public function __construct() {
    add_action( 'admin_enqueue_scripts', array( $this, 'adminEnqueueScripts' ) );
    add_filter( 'admin_init', [ $this, 'fixTinyMceFont' ], PHP_INT_MAX );
    add_filter( 'wp_theme_json_data_theme', [ $this, 'addFontToThemeJson' ] );
    add_action( 'admin_print_styles-plugin-editor.php', [ $this, 'fixCodeEditor' ] );
    add_action( 'admin_print_styles-theme-editor.php', [ $this, 'fixCodeEditor' ] );
    add_action( 'wpp_jalali_datepicker_enqueued', [ $this, 'localizeMonthsName' ] );
    add_action( 'enqueue_block_editor_assets', [ $this, 'blockEditorAssets' ] );
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
    if ( ! Settings::get( 'persian_date', false ) || ! version_compare( get_bloginfo( 'version' ), '5.0.0', '>=' ) ) {
      return;
    }

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

    wp_enqueue_style( WP_PARSI_KEY_SLUG . '-admin-fix', Assets::url( 'css-admin/admin-fix' . $debugName . '.css' )
      , false, $pluginVersion );
  }

  /**
   * Change theme JSON data for global styles and settings.
   *
   * @param \WP_Theme_JSON_Data $themeJson Class to access and update the underlying data.
   *
   * @return \WP_Theme_JSON_Data
   *
   * @since 6.2
   */
  public function addFontToThemeJson( $themeJson ): \WP_Theme_JSON_Data {
    if ( ! Settings::get( 'enable_fonts', false ) ) {
      return $themeJson;
    }

    $data = $themeJson->get_data();

    $fontFamilies = $data['settings']['typography']['fontFamilies']['theme'] ?? [];
    $newFont      = array(
      "name"       => "Vazirmatn",
      "slug"       => "vazirmatn",
      "fontFamily" => "Vazirmatn, sans-serif",
      "fontFace"   => [
        [
          "src"         => [
            Assets::url( 'fonts/Vazirmatn-VariableFont_wght.woff2' )
          ],
          "fontWeight"  => "100 900",
          "fontStyle"   => "normal",
          'fontDisplay' => 'swap',
          "fontFamily"  => "Vazirmatn"
        ]
      ]
    );

    // Add new font
//    $data['settings']['typography']['fontFamilies']          = array_merge( $fontFamilies, array( $newFont ) );
    $data['settings']['typography']['fontFamilies']['theme'] = array_merge( $fontFamilies, array( $newFont ) );

    $variableVazirmatnFont = "var:preset|font-family|vazirmatn !important";

    // Body font family
    if ( isset( $data['styles']['typography']['fontFamily'] ) ) {
      $data['styles']['typography']['fontFamily'] = $variableVazirmatnFont;
    } else {
      $data['styles']['typography'] = array(
        "fontFamily" => $variableVazirmatnFont,
        "fontSize"   => "1.2rem",
        "lineHeight" => "1.7",
        "fontStyle"  => "normal",
        "fontWeight" => "400"
      );
    }

    // Site title
    if ( isset( $data['styles']['blocks']['core/site-title']['typography']['fontFamily'] ) ) {
      $data['styles']['blocks']['core/site-title']['typography']['fontFamily'] = $variableVazirmatnFont;
    } else {
      $data['styles']['blocks']['core/site-title'] = array(
        "fontFamily" => $variableVazirmatnFont,
        "fontSize"   => "1.2rem",
        "fontStyle"  => "normal",
        "fontWeight" => "600"
      );
    }

    // Heading font family
    if ( isset( $data['styles']['elements']['heading']['typography']['fontFamily'] ) ) {
      $data['styles']['elements']['heading']['typography']['fontFamily'] = $variableVazirmatnFont;
    } else {
      $data['styles']['elements']['heading'] = array(
        "fontFamily" => $variableVazirmatnFont,
        "fontSize"   => "1.2rem",
        "fontStyle"  => "normal",
        "fontWeight" => "600"
      );
    }

    // Quote font family
    if ( isset( $data['styles']['blocks']['core/quote']['typography']['fontFamily'] ) ) {
      $data['styles']['blocks']['core/quote']['typography']['fontFamily'] = $variableVazirmatnFont;
    }
    if ( isset( $data['styles']['blocks']['core/quote']['variations']['plain']['typography']['fontFamily'] ) ) {
      $data['styles']['blocks']['core/quote']['variations']['plain']['typography']['fontFamily'] = $variableVazirmatnFont;
    }
    if ( isset( $data['styles']['blocks']['core/pullquote']['typography']['fontFamily'] ) ) {
      $data['styles']['blocks']['core/pullquote']['typography']['fontFamily'] = $variableVazirmatnFont;
    }
    if ( isset( $data['styles']['blocks']['core/pullquote']['elements']['cite']['typography']['fontFamily'] ) ) {
      $data['styles']['blocks']['core/pullquote']['elements']['cite']['typography']['fontFamily'] = $variableVazirmatnFont;
    }

    return $themeJson->update_with( $data );
  }

  /**
   * Fixes TinyMCE font
   *
   * @return              void
   * @since               2.0
   */
  public function fixTinyMceFont(): void {
    if ( Settings::get( 'enable_fonts', false ) ) {
      //$pluginVersion = Assets::getVersion();
      $debugName = WP_PARSI_DEBUG_MODE ? '' : '.min';

      // add_editor_style may fail when used with a URL, especially if the site is running on localhost.
      // Reference: /wp-includes/block-editor.php, get_block_editor_theme_styles function, wp_remote_get
      // add_editor_style( Assets::url( 'css-admin/tinymce-editor' . $debugName . '.css?v=' . $pluginVersion ) );

      add_editor_style( '../../plugins/wp-parsidate/assets/css-admin/tinymce-editor' . $debugName . '.css' );
    }
  }

  public function adminEnqueueScripts(): void {
    global $pagenow;
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

    if ( $pagenow == 'edit.php' ) {
      $postType = Param::get( 'post_type', 'post' );

      if ( ! apply_filters( 'disable_months_dropdown', false, $postType ) ) {
        wp_add_inline_script( WP_PARSI_KEY_SLUG . '-admin', "jQuery(document).ready(function ($) {\$('select[name=m]').hide()})" );
      }
    }
  }
}
