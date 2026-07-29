<?php
/**
 * Widget class
 *
 * Register plugin widgets
 */

namespace WPParsidate\Widget;

class Widget {
  public function __construct() {
    new DashboardWidget();

    new ParsiDateArchiveWidget();
    new ParsiDateCalendarWidget();
  }
}
