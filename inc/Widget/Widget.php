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

    add_action( 'widgets_init', function () {
      register_widget( 'WPParsidate\Widget\ArchiveWidget' );
      register_widget( 'WPParsidate\Widget\CalendarWidget' );
    } );
  }
}
