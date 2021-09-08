<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

/**
 * @author lord_viper
 * @copyright 2013
 */
class parsidate_calendar extends WP_Widget {
    public function __construct() {
        global $wp_version;

        if ( version_compare( $wp_version, '4.3', '>=' ) ) {
            parent::__construct( false, __( 'Jalali Date Calender', 'wp-parsidate' ), 'description=' . __( 'Jalali Date Calender', 'wp-parsidate' ) );
        } else {
            parent::WP_Widget( false, __( 'Jalali Date Calender', 'wp-parsidate' ), 'description=' . __( 'Jalali Date Calender', 'wp-parsidate' ) );
        }
    }

    /**
     * Outputs the settings update form.
     *
     * @param array $instance Current settings.
     *
     * @return void Default return is 'noform'.
     * @since 2.8.0
     *
     */
    public function form( $instance ) {
        $title = ! empty( $instance['parsidate_calendar_title'] ) ? $instance['parsidate_calendar_title'] : __( 'Jalali Date Calender', 'wp-parsidate' );
        $theme = ! empty( $instance['theme_color'] ) ? $instance['theme_color'] : 'light-mode';

        if ( ! wpp_is_active( 'conv_permalinks' ) ) {
            echo "<p style='color: #ff8153'>" . __( 'For use widget, active "Fix permalinks dates" option in plugin settings.', 'wp-parsidate' ) . "</p>";
        }
        ?>
        <p style="text-align:right; direction:rtl">
            <label for="<?php echo $this->get_field_id( 'parsidate_calendar_title' ); ?>">
                <?php _e( 'Title:', 'wp-parsidate' ) ?></label>

            <input style="width:calc(100% - 120px);float:left" id="<?php echo $this->get_field_id( 'parsidate_calendar_title' ); ?>"
                   name="<?php echo $this->get_field_name( 'parsidate_calendar_title' ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>"/>
        </p>

        <p style="text-align:right; direction:rtl">
            <label for="<?php echo $this->get_field_id( 'theme-color' ); ?>">
                <?php _e( 'Theme color:', 'wp-parsidate' ) ?></label>

            <select style="width:calc(100% - 120px);float:left" id="<?php echo $this->get_field_id( 'theme-color' ); ?>"
                    name="<?php echo $this->get_field_name( 'theme-color' ); ?>">
                <option value="light-mode" <?php selected( $theme, 'light-mode' ); ?>>
                    <?php _e( 'Light Mode', 'wp-parsidate' ) ?>
                </option>
                <option value="dark-mode" <?php selected( $theme, 'dark-mode' ); ?>>
                    <?php _e( 'Dark Mode', 'wp-parsidate' ) ?>
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
     * @since 2.8.0
     *
     */
    public function update( $new_instance, $old_instance ) {
        $instance                             = $old_instance;
        $instance['parsidate_calendar_title'] = esc_html( $new_instance['parsidate_calendar_title'] );
        $instance['theme_color']              = esc_attr( $new_instance['theme-color'] );

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
     * @since 2.8.0
     *
     */
    public function widget( $args, $instance ) {
        if ( ! wpp_is_active( 'conv_permalinks' ) ) {
            return;
        }

        $theme = ! empty( $instance['theme_color'] ) ? $instance['theme_color'] : 'light-mode';

        echo $args['before_widget'];

        if ( ! empty( $instance['parsidate_calendar_title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['parsidate_calendar_title'] ) . $args['after_title'];
        }

        wpp_get_calendar();

        echo $args['after_widget'];

        if ( $theme === 'dark-mode' ) {
            echo '<style>.widget_parsidate_calendar{background:#141414;border-radius:8px 8px 4px 4px;' .
                 'overflow:hidden;box-shadow:0 0 5px 0 #000;text-align:center;padding-top:15px;color:#dcdcdc}'.
                 '.widget_parsidate_calendar table{direction:rtl;border-radius:12px;overflow:hidden;'.
                 'background:#1d1d1d;box-shadow:inset 0 0 0 6px #141414}.widget_parsidate_calendar table th,'.
                 '.widget_parsidate_calendar table td{border:0}.widget_parsidate_calendar table th:last-child,'.
                 '.widget_parsidate_calendar table tr td:last-child{color:#f28a8a}</style>';
        } else {
            echo '<style>.widget_parsidate_calendar{background:#dbdbdb;border-radius:12px;overflow:hidden;'.
                 'box-shadow:0 0 15px 0 #0000004f,inset 0 0 0 1px #8080806e;text-align:center;padding-top:15px;'.
                 'color:#1e1e1e}.widget_parsidate_calendar table{direction:rtl;border-radius:9px;overflow:hidden;'.
                 'background:#fdfdfd;box-shadow:0 -13px 14px 0 #8080801a}.widget_parsidate_calendar table th,'.
                 '.widget_parsidate_calendar table td{border:0}.widget_parsidate_calendar table th:last-child,'.
                 '.widget_parsidate_calendar table tr td:last-child{color:#bf4a4a}</style>';
        }
    }
}