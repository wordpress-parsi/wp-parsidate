<?php
/**
 * ParsiDate Calendar Widget
 *
 * Add calendar widget to registered sidebar
 */

namespace WPParsidate\Widget;

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

use WPParsidate\Core\Calendar;
use WPParsidate\Helper\Posts;
use WPParsidate\Settings\Settings;

/**
 * @author lord_viper
 * @copyright 2013
 */
class ParsiDateCalendarWidget extends \WP_Widget {
  public function __construct() {
    parent::__construct( WP_PARSI_KEY . '_calendar', esc_html__( 'Parsidate - Calender', 'wp-parsidate' ) );

    add_action( 'widgets_init', function () {
      register_widget( 'WPParsidate\Widget\ParsiDateCalendarWidget' );
    } );
  }

  /**
   * Outputs the settings update form.
   *
   * @param array $instance Current settings.
   *
   * @return void Default return is 'noform'.
   *
   */
  public function form( $instance ) {
    $title    = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Calender', 'wp-parsidate' );
    $postType = $instance['post_type'] ?? 'post';
    $theme    = ! empty( $instance['theme_color'] ) ? $instance['theme_color'] : 'light-mode';

    if ( ! Settings::get( 'conv_permalinks', false ) ) {
      echo "<p style='color: #ff8153'>" . esc_html__( 'For use widget, active "Fix permalinks dates" option in plugin settings.',
          'wp-parsidate' ) . "</p>";
    }
    ?>
    <p style="text-align:right; direction:rtl">
      <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
        <?php esc_html_e( 'Title:', 'wp-parsidate' ) ?></label>

      <input style="width:calc(100% - 120px);"
             id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
             name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
             value="<?php echo esc_attr( $title ); ?>"/>
    </p>

    <p style="text-align:right; direction:rtl">
      <label for="<?php echo esc_attr( $this->get_field_id( 'post_type' ) ); ?>">
        <?php esc_html_e( 'Post type', 'wp-parsidate' ) ?>:</label>

      <?php echo Posts::getTypeSelect( $this->get_field_id( 'post_type' ), $this->get_field_name( 'post_type' ), $postType ) ?>
    </p>

    <p style="text-align:right; direction:rtl">
      <label for="<?php echo esc_attr( $this->get_field_id( 'theme_color' ) ); ?>">
        <?php esc_html_e( 'Theme color:', 'wp-parsidate' ) ?></label>

      <select style="width:calc(100% - 120px)"
              id="<?php echo esc_attr( $this->get_field_id( 'theme_color' ) ); ?>"
              name="<?php echo esc_attr( $this->get_field_name( 'theme_color' ) ); ?>">
        <option value="light-mode" <?php selected( $theme, 'light-mode' ); ?>>
          <?php esc_html_e( 'Light Mode', 'wp-parsidate' ) ?>
        </option>
        <option value="dark-mode" <?php selected( $theme, 'dark-mode' ); ?>>
          <?php esc_html_e( 'Dark Mode', 'wp-parsidate' ) ?>
        </option>
      </select>
    </p>
    <?php
  }

  /**
   * Updates a particular instance of a widget.
   *
   * This function should check that `$new_instance` is set correctly. The newly-calculated
   * value of `$instance` should be returned. If false is returned, the instance won't be
   * saved/updated.
   *
   * @param array $new_instance New settings for this instance as input by the user via
   *                            WP_Widget::form().
   * @param array $old_instance Old settings for this instance.
   *
   * @return array Settings to save or bool false to cancel saving.
   *
   */
  public function update( $new_instance, $old_instance ) {
    $instance                = $old_instance;
    $instance['title']       = esc_html( $new_instance['title'] );
    $instance['post_type']   = $new_instance['post_type'] ?? 'post';
    $instance['theme_color'] = esc_attr( $new_instance['theme_color'] );

    return $instance;
  }

  /**
   * Echoes the widget content.
   *
   * Subclasses should override this function to generate their widget code.
   *
   * @param array $args Display arguments including 'before_title', 'after_title',
   *                        'before_widget', and 'after_widget'.
   * @param array $instance The settings for the particular instance of the widget.
   *
   */
  public function widget( $args, $instance ) {
    if ( ! Settings::get( 'conv_permalinks', false ) ) {
      return;
    }

    $postType  = $instance['post_type'] ?? 'post';
    $theme     = ! empty( $instance['theme_color'] ) ? $instance['theme_color'] : 'light-mode';
    $widget_id = $args['widget_id'];

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $args['before_widget'];

    if ( ! empty( $instance['title'] ) ) {
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo $args['before_title'];
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo apply_filters( 'widget_title', $instance['title'] );
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo $args['after_title'];
    }

    Calendar::printCalendar( $postType );

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $args['after_widget'];

    if ( $theme === 'dark-mode' ) {
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo "<style>#$widget_id{background:#141414;border-radius:8px 8px 4px 4px;" . "overflow:hidden;box-shadow:0 0 5px 0 #000;text-align:center;padding-top:15px;color:#dcdcdc}" . "#$widget_id table{direction:rtl;border-radius:12px;overflow:hidden;" . "background:#1d1d1d;box-shadow:inset 0 0 0 6px #141414}#$widget_id table th," . "#$widget_id table td{border:0}#$widget_id table th:last-child," . "#$widget_id table tr td:last-child{color:#f28a8a}</style>";
    } else {
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo "<style>#$widget_id{background:#dbdbdb;border-radius:12px;overflow:hidden;" . "box-shadow:0 0 15px 0 #0000004f,inset 0 0 0 1px #8080806e;text-align:center;padding-top:15px;" . "color:#1e1e1e}#$widget_id table{direction:rtl;border-radius:9px;overflow:hidden;" . "background:#fdfdfd;box-shadow:0 -13px 14px 0 #8080801a}#$widget_id table th," . "#$widget_id table td{border:0}#$widget_id table th:last-child," . "#$widget_id table tr td:last-child{color:#bf4a4a}</style>";
    }
  }
}
