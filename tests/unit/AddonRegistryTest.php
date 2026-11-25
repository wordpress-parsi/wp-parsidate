<?php

namespace WPParsidate\Tests\Unit;

use WP_UnitTestCase;
use WPParsidate\Addons\Addon;
use WPParsidate\App\Integration\WooCommerce as WooCommerceAddon;
use WPParsidate\App\Integration\WooCommerce\WcGateways;
use WPParsidate\Settings\Settings;

/**
 * Release smoke test across the shipped addons: every addon file must be
 * wired into Integration, and every addon must offer a unique entry for the
 * add-ons page.
 */
class AddonRegistryTest extends WP_UnitTestCase {

  /**
   * @return string[]
   */
  private function addonClasses(): array {
    $classes = array();

    foreach ( glob( WP_PARSI_DIR . '/inc/App/Integration/*.php' ) as $file ) {
      $class = 'WPParsidate\\App\\Integration\\' . basename( $file, '.php' );

      if ( is_subclass_of( $class, Addon::class ) ) {
        $classes[] = $class;
      }
    }

    sort( $classes );

    return $classes;
  }

  public function test_every_addon_class_is_registered(): void {
    $loader = file_get_contents( WP_PARSI_DIR . '/inc/App/Integration/Integration.php' );

    preg_match_all( '/new\s+([A-Za-z0-9_]+)\s*\(/', $loader, $matches );

    $registered = array_map(
      static function ( $name ) {
        return 'WPParsidate\\App\\Integration\\' . $name;
      },
      $matches[1]
    );

    sort( $registered );

    $this->assertSame(
      $this->addonClasses(),
      $registered,
      'Integration.php must instantiate every addon file and nothing else.'
    );
  }

  public function test_every_addon_exposes_a_unique_id_and_title(): void {
    $seen = array();

    foreach ( $this->addonClasses() as $class ) {
      $addon = new $class();
      $info  = $addon->info();

      $this->assertNotSame( '', $addon->addonID, $class . ' must declare an addonID' );
      $this->assertSame( $addon->addonID, $info['id'] ?? null, $class . ' info() id must match addonID' );
      $this->assertNotEmpty( $info['title'] ?? '', $class . ' must have a title for the add-ons page' );

      // Addon::$addonInfo is keyed by addonID, so duplicates overwrite silently.
      $this->assertArrayNotHasKey( $addon->addonID, $seen, $class . ' reuses the ID ' . $addon->addonID );

      $seen[ $addon->addonID ] = $class;
    }
  }

  public function test_addons_with_a_settings_screen_return_a_valid_section(): void {
    $checked = 0;

    foreach ( $this->addonClasses() as $class ) {
      if ( ! method_exists( $class, 'addSectionSettings' ) ) {
        continue;
      }

      $addon    = new $class();
      $sections = $addon->addSectionSettings( array() );

      $this->assertArrayHasKey( $addon->addonID, $sections, $class . ' must key its settings section by its addonID' );
      $this->assertSettingsIdsMatchKeys( $class, $sections[ $addon->addonID ]['settings'] ?? array() );

      $checked ++;
    }

    $this->assertGreaterThan( 0, $checked, 'no addon declared a settings screen' );
  }

  /**
   * Addons without addSectionSettings() (WooCommerce) render their screen from
   * the wp_parsidate_<id>_settings filter, which initM1Action() registers.
   */
  public function test_tab_settings_use_ids_that_match_their_keys(): void {
    $checked = 0;

    foreach ( $this->addonClasses() as $class ) {
      if ( ! method_exists( $class, 'addTabSettings' ) ) {
        continue;
      }

      $addon = new $class();
      $hook  = 'wp_parsidate_' . $addon->addonID . '_settings';

      $addon->initM1Action();
      $section = apply_filters( $hook, array() );
      remove_filter( $hook, [ $addon, 'addTabSettings' ] );

      $this->assertSame(
        $addon->addonID,
        $section['settings_key'] ?? null,
        $class . ' must save its tab settings under its addonID'
      );
      $this->assertSettingsIdsMatchKeys( $class, $section['settings'] ?? array() );

      $checked ++;
    }

    $this->assertGreaterThan( 0, $checked, 'no addon declared tab settings' );
  }

  /**
   * WcGateways appends its own grid to the WooCommerce tab through the
   * wp_parsidate_woocommerce_settings_options filter, which only runs once a
   * legacy gateway is switched on.
   */
  public function test_gateways_settings_use_ids_that_match_their_keys(): void {
    Settings::save( 'parsian_gateway_enable', true, 'woocommerce' );

    // A throw-away instance registers the filter without touching the singleton,
    // whose enabled-gateway list another test may already have cached.
    new WcGateways();

    $settings = ( new WooCommerceAddon() )->addTabSettings()['settings'];

    $this->assertArrayHasKey(
      'woo_gateways_start_grid',
      $settings,
      'an enabled legacy gateway must append the payment gateways grid'
    );
    $this->assertSettingsIdsMatchKeys( WooCommerceAddon::class, $settings );
  }

  /**
   * The field id is what the form saves, so a grid that copies another grid's
   * id writes its value into the wrong key.
   *
   * @param string $class    Addon class being checked.
   * @param array  $settings Settings fields of that addon.
   */
  private function assertSettingsIdsMatchKeys( string $class, array $settings ): void {
    $this->assertNotEmpty( $settings, $class . ' settings section must contain fields' );

    foreach ( $settings as $key => $field ) {
      // endGrid is a bare closing tag with no data, so it has no id.
      if ( ( $field['type'] ?? '' ) === 'endGrid' ) {
        continue;
      }

      $this->assertArrayHasKey( 'id', $field, $class . ' field ' . $key . ' must declare an id' );
      $this->assertSame(
        $key,
        $field['id'],
        $class . ' field ' . $key . ' must use the same value for its id, which is what gets saved'
      );
    }
  }
}
