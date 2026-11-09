<?php

namespace WPParsidate\Tests\Unit;

use WPParsidate\Addons\Addon;
use WP_UnitTestCase;

/**
 * Minimal addon used to exercise the required-plugin activation checks.
 */
class RequiredPluginsAddonStub extends Addon {

  /**
   * Required plugin definitions returned by info().
   *
   * @var array<string, array>
   */
  public array $requirements;

  public function __construct( string $addonID, array $requirements ) {
    $this->addonID      = $addonID;
    $this->requirements = $requirements;

    parent::__construct();
  }

  public function info(): array {
    return array(
      'id'               => $this->addonID,
      'title'            => 'Required plugins test addon',
      'force_enable'     => true,
      'requires_plugins' => $this->requirements,
    );
  }
}

/**
 * Regression tests for the required-plugin activation check.
 */
class AddonActivationTest extends WP_UnitTestCase {

  private const STALE_CACHE_ADDON = 'test_stale_required_cache';

  /**
   * Older versions stored the active-plugin count permanently in the object
   * cache. A request that wrote 0 while the required plugin was unavailable
   * disabled the addon until the cache was flushed. The count must now be
   * request-scoped, so the stale entries must not affect activation.
   */
  public function test_activation_ignores_stale_object_cache_entries(): void {
    $addon = new RequiredPluginsAddonStub(
      self::STALE_CACHE_ADDON,
      array(
        'missing-plugin/missing-plugin.php' => array( 'define_check' => 'ABSPATH' ),
      )
    );

    // Values written by the previous (and older) cache-based implementations.
    wp_cache_set( self::STALE_CACHE_ADDON . '_requires_plugins_count', 0, WP_PARSI_KEY );
    wp_cache_set( self::STALE_CACHE_ADDON . '_required_plugins_count', 0, WP_PARSI_KEY );

    $this->assertTrue( $addon->isActivated() );
  }

  public function test_activation_requires_every_non_optional_plugin(): void {
    $addon = new RequiredPluginsAddonStub(
      'test_missing_required_plugin',
      array(
        'missing-plugin/missing-plugin.php' => array( 'define_check' => 'WPP_UNDEFINED_TEST_CONSTANT' ),
      )
    );

    $this->assertFalse( $addon->isActivated() );
  }

  public function tearDown(): void {
    wp_cache_delete( self::STALE_CACHE_ADDON . '_requires_plugins_count', WP_PARSI_KEY );
    wp_cache_delete( self::STALE_CACHE_ADDON . '_required_plugins_count', WP_PARSI_KEY );

    parent::tearDown();
  }
}
