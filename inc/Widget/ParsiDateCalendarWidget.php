<?php
/**
 * ParsiDate Calendar Widget
 *
 * Add calendar widget to registered sidebar
 */

namespace WPParsidate\Widget;

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

use WPParsidate\Core\Calendar;
use WPParsidate\Helper\{Assets, Posts};
use WPParsidate\Settings\Settings;

/**
 * @author lord_viper
 * @copyright 2013
 */
class ParsiDateCalendarWidget extends \WP_Widget {
  public function __construct() {
    parent::__construct( WP_PARSI_KEY . '_calendar', esc_html__( 'Parsidate - Calendar', 'wp-parsidate' ) );

    add_action( 'wp_parsidate_calendar_widget_end', [ $this, 'printStyle' ], 10, 4 );
  }

  public function printStyle( $title, $postType, $theme, $widgetID ) {
    if ( $theme === 'none' ) {
      return;
    }

    $style  = file_get_contents( Assets::path( 'css-admin/calendar-wdiget.min.css' ) );
    $handle = WP_PARSI_KEY_SLUG . '-calendar-wdiget-style';
    wp_register_style( $handle, false, [], Assets::getVersion() );
    wp_enqueue_style( $handle );
    wp_add_inline_style( $handle, $style );
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
    $title    = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Calendar', 'wp-parsidate' );
    $postType = $instance['post_type'] ?? 'post';
    $theme    = $instance['theme'] ?? 'simple';

    if ( ! Settings::get( 'conv_permalinks', false ) ) {
      echo "<p style='color: #ff8153'>" . esc_html__( 'For use widget, active "Fix permalinks dates" option in plugin settings.', 'wp-parsidate' ) . "</p>";
    }
    ?>
    <p>
      <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
        <?php esc_html_e( 'Title:', 'wp-parsidate' ) ?></label>

      <input style="width:calc(100% - 120px);"
             id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
             name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
             value="<?php echo esc_attr( $title ); ?>"/>
    </p>

    <p>
      <label for="<?php echo esc_attr( $this->get_field_id( 'post_type' ) ); ?>">
        <?php esc_html_e( 'Post type', 'wp-parsidate' ) ?>:</label>

      <?php echo Posts::getTypeSelect( $this->get_field_id( 'post_type' ), $this->get_field_name( 'post_type' ), $postType ) ?>
    </p>

    <p>
      <label for="<?php echo esc_attr( $this->get_field_id( 'theme' ) ); ?>">
        <?php esc_html_e( 'Theme:', 'wp-parsidate' ) ?></label>

      <select style="width:calc(100% - 120px)"
              id="<?php echo esc_attr( $this->get_field_id( 'theme' ) ); ?>"
              name="<?php echo esc_attr( $this->get_field_name( 'theme' ) ); ?>">
        <option value="none" <?php selected( $theme, 'none' ); ?>>---</option>
        <option value="simple" <?php selected( $theme, 'simple' ); ?>>
          <?php esc_html_e( 'Simple', 'wp-parsidate' ) ?>
        </option>
        <option value="light" <?php selected( $theme, 'light' ); ?>>
          <?php esc_html_e( 'Light', 'wp-parsidate' ) ?>
        </option>
        <option value="dark" <?php selected( $theme, 'dark' ); ?>>
          <?php esc_html_e( 'Dark', 'wp-parsidate' ) ?>
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
    $instance              = $old_instance;
    $instance['title']     = esc_html( $new_instance['title'] );
    $instance['post_type'] = $new_instance['post_type'] ?? 'post';
    $instance['theme']     = $new_instance['theme'] ?? 'simple';

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

    $title    = $instance['title'] ?? '';
    $postType = $instance['post_type'] ?? 'post';
    $theme    = $instance['theme'] ?? 'simple';
    $widgetID = $args['widget_id'];

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $args['before_widget'];

    if ( ! empty( $title ) ) {
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo $args['before_title'];
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo apply_filters( 'widget_title', $title );
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo $args['after_title'];
    }

    do_action( 'wp_parsidate_calendar_widget_start', $title, $postType, $theme, $widgetID );
    Calendar::printCalendar( $postType, $theme );
    do_action( 'wp_parsidate_calendar_widget_end', $title, $postType, $theme, $widgetID );

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $args['after_widget'];
  }
}
