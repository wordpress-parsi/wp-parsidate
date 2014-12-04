<?php
/**
 * @author lord_viper
 * @copyright 2013
 */	
    
class parsidate_archive extends WP_Widget
{
    public function __construct()
    {
       parent::WP_Widget(false,__('Jalali Date Archives','wp-parsidate'),'description='.__('Jalali Date Archives','wp-parsidate'));
    }
    
    public function form($instance)
    {
        $type=(empty($instance['parsidate_archive_type'])?'monthly':$instance['parsidate_archive_type']);
        ?>
        <p style="text-align:right; direction:rtl">
        <label></label>
        <input style="width: 200px;" id="<?php echo $this->get_field_id('parsidate_archive_title'); ?>" name="<?php echo $this->get_field_name('parsidate_archive_title'); ?>" type="text" value="<?php echo (empty($instance['parsidate_archive_title'])? __('Jalali Date Archives','wp-parsidate') :$instance['parsidate_archive_title']) ?>" />
        <br />
            <label ><input type="radio" id="parsidate_archive_type1" name="<?php echo $this->get_field_name('parsidate_archive_type'); ?>" value="yearly" <?php checked($type,'yearly'); ?>/><label for="parsidate_archive_type1"><?php _e('Yearly','wp-parsidate') ?></label><br />
            <label ><input type="radio" id="parsidate_archive_type2" name="<?php echo $this->get_field_name('parsidate_archive_type'); ?>" value="monthly" <?php checked($type,'monthly'); ?>/><?php _e('Monthly','wp-parsidate') ?></label><br />
            <label ><input type="radio" id="parsidate_archive_type3" name="<?php echo $this->get_field_name('parsidate_archive_type'); ?>" value="daily" <?php checked($type,'daily'); ?>/><?php _e('Daily','wp-parsidate') ?></label><br />
        <br />
        <input type="checkbox" name="<?php echo $this->get_field_name('parsidate_archive_count'); ?>" id="<?php echo $this->get_field_id('parsidate_archive_count'); ?>" <?php echo $instance['parsidate_archive_count']; ?>/>
        <label for="<?php echo $this->get_field_id('parsidate_archive_count'); ?>"><?php _e('Show post counts','wp-parsidate') ?></label>
        <br />
        <input type="checkbox" name="<?php echo $this->get_field_name('parsidate_archive_list'); ?>" id="<?php echo $this->get_field_id('parsidate_archive_list'); ?>" <?php echo $instance['parsidate_archive_list']; ?>/>
        <label for="<?php echo $this->get_field_id('parsidate_archive_list'); ?>"><?php _e('Display as dropdown','wp-parsidate') ?></label>
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance )
    {
        $instance=$old_instance;
        $instance['parsidate_archive_title']= strip_tags($new_instance['parsidate_archive_title']);
        $instance['parsidate_archive_count']= (empty($new_instance['parsidate_archive_count']))?'':'checked';
        $instance['parsidate_archive_list'] = (empty($new_instance['parsidate_archive_list'] ))?'':'checked';
        $instance['parsidate_archive_type'] = $new_instance['parsidate_archive_type'];
        return $instance;
    }
    
    public function widget($args ,$instance)
    {
        extract($args);
        $type       =  $instance['parsidate_archive_type'];
        $title      = (empty($instance['parsidate_archive_title']))? __('Jalali Date Archives','wp-parsidate') :$instance['parsidate_archive_title'];
        $post_count = (empty($instance['parsidate_archive_count']))?false:true;
        $ddl_style  = (empty($instance['parsidate_archive_list'] ))?false:true;
        echo "$before_widget
		      $before_title $title $after_title";
        if($ddl_style)
        {
			echo "<select name='parsidate_archive_list' onchange='document.location.href=this.options[this.selectedIndex].value;'> <option value='0'>".esc_attr($title)."</option>";
			wp_get_parchives("type=$type&format=option&show_post_count=$post_count",$ddl_style);
			echo '</select>';
        }
        else
        {
        	echo '<ul>';
			wpp_get_archives("type=$type&show_post_count=$post_count",$ddl_style);
			echo '</ul>';
        }
        echo $after_widget;
    }
}
?>