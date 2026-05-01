<?php
/**
 * ParsiDate Archive Widget
 *
 * Add archive widget to registered sidebar
 */

namespace WPParsidate\Widget;

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

use WPParsidate\Core\Archive;
use WPParsidate\Settings\Settings;

/**
 * @author lord_viper
 * @copyright 2013
 */
class ParsiDateArchiveWidget extends \WP_Widget {
  public function __construct() {
    global $wp_version;

    // backwards compatibility
    if ( version_compare( $wp_version, '4.3', '>=' ) ) {
      parent::__construct( false, esc_html__( 'Jalali Date Archives', 'wp-parsidate' ),
        'description=' . esc_html__( 'Jalali Date Archives', 'wp-parsidate' ) );
    } else {
      $this->WP_Widget( false, esc_html__( 'Jalali Date Archives', 'wp-parsidate' ),
        'description=' . esc_html__( 'Jalali Date Archives', 'wp-parsidate' ) );
    }
  }

  /**
   * Outputs the settings update form.
   *
   * @param  array  $instance  Current settings.
   *
   * @return void Default return is 'noform'.
   * @return void
   * @since 2.8.0
   */
  public function form( $instance ) {
    $type                                = $instance['parsidate_archive_type'] ?? 'monthly';
    $instance['parsidate_archive_title'] = isset( $instance['parsidate_archive_title'] ) ? wp_strip_all_tags( $instance['parsidate_archive_title'] ) : esc_html__( 'Jalali Date Archives',
      'wp-parsidate' );
    $instance['parsidate_archive_count'] = $instance['parsidate_archive_count'] ?? 0;
    $instance['parsidate_archive_list']  = $instance['parsidate_archive_list'] ?? 0;
    ?>
    <p style="text-align:right; direction:rtl">

      <label
        for="<?php echo esc_attr( $this->get_field_id( 'parsidate_archive_title' ) ); ?>"><?php esc_html_e( 'Title',
          'wp-parsidate' ) ?>:</label>

      <input style="width: 200px;" id="<?php echo esc_attr( $this->get_field_id( 'parsidate_archive_title' ) ); ?>"
             name="<?php echo esc_attr( $this->get_field_name( 'parsidate_archive_title' ) ); ?>" type="text"
             value="<?php echo( empty( $instance['parsidate_archive_title'] ) ? esc_html__( 'Jalali Date Archives',
               'wp-parsidate' ) : esc_html( $instance['parsidate_archive_title'] ) ) ?>"/>

      <br><br>

      <span><?php esc_html_e( 'How to display', 'wp-parsidate' ) ?>:</span><br>

      <label>
        <input type="radio" id="parsidate_archive_type1"
               name="<?php echo esc_attr( $this->get_field_name( 'parsidate_archive_type' ) ); ?>"
               value="yearly" <?php checked( $type, 'yearly' ); ?>/>
        <?php esc_html_e( 'Yearly', 'wp-parsidate' ) ?>
      </label>

      <br/>

      <label>
        <input type="radio" id="parsidate_archive_type2"
               name="<?php echo esc_attr( $this->get_field_name( 'parsidate_archive_type' ) ); ?>"
               value="monthly" <?php checked( $type, 'monthly' ); ?>/>
        <?php esc_html_e( 'Monthly', 'wp-parsidate' ) ?>
      </label>

      <br/>

      <label>
        <input type="radio" id="parsidate_archive_type3"
               name="<?php echo esc_attr( $this->get_field_name( 'parsidate_archive_type' ) ); ?>"
               value="weekly" <?php checked( $type, 'weekly' ); ?>/>
        <?php esc_html_e( 'Weekly', 'wp-parsidate' ) ?>
      </label>

      <br/>

      <label>
        <input type="radio" id="parsidate_archive_type4"
               name="<?php echo esc_attr( $this->get_field_name( 'parsidate_archive_type' ) ); ?>"
               value="daily" <?php checked( $type, 'daily' ); ?>/>
        <?php esc_html_e( 'Daily', 'wp-parsidate' ) ?>
      </label>

      <br/>
      <br/>

      <input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'parsidate_archive_count' ) ); ?>"
             id="<?php echo esc_attr( $this->get_field_id( 'parsidate_archive_count' ) ); ?>"
             value="1" <?php checked( $instance['parsidate_archive_count'], 1 ); ?>/>

      <label for="<?php echo esc_attr( $this->get_field_id( 'parsidate_archive_count' ) ); ?>">
        <?php esc_html_e( 'Show post counts', 'wp-parsidate' ) ?>
      </label>

      <br/>

      <input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'parsidate_archive_list' ) ); ?>"
             id="<?php echo esc_attr( $this->get_field_id( 'parsidate_archive_list' ) ); ?>"
             value="1" <?php echo checked( $instance['parsidate_archive_list'], 1 ); ?>/>

      <label for="<?php echo esc_attr( $this->get_field_id( 'parsidate_archive_list' ) ); ?>">
        <?php esc_html_e( 'Display as dropdown', 'wp-parsidate' ) ?>
      </label>

    </p>
    <?php
    if ( ! Settings::get( 'conv_permalinks', false ) ) {
      echo "<p style='color: #ff8153'>" .
           esc_html__( 'For use widget, active "Fix permalinks dates" option in plugin settings.', 'wp-parsidate' ) .
           "</p>";
    }
  }

  /**
   * Updates a particular instance of a widget.
   *
   * This function should check that `$new_instance` is set correctly. The newly-calculated
   * value of `$instance` should be returned. If false is returned, the instance won't be
   * saved/updated.
   *
   * @param  array  $new_instance  New settings for this instance as input by the user via
   *                            WP_Widget::form().
   * @param  array  $old_instance  Old settings for this instance.
   *
   * @return array Settings to save or bool false to cancel saving.
   * @since 2.8.0
   *
   */
  public function update( $new_instance, $old_instance ) {
    $instance                            = $old_instance;
    $instance['parsidate_archive_title'] = isset( $new_instance['parsidate_archive_title'] ) ? wp_strip_all_tags( $new_instance['parsidate_archive_title'] ) : esc_html__( 'Jalali Date Archives',
      'wp-parsidate' );
    $instance['parsidate_archive_count'] = $new_instance['parsidate_archive_count'] ?? 0;
    $instance['parsidate_archive_list']  = $new_instance['parsidate_archive_list'] ?? 0;
    $instance['parsidate_archive_type']  = $new_instance['parsidate_archive_type'] ?? 'monthly';

    return $instance;
  }

  /**
   * Echoes the widget content.
   *
   * Subclasses should override this function to generate their widget code.
   *
   * @param  array  $args  Display arguments including 'before_title', 'after_title',
   *                        'before_widget', and 'after_widget'.
   * @param  array  $instance  The settings for the particular instance of the widget.
   *
   * @since 2.8.0
   *
   */
  public function widget( $args, $instance ) {
    if ( ! Settings::get( 'conv_permalinks', false ) ) {
      return;
    }

    $type       = $instance['parsidate_archive_type'] ?? 'monthly';
    $title      = $instance['parsidate_archive_title'] ?? esc_html__( 'Jalali Date Archives',
      'wp-parsidate' );
    $post_count = $instance['parsidate_archive_count'] ?? false;
    $ddl_style  = isset( $instance['parsidate_archive_list'] ) && $instance['parsidate_archive_list'];

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $args['before_widget'];
    if ( ! empty( $instance['parsidate_archive_title'] ) ) {
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo $args['before_title'];
      // @TODO: Escape widget_title maybe corrupted some theme add custom HTML on widget_title hook
      echo esc_html( apply_filters( 'widget_title', $instance['parsidate_archive_title'] ) );
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo $args['after_title'];
    }

    if ( $ddl_style ) {
      echo "<select name='parsidate_archive_list' onchange='document.location.href=this.options[this.selectedIndex].value;'> <option value='0'>" . esc_attr( $title ) . "</option>";

      Archive::getPostArchives( array(
        'type'            => $type,
        'format'          => 'option',
        'show_post_count' => $post_count
      ) );

      echo '</select>';
    } else {
      echo '<ul>';

      Archive::getPostTypeArchives( array(
        'type'            => $type,
        'show_post_count' => $post_count
      ) );

      echo '</ul>';
    }

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $args['after_widget'];
  }
}
