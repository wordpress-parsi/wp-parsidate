<?php
/**
 * Other Fix Function
 *
 * @package             WP-Parsidate
 * @subpackage          Admin/Other
 */
 
if ( ! defined( 'ABSPATH' ) ) exit; // No direct access allowed

if(is_admin()) {
	/**
	* Fix Broken Plugins Update Link
	*
	* @author              Parsa Kafi
	* @parms			   string $buffer admin page
	* @return              string
	*/
    function wpp_plugins_update_link_fix($buffer)
    {		
        if (get_locale() == 'fa_IR') 
		{
            if(preg_match_all('(صورت <a.*?>خودکار به‌روز کنید<\/a>)', $buffer, $update_links))
            {
                foreach($update_links[0] as $link) 
				{
					preg_match('/<a href="(.+)">/', $link, $href);					
					if(! wp_http_validate_url($href[1]))
					{
						preg_match('!https?://\S+!', $link, $url);
						$link_f = ' صورت '.'<a href="'.$url[0].'"> خودکار به‌روز کنید</a>';
						$buffer = str_replace($link, $link_f, $buffer);
					}
                }
            }
        }
		
        return $buffer;
    }

    add_action('init', 'wpp_admin_buffer_start');
    add_action('admin_footer', 'wpp_admin_buffer_end');
    function wpp_admin_buffer_start()
    {
		global $pagenow;
		if($pagenow == 'plugins.php')
			ob_start("wpp_plugins_update_link_fix");
    }

    function wpp_admin_buffer_end()
    {
        ob_end_flush();
    }
}
