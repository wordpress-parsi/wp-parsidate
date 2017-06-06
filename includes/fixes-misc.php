<?php
/**
 * Fixes numbers in selected options
 *
 * @author          Mobin Ghasempoor
 * @author          Ehsaan
 * @package         WP-Parsidate
 * @subpackage      Fixes/NumbersAndArabic
 */

global $wpp_settings;

if ( isset( $wpp_settings['conv_page_title'] ) && $wpp_settings['conv_page_title'] != 'disable' ) {
		  if (get_locale() == 'fa_IR') { 
	add_filter( 'wp_title', 'fixnumber', 1000 );
	add_filter( 'pre_get_document_title', 'fixnumber', 1000 ); // WP 4.4+
		  } else {
			  	remove_filter( 'wp_title', 'fixnumber', 1000 );
				remove_filter( 'pre_get_document_title', 'fixnumber', 1000 ); // WP 4.4+
		  }
}

if ( isset( $wpp_settings['conv_title'] ) && $wpp_settings['conv_title'] != 'disable' ) {
	  if (get_locale() == 'fa_IR') { 
	add_filter( 'the_title', 'fixnumber', 1000 );
	  } else {
		  remove_filter( 'the_title', 'fixnumber', 1000 );
	  }
}

if ( isset( $wpp_settings['conv_contents'] ) && $wpp_settings['conv_contents'] != 'disable' ) {
	  if (get_locale() == 'fa_IR') { 
	add_filter( 'the_content', 'fixnumber', 1000 );
	  } else {
		  	remove_filter( 'the_content', 'fixnumber', 1000 );
	  }
}

if ( isset( $wpp_settings['conv_excerpt'] ) && $wpp_settings['conv_excerpt'] != 'disable' ) {
	  if (get_locale() == 'fa_IR') { 
	add_filter( 'the_excerpt', 'fixnumber', 1000 );
	else {
			remove_filter( 'the_excerpt', 'fixnumber', 1000 );
	}
}

if ( isset( $wpp_settings['conv_comments'] ) && $wpp_settings['conv_comments'] != 'disable' ) {
		  if (get_locale() == 'fa_IR') { 
	add_filter( 'comment_text', 'fixnumber', 1000 );
		  } else {
			  	remove_filter( 'comment_text', 'fixnumber', 1000 );
		  }
}

if ( isset( $wpp_settings['conv_comment_count'] ) && $wpp_settings['conv_comment_count'] != 'disable' ) {
		  if (get_locale() == 'fa_IR') { 
	add_filter( 'comments_number', 'fixnumber', 1000 );
		  } else {
			 	remove_filter( 'comments_number', 'fixnumber', 1000 ); 
		  }
}

if ( isset( $wpp_settings['conv_cats'] ) && $wpp_settings['conv_cats'] != 'disable' ) {
			  if (get_locale() == 'fa_IR') { 
	add_filter( 'wp_list_categories', 'fixnumber', 1000 );
			  } else {
				  	remove_filter( 'wp_list_categories', 'fixnumber', 1000 );
			  }		
}

if ( isset( $wpp_settings['conv_arabic'] ) && $wpp_settings['conv_arabic'] != 'disable' ) {
			  if (get_locale() == 'fa_IR') { 
	add_filter( 'the_content', 'fixarabic' ,1000);
	add_filter( 'the_title', 'fixarabic' ,1000);
	add_filter( 'comment_text', 'fixarabic',1000 );
	add_filter( 'wp_list_categories', 'fixarabic',1000 );
	add_filter( 'the_excerpt', 'fixarabic' ,1000);
	add_filter( 'wp_title', 'fixarabic', 1000 );
	add_filter( 'pre_get_document_title', 'fixarabic', 1000 ); // WP 4.4+
			  } else {
	remove_filter( 'the_content', 'fixarabic' ,1000);
	remove_filter( 'the_title', 'fixarabic' ,1000);
	remove_filter( 'comment_text', 'fixarabic',1000 );
	remove_filter( 'wp_list_categories', 'fixarabic',1000 );
	remove_filter( 'the_excerpt', 'fixarabic' ,1000);
	remove_filter( 'wp_title', 'fixarabic', 1000 );
	remove_filter( 'pre_get_document_title', 'fixarabic', 1000 ); // WP 4.4+
			  }
			
}