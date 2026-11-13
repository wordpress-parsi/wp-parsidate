<?php
/**
 * Moves the built-in bank gateways of Parsi Date to ParsiGate
 *
 * Phase 1 of removing the gateways from Parsi Date: only sites that switched a
 * gateway on are told, they get a one-click install/activate of ParsiGate, and
 * Parsi Date steps aside for every gateway ParsiGate reports as taken over.
 *
 * @package                 WP-Parsidate
 * @subpackage              Plugins/WooCommerce/PaymentGateways
 */

namespace WPParsidate\App\Integration\WooCommerce;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Admin\AdminPages;
use WPParsidate\Helper\WordPress;

class GatewayMigration {
  /**
   * The Parsi Date version that removes the gateways, shown to users
   */
  public const REMOVAL_VERSION = '7.0';

  public const PARSIGATE_SLUG = 'parsigate';

  public const PARSIGATE_PLUGIN = 'parsigate/ParsiGate.php';

  public const NOTICE_ID = 'wpp_legacy_gateways_migration';

  private WcGateways $gateways;

  public function __construct( WcGateways $gateways ) {
    $this->gateways = $gateways;

    add_filter( 'wp_parsidate_wp_admin_notice', [ $this, 'adminNotices' ] );
    // The general "install ParsiGate" notice says the same thing less precisely.
    add_filter( 'wp_parsidate_wp_admin_notice', [ $this, 'removeGeneralParsigateNotice' ], 20 );
  }

  /**
   * Adds the migration notice for sites that still run a gateway from Parsi Date
   *
   * @param array $notices Notice list
   *
   * @return array Notice list
   */
  public function adminNotices( array $notices ): array {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
      return $notices;
    }

    $pending = $this->gateways->getPendingMigrationGateways();

    if ( empty( $pending ) ) {
      return $notices;
    }

    $message = $this->message( $pending );
    $isLive  = $this->hasLiveGateway( $pending );

    // Everywhere in the admin; can be hidden for a week at most.
    $notices[] = array(
      'id'           => self::NOTICE_ID,
      'message'      => $message,
      'type'         => $isLive ? 'error' : 'warning',
      'dismissible'  => true,
      'dismiss_time' => WEEK_IN_SECONDS,
      'not_screen'   => 'woocommerce_page_wc-settings',
      'class'        => 'wppd-parsigate-migration-notice',
      'inside_tag'   => 'div'
    );

    // On the WooCommerce settings (payments) screen it can not be hidden.
    $notices[] = array(
      'id'         => self::NOTICE_ID . '_wc_settings',
      'message'    => $message,
      'type'       => $isLive ? 'error' : 'warning',
      'screen'     => 'woocommerce_page_wc-settings',
      'class'      => 'wppd-parsigate-migration-notice',
      'inside_tag' => 'div'
    );

