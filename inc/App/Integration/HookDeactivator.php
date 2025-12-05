<?php

namespace WPParsidate\App\Integration;

use WPParsidate\Helper\Cache;
use WPParsidate\Helper\WordPress;
use WPParsidate\Settings\Settings;

class HookDeactivator {
  private const sectionID = 'hook-deactivator';

  public function __construct() {
    add_filter( 'wp_parsidate_integration_settings_sections', [ $this, 'addSectionSettings' ] );
  }

  /**
   * Check hook list for deactivating WP-Parsidate functionality, If disable return True.
   *
   * @return bool Disable status
   */
  public static function checkDisable(): bool {
    if ( apply_filters( 'wp_parsidate_hook_deactivator_check_disable', false ) ) {
      return true;
    }

    if ( WordPress::isFeed() ) {
      return true;
    }

    $i        = 0;
    $dis_hook = self::getList();
    $calls    = debug_backtrace();
    unset( $calls[0], $calls[1], $calls[2] );

    foreach ( $calls as $i => $call ) {
      unset( $calls[ $i ] );

      if ( $call['function'] === 'apply_filters' && empty( $call['class'] ) ) {
        break;
      }
    }

    $func = $calls[ ++ $i ]['function'];

    if ( empty( $dis_hook[ $func ] ) ) {
      return false;
    }

    $hooks = $dis_hook[ $func ];

    unset( $calls[ $i ] );

    foreach ( $calls as $i => $call ) {
      foreach ( $hooks as $hook ) {
        $hook['class'] = trim( $hook['class'] );

        if ( ( isset( $call['class'] ) && empty( $hook['class'] ) ) || ( ! isset( $call['class'] ) && ! empty( $hook['class'] ) ) ) {
          continue;
        }

        if ( ! empty( $hook['func'] ) && ( $call['function'] !== trim( $hook['func'] ) ) ) {
          continue;
        }

        /*if (
          ! empty( $call['function'] ) &&
          ! empty( $hook['func'] ) &&
          ! empty( $call['class'] ) &&
          ! empty( $hook['class'] ) &&
          $call['function'] === $hook['func'] &&
          $call['class'] === $hook['class']
        ) {
          return true;
        }

        if (
          ! empty( $call['function'] ) &&
          ! empty( $hook['func'] ) &&
          empty( $call['class'] ) &&
          empty( $hook['class'] ) &&
          $call['function'] === $hook['func']
        ) {
          return true;
        }*/

        if ( ( ! isset( $call['class'] ) && empty( $hook['class'] ) ) || $call['class'] === $hook['class'] ) {
          return true;
        }
      }
    }

    return false;
  }

  public static function getList(): array {
    $cache = Cache::get( 'hook_deactivator_list', false );
    if ( is_array( $cache ) ) {
      return $cache;
    }

    $hooks   = array();
    $rawList = Settings::get( 'hook_deactivator_list', '' );
    $rawList = apply_filters( 'wp_parsidate_hook_deactivator_raw_list', $rawList );
    $lists   = explode( "\n", $rawList );

    foreach ( $lists as $list ) {
      $list = explode( ',', $list );
      $list = array_map( 'trim', $list );

      if ( count( $list ) < 2 ) {
        continue;
      }

      $hooks[ $list[0] ][] = array( 'func' => $list[1], 'class' => ( $list[2] ?? '' ) );
    }

    Cache::set( 'hook_deactivator_list', $hooks );

    return apply_filters( 'wp_parsidate_hook_deactivator_list', $hooks );
  }

  public function addSectionSettings( array $sections ): array {
    $settings = [
      'start_grid_hook_deactivator' => array(
        'title' => __( 'Hooks', 'wp-parsidate' ),
        'type'  => 'startGrid',
      ),
      'hook_deactivator_list'       => array(
        'id'         => 'hook_deactivator_list',
        'title'      => __( 'Hook list', 'wp-parsidate' ),
        'type'       => 'textarea',
        'desc'       => __( 'Enter hook,class,function to remove parsidate filter from it',
          'wp-parsidate' ),
        'class'      => 'ltr-field',
        'attributes' => array(
          'rows' => 7
        )
      ),
      'end_grid_hook_deactivator'   => array(
        'type' => 'endGrid',
      )
    ];

    $sections[ self::sectionID ] = array(
      'title'    => __( 'Hook deactivator', 'wp-parsidate' ),
      'desc'     => __( 'Disable plugin hooks', 'wp-parsidate' ),
      'settings' => $settings
    );

    return $sections;
  }
}
