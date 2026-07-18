<?php
/**
 * Core settings
 *
 * Fix dates, title(s), editor.
 */

namespace WPParsidate\App\Core;

defined( 'ABSPATH' ) || exit;

class Core {
  public function __construct() {
    new ShamsiDate();
    new Debug();

    new FixTitle();
    new FixDates();
    new FixPermalink();
  }
}
