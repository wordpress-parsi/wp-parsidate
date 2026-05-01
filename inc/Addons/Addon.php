<?php

namespace WPParsidate\Addons;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Admin\AdminPages;
use WPParsidate\Admin\AdminSettings;
use WPParsidate\Helper\Assets;
use WPParsidate\Helper\Cache;
use WPParsidate\Settings\Settings;

abstract class Addon {
  public string $addonID;

  public string $currentTab = '';

  public string $currentSection = '';

  public function __construct() {
    add_filter( 'wp_parsidate_addons', [ $this, 'registerAddon' ] );
    add_action( 'wp_parsidate_admin_init', [ $this, 'registerMenu' ] );
    add_filter( 'wp_parsidate_settings', [ $this, 'allSettings' ] );

    if ( $this->addonID ) {
      //add_filter( 'wp_parsidate_' . $this->addonID . '_tab_display_notice', '__return_false' );
      //add_filter( 'wp_parsidate_' . $this->addonID . '_tab_content_display_notice', '__return_true' );
      if ( $this->currentTab ) {
        add_filter( 'wp_parsidate_dashboard_addon_links', [ $this, 'addDashboardLink' ] );
      }
    }

    // Register Plugin hooks
    if ( $this->currentTab ) {
      add_filter( 'wp_parsidate_' . $this->currentTab . '_settings_sections', [
        $this,
        'registerAddSectionSettings'
      ] );
    }

    // Register WordPress hooks
    add_action( 'init', [ $this, 'registerInitM1Action' ], - 1 );
    add_action( 'init', [ $this, 'registerInitAction' ], 9 );
    add_action( 'admin_init', [ $this, 'registerAdminInitAction' ] );
    add_action( 'template_redirect', [ $this, 'registerTemplateRedirectAction' ] );
    add_action( 'wp', [ $this, 'registerWpAction' ] );
    add_action( 'wp_enqueue_scripts', [ $this, 'registerWpEnqueueScriptsAction' ] );
    add_action( 'admin_enqueue_scripts', [ $this, 'registerAdminEnqueueScriptsAction' ] );
    add_action( 'wp_footer', [ $this, 'registerWpFooterAction' ] );
    add_action( 'wp_body_open', [ $this, 'registerWpBodyOpenAction' ], 0 );

    add_filter( 'query_vars', [ $this, 'registerQueryVarsFilter' ] );
    add_filter( 'woocommerce_account_menu_items', [ $this, 'registerWooAccountMenuItemsFilter' ] );
    add_filter( 'admin_body_class', [ $this, 'registerAdminBodyClassFilter' ] );
  }

  public function registerAddSectionSettings( $sections ): array {
    if ( method_exists( $this, 'addSectionSettings' ) && $this->isActivated() ) {
      return $this->addSectionSettings( $sections );
    }

    return $sections;
  }

  public function registerQueryVarsFilter( $vars ) {
    if ( method_exists( $this, 'queryVarsFilter' ) && $this->isActivated() ) {
      return $this->queryVarsFilter( $vars );
    }

    return $vars;
  }

  public function registerWooAccountMenuItemsFilter( $items ) {
    if ( method_exists( $this, 'wooAccountMenuItemsFilter' ) && $this->isActivated() ) {
      return $this->wooAccountMenuItemsFilter( $items );
    }

    return $items;
  }

  public function registerAdminBodyClassFilter( $classes ) {
    if ( method_exists( $this, 'adminBodyClassFilter' ) && $this->isActivated() ) {
      return $this->adminBodyClassFilter( $classes );
    }

    return $classes;
  }

  public function registerWpFooterAction(): void {
    if ( method_exists( $this, 'wpFooterAction' ) && $this->isActivated() ) {
      $this->wpFooterAction();
    }
  }

  public function registerWpBodyOpenAction(): void {
    if ( method_exists( $this, 'wpBodyOpenAction' ) && $this->isActivated() ) {
      $this->wpBodyOpenAction();
    }
  }

  public function registerAdminEnqueueScriptsAction(): void {
    if ( method_exists( $this, 'adminEnqueueScriptsAction' ) && $this->isActivated() ) {
      $this->adminEnqueueScriptsAction();
    }
  }

  public function registerWpEnqueueScriptsAction(): void {
    if ( method_exists( $this, 'wpEnqueueScriptsAction' ) && $this->isActivated() ) {
      $this->wpEnqueueScriptsAction();
    }
  }

  public function registerAdminInitAction(): void {
    if ( method_exists( $this, 'adminInitAction' ) && $this->isActivated() ) {
      $this->adminInitAction();
    }
  }

  public function registerInitM1Action(): void {
    if ( method_exists( $this, 'initM1Action' ) && $this->isActivated() ) {
      $this->initM1Action();
    }
  }

  public function registerInitAction(): void {
    if ( method_exists( $this, 'initAction' ) && $this->isActivated() ) {
      $this->initAction();
    }
  }

  public function registerTemplateRedirectAction(): void {
    if ( method_exists( $this, 'templateRedirectAction' ) && $this->isActivated() ) {
      $this->templateRedirectAction();
    }
  }

  public function registerWpAction(): void {
    if ( method_exists( $this, 'wpAction' ) && $this->isActivated() ) {
      $this->wpAction();
    }
  }

