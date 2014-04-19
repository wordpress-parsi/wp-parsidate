<?php

/*
Plugin Name: WP-Parsidate
Version: 1.3.1
Author: Mobin Ghasempoor
Author URI: http://wp-parsi.com/
Plugin URI: http://forum.wp-parsi.com/
Description: Persian package builder for WordPress, Full RTL and Shamsi(Jalali) date in: posts, comments, pages, archives, search, categories, permalinks and all admin sections such as tinymce editor, posts lists, post quick edit, comments lists, comments quick edit, pages lists, pages quick edit. This package already has Shamsi(Jalali) archie widget.
*/

/* 
Special thanks to :
 	Wordpress Parsi admins and moderators (Parsa Kafi, Mohsen Ghiasi, Saeed Fard, Abdolmajed Shahbakhsh, Morteza Rocky and Mostafa Soufi)
 	Wordpress Parsi forum members for great support(forum.wp-parsi.com)
*/

define('wp_parsipath', dirname(__file__));
define('wp_contentpath',dirname(dirname(wp_parsipath)));
global $timezone,$persian_month_names;
$persian_month_names = array('','فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند');

@define('WP_MEMORY_LIMIT', '64M');
$timezone = get_option('timezone_string');
if(empty($timezone))
$timezone='Asia/Tehran';
date_default_timezone_set($timezone);

include_once (join(DIRECTORY_SEPARATOR,array(wp_parsipath,'lib','parsidate.php')));
include_once (join(DIRECTORY_SEPARATOR,array(wp_parsipath,'lib','functions.php')));
include_once (join(DIRECTORY_SEPARATOR,array(wp_parsipath,'widegets.php')));
include_once (join(DIRECTORY_SEPARATOR,array(wp_parsipath,'admin.php')));
register_activation_hook( __FILE__,'parsidate_plugin_install');

function parsidate_plugin_install()
{ 
    if (!is_dir(join(DIRECTORY_SEPARATOR,array(wp_contentpath,'languages')))) 
        mkdir(join(DIRECTORY_SEPARATOR,array(wp_contentpath,'languages')));

    $source      = join(DIRECTORY_SEPARATOR,array(wp_parsipath,'languages','*'));
    $destination = join(DIRECTORY_SEPARATOR,array(wp_contentpath,'languages'));
    $files       = glob($source);

    foreach($files as $sfile)
    {
        @copy($sfile, $destination.DIRECTORY_SEPARATOR.basename($sfile));
    }
}

if(isset($_POST['wp_parsidate_save']))
{
    $val['sep_fixdate']    = $_POST['sep_fixdate'];
    $val['sep_persian']    = $_POST['sep_persian'];
    $val['sep_titlenum']   = (isset($_POST['sep_titlenum'])  ?'checked':'');
    $val['sep_postnum']    = (isset($_POST['sep_postnum'])   ?'checked':'');
    $val['sep_commentnum'] = (isset($_POST['sep_commentnum'])?'checked':'');
    $val['sep_commentcnt'] = (isset($_POST['sep_commentcnt'])?'checked':'');
    $val['sep_datesnum']   = (isset($_POST['sep_datesnum'])  ?'checked':'');
    $val['sep_catnum']     = (isset($_POST['sep_catnum'])    ?'checked':'');
    $val['sep_excnum']     = (isset($_POST['sep_excnum'])    ?'checked':'');
    $val['sep_fixarabic']  = $_POST['sep_fixarabic'];
    $val['sep_fixurl']     = $_POST['sep_fixurl'];
    $val['sep_planet']     = $_POST['sep_planet'];
    
    update_option('parsidate_option',$val);        
}

global $val;

$val = get_option('parsidate_option');

if(empty($val))
{
    $val['sep_fixdate']    = 'بلی';
    $val['sep_persian']    = 'بلی';
    $val['sep_titlenum']   = '';
    $val['sep_postnum']    = '';
    $val['sep_commentnum'] = '';
    $val['sep_commentcnt'] = '';
    $val['sep_datesnum']   = '';
    $val['sep_catnum']     = '';
    $val['sep_fixarabic']  = 'بلی';
    $val['sep_fixurl']     = 'بلی';
    $val['sep_planet']     = 0;
    $val['sep_excnum']     = '';
}

if($val['sep_persian']=='بلی')
add_filter('locale','new_locale');

function new_locale()
{
    return 'fa_IR';
}
/*
* admin config page
*/
add_action('admin_menu', 'add_persiandate_menu');

