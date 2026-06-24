<?php
/**
 * Makes Schema Pro compatible with WP-Parsidate plugin
 *
 * @package                 WP-Parsidate
 * @subpackage              Plugins/SchemaPro
 */

namespace WPParsidate\App\Integration;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Addons\Addon;

class SchemaPro extends Addon {
  public string $addonID = 'wp_schema_pro';

  public function initAction(): void {
    add_filter( 'wp_parsidate_hook_deactivator_raw_list', [ $this, 'addDateHooksToDeactivator' ] );
  }

  public function addDateHooksToDeactivator( $rawList ): string {
    $rawList .= "\nget_the_date,get_post_data,BSF_AIOSRS_Pro_Schema_Template\nwp_date,get_post_data,BSF_AIOSRS_Pro_Schema_Template";

    return $rawList;
  }

  public function info(): array {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" x="0" y="0" version="1.1" viewBox="0.055 -0.346 26.893 24.25"><style>.st3{fill:#fff}</style><path d="M26.7 19.9c0 2.1-1.7 3.8-3.8 3.8s-3.8-1.7-3.8-3.8V3.6c0-2.1 1.7-3.8 3.8-3.8s3.8 1.7 3.8 3.8z" style="fill:#f71568"/><path d="M17.2 19.9c0 2.1-1.7 3.8-3.8 3.8S9.6 22 9.6 19.9v-9.6c0-2.1 1.7-3.8 3.8-3.8s3.8 1.7 3.8 3.8z" style="fill:#ea6ea6"/><path d="M7.8 19.9c0 2.1-1.7 3.8-3.8 3.8S.2 22 .2 19.9V18c0-2.1 1.7-3.8 3.8-3.8s3.8 1.7 3.8 3.8z" style="fill:#e291ba"/></svg>';

    return array(
      'id'               => $this->addonID,
      'title'            => esc_html__( 'Schema Pro', 'wp-parsidate' ),
      'desc'             => esc_html__( 'ParsiDate integration for Schema Pro', 'wp-parsidate' ),
      'force_enable'     => true,
      'icon'             => $svg,
      'image_link'       => 'https://wpschema.com',
      'tags'             => [ esc_html__( 'Schema', 'wp-parsidate' ) ],
      'cat'              => 'seo',
      'settings_key'     => $this->addonID,
      'requires_plugins' => [
        'wp-schema-pro/wp-schema-pro.php' => array(
          'is_wp_plugin' => false,
          'is_free'      => false,
          'plugin_link'  => 'https://wpschema.com',
          'class_check'  => 'BSF_AIOSRS_Pro_Schema',
          'define_check' => 'BSF_AIOSRS_PRO_VER',
        )
      ]
    );
  }
}
