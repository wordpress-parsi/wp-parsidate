<?php
/**
 * ParsiDate Archive Widget
 *
 * Add archive widget to registered sidebar
 */

namespace WPParsidate\Widget;

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

use WPParsidate\Core\Archive;
use WPParsidate\Helper\Posts;
use WPParsidate\Settings\Settings;

/**
 * @author lord_viper
 * @copyright 2013
 */
class ParsiDateArchiveWidget extends \WP_Widget {
  public function __construct() {
    parent::__construct( WP_PARSI_KEY . '_archive', esc_html__( 'Parsidate - Archive', 'wp-parsidate' ) );
  }

  /**
   * Outputs the settings update form.
   *
   * @param array $instance Current settings.
   *
   * @return void Default return is 'noform'.
   */
  public function form( $instance ) {
    $type                       = $instance['type'] ?? 'monthly';
    $instance['title']          = isset( $instance['title'] ) ? wp_strip_all_tags( $instance['title'] ) : esc_html__( 'Archive', 'wp-parsidate' );
    $instance['post_type']      = $instance['post_type'] ?? 'post';
    $instance['display_count']  = $instance['display_count'] ?? 0;
    $instance['display_select'] = $instance['display_select'] ?? 0;

    if ( ! Settings::get( 'conv_permalinks', false ) ) {
      echo "<p style='color: #ff8153'>" .
           esc_html__( 'For use widget, active "Fix permalinks dates" option in plugin settings.', 'wp-parsidate' ) .
           "</p>";
    }
    ?>
    <p>
      <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
        <?php esc_html_e( 'Title', 'wp-parsidate' ) ?>:</label>
      <input style="width: 200px;" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
             name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
             value="<?php echo $instance['title'] ?>"/>
    </p>

    <p>
      <label for="<?php echo esc_attr( $this->get_field_id( 'post_type' ) ); ?>">
        <?php esc_html_e( 'Post type', 'wp-parsidate' ) ?>:</label>
      <?php echo Posts::getTypeSelect( $this->get_field_id( 'post_type' ), $this->get_field_name( 'post_type' ), $instance['post_type'] ) ?>
    </p>

    <p>
      <label><?php esc_html_e( 'How to display', 'wp-parsidate' ) ?>:</label><br>
      <label>
        <input type="radio" id="type1"
               name="<?php echo esc_attr( $this->get_field_name( 'type' ) ); ?>"
               value="yearly" <?php checked( $type, 'yearly' ); ?>/>
        <?php esc_html_e( 'Yearly', 'wp-parsidate' ) ?>
      </label>
      <label>
        <input type="radio" id="type2"
               name="<?php echo esc_attr( $this->get_field_name( 'type' ) ); ?>"
               value="monthly" <?php checked( $type, 'monthly' ); ?>/>
        <?php esc_html_e( 'Monthly', 'wp-parsidate' ) ?>
      </label>
      <label>
        <input type="radio" id="type4"
               name="<?php echo esc_attr( $this->get_field_name( 'type' ) ); ?>"
               value="daily" <?php checked( $type, 'daily' ); ?>/>
        <?php esc_html_e( 'Daily', 'wp-parsidate' ) ?>
      </label>
    </p>

    <p>
      <input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'display_select' ) ); ?>"
             id="<?php echo esc_attr( $this->get_field_id( 'display_select' ) ); ?>"
             value="1" <?php echo checked( $instance['display_select'], 1 ); ?>/>

      <label for="<?php echo esc_attr( $this->get_field_id( 'display_select' ) ); ?>">
        <?php esc_html_e( 'Display as dropdown', 'wp-parsidate' ) ?>
      </label>
    </p>
    <p>
      <input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'display_count' ) ); ?>"
             id="<?php echo esc_attr( $this->get_field_id( 'display_count' ) ); ?>"
             value="1" <?php checked( $instance['display_count'], 1 ); ?>/>

      <label for="<?php echo esc_attr( $this->get_field_id( 'display_count' ) ); ?>">
        <?php esc_html_e( 'Show post counts', 'wp-parsidate' ) ?>
      </label>
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
  public function update( $new_instance, $old_instance ): array {
    $instance                   = $old_instance;
    $instance['title']          = isset( $new_instance['title'] ) ? wp_strip_all_tags( $new_instance['title'] ) : esc_html__( 'Archive', 'wp-parsidate' );
    $instance['post_type']      = $new_instance['post_type'] ?? 'post';
    $instance['type']           = $new_instance['type'] ?? 'monthly';
    $instance['display_select'] = $new_instance['display_select'] ?? 0;
    $instance['display_count']  = $new_instance['display_count'] ?? 0;

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
    $title     = $instance['title'] ?? esc_html__( 'Archive', 'wp-parsidate' );
    $postType  = $instance['post_type'] ?? 'post';
    $type      = $instance['type'] ?? 'monthly';
    $postCount = (bool) ( $instance['display_count'] ?? false );
    $isList    = (bool) ( $instance['display_select'] ?? false );
    $widgetID  = $args['widget_id'];

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

    do_action( 'wp_parsidate_archive_widget_start', $title, $postType, $type, $postCount, $isList, $widgetID );
    if ( $isList ) {
      echo "<select name='display_select' onchange='document.location.href=this.options[this.selectedIndex].value;'> <option value='0'>" . esc_attr( $title ) . "</option>";
    } else {
      echo '<ul>';
    }

    Archive::getPostTypeArchives( array(
      'type'            => $type,
      'format'          => $isList ? 'option' : 'html',
      'post_type'       => $postType,
      'show_post_count' => $postCount
    ) );

    echo $isList ? '</select>' : '</ul>';
    do_action( 'wp_parsidate_archive_widget_end', $title, $postType, $type, $postCount, $isList, $widgetID );

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $args['after_widget'];
  }
}