function add_persiandate_menu()
{
    add_menu_page('تنظیمات پارسی', 'تنظیمات پارسی', 'add_users', 'parsi_plugin_page','parsi_plugin_page', '');
}

/*
* fix theme editor rtl
*/
add_action( 'admin_print_styles-plugin-editor.php', 'theme_editor_add_init', 11 );
add_action( 'admin_print_styles-theme-editor.php', 'theme_editor_add_init', 11 );
function theme_editor_add_init(){
    wp_enqueue_style("functions", plugins_url(basename(wp_parsipath)."/css/admin.css"), false, "1.0", "all");
}
	
	
/*
* fix tiny mce rtl
*/
add_filter('tiny_mce_before_init', 'wpb_mce_set_direction',1000);

function wpb_mce_set_direction($input)
{
    $input['content_css']=plugins_url(basename(wp_parsipath).'/css/editor.css');
    return $input;
}

/*
* hooks and filters for persian date
*/

if($val['sep_fixdate']=='بلی')
{
    if(!detect_rss())
    {
        add_filter('the_time', 'add_ptime',1001,2);
        add_filter('the_date', 'add_pdate',1001,2);
        add_action('date_i18n', 'add_pi18n',1001,3);//revision
        add_filter('get_comment_time', 'add_ctime',1001,2);
        add_filter('get_comment_date', 'add_cdate',1001,2);
    }
}

function add_ptime($time,$format='')
{
    global $post,$val;
    if(empty($format))
    $format=get_option('time_format');
    if(empty($val['sep_datesnum']))
    return parsidate($format,$post->post_date,'eng');
    else
    return parsidate($format,$post->post_date);
}

function add_pdate($time,$format='')
{
    global $post,$val;
    if(empty($format))
    $format=get_option('date_format');
    if(empty($val['sep_datesnum']))
    return parsidate($format,$post->post_date,'eng');
    else
    return parsidate($format,$post->post_date);
}

function add_ctime($time,$format='')
{
    global $comment,$val;
    if(empty($format))
    $format=get_option('time_format');
    if(empty($val['sep_datesnum']))
    return parsidate($format,$comment->comment_date,'eng');
    else
    return parsidate($format,$comment->comment_date);
}

function add_cdate($time,$format='')
{
    global $comment,$val;
    if(empty($format))
    $format=get_option('date_format');
    if(empty($val['sep_datesnum']))
    return parsidate($format,$comment->comment_date,'eng');
    else
    return parsidate($format,$comment->comment_date); 
}

function add_pi18n($dateformatstring,$unixtimestamp,$gmt)
{
    global $val;
    if(empty($val['sep_datesnum']))
    return parsidate($unixtimestamp,$gmt,'eng');
    else
    return parsidate($unixtimestamp,$gmt);
}
/*
* fix persian numbers
*/
if(!empty($val['sep_postnum']))
    add_filter('the_content', 'fixnumber');
if(!empty($val['sep_titlenum']))
    add_filter('the_title', 'fixnumber');
if(!empty($val['sep_commentnum']))
    add_filter('comment_text', 'fixnumber');
if(!empty($val['sep_commentcnt']))
    add_filter('comments_number', 'fixnumber');
if(!empty($val['sep_catnum']))
    add_filter('wp_list_categories', 'fixnumber');
if(!empty($val['sep_excnum']))
    add_filter('the_excerpt', 'fixnumber');
        
/*
* fix arabic characters
*/
if($val['sep_fixarabic']=='بلی')
{
    add_filter('the_content', 'fixarabic');
    add_filter('the_title', 'fixarabic');
    add_filter('comment_text', 'fixarabic');
    add_filter('wp_list_categories', 'fixarabic');
    add_filter('the_excerpt', 'fixarabic');
}
/*
*fix archive title
*/
add_filter( 'wp_title', 'wp_pdtitle', 1001,3);

function wp_pdtitle($title, $sep,$seplocation)
{
    global $persian_month_names,$wp_query;
    $query=$wp_query->query;

    if(!is_archive() or (is_archive() and !isset($query['monthnum']) ))
    return $title;
    if($seplocation=='right')
    $query = array_reverse($query);
    $query['name']=get_option('blogname');
    $query['monthnum']=$persian_month_names[intval($query['monthnum'])];
    return fixnumber(implode(" $sep ",$query));
}
/*
* fix persian permalink
*/
if ($val['sep_fixurl']=='بلی')
{
    add_filter("post_link","get_pdpermalink",10,3);
    add_action( 'pre_get_posts','wppd_pre_get_posts');
    add_filter( 'posts_where' , 'wppd_posts_where');
}

