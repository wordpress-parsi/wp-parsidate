<?php

/**
 * @author lord_viper
 * @copyright 2013
 */
class parsidate_calendar extends WP_Widget
{
    public function __construct()
    {
        global $wp_version;
        if (version_compare($wp_version, '4.3', '>=')) {
            parent::__construct(false, __('Jalali Date Calender', 'wp-parsidate'), 'description=' . __('Jalali Date Calender', 'wp-parsidate'));
        } else {
            parent::WP_Widget(false, __('Jalali Date Calender', 'wp-parsidate'), 'description=' . __('Jalali Date Calender', 'wp-parsidate'));
        }
    }

    public function form($instance)
    {
        global $wpp_settings;
        ?>
        <p style="text-align:right; direction:rtl">
            <label><?php _e('Title:', 'wp-parsidate') ?></label>
            <input style="width: 200px;" id="<?php echo $this->get_field_id('parsidate_calendar_title'); ?>"
                   name="<?php echo $this->get_field_name('parsidate_calendar_title'); ?>" type="text"
                   value="<?php echo(empty($instance['parsidate_calendar_title']) ? __('Jalali Date Calender', 'wp-parsidate') : $instance['parsidate_calendar_title']) ?>"/>
        </p>
        <?php
        if ($wpp_settings['conv_permalinks'] == 'disable') {
            echo "<p style='color: #ff8153'>" . __('For use widget, active "Fix permalinks dates" option in plugin settings.', 'wp-parsidate') . "</p>";
        }
    }

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['parsidate_calendar_title'] = strip_tags($new_instance['parsidate_calendar_title']);

        return $instance;
    }

    public function widget($args, $instance)
    {
        global $wpp_settings;
        if ($wpp_settings['conv_permalinks'] == 'disable') {
            return;
        }
        echo $args['before_widget'];
        if (!empty($instance['parsidate_calendar_title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['parsidate_calendar_title']) . $args['after_title'];
        }
        wpp_get_calendar();
        echo $args['after_widget'];
    }
}