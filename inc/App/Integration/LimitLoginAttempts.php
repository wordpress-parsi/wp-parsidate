<?php
/**
 * Makes Limit Login Attempts Security compatible with WP-Parsidate plugin
 *
 * @package                 WP-Parsidate
 * @subpackage              Plugins/Elementor
 */

namespace WPParsidate\App\Integration;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Addons\Addon;

class LimitLoginAttempts extends Addon {
  public string $addonID = 'limit_login_attempts';

  public function initAction(): void {
    add_filter( 'wp_parsidate_hook_deactivator_raw_list', [ $this, 'addDateHooksToDeactivator' ] );
  }

  public function addDateHooksToDeactivator( $rawList ): string {
    $rawList .= "\ndate_i18n,dashboard_widgets_content,LLAR\Core\LimitLoginAttempts\ndate_i18n,options_page,LLAR\Core\LimitLoginAttempts\nwp_date,dashboard_widgets_content,LLAR\Core\LimitLoginAttempts\nwp_date,options_page,LLAR\Core\LimitLoginAttempts";

    return $rawList;
  }

  public function info(): array {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 18094 18094"><path fill="#1A3C4C" d="M10409 7968c-217 67-409 197-552 374-142 177-228 393-247 619-66 743 328 788 503 1269 138 378 70 1250 76 1711 6 547 422 1009 928 639 352-259 249-966 247-1492 0-944 19-782 413-1373 590-888-296-2099-1365-1746h-4z"/><path fill="#3DC1CE" d="M9998 3895c-5927 945-5996 9052-561 10419 326 83 1038 215 1386 151 492-92 538-769 112-918-457-160-1341 33-2466-689-3023-1947-2703-6433 752-7761 880-336 1846-377 2751-117 381 114 745 281 1080 496 232 150 701 655 1078 280 628-625-904-1335-1397-1541-866-353-1812-463-2736-319z"/><path fill="#FF9A26" d="M10178 5662c-3437 642-3681 4706-1688 6196 279 206 702 159 807-194 206-685-1278-895-1098-2751 29-348 130-687 296-994 166-308 393-578 668-794s591-373 929-461 691-106 1036-52c1811 258 2796 2405 1735 3948-153 222-584 626-619 728-129 360 238 1316 1291 0 1944-2430-230-6211-3358-5627z"/><path fill="#3DC1CE" d="M4022 10223c-343 232-192 587-34 1040 576 1687 1772 3091 3345 3928 1574 837 3407 1043 5127 578 608-155 466-1105-236-928-2708 722-5702-542-6997-3137-277-559-458-1981-1205-1480zM11331 2105c-1960-163-3857 576-5300 1785-363 379 103 1046 765 573 1517-1182 2715-1400 4511-1415 678-6 796-865 24-942z"/><rect fill="none" width="18094" height="18094" rx="4996" ry="4996"/></svg>';

    return array(
      'id'               => $this->addonID,
      'title'            => esc_html__( 'Limit Login Attempts Security', 'wp-parsidate' ),
      'desc'             => esc_html__( 'ParsiDate integration for Limit Login Attempts Security', 'wp-parsidate' ),
      'force_enable'     => true,
      'icon'             => $svg,
      'tags'             => [ esc_html__( 'Security', 'wp-parsidate' ) ],
      'cat'              => 'security',
      'settings_key'     => $this->addonID,
      'requires_plugins' => [
        'limit-login-attempts-reloaded/limit-login-attempts-reloaded.php' => array(
          'is_wp_plugin' => true,
          'is_free'      => true,
          'plugin_link'  => 'https://wordpress.org/plugins/limit-login-attempts-reloaded/',
          'class_check'  => 'LLAR\Core\LimitLoginAttempts',
          'define_check' => 'LLA_PLUGIN_URL',
        )
      ]
    );
  }
}
