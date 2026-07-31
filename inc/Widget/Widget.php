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
    new ArchiveBlock();

    add_action( 'widgets_init', function () {
      register_widget( 'WPParsidate\Widget\ParsiDateArchiveWidget' );
      register_widget( 'WPParsidate\Widget\ParsiDateCalendarWidget' );
    } );
  }
}
