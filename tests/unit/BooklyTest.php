<?php

namespace WPParsidate\Tests\Unit;

use WP_UnitTestCase;
use WPParsidate\App\Integration\Bookly as BooklyAddon;

class BooklyTest extends WP_UnitTestCase {

  public function test_addon_requires_only_bookly_free(): void {
    $addon       = new BooklyAddon();
    $requirements = $addon->info()['requires_plugins'];

    $this->assertSame(
      [ 'bookly-responsive-appointment-booking-tool/main.php' ],
      array_keys( $requirements )
    );
    $this->assertTrue( $requirements['bookly-responsive-appointment-booking-tool/main.php']['is_free'] );
    $this->assertEmpty( $requirements['bookly-responsive-appointment-booking-tool/main.php']['optional'] ?? false );
  }

  /**
   * @return array<string, array<string, mixed>>
   */
  private function settings(): array {
    $addon = new BooklyAddon();

    return $addon->addSectionSettings( [] )['bookly']['settings'];
  }

  /**
   * @return string[]
   */
  private function surfaces(): array {
    return [
      'jalali_booking_form',
      'jalali_pro_calendar',
      'jalali_pro_forms',
      'jalali_ai_assistant',
      'jalali_admin_calendar',
      'jalali_admin_daterange',
      'jalali_appearance_preview',
    ];
  }

  public function test_settings_register_one_toggle_per_surface(): void {
    $settings = $this->settings();

    $toggles = [];
    foreach ( $settings as $key => $field ) {
      if ( ( $field['type'] ?? '' ) === 'toggle' ) {
        $toggles[] = $key;
      }
    }

    $this->assertSame( $this->surfaces(), $toggles );

    // Add-on activation is the only global switch; the old master key is gone.
    $this->assertArrayNotHasKey( 'jalali_booking_calendar', $settings );

    foreach ( $this->surfaces() as $key ) {
      $this->assertTrue( $settings[ $key ]['default'], $key . ' should default to enabled' );
      $this->assertSame( 'bool', $settings[ $key ]['sanitize'], $key . ' should sanitize as bool' );
    }
  }

  public function test_settings_group_toggles_in_frontend_and_admin_grids(): void {
    $settings = $this->settings();

    $gridTitles = [];
    $openGrids  = 0;
    $endGrids   = 0;

    foreach ( $settings as $field ) {
      $type = $field['type'] ?? '';

      if ( 'startGrid' === $type ) {
        $openGrids ++;
        $gridTitles[] = $field['title'];
      } elseif ( 'endGrid' === $type ) {
        $endGrids ++;
      }
    }

    $this->assertSame( 2, $openGrids );
    $this->assertSame( 2, $endGrids );
    $this->assertSame( [ 'Frontend', 'Admin' ], $gridTitles );

    $keys = array_keys( $settings );
    $this->assertSame( 'bookly_start_grid_frontend', $keys[0] );
    $this->assertSame( 'bookly_end_grid_admin', end( $keys ) );
  }

  public function test_each_surface_can_be_disabled_independently(): void {
    $addon = new BooklyAddon();

    $surface = new \ReflectionMethod( $addon, 'isSurfaceEnabled' );
    $surface->setAccessible( true );

    foreach ( $this->surfaces() as $key ) {
      $this->assertTrue( $surface->invoke( $addon, $key ), $key . ' should default to enabled' );
    }

    foreach ( $this->surfaces() as $key ) {
      $addon->saveSetting( $key, false );
      $this->assertFalse( $surface->invoke( $addon, $key ), $key . ' should honour its own toggle' );

      foreach ( $this->surfaces() as $other ) {
        if ( $other === $key ) {
          continue;
        }

        $this->assertTrue( $surface->invoke( $addon, $other ), $other . ' must stay enabled when ' . $key . ' is off' );
      }

      $addon->saveSetting( $key, true );
    }
  }
}
