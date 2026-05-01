<?php
/**
 * Hook Deactivator settings
 *
 * User can disable Jalali date in some hooks
 * Format: mainCallFunction,methodOfClass,ClassName
 * Example: wp_date,render_field,acf_field_date_picker
 *
 * Without class:
 * Format: mainCallFunction,usedFunction
 * Example: wp_date,acf_format_date
 */

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
    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
    $calls = debug_backtrace();
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

    foreach ( $lists as $item ) {
      $item = explode( ',', $item );
      $item = array_map( 'trim', $item );

      if ( count( $item ) < 2 ) {
        continue;
      }

      $hooks[ $item[0] ][] = array( 'func' => $item[1], 'class' => ( $item[2] ?? '' ) );
    }

    Cache::set( 'hook_deactivator_list', $hooks );

    return apply_filters( 'wp_parsidate_hook_deactivator_list', $hooks );
  }

  public function addSectionSettings( array $sections ): array {
    $listPlaceholder = esc_html__( 'Format:', 'wp-parsidate' ) . "\n" .
                       "<code>mainCallFunction,methodOfClass,ClassName</code>\n" .
                       esc_html__( 'Example:', 'wp-parsidate' ) . "\n" .
                       "<code>wp_date,render_field,acf_field_date_picker</code>\n\n" .
                       esc_html__( 'Without class format:', 'wp-parsidate' ) . "\n" .
                       "<code>mainCallFunction,usedFunction</code>\n" .
                       esc_html__( 'Example:', 'wp-parsidate' ) . "\n" .
                       "<code>wp_date,acf_format_date</code>\n";

    $settings = [
      'start_grid_hook_deactivator' => array(
        'title' => esc_html__( 'Hooks', 'wp-parsidate' ),
        'type'  => 'startGrid',
      ),
      'hook_deactivator_list'       => array(
        'id'          => 'hook_deactivator_list',
        'title'       => esc_html__( 'Hook list', 'wp-parsidate' ),
        'type'        => 'textarea',
        'desc'        => esc_html__( 'Enter hook, function and class to remove Parsidate filter from it',
            'wp-parsidate' ) . "\n\n" . $listPlaceholder,
        'class'       => 'ltr-field',
        'placeholder' => wp_strip_all_tags( $listPlaceholder ),
        'attributes'  => array(
          'rows' => 10
        )
      ),
      'end_grid_hook_deactivator'   => array(
        'type' => 'endGrid',
      )
    ];

    $sections[ self::sectionID ] = array(
      'title'    => esc_html__( 'Hook deactivator', 'wp-parsidate' ),
      'desc'     => esc_html__( 'Disable plugin hooks', 'wp-parsidate' ),
      'settings' => $settings
    );

    return $sections;
  }
}
