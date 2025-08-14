<?php
defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

/**
 * Adds settings part to plugin
 * Originally, wrote by Pippin Williamson
 *
 * @author          Pippin Williamson
 * @author          Hamid Reza Yazdani
 * @author          Ehsaan
 * @author          Morteza Geransayeh
 * @author          Mobin Ghasempoor
 * @package         WP-Parsidate
 * @subpackage      Admin/Settings
 */

/**
 * Add WP-Parsidate admin page settings
 **/
function wpp_add_settings_menu() {
	if ( wpp_is_active( 'submenu_move' ) ) {
		add_submenu_page(
			'options-general.php',
			__( 'Parsi Settings', 'wp-parsidate' ),
			__( 'Parsi Settings', 'wp-parsidate' ),
			'manage_options',
			'wp-parsi-settings',
			'wpp_render_settings'
		);
	} else {
		add_menu_page(
			__( 'Parsi Settings', 'wp-parsidate' ),
			__( 'Parsi Settings', 'wp-parsidate' ),
			'manage_options',
			'wp-parsi-settings',
			'wpp_render_settings',
			'dashicons-admin-site'
		);
	}

	add_action( 'admin_enqueue_scripts', 'wpp_enqueue_setting_page_style' );
}

add_action( 'admin_menu', 'wpp_add_settings_menu', 11 );

/**
 * Gets saved settings from WP core
 *
 * @return          array Parsi Settings
 * @since           2.0
 */
function wp_parsi_get_settings() {
	$settings = get_option( 'wpp_settings' );

	if ( empty( $settings ) ) {
		update_option( 'wpp_settings', array(
			'admin_lang'           => 'disable',
			'user_lang'            => 'disable',
			'persian_date'         => 'disable',
			'disable_widget_block' => 'disable',
			'submenu_move'         => 'disable',
			'dev_mode'             => 'disable',
			'enable_fonts'         => 'disable',
			'conv_title'           => 'disable',
			'conv_contents'        => 'disable',
			'conv_excerpt'         => 'disable',
			'conv_comments'        => 'disable',
			'conv_comment_count'   => 'disable',
			'conv_dates'           => 'disable',
			'conv_cats'            => 'disable',
			'conv_arabic'          => 'disable',
			'conv_permalinks'      => 'disable',
			'news_source'          => 'parsi'
		) );
	}

	return apply_filters( 'wpp_get_settings', $settings );
}

/**
 * Registers settings in WP core
 *
 * @return          void
 * @since           2.0
 */
function wpp_register_settings() {
	if ( false === get_option( 'wpp_settings' ) ) {
		add_option( 'wpp_settings', array() );
	}

	foreach ( wpp_get_registered_settings() as $tab => $settings ) {
		add_settings_section(
			'wpp_settings_' . $tab,
			__return_null(),
			'__return_false',
			'wpp_settings_' . $tab
		);

		foreach ( $settings as $option ) {
			$name = $option['name'] ?? '';

			add_settings_field(
				'wpp_settings[' . $option['id'] . ']',
				$name,
				function_exists( 'wpp_' . $option['type'] . '_callback' ) ? 'wpp_' . $option['type'] . '_callback' : 'wpp_missing_callback',
				'wpp_settings_' . $tab,
				'wpp_settings_' . $tab,
				array(
					'id'      => $option['id'] ?? null,
					'desc'    => ! empty( $option['desc'] ) ? $option['desc'] : '',
					'name'    => $option['name'] ?? null,
					'section' => $tab,
					'size'    => $option['size'] ?? null,
					'options' => $option['options'] ?? '',
					'std'     => $option['std'] ?? ''
				)
			);

			register_setting( 'wpp_settings', 'wpp_settings', 'wpp_settings_sanitize' );
		}
	}
}

add_action( 'admin_init', 'wpp_register_settings' );

/**
 * Gets settings tabs
 *
 * @return              array Tabs list
 * @since               2.0
 */
