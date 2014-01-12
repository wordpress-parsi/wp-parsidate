<?php
/**
 * @author lord_viper
 * @copyright 2013
 */
class parsidate_archive extends WP_Widget
{
    public function __construct()
    {
       parent::WP_Widget(false,'بایگانی تاریخ خورشیدی','description=بایگانی تاریخ خورشیدی');
    }
    
    public function form($instance)
    {
        $type=(empty($instance['parsidate_archive_type'])?'monthly':$instance['parsidate_archive_type']);
        ?>
        <p style="text-align:right; direction:rtl">
        <label>عنوان:</label> 
        <input style="width: 200px;" id="<?php echo $this->get_field_id('parsidate_archive_title'); ?>" name="<?php echo $this->get_field_name('parsidate_archive_title'); ?>" type="text" value="<?php echo (empty($instance['parsidate_archive_title'])?'بایگانی تاریخ خورشیدی':$instance['parsidate_archive_title']) ?>" />
        <br />
        <input type="radio" id="parsidate_archive_type1" name="<?php echo $this->get_field_name('parsidate_archive_type'); ?>" value="yearly" <?php checked($type,'yearly'); ?>/><label id="parsidate_archive_type1">سالانه</label><br />
        <input type="radio" id="parsidate_archive_type2" name="<?php echo $this->get_field_name('parsidate_archive_type'); ?>" value="monthly" <?php checked($type,'monthly'); ?>/><label id="parsidate_archive_type2">ماهانه</label><br />
        <input type="radio" id="parsidate_archive_type3" name="<?php echo $this->get_field_name('parsidate_archive_type'); ?>" value="daily" <?php checked($type,'daily'); ?>/><label id="parsidate_archive_type3">روزانه</label><br />
        <br />
        <input type="checkbox" name="<?php echo $this->get_field_name('parsidate_archive_count'); ?>" id="<?php echo $this->get_field_id('parsidate_archive_count'); ?>" <?php echo $instance['parsidate_archive_count']; ?>/>
        <label for="<?php echo $this->get_field_id('parsidate_archive_count'); ?>">نمایش تعداد نوشته ها</label>
        <br />
        <input type="checkbox" name="<?php echo $this->get_field_name('parsidate_archive_list'); ?>" id="<?php echo $this->get_field_id('parsidate_archive_list'); ?>" <?php echo $instance['parsidate_archive_list']; ?>/>
        <label for="<?php echo $this->get_field_id('parsidate_archive_list'); ?>">نمایش به صورت لیست بازشو</label>
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
        $title      = (empty($instance['parsidate_archive_title']))?'بایگانی تاریخ خورشیدی':$instance['parsidate_archive_title'];
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
			wp_get_parchives("type=$type&show_post_count=$post_count",$ddl_style);
			echo '</ul>';
        }
        echo $after_widget;
    }
}

add_action( 'widgets_init', 'register_wpb_widgets' );

function register_wpb_widgets()
{
    register_widget('parsidate_archive');
}
?>