    return $notices;
  }

  /**
   * Removes the general ParsiGate promotion while the migration notice is shown
   *
   * @param array $notices Notice list
   *
   * @return array Notice list
   */
  public function removeGeneralParsigateNotice( array $notices ): array {
    $hasMigrationNotice = false;

    foreach ( $notices as $notice ) {
      if ( is_array( $notice ) && ( $notice['id'] ?? '' ) === self::NOTICE_ID ) {
        $hasMigrationNotice = true;
        break;
      }
    }

    if ( ! $hasMigrationNotice ) {
      return $notices;
    }

    return array_values( array_filter( $notices, static function ( $notice ) {
      return ! is_array( $notice ) || ( $notice['id'] ?? '' ) !== 'parsigate_demo_install';
    } ) );
  }

  /**
   * @param array $pending Gateway codes still running from Parsi Date
   *
   * @return string Notice HTML
   */
  private function message( array $pending ): string {
    $names = array_intersect_key( $this->gateways->gateways(), array_flip( $pending ) );
    $names = esc_html( implode( '، ', $names ) );
    $count = count( $pending );

    $title = $count > 1 ? 'درگاه‌های پرداخت فروشگاه شما به‌زودی از پارسی‌دیت حذف می‌شوند' : 'درگاه پرداخت فروشگاه شما به‌زودی از پارسی‌دیت حذف می‌شود';

    $message = '<strong>' . $title . '</strong><br/>';
    $message .= sprintf(
      'درگاه %1$s روی سایت شما از افزونه‌ی پارسی‌دیت اجرا می‌شود. درگاه‌های بانکی در نسخه‌ی %2$s از پارسی‌دیت حذف می‌شوند و از آن به بعد فقط در افزونه‌ی رایگان <span class="plugin-name">پارسی‌گیت</span> در دسترس هستند. ',
      '<strong>' . $names . '</strong>',
      esc_html( self::REMOVAL_VERSION )
    );
    $message .= 'اگر تا آن زمان پارسی‌گیت را فعال نکنید، این روش پرداخت از صفحه‌ی پرداخت فروشگاه شما حذف خواهد شد.<br/>';
    $message .= 'پارسی‌گیت همین درگاه‌ها را دارد و تنظیمات فعلی شما (مثل شماره ترمینال) را خودکار منتقل می‌کند؛ لازم نیست چیزی را دوباره وارد کنید.<br/>';

    if ( in_array( 'pasargad', $pending, true ) ) {
      $message .= '<strong>درگاه پاسارگاد:</strong> پارسی‌گیت از سرویس جدید بانک پاسارگاد استفاده می‌کند که به نام کاربری و رمز عبور نیاز دارد. آن‌ها را از بانک پاسارگاد دریافت کنید، سپس درگاه پاسارگاد را در تنظیمات پارسی‌گیت روشن کنید و در تنظیمات پرداخت ووکامرس وارد کنید.<br/>';
    }

    $message .= '<br/>';
    $message .= '<div class="wppd-admin-notice-actions">';
    $message .= $this->actionButton();
    $message .= ' <a href="https://wordpress.org/plugins/' . self::PARSIGATE_SLUG . '/" class="wppd-button wppd-button-secondary" target="_blank" rel="noopener">درباره‌ی پارسی‌گیت</a>';
    $message .= '</div>';

    return $message;
  }

  /**
   * The next step for this site: install, activate or update ParsiGate
   *
   * @return string Button HTML, empty if the user can not do it
   */
  private function actionButton(): string {
    $installed = file_exists( WP_PLUGIN_DIR . '/' . self::PARSIGATE_PLUGIN );

    if ( ! $installed ) {
      if ( ! current_user_can( 'install_plugins' ) ) {
        return '';
      }

      $url = wp_nonce_url(
        self_admin_url( 'update.php?action=install-plugin&plugin=' . self::PARSIGATE_SLUG ),
        'install-plugin_' . self::PARSIGATE_SLUG
      );

      return '<a href="' . esc_url( $url ) . '" class="wppd-button wppd-button-primary">نصب پارسی‌گیت</a>';
    }

    if ( ! WordPress::isPluginActivated( self::PARSIGATE_PLUGIN ) ) {
      if ( ! current_user_can( 'activate_plugins' ) ) {
        return '';
      }

      $url = wp_nonce_url(
        self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( self::PARSIGATE_PLUGIN ) ),
        'activate-plugin_' . self::PARSIGATE_PLUGIN
      );

      return '<a href="' . esc_url( $url ) . '" class="wppd-button wppd-button-primary">فعال‌سازی پارسی‌گیت</a>';
    }

    // Active and able to take over (ParsiGate 1.2+): what is left needs the user,
    // e.g. Pasargad credentials or a gateway they switched off in ParsiGate.
    if ( class_exists( '\ParsiGate\LegacyParsidateGateways', false ) ) {
      return '<a href="' . esc_url( AdminPages::link( [ 'tab' => 'parsigate' ] ) ) . '" class="wppd-button wppd-button-primary">تنظیمات پارسی‌گیت</a>';
    }

    // Active, but this ParsiGate version can not take the gateways over yet.
    if ( ! current_user_can( 'update_plugins' ) ) {
      return '';
    }

    return '<a href="' . esc_url( self_admin_url( 'plugins.php?plugin_status=upgrade' ) ) . '" class="wppd-button wppd-button-primary">به‌روزرسانی پارسی‌گیت</a>';
  }

  /**
   * @param array $codes Gateway codes
   *
   * @return bool True, if customers can pay with at least one of them
   */
  private function hasLiveGateway( array $codes ): bool {
    foreach ( $codes as $code ) {
      if ( $this->gateways->isLiveInWooCommerce( $code ) ) {
        return true;
      }
    }

    return false;
  }
}
