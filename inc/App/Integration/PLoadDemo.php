<?php
/**
 * Add demo addon of PLoad to WP-Parsidate plugin
 *
 * @package                 WP-Parsidate
 * @subpackage              Plugins/UltimateMember
 */

namespace WPParsidate\App\Integration;

use WPParsidate\Addons\Addon;

defined( 'ABSPATH' ) || exit;

class PLoadDemo extends Addon {
  public string $addonID = 'pload_demo';

  public function info(): array {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="1.67 10.67 234.65 234.65" width="512" height="512" role="img" aria-label="Pload"><title>Pload</title><defs><mask id="m-blacki" maskUnits="userSpaceOnUse" x="-40" y="0" width="320" height="260"><rect x="-40" width="320" height="260" fill="#fff"/><path d="M0 122.5H76.5" stroke="#000" stroke-width="7" stroke-linecap="round"/><path d="M0 140.0H88.0" stroke="#000" stroke-width="8" stroke-linecap="round"/><path d="M0 158.0H68.0" stroke="#000" stroke-width="8" stroke-linecap="round"/><path d="M0 177.0H83.0" stroke="#000" stroke-width="10" stroke-linecap="round"/></mask><clipPath id="c-blacki"><path d="M116 80H148a28 28 0 0 1 0 56H116a4 4 0 0 1-4-4V84a4 4 0 0 1 4-4Z"/></clipPath></defs><g mask="url(#m-blacki)" fill="#000"><path fill-rule="evenodd" d="M56 52a12 12 0 0 1 12-12H148a68 68 0 0 1 0 136H104V204a12 12 0 0 1-12 12H68a12 12 0 0 1-12-12Z M112 72H148a36 36 0 0 1 0 72H112a8 8 0 0 1-8-8V80a8 8 0 0 1 8-8Z"/><path d="M35.0 126H96V136H35.0A5.0 5.0 0 0 1 35.0 126Z"/><path d="M24.0 144H96V154H24.0A5.0 5.0 0 0 1 24.0 144Z"/><path d="M42.5 162H96V172H42.5A5.0 5.0 0 0 1 42.5 162Z"/><path d="M42.9 182H96V192H42.9A5.0 5.0 0 0 1 42.9 182Z"/></g><g clip-path="url(#c-blacki)" fill="#000"><path d="M104 144L132 104L148 122L160 110L192 144Z"/><circle cx="156" cy="96" r="8"/></g></svg>';

    return array(
      'id'               => $this->addonID,
      'title'            => esc_html__( 'PLoad', 'wp-parsidate' ),
      'desc'             => esc_html__( 'Smart Image Optimizer & WebP', 'wp-parsidate' ),
      'force_enable'     => true,
      'is_demo'          => true,
      'is_suggested'     => true,
      'icon'             => $svg,
      'image_link'       => 'https://wordpress.org/plugins/pload/',
      'tags'             => [
        esc_html__( 'Image', 'wp-parsidate' ),
        esc_html__( 'Optimize', 'wp-parsidate' ),
        'WebP'
      ],
      'cat'              => 'recommended',
      'requires_plugins' => [
        'pload/pload.php' => array(
          'is_wp_plugin' => true,
          'is_free'      => true,
          'plugin_link'  => 'https://wordpress.org/plugins/pload/',
          'define_check' => 'PLOAD_FILE'
        )
      ]
    );
  }
}