  public function registerMenu(): void {
    if ( $this->getInfo( 'has_page', false ) && $this->isActivated() ) {
      add_filter( 'wp_parsidate_menus', [ $this, 'addMenu' ] );

      if ( $this->getInfo( 'content_header', false ) ) {
        add_action( 'wp_parsidate_' . $this->addonID . '_tab_header',
          [ $this, 'displayContentHeader' ], - 10 );
      }

      if ( method_exists( $this, 'content' ) ) {
        add_action( 'wp_parsidate_' . $this->addonID . '_tab_content', [ $this, 'content' ] );
      }

      if ( method_exists( $this, 'settings' ) ) {
        add_filter( 'wp_parsidate_' . $this->addonID . '_settings', [ $this, 'settings' ] );
      }
    }
  }

  public function displayContentHeader(): void {
    if ( $this->getInfo( 'content_header', false ) ) {
      AdminSettings::headerSettings( $this->addonID, $this->getInfo() );
    }
  }

  public function allSettings( $settings ): array {
    if ( method_exists( $this, 'settings' ) ) {
      $settings[ $this->addonID ] = $this->settings();
    }

    return $settings;
  }

  public function addMenu( $menus ) {
    $addon = $this->getInfo();

    $menus[ $this->addonID ] = array(
      'title' => $addon['menu_title'] ?? ( $addon['name'] ?? $addon['title'] ),
      'icon'  => $addon['menu_icon'] ?? ( $addon['icon'] ?? '' )
    );

    return $menus;
  }

  public function addDashboardLink( $addons ): array {
    if ( ! $this->isActivated() ) {
      return $addons;
    }

    $addon     = $this->getInfo();
    $addonCats = Addons::getAddonCats();
    $cat       = empty( $addon['cat'] ) || ! array_key_exists( $addon['cat'],
      $addonCats ) ? 'other' : $addon['cat'];
    $icon      = ! empty( $addon['icon'] ) && Assets::isSvgImageString( $addon['icon'] ) ? Assets::setSvgDimensions( $addon['icon'],
      50 ) : '';

    if ( $this->getInfo( 'has_page', false ) ) {
      $link = AdminPages::link( [
        'tab' => $this->addonID
      ] );
    } else {
      $link = AdminPages::link( [
        'tab'     => $this->currentTab,
        'section' => empty( $this->currentSection ) ? $this->addonID : $this->currentSection,
      ] );
    }

    $addons[ $cat ][] = [
      'title' => $addon['name'] ?? $addon['title'],
      'desc'  => $addon['desc'] ?? '',
      'link'  => $link,
      'icon'  => $icon,
      'type'  => 'addon'
    ];

    return $addons;
  }

  public function registerAddon( $addons ) {
    $addons[] = $this->getInfo();

    return $addons;
  }

  private function getInfo( $key = null, $default = null ) {
    $addon = Cache::get( $this->addonID . '_internal_addon_info', false );

    if ( ! is_array( $addon ) ) {
      $addon = $this->info();
      Cache::set( $this->addonID . '_internal_addon_info', $addon );
    }

    if ( $key !== null ) {
      return $addon[ $key ] ?? $default;
    }

    return $addon;
  }

  public function isActivated(): bool {
    if ( ! $this->getInfo( 'force_enable', false ) &&
         Settings::get( 'internal_addon_' . $this->addonID, false ) !== 1 ) {
      return false;
    }

    $requiresPlugins = $this->getInfo( 'requires_plugins', [] );
    $canActivate     = empty( $requiresPlugins );

    if ( ! $canActivate && ! empty( $requiresPlugins ) && is_array( $requiresPlugins ) ) {
      $requirePluginsActive = Cache::get( $this->addonID . '_requires_plugins_count', false );

      if ( $requirePluginsActive === false ) {
        $requirePluginsActive = 0;
        foreach ( $requiresPlugins as $requirePluginPath => $requirePlugin ) {
          if (
            ( file_exists( WP_PLUGIN_DIR . '/' . $requirePluginPath ) && is_plugin_active( $requirePluginPath ) ) ||
            ( ! empty( $requirePlugin['function_check'] ) && function_exists( $requirePlugin['function_check'] ) ) ||
            ( ! empty( $requirePlugin['class_check'] ) && class_exists( $requirePlugin['class_check'] ) ) ||
            ( ! empty( $requirePlugin['define_check'] ) && defined( $requirePlugin['define_check'] ) )
          ) {
            $requirePluginsActive ++;
          }
        }
        Cache::set( $this->addonID . '_requires_plugins_count', $requirePluginsActive );
      }

      $canActivate = $requirePluginsActive > 0 && $requirePluginsActive === count( $requiresPlugins );
    }

    return $canActivate;
  }

  public function getSettingsKey() {
    return $this->getInfo( 'settings_key' );
  }

  public function getSetting( $key = null, $default = null, $useCache = true ) {
    return Settings::get( $key, $default, $this->getSettingsKey(), $useCache );
  }

  public function saveSetting( $key, $value ): bool {
    return Settings::save( $key, $value, $this->getSettingsKey() );
  }

  public function savesSetting( $options ): bool {
    return Settings::saves( $options, $this->getSettingsKey() );
  }

  public function deleteSetting( $key ): bool {
    return Settings::delete( $key, $this->getSettingsKey() );
  }

  public function addToArraySetting( $key, $value, $reverse = false ): bool {
    return Settings::addToArray( $key, $value, $this->getSettingsKey(), $reverse );
  }

  public function deleteFromArraySetting( $key, $index ): bool {
    return Settings::deleteFromArray( $key, $index, $this->getSettingsKey() );
  }
}
