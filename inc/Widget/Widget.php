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

    add_action( 'widgets_init', array( $this, 'registerWidgets' ) );
  }

  /**
   * Register Plugin Widgets
   *
   * @return          void
   * @since           2.0
   */
  public function registerWidgets(): void {
    register_widget( ParsiDateArchiveWidget::class );
    register_widget( ParsiDateCalendarWidget::class );
  }
}
