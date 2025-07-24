<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

/**
 * Make Elementor compatible with WP-Parsidate
 */
class WPP_Elementor {

	public function __construct() {
		add_action( "elementor/editor/after_enqueue_styles", array( $this, "add_elementor_editor_css" ) );
	}

	public function add_elementor_editor_css( $value = '' ) {
		$wpp_elementor_css = "
      body, .tipsy-inner, .elementor-button, .elementor-panel {
        font-family: Tahoma,Arial,Helvetica,Verdana,sans-serif;
      }
      .tipsy-inner {
        font-size: small;
      }";
		$wpp_elementor_css = apply_filters( "wpp_elementor_css", $wpp_elementor_css );
		wp_add_inline_style( "elementor-editor", $wpp_elementor_css );
	}
}

return new WPP_Elementor;
