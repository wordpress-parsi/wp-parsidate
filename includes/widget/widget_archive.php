<?php

/**
 * @author lord_viper
 * @copyright 2013
 */
class parsidate_archive extends WP_Widget
{
    public function __construct()
    {
        global $wp_version;

        // backwards compability
        if (version_compare($wp_version, '4.3', '>=')) {
            parent::__construct(false, __('Jalali Date Archives', 'wp-parsidate'), 'description=' . __('Jalali Date Archives', 'wp-parsidate'));
        } else {
            parent::WP_Widget(false, __('Jalali Date Archives', 'wp-parsidate'), 'description=' . __('Jalali Date Archives', 'wp-parsidate'));
        }
    }

    public function form($instance)
    {
        global $wpp_settings;
        $type = isset($instance['parsidate_archive_type']) ? $instance['parsidate_archive_type'] : 'monthly';
        $instance['parsidate_archive_title'] = isset($instance['parsidate_archive_title']) ? strip_tags($instance['parsidate_archive_title']) : __('Jalali Date Archives', 'wp-parsidate');
        $instance['parsidate_archive_count'] = isset($instance['parsidate_archive_count']) ? $instance['parsidate_archive_count'] : 0;
        $instance['parsidate_archive_list'] = isset($instance['parsidate_archive_list']) ? $instance['parsidate_archive_list'] : 0;

        ?>
        <p style="text-align:right; direction:rtl">
            <label></label>
            <input style="width: 200px;" id="<?php echo $this->get_field_id('parsidate_archive_title'); ?>"
                   name="<?php echo $this->get_field_name('parsidate_archive_title'); ?>" type="text"
                   value="<?php echo(empty($instance['parsidate_archive_title']) ? __('Jalali Date Archives', 'wp-parsidate') : $instance['parsidate_archive_title']) ?>"/>
            <br/>
            <label><input type="radio" id="parsidate_archive_type1"
                          name="<?php echo $this->get_field_name('parsidate_archive_type'); ?>"
                          value="yearly" <?php checked($type, 'yearly'); ?>/><label
                        for="parsidate_archive_type1"><?php _e('Yearly', 'wp-parsidate') ?></label><br/>
                <label><input type="radio" id="parsidate_archive_type2"
                              name="<?php echo $this->get_field_name('parsidate_archive_type'); ?>"
                              value="monthly" <?php checked($type, 'monthly'); ?>/><?php _e('Monthly', 'wp-parsidate') ?>
                </label><br/>
                <label><input type="radio" id="parsidate_archive_type3"
                              name="<?php echo $this->get_field_name('parsidate_archive_type'); ?>"
                              value="daily" <?php checked($type, 'daily'); ?>/><?php _e('Daily', 'wp-parsidate') ?>
                </label><br/>
                <br/>
                <input type="checkbox" name="<?php echo $this->get_field_name('parsidate_archive_count'); ?>"
                       id="<?php echo $this->get_field_id('parsidate_archive_count'); ?>"
                       value="1" <?php checked($instance['parsidate_archive_count'], 1, true); ?>/>
                <label for="<?php echo $this->get_field_id('parsidate_archive_count'); ?>"><?php _e('Show post counts', 'wp-parsidate') ?></label>
                <br/>
                <input type="checkbox" name="<?php echo $this->get_field_name('parsidate_archive_list'); ?>"
                       id="<?php echo $this->get_field_id('parsidate_archive_list'); ?>"
                       value="1" <?php echo checked($instance['parsidate_archive_list'], 1, true); ?>/>
                <label for="<?php echo $this->get_field_id('parsidate_archive_list'); ?>"><?php _e('Display as dropdown', 'wp-parsidate') ?></label>
        </p>
        <?php
        if ($wpp_settings['conv_permalinks'] == 'disable') {
            echo "<p style='color: #ff8153'>" . __('For use widget, active "Fix permalinks dates" option in plugin settings.', 'wp-parsidate') . "</p>";
        }
    }

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['parsidate_archive_title'] = isset($new_instance['parsidate_archive_title']) ? strip_tags($new_instance['parsidate_archive_title']) : __('Jalali Date Archives', 'wp-parsidate');
        $instance['parsidate_archive_count'] = isset($new_instance['parsidate_archive_count']) ? $new_instance['parsidate_archive_count'] : 0;
        $instance['parsidate_archive_list'] = isset($new_instance['parsidate_archive_list']) ? $new_instance['parsidate_archive_list'] : 0;
        $instance['parsidate_archive_type'] = isset($new_instance['parsidate_archive_type']) ? $new_instance['parsidate_archive_type'] : 'monthly';

        return $instance;
    }

    public function widget($args, $instance)
    {
        global $wpp_settings;
        if ($wpp_settings['conv_permalinks'] == 'disable') {
            return;
        }

        $type = isset($instance['parsidate_archive_type']) ? $instance['parsidate_archive_type'] : 'monthly';
        $title = isset($instance['parsidate_archive_title']) ? $instance['parsidate_archive_title'] : __('Jalali Date Archives', 'wp-parsidate');
        $post_count = isset($instance['parsidate_archive_count']) ? $instance['parsidate_archive_count'] : false;
        $ddl_style = isset($instance['parsidate_archive_list']) ? $instance['parsidate_archive_list'] : false;

        echo $args['before_widget'];
        if (!empty($instance['parsidate_archive_title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['parsidate_archive_title']) . $args['after_title'];
        }

        if ($ddl_style) {
            echo "<select name='parsidate_archive_list' onchange='document.location.href=this.options[this.selectedIndex].value;'> <option value='0'>" . esc_attr($title) . "</option>";
            wp_get_parchives("type=$type&format=option&show_post_count=$post_count");
            echo '</select>';
        } else {
            echo '<ul>';
            wpp_get_archives("type=$type&show_post_count=$post_count");
            echo '</ul>';
        }
        echo $args['after_widget'];
    }
}