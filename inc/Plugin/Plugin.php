<?php
/**
 * Plugin hooks
 *
 * Load local text domain, Add starter notice, Change some links
 */

namespace WPParsidate\Plugin;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Admin\AdminPages;
use WPParsidate\Settings\Settings;

class Plugin {
  public function __construct() {
    add_filter( 'plugin_action_links_' . plugin_basename( WP_PARSI_ROOT ), [ $this, 'pluginActionLink' ] );
    add_filter( 'login_headerurl', [ $this, 'changeLoginLink' ], 10, 2 );
    add_filter( 'wp_parsidate_wp_admin_notice', [ $this, 'adminNotice' ] );
    add_action( 'init', [ $this, 'loadTextDomain' ], - 1 );
    add_filter( 'http_request_args', [ $this, 'limitWpParsiTimeout' ], 10, 2 );
  }

  /**
   * Limits the timeout for requests to wp-parsi.com to prevent dashboard lag.
   *
   * @param array $args Request arguments.
   * @param string $url Request URL.
   *
   * @return array Modified request arguments.
   */
  public function limitWpParsiTimeout( array $args, string $url ): array {
    if ( str_contains( $url, 'wp-parsi.com' ) ) {
      $args['timeout'] = 6;
    }

    return $args;
  }

  /**
   * Load plugin translations from the local languages directory.
   *
   * Always loads the local .mo file so that newly added/translated strings
   * are available immediately, even before they're published on translate.wordpress.org.
   * Uses determine_locale() instead of hardcoding 'fa_IR' for multi-locale flexibility.
   */
  public function loadTextDomain(): void {
    if ( __( 'WordPress', 'wp-parsidate' ) !== 'وردپرس' || Settings::get( 'local_text_domain', false ) ) {
      load_textdomain(
        'wp-parsidate',
        WP_PARSI_DIR . '/languages/wp-parsidate-' . determine_locale() . '.mo'
      );
    }
  }

  /**
   * Notice for the activation.
   *
   * @param array $notices Notice array list
   *
   * @return array Notice array list
   */
  public function adminNotice( array $notices ): array {
    if ( ! Settings::get( 'persian_date', false ) ) {
      $notices[] = array(
        'id'           => 'persian_date_activation',
        'message'      => sprintf(
          __( 'ParsiDate activated, you may need to configure it to work properly. <a href="%s">Go to configuration page</a>', 'wp-parsidate' ),
          esc_url_raw( AdminPages::link( [ 'tab' => 'core' ] ) )
        ),
        'type'         => 'warning',
        'dismissible'  => true,
        'dismiss_time' => MONTH_IN_SECONDS,
        'not_page'     => WP_PARSI_KEY_SLUG
      );
    }

    return $notices;
  }

  /**
   * Change login header url in wp-login.php & Widget primary link
   *
   * @return              string
   */
  public function changeLoginLink(): string {
    return 'https://wp-parsi.com';
  }

  /**
   * Add setting link to admin plugins
   *
   * @param array $links
   *
   * @return          array
   */
  public static function pluginActionLink( $links ): array {
    $links[] = '<a href="' . menu_page_url( WP_PARSI_KEY_SLUG, false ) . '">' .
               esc_html__( 'Settings', 'wp-parsidate' ) . '</a>';

    return $links;
  }
}
