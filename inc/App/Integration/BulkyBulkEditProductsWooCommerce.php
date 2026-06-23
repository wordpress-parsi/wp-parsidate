<?php
/**
 * Makes Bulky (Bulk Edit Products for WooCommerce) compatible with WP-Parsidate plugin
 *
 * @package                 WP-Parsidate
 * @subpackage              Plugins/Elementor
 */

namespace WPParsidate\App\Integration;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Addons\Addon;

class BulkyBulkEditProductsWooCommerce extends Addon {
  public string $addonID = 'bulky_bulk_edit_products_woo';

  public function initAction(): void {
    add_filter( 'wp_parsidate_hook_deactivator_raw_list', [ $this, 'addDateHooksToDeactivator' ] );
  }

  public function addDateHooksToDeactivator( $rawList ): string {
    $rawList .= "\nwp_date,get_product_data,BULKY\Admin\Handle_Product\ndate_i18n,get_product_data,BULKY\Admin\Handle_Product";

    return $rawList;
  }

  public function info(): array {
    return array(
      'id'               => $this->addonID,
      'title'            => esc_html__( 'Bulky – Bulk Edit Products for WooCommerce', 'wp-parsidate' ),
      'desc'             => esc_html__( 'ParsiDate integration for Bulky (Bulk Edit Products for WooCommerce)', 'wp-parsidate' ),
      'force_enable'     => true,
      'image_link'       => 'https://wordpress.org/plugins/bulky-bulk-edit-products-for-woo/',
      'tags'             => [ esc_html__( 'WooCommerce', 'wp-parsidate' ) ],
      'cat'              => 'ecommerce',
      'settings_key'     => $this->addonID,
      'requires_plugins' => [
        'bulky-bulk-edit-products-for-woo/bulky-bulk-edit-products-for-woo.php' => array(
          'is_wp_plugin' => true,
          'is_free'      => true,
          'plugin_link'  => 'https://wordpress.org/plugins/bulky-bulk-edit-products-for-woo/',
          'class_check'  => 'BULKY\Admin\Admin'
        )
      ]
    );
  }
}
