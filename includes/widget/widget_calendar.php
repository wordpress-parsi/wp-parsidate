<?php
/**
 * @author lord_viper
 * @copyright 2013
 */
 
class parsidate_calendar extends WP_Widget
{
    public function __construct()
    {
       parent::WP_Widget(false,__('Jalali Date Calender','wp-parsidate'),'description='.__('Jalali Date Calender','wp-parsidate'));
    }
    
    public function form($instance)
    {
        ?>
        <p style="text-align:right; direction:rtl">
        <label><?php _e('Title:','wp-parsidate') ?></label>
        <input style="width: 200px;" id="<?php echo $this->get_field_id('parsidate_calendar_title'); ?>" name="<?php echo $this->get_field_name('parsidate_calendar_title'); ?>" type="text" value="<?php echo (empty($instance['parsidate_calendar_title'])? __('Jalali Date Calender','wp-parsidate') :$instance['parsidate_calendar_title']) ?>" />
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance )
    {
        $instance = $old_instance;
        $instance['parsidate_calendar_title'] = strip_tags($new_instance['parsidate_calendar_title']);
        return $instance;
    }       

    public function widget($args ,$instance)
    {
        extract($args);
        $title      = (empty($instance['parsidate_calendar_title']))?'':$instance['parsidate_calendar_title'];
        echo "$before_widget\n$before_title $title $after_title";
        wpp_get_calendar();
        echo $after_widget;
    }    
}
?>