<?php

namespace WPParsidate\Helper;

use WPParsidate\Settings\Settings;

class Debug {
  public static function check(): bool {
    return self::wp() || self::plugin();
  }

  public static function plugin(): bool {
    return defined( 'WP_PARSI_DEBUG_MODE' ) && WP_PARSI_DEBUG_MODE || Settings::get( 'debug_mode', false );
  }

  public static function wp(): bool {
    return WordPress::debug();
  }
}
