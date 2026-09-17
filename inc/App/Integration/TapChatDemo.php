<?php
/**
 * Add demo addon of TapChat to WP-Parsidate plugin
 *
 * @package                 WP-Parsidate
 * @subpackage              Plugins/UltimateMember
 */

namespace WPParsidate\App\Integration;

use WPParsidate\Addons\Addon;
use WPParsidate\Admin\AdminPages;
use WPParsidate\Helper\WordPress;

defined( 'ABSPATH' ) || exit;

class TapChatDemo extends Addon {
  public string $addonID = 'tapchat_demo';

  public function info(): array {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#28ad5e" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" aria-hidden="true" viewBox="0 0 24 24"><path d="M2.992 16.342a2 2 0 0 1 .094 1.167l-1.065 3.29a1 1 0 0 0 1.236 1.168l3.413-.998a2 2 0 0 1 1.099.092 10 10 0 1 0-4.777-4.719"/></svg>';

    return array(
      'id'               => $this->addonID,
      'title'            => esc_html__( 'Tap Chat', 'wp-parsidate' ),
      'desc'             => esc_html__( 'Floating Contact Button', 'wp-parsidate' ),
      'force_enable'     => true,
      'is_demo'          => true,
      'is_suggested'     => true,
      'icon'             => $svg,
      'image_link'       => 'https://wordpress.org/plugins/tap-chat/',
      'tags'             => [ esc_html__( 'Chat', 'wp-parsidate' ) ],
      'cat'              => 'recommended',
      'requires_plugins' => [
        'tap-chat/tap-chat.php' => array(
          'is_wp_plugin' => true,
          'is_free'      => true,
          'plugin_link'  => 'https://wordpress.org/plugins/tap-chat/',
          'define_check' => 'TAP_CHAT_PLUGIN_FILE'
        )
      ]
    );
  }
}
