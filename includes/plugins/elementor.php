<?php
# @Author: Amirhosseinhpv
# @Date:   2022/02/26 20:55:45
# @Email:  its@hpv.im
# @Last modified by:   Amirhosseinhpv
# @Last modified time: 2022/02/26 21:21:55

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

/**
 * Make Elementor compatible with WP-Parsidate
 */
class WPP_Elementor
{

  public function __construct()
  {
    add_action( "elementor/editor/after_enqueue_styles", array( $this, "add_elementor_editor_css"));
  }
  public function add_elementor_editor_css($value='')
  {
    $wpp_elmentor_css = "
      body, .tipsy-inner, .elementor-button, .elementor-panel {
        font-family: Tahoma,Arial,Helvetica,Verdana,sans-serif;
      }
      .tipsy-inner {
        font-size: small;
      }";
    $wpp_elmentor_css = apply_filters("wpp_elementor_css", $wpp_elmentor_css);
    wp_add_inline_style("elementor-editor", $wpp_elmentor_css);
  }
}
/*##########################################
Developer: [amirhosseinhpv](https://hpv.im/)
############################################*/
return new WPP_Elementor;