function get_pdpermalink($perma, $post,$leavename = false)
{
    if(empty($post->ID))
        return false;
	if ( $post->post_type == 'page' ||  $post->post_status == 'static')
	    return get_page_link($post->ID);
	elseif ( $post->post_type == 'attachment' )
	    return get_attachment_link( $post->ID);
	elseif ( in_array($post->post_type, get_post_types( array('_builtin' => false))))
	    return get_post_permalink($post->ID);
    $permalink  = get_option('permalink_structure');
    preg_match_all('/%([^\/]*)%/',$permalink,$rewritecode);
    $rewritecode = $rewritecode[0];
	if ( '' != $permalink && !in_array($post->post_status, array('draft', 'pending', 'auto-draft')))
    {
        if($leavename)
        $rewritecode=array_diff($rewritecode,array('%postname%','%pagename%'));
        
        $date= explode(" ",parsidate('Y m d H i s',$post->post_date,'eng'));
        $out=array();
        foreach($rewritecode as $rewrite)
        {
            switch($rewrite)
            {
                case'%year%':
                    $out[] = $date[0];
                break;
                case'%monthnum%':
                    $out[] = $date[1];
                break;
                case'%day%':
                    $out[] = $date[2];
                break;
                case'%hour%':
                    $out[] = $date[3];
                break;
                case'%minute%':
                    $out[] = $date[4];
                break;
                case'%second%':
                    $out[] = $date[5];
                break;
                case'%postname%':
                    $out[] = $post->post_name;
                break;
                case'%post_id%':
                    $out[] = $post->ID;
                break;
                case'%category%':
                    $category='';
                	$cats = get_the_category($post->ID);
	                if ($cats)
                    {
	                     usort($cats, '_usort_terms_by_ID');
	                     $category = $cats[0]->slug;
	                     if ( $parent = $cats[0]->parent )
	                     $category = get_category_parents($parent, false, '/', true) . $category;
	                }
	                if (empty($category))
                    {
                        $default_category = get_term( get_option('default_category'),'category');
	                    $category = is_wp_error( $default_category ) ? '' : $default_category->slug;
	                }
                    $out[] = $category;
                break;
                case'%author%':
                	$authordata = get_userdata($post->post_author);
	                $out[]      = $authordata->user_nicename;
                break;
                case'%pagename%':
                    $out[] = $post->post_name;
                break;
                default:unset($rewritecode[array_search($rewrite,$rewritecode)]);
            }
        }
	    $permalink = home_url( str_replace($rewritecode, $out, $permalink));
	    return user_trailingslashit($permalink, 'single');
	}
    else
	 return home_url("?p=$post->ID");
}

function wppd_pre_get_posts( $query )
{
    global $wpdb;
    $permalink = $query->query;
    $year      = '';
    $monthnum  = '';
    $day       = '';
    if(isset($permalink['year']))
    $year=$permalink['year'];
    if(isset($permalink['monthnum']))
    $monthnum=$permalink['monthnum'];
    if(isset($permalink['day']))
    $day=$permalink['day'];
    if(!empty($year)||!empty($monthnum)||!empty($day))
    {
        $cnt      = '';
        $post_id  = '';
        $name     = '';
        $out      = false;
        if(isset($permalink['name']))
        {
            $name = $permalink['name'];
            $var  = $wpdb->get_var("select post_date from {$wpdb->prefix}posts where post_name='$name' order by id");
            $per  = parsidate('Y-m-d',$var,'eng');
            update_option('options',$per);
            $per  = explode('-',$per);
            $out  = true;
            if(!empty($year))
            if($year != $per[0])
            $out = false;
            if($out and !empty($monthnum))
            if($monthnum!=$per[1])
            $out = false;
            if($out and !empty($day))
            if($day != $per[2])
            $out = false;
        }
        elseif($permalink['post_id'])
        {
            $out     = true;
            $post_id = $permalink['post_id'];
            $var     = $wpdb->get_var("select post_date from {$wpdb->prefix}wp_posts where ID=$post_id");
        }
        elseif(!empty($year)and!empty($monthnum)and!empty($day))
        {
            $out = true;
            $var = gregdate('Y-m-d',"$year-$monthnum-$day");
        }

        if($out)       
        {
            preg_match_all('!\d+!', $var, $matches);
            $var=$matches[0];
            $query->set( 'year', $var[0]);
            $query->set( 'monthnum', $var[1]);
            $query->set( 'day', $var[2]);
        }
        return $query;
    }
    else
    return $query;
}

