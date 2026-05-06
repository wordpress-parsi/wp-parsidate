<?php
/**
 * Plugin hooks
 *
 * Load local text domain, Add starter notice, Change some links
 */

namespace WPParsidate\Plugin;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Helper\Param;
use WPParsidate\Settings\Settings;

class Plugin {
  public function __construct() {
    add_filter( 'plugin_action_links_' . plugin_basename( WP_PARSI_ROOT ), [ $this, 'pluginActionLink' ] );
    add_filter( 'login_headerurl', [ $this, 'changeLoginLink' ], 10, 2 );
    add_action( 'admin_notices', [ $this, 'activationAdminNotice' ] );
    add_action( 'admin_init', [ $this, 'dismissActivationNotice' ] );
    add_action( 'init', [ $this, 'loadTextDomain' ], - 1 );
    add_filter( 'http_request_args', [ $this, 'limitWpParsiTimeout' ], 10, 2 );
  }

  /**
   * Limits the timeout for requests to wp-parsi.com to prevent dashboard lag.
   *
   * @param array  $args Request arguments.
   * @param string $url  Request URL.
   * @return array Modified request arguments.
   */
  public function limitWpParsiTimeout( array $args, string $url ): array {
    if ( str_contains( $url, 'wp-parsi.com' ) ) {
      $args['timeout'] = 3;
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
    load_textdomain(
      'wp-parsidate',
      WP_PARSI_DIR . 'languages/wp-parsidate-' . determine_locale() . '.mo'
    );
  }

  /**
   * Notice for the activation.
   * Added dismiss feature.
   *
   * @return              void
   * @author              Ehsaan
   */
  public function activationAdminNotice(): void {
    $dismissed = Settings::get( 'activation_admin_notice_dismissed', false );

    if ( ! $dismissed && ( ! isset( $_GET['page'] ) || WP_PARSI_KEY_SLUG !== Param::get( 'page' ) ) &&
         ! Settings::get( 'persian_date', false ) ) {
      $dismiss_url = wp_nonce_url( add_query_arg( 'wpp-action', 'dismiss-active-notice' ), 'wpp_dismiss_notice' );

      /* translators: 1: ParsiDate settings link, 2: Dismiss notice link */
      $message = __( '<div class="updated wpp-message"><p>ParsiDate activated, you may need to configure it to work properly. <a href="%1$s">Go to configuration page</a> &ndash; <a href="%2$s">Dismiss</a></p></div>',
        'wp-parsidate' );
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo sprintf( $message,
        esc_url_raw( menu_page_url( WP_PARSI_KEY_SLUG, false ) ),
        esc_url_raw( $dismiss_url ),
      );
    }
  }

  /**
   * Dismiss the notice action
   *
   * @return              void
   * @author              Ehsaan
   */
  public function dismissActivationNotice(): void {
    if ( isset( $_GET['wpp-action'] ) && Param::get( 'wpp-action' ) === 'dismiss-active-notice' ) {
      check_admin_referer( 'wpp_dismiss_notice' );
      Settings::save( 'activation_admin_notice_dismissed', true );
    }
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
   * @param  array  $links
   *
   * @return          array
   */
  public static function pluginActionLink( $links ): array {
    $links[] = '<a href="' . menu_page_url( WP_PARSI_KEY_SLUG, false ) . '">' .
               esc_html__( 'Settings', 'wp-parsidate' ) . '</a>';

    return $links;
  }
}