function wpp_get_tabs() {
    return apply_filters('wpp_registered_settings_tabs', array(
		'core'    => sprintf( __( '%s Core', 'wp-parsidate' ), '<span class="dashicons dashicons-admin-site"></span>' ),
		'conv'    => sprintf( __( '%s Converts', 'wp-parsidate' ), '<span class="dashicons dashicons-admin-settings"></span>' ),
		'tools'   => sprintf( __( '%s Tools', 'wp-parsidate' ), '<span class="dashicons dashicons-admin-tools"></span>' ),
		'plugins' => sprintf( __( '%s Plugins compatibility', 'wp-parsidate' ), '<span class="dashicons dashicons-admin-plugins"></span>' ),
		'about'   => sprintf( __( '%s About', 'wp-parsidate' ), '<span class="dashicons dashicons-info"></span>' ),
	));
}

/**
 * Sanitizes and saves settings after submit
 *
 * @param array $input Settings input
 *
 * @return              array New settings
 * @since               2.0
 *
 */
function wpp_settings_sanitize( $input = array() ) {
	global $wpp_settings;

	if ( empty( $_POST['_wp_http_referer'] ) ) {
		return $input;
	}

	parse_str( $_POST['_wp_http_referer'], $referrer );

	$settings = wpp_get_registered_settings();
	$tab      = $referrer['tab'] ?? 'core';
	$input    = $input ?: array();
	$input    = apply_filters( 'wpp_settings_' . $tab . '_sanitize', $input );

	// Loop through each setting being saved and pass it through a sanitization filter
	foreach ( $input as $key => $value ) {
		// Get the setting type (checkbox, select, etc.)
		$type = $settings[ $tab ][ $key ]['type'] ?? false;

		if ( $type ) {
			// Field type specific filter
			$input[ $key ] = apply_filters( 'wpp_settings_sanitize_' . $type, $value, $key );
		}

		// General filter
		$input[ $key ] = apply_filters( 'wpp_settings_sanitize', $value, $key );
	}

	// Loop through the whitelist and unset any that are empty for the tab being saved
	if ( ! empty( $settings[ $tab ] ) ) {
		foreach ( $settings[ $tab ] as $key => $value ) {
			// settings used to have numeric keys, now they have keys that match the option ID. This ensures both methods work
			if ( is_numeric( $key ) ) {
				$key = $value['id'];
			}

			if ( ! isset( $input[ $key ] ) ) {
				unset( $wpp_settings[ $key ] );
			}
		}
	}

	// Merge our new settings with the existing
	return array_merge( $wpp_settings, $input );
}

/**
 * Get settings fields
 *
 * @return          array Fields
 * @since           2.0
 */
