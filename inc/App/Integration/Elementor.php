<?php
/**
 * Makes Elementor compatible with WP-Parsidate plugin
 *
 * @package                 WP-Parsidate
 * @subpackage              Plugins/Elementor
 */

namespace WPParsidate\App\Integration;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Addons\Addon;

class Elementor extends Addon {
  public string $addonID = 'elementor';

  public function initAction(): void {
    add_action( "elementor/editor/after_enqueue_styles", [ $this, 'fixEditorStyle' ] );
  }

  public function fixEditorStyle(): void {
    $wpp_elementor_css = "
      body, .tipsy-inner, .elementor-button, .elementor-panel {
        font-family: Tahoma,Arial,Helvetica,Verdana,sans-serif;
      }
      .tipsy-inner {
        font-size: small;
      }";
    $wpp_elementor_css = apply_filters( "wpp_elementor_css", $wpp_elementor_css );
    wp_add_inline_style( "elementor-editor", $wpp_elementor_css );
  }

  public function info(): array {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 100 100"><path fill="#010051" d="M50 0C22.383 0 0 22.383 0 50c0 27.608 22.383 50 50 50s50-22.383 50-50C99.99 22.383 77.608 0 50 0M37.502 70.827h-8.329V29.164h8.33zm33.324 0H45.831v-8.33h24.995zm0-16.667H45.831V45.83h24.995zm0-16.667H45.831v-8.329h24.995z"/></svg>';

    return array(
      'id'               => $this->addonID,
      'title'            => esc_html__( 'Elementor', 'wp-parsidate' ),
      'desc'             => esc_html__( 'ParsiDate integration for Elementor', 'wp-parsidate' ),
      'force_enable'     => false,
      'icon'             => $svg,
      'tags'             => [ esc_html__( 'Elementor', 'wp-parsidate' ) ],
      'cat'              => 'page_builder',
      'settings_key'     => $this->addonID,
      'requires_plugins' => [
        'elementor/elementor.php' => array(
          'is_wp_plugin' => true,
          'is_free'      => true,
          'plugin_link'  => 'https://wordpress.org/plugins/elementor/',
          'define_check' => 'ELEMENTOR_PLUGIN_BASE'
        )
      ]
    );
  }
}