function wppd_posts_where($where)
{
    global $wp_query, $wpdb,$pagenow;
    if(empty($wp_query->query_vars))
    return $where;
    $j_days_in_month = array('',31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
   	$m      = $wp_query->query_vars['m'];
	$hour   = $wp_query->query_vars['hour'];
	$minute = $wp_query->query_vars['minute'];
	$second = $wp_query->query_vars['second'];
	$year   = $wp_query->query_vars['year'];
	$month  = $wp_query->query_vars['monthnum'];
	$day    = $wp_query->query_vars['day'];
    
   if(!empty($m))
   {
        $len    = strlen($m);
        $year   = substr($m, 0,4);
        if($len>5)
        $month  = substr($m, 4, 2);
        if($len>7)
        $day    = substr($m, 6, 2);
        if($len>9)
        $hour   = substr($m, 8, 2);
        if($len>11)
        $minute = substr($m, 10, 2);
        if($len>13)
        $second = substr($m, 12, 2);
   }

   if(empty($year)|| $year>1700)
   return $where;

   $stamon = 1;
   $staday = 1;
   $stahou = '00';
   $stamin = '00';
   $stasec = '00';
   $endmon = 1;
   $endday = 1;
   $endhou = '00';
   $endmin = '00';
   $endsec = '00';
   
   $stayear=$year;
   $endyear=$year+1;
   if($month!='')
   {
       $stamon  = $month;
       $endmon  = ($month==12?1:$month+1);
       $endyear = ($endmon==1?$stayear+1:$stayear);
   }
   if($day!='')
   {
       $staday = $day;
       $endday = ($day==$j_days_in_month[$month]?1:$day+1);
       $endmon = ($endday==1?$stamon+1:$stamon);
   }
   if($hour!='')
   {
       $stahou = $hour;
       $endhou = ($hour==24?'00':$hour+1);
       $endday = ($endhou=='00'?$staday+1:$staday);
   }
   if($minute!='')
   {
       $stamin=$minute;
       $endmin=($minute==59?'00':$minute+1);
       $endhou=($endmin=='00'?$stahou+1:$stahou);
   }
   if($second!='')
   {
        $stasec=$second;
        $endsec=($second==59?'00':$second+1);
        $endmin=($endsec=='00'?$stamin+1:$stamin);
   }
   $stadate = "$stayear-$stamon-$staday";
   $enddate = "$endyear-$endmon-$endday";
   $stadate = gregdate('Y-m-d',$stadate);
   $enddate = gregdate('Y-m-d',$enddate);
   $stadate.=" $stahou:$stamin:$stasec";
   $enddate.=" $endhou:$endmin:$endsec";
   $paterns = array('/YEAR\((.*?)post_date\s*\)\s*=\s*[0-9\']*/',
                    '/DAYOFMONTH\((.*?)post_date\s*\)\s*=\s*[0-9\']*/',
                    '/MONTH\((.*?)post_date\s*\)\s*=\s*[0-9\']*/',
                    '/HOUR\((.*?)post_date\s*\)\s*=\s*[0-9\']*/',
                    '/MINUTE\((.*?)post_date\s*\)\s*=\s*[0-9\']*/',
                    '/SECOND\((.*?)post_date\s*\)\s*=\s*[0-9\']*/');
   foreach($paterns as $ptn)
   {
        $where = preg_replace($ptn,'1=1',$where);
   }
   $prefixp = "{$wpdb->posts}.";
   $prefixp = (strpos($where, $prefixp) === false) ? '' : $prefixp;
   $where  .= " AND {$prefixp}post_date >= '$stadate' AND {$prefixp}post_date < '$enddate' ";
   return $where; 
}
/*
* fix admin edit section
*/
add_action('admin_footer','parsidate_js',1);

function parsidate_js()
{
    $dirname=basename(dirname(__file__));
    echo "<script type='text/javascript' src='".plugins_url()."/$dirname/js/parsidate.js'></script>";
}
/*
* fix search dropdownlist admin edit.php
*/
add_action('load-edit.php', 'wppd_admin_init');

function wppd_admin_init()
{
    add_action('restrict_manage_posts','wppd_restrict_manage_posts');
    add_filter('posts_where', 'wp_admin_posts_where');
}

function wp_admin_posts_where($where)
{
    global $wp_query;
	if( isset($_GET['mfa']) and $_GET['mfa'] != '0' )
	{
		$wp_query->query_vars['m'] = $_GET['mfa'];
		$where = wppd_posts_where($where);
	}
	return $where;
}

function wppd_restrict_manage_posts()
{
	global $post_type, $wpdb,$persian_month_names;
	$list = $wpdb->get_col("SELECT DISTINCT date( post_date ) AS date 
            FROM $wpdb->posts
			WHERE post_type = '$post_type' AND post_status <> 'auto-draft' AND date( post_date )<>'0000-00-00'
			ORDER BY post_date DESC");
	if ( empty($list))
	return;
	$m = isset( $_GET['mfa'] ) ? (int) $_GET['mfa'] : 0;

	echo '<select name="mfa">';
	echo "<option ".selected( $m, 0 ,false)." value='0'>".__( 'Show all dates','wp-persian' )."</option>\n";
	foreach ( $list as $date )
    {
		$date  = parsidate('Ym',$date,'eng');
        $year  = substr($date,0,4);
        $month = substr($date,4,2);
        $month=$persian_month_names[intval($month)];
		if($predate != $date)
		echo "<option %s value='$date'".selected( $m, $date, false ).">$month ".fixnumber($year)."</option>\n";
		$predate = $date;
	}
	echo '</select>';
}
//___________________________________________________persian archives _______________________________________________
    function wp_get_parchives($args='')
    {
        global $wpdb, $wp_locale,$persian_month_names;
	    $defaults = array(
	           'type' => 'monthly', 'limit' => '',
	           'format' => 'html', 'before' => '',
	           'after' => '', 'show_post_count' => false,
	           'echo' => 1, 'order' => 'DESC');
               
	    $r = wp_parse_args( $args, $defaults );
        extract( $r, EXTR_SKIP );
        $archive_link_m =home_url("'?m='");
        
        $results= $wpdb->get_results( "SELECT date( post_date )as date,count(ID)as count FROM $wpdb->posts WHERE post_date < NOW() AND post_type = 'post' AND post_status = 'publish' group by date ORDER BY post_date DESC");
        if(!empty($results))
        {        
            if($type=='yearly')
            {
                $old_date=parsidate('Y',$results[0]->date,'eng');
                $count=$results[0]->count;
                $c=count($results);
                for($i=1;$i<$c;$i++)
                {
                    $dt=$results[$i];
                    $date=parsidate('Y',$dt->date,'eng');
                    if($date==$old_date)
                    $count+=$dt->count;
                    else
                    {
                        echo_yarchive($old_date,$format,$before,$count,$show_post_count);
                        $old_date=$date;
                        $count=$dt->count;
                    }
                }
                echo_yarchive($old_date,$format,$before,$count,$show_post_count);
            }
            elseif($type=='monthly')
            {
                $old_date=parsidate('Ym',$results[0]->date,'eng');
                $count=$results[0]->count;
                $c=count($results);
                for($i=1;$i<$c;$i++)
                {
                    $dt=$results[$i];
                    $date=parsidate('Ym',$dt->date,'eng');
                    if($date==$old_date)
                    $count+=$dt->count;
                    else
                    {
                        echo_marchive($old_date,$format,$before,$count,$show_post_count);
                        $old_date=$date;
                        $count=$dt->count;
                    }
                }
                echo_marchive($old_date,$format,$before,$count,$show_post_count);
            }
            elseif($type=='daily')
            {
                foreach($results as $row)
                {
                    $date = parsidate('Y,m,d',$row->date,'eng');
                    $date=explode(',',$date);
                    if($show_post_count)
                    $count='&nbsp;('.fixnumber($row->count).')';
                    else
                    $count = '';
                    $text  = fixnumber($date[2]).' '.$persian_month_names[intval($date[1])].' '.fixnumber($date[0]);
                    echo get_archives_link(get_day_link($date[0],$date[1],$date[2]),$text,$format, $before, $count);
                }
            }
        }
    }
    
    function echo_yarchive($year,$format,$before,$count,$show_post_count)
    {
        if($show_post_count)
        $count='&nbsp;('.fixnumber($count).')';
        else
        $count='';
        echo get_archives_link(get_year_link($year),fixnumber($year), $format, $before,$count);
    } 
       
    function echo_marchive($old_date,$format,$before,$count,$show_post_count)
    {
        global $persian_month_names;
        $year=substr($old_date,0,4);
        $month=substr($old_date,4,2);
        if($show_post_count)
        $count='&nbsp;('.fixnumber($count).')';
        else
        $count='';
        echo get_archives_link(get_month_link($year,$month),$persian_month_names[intval($month)].' '.fixnumber($year), $format, $before,$count);
    }
?>