function wpp_get_registered_settings() {
	return apply_filters( 'wpp_registered_settings', array(
		'core'    => apply_filters( 'wpp_core_settings', array(
			'admin_lang'           => array(
				'id'      => 'admin_lang',
				'name'    => __( 'Change Locale in admin', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 'enable',
				'std'     => 0,
				'desc'    => __( 'This option change WordPress locale to Persian in Admin', 'wp-parsidate' ),
			),
			'user_lang'            => array(
				'id'      => 'user_lang',
				'name'    => __( 'Change Locale in theme', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 'enable',
				'std'     => 0,
				'desc'    => __( 'This option change WordPress locale to Persian in theme', 'wp-parsidate' ),
			),
			'persian_date'         => array(
				'id'      => 'persian_date',
				'name'    => __( 'Shamsi date', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 'enable',
				'std'     => 0,
				'desc'    => __( 'By enabling this, Dates will convert to Shamsi (Jalali) dates', 'wp-parsidate' ),
			),
			'months_name_type'     => array(
				'id'      => 'months_name_type',
				'name'    => __( 'Months name type', 'wp-parsidate' ),
				'type'    => 'select',
				'options' => array(
					'persian' => __( 'Persian', 'wp-parsidate' ),
					'dari'    => __( 'Dari', 'wp-parsidate' ),
					'kurdish' => __( 'Kurdish', 'wp-parsidate' ),
					'pashto'  => __( 'Pashto', 'wp-parsidate' ),
				),
				'std'     => 0,
			),
			'disable_widget_block' => array(
				'id'      => 'disable_widget_block',
				'name'    => __( 'Disable Widget Block', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 'enable',
				'std'     => 0,
				'desc'    => __( 'By enabling this, Widget Block Editor disabled', 'wp-parsidate' ),
			),
			'submenu_move'         => array(
				'id'      => 'submenu_move',
				'name'    => __( 'Move page to submenu?', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 'enable',
				'std'     => 0,
				'desc'    => __( 'By enabling this option, page item will be moved to Settings menu as submenu.', 'wp-parsidate' ),
			),
			'dev_mode'             => array(
				'id'      => 'dev_mode',
				'name'    => __( 'Debug Mode', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 'enable',
				'std'     => 0,
				'desc'    => __( 'By enabling this option, the uncompressed version of the JS and CSS files will be loaded.', 'wp-parsidate' ),
			),
			'enable_fonts'         => array(
				'id'      => 'enable_fonts',
				'name'    => __( 'Vazir Font', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 'enable',
				'std'     => 0,
				'desc'    => __( 'By enabling this option, the Vazir font will be enable in whole admin area.', 'wp-parsidate' ),
			),
		) ),
		'conv'    => apply_filters( 'wpp_conv_settings', array(
			'conv_nums'          => array(
				'id'   => 'conv_nums',
				'name' => __( 'Persian digits', 'wp-parsidate' ),
				'type' => 'header',
			),
			'conv_page_title'    => array(
				'id'      => 'conv_page_title',
				'name'    => __( 'Page title', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 'enable',
				'std'     => 0,
				'desc'    => __( 'Active', 'wp-parsidate' ),
			),
			'conv_title'         => array(
				'id'      => 'conv_title',
				'name'    => __( 'Post title', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 'enable',
				'std'     => 0,
				'desc'    => __( 'Active', 'wp-parsidate' ),
			),
			'conv_contents'      => array(
				'id'      => 'conv_contents',
				'name'    => __( 'Post content', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 'enable',
				'std'     => 'enable',
				'desc'    => __( 'Active', 'wp-parsidate' ),
			),
			'conv_excerpt'       => array(
				'id'      => 'conv_excerpt',
				'name'    => __( 'Post excerpt', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 'enable',
				'std'     => 0,
				'desc'    => __( 'Active', 'wp-parsidate' ),
			),
			'conv_comments'      => array(
				'id'      => 'conv_comments',
				'name'    => __( 'Comments text', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 'enable',
				'std'     => 0,
				'desc'    => __( 'Active', 'wp-parsidate' ),
			),
			'conv_comment_count' => array(
				'id'      => 'conv_comment_count',
				'name'    => __( 'Comments count', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 'enable',
				'std'     => 0,
				'desc'    => __( 'Active', 'wp-parsidate' ),
			),
			'conv_dates'         => array(
				'id'      => 'conv_dates',
				'name'    => __( 'Dates', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 'enable',
				'std'     => 0,
				'desc'    => __( 'Active', 'wp-parsidate' ),
			),
			'conv_cats'          => array(
				'id'      => 'conv_cats',
				'name'    => __( 'Categories', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 'enable',
				'std'     => 0,
				'desc'    => __( 'Active', 'wp-parsidate' ),
			),
			'sep'                => array(
				'id'   => 'sep',
				'type' => 'header',
			),
			'conv_arabic'        => array(
				'id'      => 'conv_arabic',
				'name'    => __( 'Fix arabic characters', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 'enable',
				'std'     => 'disable',
				'desc'    => __( 'Fixes arabic characters caused by wrong keyboard layouts', 'wp-parsidate' ),
			),
			'conv_permalinks'    => array(
				'id'      => 'conv_permalinks',
				'name'    => __( 'Fix permalinks dates', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 'enable',
				'std'     => 0,
				'desc'    => __( 'By enabling this, dates in permalinks converted to Shamsi (Jalali) date', 'wp-parsidate' ),
			),
			'sep_font'           => array(
				'id'   => 'sep_font',
				'type' => 'header',
			),
		) ),
		'tools'   => apply_filters( 'wpp_tools_settings', array(
			'advanced_tools'      => array(
				'id'   => 'advanced_tools',
				'name' => __( 'Advanced Tools', 'wp-parsidate' ),
				'type' => 'header'
			),
			'date_in_admin_bar'   => array(
				'id'      => 'date_in_admin_bar',
				'name'    => __( "Display date in the admin bar", 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 'enable',
				'std'     => 'disable',
				'desc'    => __( "Display today's Jalali date in the WordPress admin bar.", 'wp-parsidate' ),
			),
			/*'sep_admin_bar_ate'   => array(
				'id'   => 'sep_admin_bar_ate',
				'type' => 'header',
			),
			'disable_copy'        => array(
				'id'      => 'disable_copy',
				'name'    => __( 'Prevent users from copying site content', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 'enable',
				'std'     => 'disable',
				'desc'    => __( "Simply protect your site's content from those who want to copy it.", 'wp-parsidate' ),
			),
			'disable_right_click' => array(
				'id'      => 'disable_right_click',
				'name'    => __( 'Disable right click on website pages', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 'enable',
				'std'     => 0,
				'desc'    => __( "Don't worry about downloading website images and other files anymore, this option prevents users from right clicking", 'wp-parsidate' ),
			),*/
		) ),
		'plugins' => apply_filters( 'wpp_plugins_compatibility_settings', array() ),
	) );
}

/* Form Callbacks Made by EDD Development Team */
/**
 * @param $args
 */
function wpp_header_callback( $args ) {
	echo '<hr/>';
}

/**
 * Generates checkbox field
 *
 * @param $args
 */
function wpp_checkbox_callback( $args ) {
	global $wpp_settings;

	if ( isset( $wpp_settings[ $args['id'] ] ) ) {
		$checked = ! is_array( $wpp_settings[ $args['id'] ] ) ? checked( 'enable', $wpp_settings[ $args['id'] ], false ) : checked( 'enable', $wpp_settings[ $args['parent'] ][ $args['id'] ], false );
	} else {
		$checked = '';
	}
	$is_multiple = ! empty( $args['is_multiple'] ) ? ' checkbox-list' : '';
	$html        = sprintf( '<input type="checkbox" id="wpp_settings%1$s" name="wpp_settings%1$s" value="enable" %2$s/>' .
	                        '<label for="wpp_settings%1$s" class="wpp-checkbox-label %3$s %4$s"><span></span> %5$s</label>',
		! $is_multiple ? '[' . $args['id'] . ']' : '[' . $args['parent'] . '][' . $args['id'] . ']',
		$checked,
		empty( $args['desc'] ) ? 'empty-label' : '',
		$is_multiple,
		$args['desc'],
	);

	echo $html;
}

/**
 * Generates multiple checkboxes fields
 *
 * @param $args
 */
function wpp_multicheck_callback( $args ) {
	global $wpp_settings;

	$html  = '<ul class="wpp-settings-multicheck">';
	$value = $wpp_settings[ $args['id'] ] ?? $args['std'] ?? array();

	foreach ( $args['options'] as $key => $option ) {
		$html .= sprintf(
			'<li><input name="wpp_settings[%1$s][%2$s]" id="wpp_settings[%1$s][%2$s]" type="checkbox" value="%2$s" %3$s/><label for="wpp_settings[%1$s][%2$s]" class="wpp-checkbox-label multicheck">%4$s<span></span> %5$s</label></li>',
			$args['id'],
			$key,
			in_array( $key, $value ) ? 'checked="checked"' : '',
			$option,
			$args['desc']
		);
	}

	echo $html . '</ul>';
}

/**
 * @param $args
 */
function wpp_radio_callback( $args ) {
	global $wpp_settings;

	foreach ( $args['options'] as $key => $option ) :
		$checked = false;

		if ( isset( $wpp_settings[ $args['id'] ] ) && $wpp_settings[ $args['id'] ] == $key ) {
			$checked = true;
		} elseif ( isset( $args['std'] ) && $args['std'] == $key && ! isset( $wpp_settings[ $args['id'] ] ) ) {
			$checked = true;
		}

		echo '<input name="wpp_settings[' . $args['id'] . ']"" id="wpp_settings[' . $args['id'] . '][' . $key . ']" type="radio" value="' . $key . '" ' . checked( true, $checked, false ) . '/>';
		echo '<label for="wpp_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label>&nbsp;&nbsp;';
	endforeach;

	echo '<p class="description">' . $args['desc'] . '</p>';
}

/**
 * @param $args
 */
function wpp_text_callback( $args ) {
	global $wpp_settings;

	$value = $wpp_settings[ $args['id'] ] ?? $args['std'] ?? '';
	$size  = ( isset( $args['size'] ) ) ? $args['size'] : 'regular';
	$html  = '<input type="text" class="' . $size . '-text" id="wpp_settings[' . $args['id'] . ']" name="wpp_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html  .= '<label for="wpp_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

/**
 * @param $args
 */
function wpp_number_callback( $args ) {
	global $wpp_settings;

	$value = $wpp_settings[ $args['id'] ] ?? $args['std'] ?? '';
	$max   = $args['max'] ?? 999999;
	$min   = $args['min'] ?? 0;
	$step  = $args['step'] ?? 1;
	$size  = ( isset( $args['size'] ) ) ? $args['size'] : 'regular';
	$html  = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $size . '-text" id="wpp_settings[' . $args['id'] . ']" name="wpp_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html  .= '<label for="wpp_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

/**
 * @param $args
 */
function wpp_textarea_callback( $args ) {
	global $wpp_settings;

	$value = $wpp_settings[ $args['id'] ] ?? $args['std'] ?? '';
	$size  = ( isset( $args['size'] ) ) ? $args['size'] : 'regular';
	$html  = '<textarea class="large-text" cols="50" rows="5" id="wpp_settings[' . $args['id'] . ']" name="wpp_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	$html  .= '<label for="wpp_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

/**
 * @param $args
 */
function wpp_password_callback( $args ) {
	global $wpp_settings;

	$value = $wpp_settings[ $args['id'] ] ?? $args['std'] ?? '';
	$size  = ( isset( $args['size'] ) ) ? $args['size'] : 'regular';
	$html  = '<input type="password" class="' . $size . '-text" id="wpp_settings[' . $args['id'] . ']" name="wpp_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '"/>';
	$html  .= '<label for="wpp_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

/**
 * @param $args
 *
 * @return false
 */
function wpp_missing_callback( $args ) {
	echo '&ndash;';

	return false;
}

/**
 * @param $args
 */
function wpp_select_callback( $args ) {
	global $wpp_settings;

	$value = $wpp_settings[ $args['id'] ] ?? $args['std'] ?? '';
	$html  = '<select id="wpp_settings[' . $args['id'] . ']" name="wpp_settings[' . $args['id'] . ']"/>';

	foreach ( $args['options'] as $option => $name ) :
		$selected = selected( $option, $value, false );
		$html     .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
	endforeach;

	$html .= '</select>';
	$html .= '<label for="wpp_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

/**
 * @param $args
 */
function wpp_color_select_callback( $args ) {
	global $wpp_settings;

	$value = $wpp_settings[ $args['id'] ] ?? $args['std'] ?? '';
	$html  = '<select id="wpp_settings[' . $args['id'] . ']" name="wpp_settings[' . $args['id'] . ']"/>';

	foreach ( $args['options'] as $option => $color ) :
		$selected = selected( $option, $value, false );
		$html     .= '<option value="' . $option . '" ' . $selected . '>' . $color['label'] . '</option>';
	endforeach;

	$html .= '</select>';
	$html .= '<label for="wpp_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

/**
 * @param $args
 */
function wpp_rich_editor_callback( $args ) {
	global $wpp_settings, $wp_version;

	$value = $wpp_settings[ $args['id'] ] ?? $args['std'] ?? '';

	if ( $wp_version >= 3.3 && function_exists( 'wp_editor' ) ) {
		ob_start();

		wp_editor( stripslashes( $value ), 'wpp_settings[' . $args['id'] . ']', array( 'textarea_name' => 'wpp_settings[' . $args['id'] . ']' ) );

		$html = ob_get_contents();

		ob_end_clean();
	} else {
		$html = '<textarea class="large-text" rows="10" id="wpp_settings[' . $args['id'] . ']" name="wpp_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	}

	$html .= '<br/><label for="wpp_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

/**
 * @param $args
 */
function wpp_upload_callback( $args ) {
	global $wpp_settings;

	$value = $wpp_settings[ $args['id'] ] ?? $args['std'] ?? '';
	$size  = ( isset( $args['size'] ) ) ? $args['size'] : 'regular';
	$html  = '<input type="text" class="' . $size . '-text wpp_upload_field" id="wpp_settings[' . $args['id'] . ']" name="wpp_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html  .= '<span>&nbsp;<input type="button" class="wpp_settings_upload_button button-secondary" value="' . __( 'Upload File', 'wpp' ) . '"/></span>';
	$html  .= '<label for="wpp_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

/**
 * @param $args
 */
function wpp_color_callback( $args ) {
	global $wpp_settings;

	$value   = $wpp_settings[ $args['id'] ] ?? $args['std'] ?? '';
	$default = $args['std'] ?? '';
	$size    = ( isset( $args['size'] ) ) ? $args['size'] : 'regular';
	$html    = '<input type="text" class="wpp-color-picker" id="wpp_settings[' . $args['id'] . ']" name="wpp_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $default ) . '" />';
	$html    .= '<label for="wpp_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

function wpp_render_settings() {
	$active_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], wpp_get_tabs() ) ? $_GET['tab'] : 'core';

	ob_start();
	?>
	<?php settings_errors( 'wpp-notices' ); ?>
    <div class="wrapp wpp-settings-wrap">
        <h2><?php _e( 'Parsi Settings', 'wp-parsidate' ) ?></h2>
        <h2 class="nav-tab-wrapper">
			<?php
			foreach ( wpp_get_tabs() as $tab_id => $tab_name ) {

				$tab_url = add_query_arg( array(
					'settings-updated' => false,
					'tab'              => $tab_id,
				) );

				$active = $active_tab == $tab_id ? ' nav-tab-active' : '';

				echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( strip_tags( $tab_name ) ) . '" class="nav-tab' . $active . '">';
				echo $tab_name;
				echo '</a>';
			}
			?>
        </h2>
        <div id="tab_container">
			<?php if ( 'about' !== $active_tab ) : ?>
                <form method="post" action="options.php">
                    <table class="form-table">
						<?php
						settings_fields( 'wpp_settings' );
						do_settings_fields( 'wpp_settings_' . $active_tab, 'wpp_settings_' . $active_tab );
						?>
                    </table>
					<?php submit_button(); ?>
                </form>
			<?php else : ?>
				<?php include WP_PARSI_DIR . 'includes/views/html-about.php'; ?>
			<?php endif; ?>
        </div><!-- #tab_container-->
    </div><!-- .wrap -->
	<?php
	echo ob_get_clean();
}

/**
 * Gets an option name and check that option is active or not
 *
 * @param               $option_name
 *
 * @return              bool
 * @since               4.0.0
 */
function wpp_is_active( $option_name ) {
	global $wpp_settings;

	return ! empty( $wpp_settings[ $option_name ] ) && 'enable' === $wpp_settings[ $option_name ];
}

/**
 * Gets an option name and returns the value
 *
 * @param               $option_name
 *
 * @return              string
 * @since               4.0.1
 */
function wpp_get_option( $option_name ) {
	global $wpp_settings;

	return ! empty( $wpp_settings[ $option_name ] ) ? $wpp_settings[ $option_name ] : '';
}

/**
 * Enqueue setting page style
 *
 * @param $hook
 *
 * @since 4.0.0
 */
function wpp_enqueue_setting_page_style( $hook ) {
	if ( ! in_array( $hook, array( 'toplevel_page_wp-parsi-settings', 'settings_page_wp-parsi-settings' ) ) ) {
		return;
	}

	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || wpp_is_active( 'dev_mode' ) ? '' : '.min';

	wp_enqueue_style( 'wpp_option_page', WP_PARSI_URL . "assets/css/settings$suffix.css", null, WP_PARSI_VER );
}

function wpp_multilingual_compatibility_option( $old_settings ) {
	if ( WP_Parsidate::wpp_multilingual_is_active() ) {
		$settings = array(
			'wpp_multilingual_support' => array(
				'id'      => 'wpp_multilingual_support',
				'name'    => __( 'Multilingual compatibility', 'wp-parsidate' ),
				'type'    => 'checkbox',
				'options' => 1,
				'std'     => 0,
				'desc'    => __( 'By enabling this, ParsiDate options only work in persian locale', 'wp-parsidate' )
			),
		);

		return array_merge( $old_settings, $settings );
	}

	return $old_settings;
}

add_filter( 'wpp_core_settings', 'wpp_multilingual_compatibility_option' );
