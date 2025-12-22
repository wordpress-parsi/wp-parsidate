<?php

namespace WPParsidate\Admin;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Helper\Assets;
use WPParsidate\Helper\Nonce;

class AdminAssets {
  public function __construct() {
    add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );
  }

  public function enqueueScripts(): void {
    if ( ! AdminPages::isSettingPage() ) {
      return;
    }
    $pluginVersion = Assets::getVersion();
    $debugName     = WP_PARSI_DEBUG_MODE ? '' : '.min';

    //wp_enqueue_media();
    //wp_enqueue_style( 'wp-color-picker' );
    //wp_enqueue_script( 'wp-color-picker' );

    wp_enqueue_style( WP_PARSI_KEY_SLUG . '-admin-style',
      Assets::url( 'css-admin/admin-style' . $debugName . '.css' ), false, $pluginVersion );

    wp_enqueue_script( WP_PARSI_KEY_SLUG . '-admin',
      Assets::url( 'js-admin/script.min.js' ),
      [
        'jquery',
        'jquery-ui-sortable',
      ], $pluginVersion, [ 'in_footer' => true ] );

    wp_localize_script( WP_PARSI_KEY_SLUG . '-admin', WP_PARSI_KEY_CAP, array(
      'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
      'ajaxNonce'          => Nonce::create(),
      'pageRefreshedAfter' => apply_filters( 'wp_parsidate_settings_page_refreshed_after', 0 ),
      'pageRefreshUrl'     => apply_filters( 'wp_parsidate_settings_page_refresh_url', null ),
      'removeText'         => esc_html__( 'Remove', 'wp-parsidate' ),
      'dtuConfirmDelete'   => esc_html__( 'Are you sure you want to delete this item(s)?', 'wp-parsidate' ),
      'copyText'           => esc_html__( 'Click to copy this text.', 'wp-parsidate' ),
    ) );
  }

  public static function imageUrl( $path ): string {
    return WP_PARSI_URL . 'assets/images/' . $path;
  }
}
