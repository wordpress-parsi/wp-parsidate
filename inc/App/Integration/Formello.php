<?php
/**
 * Makes Formello compatible with WP-Parsidate plugin
 *
 * @package                 WP-Parsidate
 * @subpackage              Plugins/Formello
 */

namespace WPParsidate\App\Integration;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Addons\Addon;

class Formello extends Addon {
  public string $addonID = 'formello';

  public function initAction(): void {
    add_filter( 'wp_parsidate_hook_deactivator_raw_list', [ $this, 'addDateHooksToDeactivator' ] );
  }

  public function addDateHooksToDeactivator( $rawList ): string {
    $rawList .= "\nwp_date,add_details,Formello\Processor\Form";

    return $rawList;
  }

  public function info(): array {
    $svg = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" width="256" height="256">
<path d="M0.13 0.13C85.38 0.13 170.63 0.13 255.88 0.13C255.88 85.38 255.88 170.63 255.88 255.88C170.63 255.88 85.38 255.88 0.13 255.88C0.13 170.63 0.13 85.38 0.13 0.13ZM173.32 81.25C171.43 75.74 164.1 70.85 159.45 67.78C138.28 53.78 108.53 59.64 93.27 79.52C82.04 94.16 82.41 111.13 82.41 128.75C82.41 139.25 82.41 149.75 82.41 160.25C82.41 170.42 80.96 187.68 82.74 196.99C95.82 196.99 108.9 196.99 121.98 196.99C122.24 185.39 122.49 173.8 122.75 162.2C127.03 162.2 131.3 162.2 135.58 162.2C135.58 150.32 135.58 138.44 135.58 126.57C131.21 126.29 126.83 126.02 122.46 125.75C121.78 122.79 122.22 119.31 122.22 116.25C122.22 108.64 122.44 93.02 135.1 98.2C138.36 99.53 138.94 102.55 141.14 104.75C151.86 96.92 162.59 89.08 173.32 81.25Z" fill="#975be5" fill-rule="evenodd" stroke="#975be5" stroke-width="0.25" stroke-linejoin="round"/>
<path d="M173.32 81.25C162.59 89.08 151.86 96.92 141.14 104.75C138.94 102.55 138.36 99.53 135.1 98.2C122.44 93.02 122.22 108.64 122.22 116.25C122.22 119.31 121.78 122.79 122.46 125.75C126.83 126.02 131.21 126.29 135.58 126.57C135.58 138.44 135.58 150.32 135.58 162.2C131.3 162.2 127.03 162.2 122.75 162.2C122.49 173.8 122.24 185.39 121.98 196.99C108.9 196.99 95.82 196.99 82.74 196.99C80.96 187.68 82.41 170.42 82.41 160.25C82.41 149.75 82.41 139.25 82.41 128.75C82.41 111.13 82.04 94.16 93.27 79.52C108.53 59.64 138.28 53.78 159.45 67.78C164.1 70.85 171.43 75.74 173.32 81.25Z" fill="#ffffff" fill-rule="evenodd" stroke="#ffffff" stroke-width="0.25" stroke-linejoin="round"/>
</svg>';

    return array(
      'id'               => $this->addonID,
      'title'            => esc_html__( 'Formello', 'wp-parsidate' ),
      'desc'             => esc_html__( 'ParsiDate integration for Formello', 'wp-parsidate' ),
      'force_enable'     => true,
      'icon'             => $svg,
      'image_link'       => 'https://wordpress.org/plugins/formello/',
      'tags'             => [ esc_html__( 'Form', 'wp-parsidate' ) ],
      'cat'              => 'integration',
      'settings_key'     => $this->addonID,
      'requires_plugins' => [
        'formello/formello.php' => array(
          'is_wp_plugin'   => true,
          'is_free'        => true,
          'plugin_link'    => 'https://wordpress.org/plugins/formello/',
          'function_check' => 'formello_run',
        )
      ]
    );
  }
}
