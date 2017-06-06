<?php
/**
 * Adds settings part to plugin
 * Originally, wrote by Pippin Williamson
 *
 * @author          Pippin Williamson
 * @author          Ehsaan
 * @package         WP-Parsidate
 * @subpackage      Admin/Settings
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // No direct access allowed ;)

add_action( 'admin_menu', 'wpp_add_settings_menu', 11 );

/**
 * Add WP-Parsidate admin page settings
 * */
function wpp_add_settings_menu() {
	global $wpp_settings;

	if ( $wpp_settings['submenu_move'] != 'disable' ) {
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
}

/**
 * Gets saved settings from WP core
 *
 * @since           2.0
 * @return          array Parsi Settings
 */
function wp_parsi_get_settings() {
	$settings = get_option( 'wpp_settings' );
	if ( empty( $settings ) ) {
		update_option( 'wpp_settings', array(
			'admin_lang'         => 'enable',
			'user_lang'          => 'enable',
			'submenu_move'       => 'disable',
			'persian_date'       => 'disable',
			'droidsans_editor'   => 'enable',
			'droidsans_admin'    => 'enable',
			'conv_title'         => 'disable',
			'conv_contents'      => 'disable',
			'conv_excerpt'       => 'disable',
			'conv_comments'      => 'disable',
			'conv_comment_count' => 'disable',
			'conv_dates'         => 'disable',
			'conv_cats'          => 'disable',
			'conv_arabic'        => 'disable',
			'conv_permalinks'    => 'disable',
			'news_source'        => 'parsi'
		) );
	}

	return apply_filters( 'wpp_get_settings', $settings );
}

/**
 * Registers settings in WP core
 *
 * @since           2.0
 * @return          void
 */
function wpp_register_settings() {
	if ( false == get_option( 'wpp_settings' ) ) {
		add_option( 'wpp_settings' );
	}

	foreach ( wpp_get_registered_settings() as $tab => $settings ) {
		add_settings_section(
			'wpp_settings_' . $tab,
			__return_null(),
			'__return_false',
			'wpp_settings_' . $tab
		);

		foreach ( $settings as $option ) {
			$name = isset( $option['name'] ) ? $option['name'] : '';

			add_settings_field(
				'wpp_settings[' . $option['id'] . ']',
				$name,
				function_exists( 'wpp_' . $option['type'] . '_callback' ) ? 'wpp_' . $option['type'] . '_callback' : 'wpp_missing_callback',
				'wpp_settings_' . $tab,
				'wpp_settings_' . $tab,
				array(
					'id'      => isset( $option['id'] ) ? $option['id'] : null,
					'desc'    => ! empty( $option['desc'] ) ? $option['desc'] : '',
					'name'    => isset( $option['name'] ) ? $option['name'] : null,
					'section' => $tab,
					'size'    => isset( $option['size'] ) ? $option['size'] : null,
					'options' => isset( $option['options'] ) ? $option['options'] : '',
					'std'     => isset( $option['std'] ) ? $option['std'] : ''
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
 * @since               2.0
 * @return              array Tabs list
 */
function wpp_get_tabs() {
	$tabs = array(
		'core'    => sprintf( __( '%s Core', 'wp-parsidate' ), '<span class="dashicons dashicons-admin-site"></span>' ),
		'conv'    => sprintf( __( '%s Converts', 'wp-parsidate' ), '<span class="dashicons dashicons-admin-settings"></span>' ),
		'plugins' => sprintf( __( '%s Plugins compability', 'wp-parsidate' ), '<span class="dashicons dashicons-admin-plugins"></span>' )
	);

	return $tabs;
}

/**
 * Sanitizes and saves settings after submit
 *
 * @since               2.0
 *
 * @param               array $input Settings input
 *
 * @return              array New settings
 */
function wpp_settings_sanitize( $input = array() ) {

	global $wpp_settings;

	if ( empty( $_POST['_wp_http_referer'] ) ) {
		return $input;
	}

	parse_str( $_POST['_wp_http_referer'], $referrer );

	$settings = wpp_get_registered_settings();
	$tab      = isset( $referrer['tab'] ) ? $referrer['tab'] : 'core';

	$input = $input ? $input : array();
	$input = apply_filters( 'wpp_settings_' . $tab . '_sanitize', $input );

	// Loop through each setting being saved and pass it through a sanitization filter
	foreach ( $input as $key => $value ) {

		// Get the setting type (checkbox, select, etc)
		$type = isset( $settings[ $tab ][ $key ]['type'] ) ? $settings[ $tab ][ $key ]['type'] : false;

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

			if ( empty( $input[ $key ] ) ) {
				unset( $wpp_settings[ $key ] );
			}

		}
	}

	// Merge our new settings with the existing
	$output = array_merge( $wpp_settings, $input );

	return $output;

}

/**
 * Get settings fields
 *
 * @since           2.0
 * @return          array Fields
 */
function wpp_get_registered_settings() {
	$options  = array(
		'enable'  => __( 'Enable', 'wp-parsidate' ),
		'disable' => __( 'Disable', 'wp-parsidate' )
	);
	$settings = apply_filters( 'wpp_registered_settings', array(
		'core'    => apply_filters( 'wpp_core_settings', array(
			'admin_lang'   => array(
				'id'      => 'admin_lang',
				'name'    => __( 'Change Locale in admin', 'wp-parsidate' ),
				'type'    => 'radio',
				'options' => $options,
				'std'     => 'enable',
				'desc'    => __( 'This option change WordPress locale in Admin', 'wp-parsidate' )
			),
			'user_lang'    => array(
				'id'      => 'user_lang',
				'name'    => __( 'Change Locale in theme', 'wp-parsidate' ),
				'type'    => 'radio',
				'options' => $options,
				'std'     => 'enable',
				'desc'    => __( 'This option change WordPress locale in theme', 'wp-parsidate' )
			),
			'persian_date' => array(
				'id'      => 'persian_date',
				'name'    => __( 'Shamsi date', 'wp-parsidate' ),
				'type'    => 'radio',
				'options' => $options,
				'std'     => 'disable',
				'desc'    => __( 'By enabling this, Dates will convert to Shamsi (Jalali) dates', 'wp-parsidate' )
			),
			'submenu_move' => array(
				'id'      => 'submenu_move',
				'name'    => __( 'Move page to submenu?', 'wp-parsidate' ),
				'type'    => 'radio',
				'options' => $options,
				'std'     => 'disable',
				'desc'    => __( 'With enabling this option, page item will be moved to Settings menu as submenu.', 'wp-parsidate' )
			),
		) ),
		'conv'    => apply_filters( 'wpp_conv_settings', array(
			'conv_nums'          => array(
				'id'   => 'conv_nums',
				'name' => __( 'Persian digits', 'wp-parsidate' ),
				'type' => 'header'
			),
			'conv_page_title'    => array(
				'id'      => 'conv_page_title',
				'name'    => __( 'Page title', 'wp-parsidate' ),
				'type'    => 'radio',
				'options' => $options,
				'std'     => 'disable'
			),
			'conv_title'         => array(
				'id'      => 'conv_title',
				'name'    => __( 'Post title', 'wp-parsidate' ),
				'type'    => 'radio',
				'options' => $options,
				'std'     => 'disable'
			),
			'conv_contents'      => array(
				'id'      => 'conv_contents',
				'name'    => __( 'Post content', 'wp-parsidate' ),
				'type'    => 'radio',
				'options' => $options,
				'std'     => 'enable'
			),
			'conv_excerpt'       => array(
				'id'      => 'conv_excerpt',
				'name'    => __( 'Post excerpt', 'wp-parsidate' ),
				'type'    => 'radio',
				'options' => $options,
				'std'     => 'disable'
			),
			'conv_comments'      => array(
				'id'      => 'conv_comments',
				'name'    => __( 'Comments text', 'wp-parsidate' ),
				'type'    => 'radio',
				'options' => $options,
				'std'     => 'disable'
			),
			'conv_comment_count' => array(
				'id'      => 'conv_comment_count',
				'name'    => __( 'Comments count', 'wp-parsidate' ),
				'type'    => 'radio',
				'options' => $options,
				'std'     => 'disable'
			),
			'conv_dates'         => array(
				'id'      => 'conv_dates',
				'name'    => __( 'Dates', 'wp-parsidate' ),
				'type'    => 'radio',
				'options' => $options,
				'std'     => 'disable'
			),
			'conv_cats'          => array(
				'id'      => 'conv_cats',
				'name'    => __( 'Categories', 'wp-parsidate' ),
				'type'    => 'radio',
				'options' => $options,
				'std'     => 'disable'
			),
			'sep'                => array(
				'id'   => 'sep',
				'type' => 'header'
			),
			'conv_arabic'        => array(
				'id'      => 'conv_arabic',
				'name'    => __( 'Fix arabic characters', 'wp-parsidate' ),
				'type'    => 'radio',
				'options' => $options,
				'std'     => 'disable',
				'desc'    => __( 'Fixes arabic characters caused by wrong keyboard layouts', 'wp-parsidate' )
			),
			'conv_permalinks'    => array(
				'id'      => 'conv_permalinks',
				'name'    => __( 'Fix permalinks dates', 'wp-parsidate' ),
				'type'    => 'radio',
				'options' => $options,
				'std'     => 'disable',
				'desc'    => __( 'By enabling this, dates in permalinks converted to Shamsi (Jalali) date', 'wp-parsidate' )
			),
			'sep_font'           => array(
				'id'   => 'sep_font',
				'type' => 'header'
			),
			'droidsans_admin'    => array(
				'id'      => 'droidsans_admin',
				'name'    => __( 'Use Droid Sans font for admin side', 'wp-parsidate' ),
				'type'    => 'radio',
				'options' => $options,
				'std'     => 'enable',
				'desc'    => __( 'Droid Sans Naskh and Roboto font families will be activated in admin side, if this is enabled.', 'wp-parsidate' )
			),
			'droidsans_editor'   => array(
				'id'      => 'droidsans_editor',
				'name'    => __( 'Use Droid Sans font for editors', 'wp-parsidate' ),
				'type'    => 'radio',
				'options' => $options,
				'std'     => 'enable',
				'desc'    => __( 'Droid Sans Naskh and Roboto font families will be activated in all rich editors in back end.', 'wp-parsidate' )
			)
		) ),
		'plugins' => apply_filters( 'wpp_plugins_compability_settings', array() )
	) );

	return $settings;
}

/* Form Callbacks Made by EDD Development Team */
function wpp_header_callback( $args ) {
	echo '<hr/>';
}

function wpp_checkbox_callback( $args ) {
	global $wpp_settings;

	$checked = isset( $wpp_settings[ $args['id'] ] ) ? checked( 1, $wpp_settings[ $args['id'] ], false ) : '';
	$html    = '<input type="checkbox" id="wpp_settings[' . $args['id'] . ']" name="wpp_settings[' . $args['id'] . ']" value="1" ' . $checked . '/>';
	$html    .= '<label for="wpp_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

function wpp_multicheck_callback( $args ) {
	global $wpp_settings;

	$html = '';
	foreach ( $args['options'] as $key => $value ) {
		$option_name = $args['id'] . '-' . $key;
		wpp_checkbox_callback( array(
			'id'   => $option_name,
			'desc' => $value
		) );
		echo '<br>';
	}

	echo $html;
}

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

function wpp_text_callback( $args ) {
	global $wpp_settings;

	if ( isset( $wpp_settings[ $args['id'] ] ) ) {
		$value = $wpp_settings[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="' . $size . '-text" id="wpp_settings[' . $args['id'] . ']" name="wpp_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<label for="wpp_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

function wpp_number_callback( $args ) {
	global $wpp_settings;

	if ( isset( $wpp_settings[ $args['id'] ] ) ) {
		$value = $wpp_settings[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$max  = isset( $args['max'] ) ? $args['max'] : 999999;
	$min  = isset( $args['min'] ) ? $args['min'] : 0;
	$step = isset( $args['step'] ) ? $args['step'] : 1;

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $size . '-text" id="wpp_settings[' . $args['id'] . ']" name="wpp_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<label for="wpp_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

function wpp_textarea_callback( $args ) {
	global $wpp_settings;

	if ( isset( $wpp_settings[ $args['id'] ] ) ) {
		$value = $wpp_settings[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<textarea class="large-text" cols="50" rows="5" id="wpp_settings[' . $args['id'] . ']" name="wpp_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	$html .= '<label for="wpp_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

function wpp_password_callback( $args ) {
	global $wpp_settings;

	if ( isset( $wpp_settings[ $args['id'] ] ) ) {
		$value = $wpp_settings[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="password" class="' . $size . '-text" id="wpp_settings[' . $args['id'] . ']" name="wpp_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '"/>';
	$html .= '<label for="wpp_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

function wpp_missing_callback( $args ) {
	echo '&ndash;';

	return false;
}


function wpp_select_callback( $args ) {
	global $wpp_settings;

	if ( isset( $wpp_settings[ $args['id'] ] ) ) {
		$value = $wpp_settings[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$html = '<select id="wpp_settings[' . $args['id'] . ']" name="wpp_settings[' . $args['id'] . ']"/>';

	foreach ( $args['options'] as $option => $name ) :
		$selected = selected( $option, $value, false );
		$html     .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
	endforeach;

	$html .= '</select>';
	$html .= '<label for="wpp_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

function wpp_color_select_callback( $args ) {
	global $wpp_settings;

	if ( isset( $wpp_settings[ $args['id'] ] ) ) {
		$value = $wpp_settings[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$html = '<select id="wpp_settings[' . $args['id'] . ']" name="wpp_settings[' . $args['id'] . ']"/>';

	foreach ( $args['options'] as $option => $color ) :
		$selected = selected( $option, $value, false );
		$html     .= '<option value="' . $option . '" ' . $selected . '>' . $color['label'] . '</option>';
	endforeach;

	$html .= '</select>';
	$html .= '<label for="wpp_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

function wpp_rich_editor_callback( $args ) {
	global $wpp_settings, $wp_version;

	if ( isset( $wpp_settings[ $args['id'] ] ) ) {
		$value = $wpp_settings[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	if ( $wp_version >= 3.3 && function_exists( 'wp_editor' ) ) {
		$html = wp_editor( stripslashes( $value ), 'wpp_settings[' . $args['id'] . ']', array( 'textarea_name' => 'wpp_settings[' . $args['id'] . ']' ) );
	} else {
		$html = '<textarea class="large-text" rows="10" id="wpp_settings[' . $args['id'] . ']" name="wpp_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	}

	$html .= '<br/><label for="wpp_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

function wpp_upload_callback( $args ) {
	global $wpp_settings;

	if ( isset( $wpp_settings[ $args['id'] ] ) ) {
		$value = $wpp_settings[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="' . $size . '-text wpp_upload_field" id="wpp_settings[' . $args['id'] . ']" name="wpp_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<span>&nbsp;<input type="button" class="wpp_settings_upload_button button-secondary" value="' . __( 'Upload File', 'wpp' ) . '"/></span>';
	$html .= '<label for="wpp_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

function wpp_color_callback( $args ) {
	global $wpp_settings;

	if ( isset( $wpp_settings[ $args['id'] ] ) ) {
		$value = $wpp_settings[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$default = isset( $args['std'] ) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="wpp-color-picker" id="wpp_settings[' . $args['id'] . ']" name="wpp_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $default ) . '" />';
	$html .= '<label for="wpp_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

function wpp_render_settings() {
	global $wpp_settings;
	$active_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], wpp_get_tabs() ) ? $_GET['tab'] : 'core';

	ob_start();
	?>
    <div class="wrap wpp-settings-wrap">
        <h2><?php _e( 'Parsi Settings', 'wp-parsidate' ) ?></h2>
        <h2 class="nav-tab-wrapper">
			<?php
			foreach ( wpp_get_tabs() as $tab_id => $tab_name ) {

				$tab_url = add_query_arg( array(
					'settings-updated' => false,
					'tab'              => $tab_id
				) );

				$active = $active_tab == $tab_id ? ' nav-tab-active' : '';

				echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $tab_name ) . '" class="nav-tab' . $active . '">';
				echo $tab_name;
				echo '</a>';
			}
			?>
        </h2>
		<?php settings_errors( 'wpp-notices' ); ?>
        <div id="tab_container">
            <form method="post" action="options.php">
                <table class="form-table">
					<?php
					settings_fields( 'wpp_settings' );
					do_settings_fields( 'wpp_settings_' . $active_tab, 'wpp_settings_' . $active_tab );
					?>
                </table>
				<?php submit_button(); ?>
            </form>
        </div><!-- #tab_container-->
    </div><!-- .wrap -->
	<?php
	echo ob_get_clean();
}