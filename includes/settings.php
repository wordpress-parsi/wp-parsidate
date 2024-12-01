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

if ( ! function_exists( 'wpp_add_settings_menu' ) ) {

	/**
	 * Add WP-Parsidate admin page settings
	 **/
	function wpp_add_settings_menu() {
		add_menu_page(
			__( 'Parsi Date', 'wp-parsidate' ),
			__( 'Parsi Date', 'wp-parsidate' ),
			'manage_options',
			'wp-parsi-settings',
			'wpp_render_settings',
			' data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB3aWR0aD0iMjRweCIgaGVpZ2h0PSIyNHB4IiB2aWV3Qm94PSIwIDAgMjQgMjQiIHZlcnNpb249IjEuMSI+CjxnIGlkPSJzdXJmYWNlMSI+CjxwYXRoIHN0eWxlPSIgc3Ryb2tlOm5vbmU7ZmlsbC1ydWxlOm5vbnplcm87ZmlsbDpyZ2IoMCUsMCUsMCUpO2ZpbGwtb3BhY2l0eToxOyIgZD0iTSAxMS4wMzEyNSAwLjAzMTI1IEMgOS41IDAuMTcxODc1IDcuOTg4MjgxIDAuNTk3NjU2IDYuNjc1NzgxIDEuMjUzOTA2IEMgNC4yMzQzNzUgMi40NzI2NTYgMi40NzI2NTYgNC4yMzQzNzUgMS4yNDYwOTQgNi42ODM1OTQgQyAtMC41NjY0MDYgMTAuMzA0Njg4IC0wLjM3ODkwNiAxNC42ODc1IDEuNzM4MjgxIDE4LjE3OTY4OCBDIDMuNjQ4NDM4IDIxLjMzOTg0NCA2Ljk1MzEyNSAyMy40NDkyMTkgMTAuNjUyMzQ0IDIzLjg3MTA5NCBDIDExLjM5MDYyNSAyMy45NTcwMzEgMTMuMjkyOTY5IDIzLjkxNDA2MiAxMy45MzM1OTQgMjMuNzkyOTY5IEMgMTUuNDUzMTI1IDIzLjUxNTYyNSAxNi42OTkyMTkgMjMuMDY2NDA2IDE3LjkwMjM0NCAyMi4zNzg5MDYgQyAyMS4wMjczNDQgMjAuNTg1OTM4IDIzLjA4MjAzMSAxNy42NjQwNjIgMjMuNzY5NTMxIDE0LjAzOTA2MiBDIDIzLjkxNDA2MiAxMy4yOTI5NjkgMjMuOTY4NzUgMTEuNDcyNjU2IDIzLjg3MTA5NCAxMC42NTIzNDQgQyAyMy42Mjg5MDYgOC41MDM5MDYgMjIuODQ3NjU2IDYuNTQyOTY5IDIxLjUzOTA2MiA0Ljc3MzQzOCBDIDIxLjAzOTA2MiA0LjEwMTU2MiAxOS44NTU0NjkgMi45MTQwNjIgMTkuMTcxODc1IDIuNDEwMTU2IEMgMTcuNTYyNSAxLjIwMzEyNSAxNS43NDIxODggMC40NDE0MDYgMTMuNzQ2MDk0IDAuMTMyODEyIEMgMTMuMjEwOTM4IDAuMDU0Njg3NSAxMS41MjczNDQgLTAuMDExNzE4OCAxMS4wMzEyNSAwLjAzMTI1IFogTSAxMy4wNTQ2ODggMC43NzM0MzggQyAxMy4zMDQ2ODggMC43OTI5NjkgMTMuODAwNzgxIDAuODYzMjgxIDE0LjE2NDA2MiAwLjkzNzUgQyAxOC4wODU5MzggMS43MjI2NTYgMjEuMjc3MzQ0IDQuNTE5NTMxIDIyLjU4OTg0NCA4LjMyODEyNSBDIDIzLjAzMTI1IDkuNjEzMjgxIDIzLjIxMDkzOCAxMC44ODI4MTIgMjMuMTY0MDYyIDEyLjMzMjAzMSBDIDIzLjEyMTA5NCAxMy41ODIwMzEgMjIuOTE0MDYyIDE0LjY3NTc4MSAyMi41MDM5MDYgMTUuNzczNDM4IEMgMjEuMzM5ODQ0IDE4Ljg5MDYyNSAxOC45MzM1OTQgMjEuMjk2ODc1IDE1LjgxNjQwNiAyMi40NTMxMjUgQyAxNC43MDcwMzEgMjIuODYzMjgxIDEzLjY0MDYyNSAyMy4wNjY0MDYgMTIuMzgyODEyIDIzLjEwOTM3NSBDIDEwLjkxNzk2OSAyMy4xNTYyNSA5LjgwMDc4MSAyMy4wMDM5MDYgOC40NjA5MzggMjIuNTYyNSBDIDQuNDE3OTY5IDIxLjIxODc1IDEuNTMxMjUgMTcuNzc3MzQ0IDAuODc1IDEzLjUwNzgxMiBDIDAuNzUgMTIuNjk5MjE5IDAuNzUgMTEuMjA3MDMxIDAuODc1IDEwLjM2MzI4MSBDIDEuMjMwNDY5IDcuOTY0ODQ0IDIuMjY5NTMxIDUuODUxNTYyIDMuOTM3NSA0LjEzNjcxOSBDIDUuNjY3OTY5IDIuMzU1NDY5IDcuNzczNDM4IDEuMjU3ODEyIDEwLjIzMDQ2OSAwLjg1MTU2MiBDIDExLjA1MDc4MSAwLjcxNDg0NCAxMS44NTE1NjIgMC42OTE0MDYgMTMuMDU0Njg4IDAuNzczNDM4IFogTSAxMy4wNTQ2ODggMC43NzM0MzggIi8+CjxwYXRoIHN0eWxlPSIgc3Ryb2tlOm5vbmU7ZmlsbC1ydWxlOm5vbnplcm87ZmlsbDpyZ2IoMCUsMCUsMCUpO2ZpbGwtb3BhY2l0eToxOyIgZD0iTSAxMC41NzgxMjUgMS43ODkwNjIgQyA4Ljg2MzI4MSAyLjAxOTUzMSA3LjIzNDM3NSAyLjY4NzUgNS44MjgxMjUgMy43MzQzNzUgQyA0LjUyMzQzOCA0LjcxMDkzOCAzLjUyMzQzOCA1LjkxNDA2MiAyLjc2OTUzMSA3LjQwNjI1IEMgMi41MTk1MzEgNy45MDYyNSAyLjEzMjgxMiA4Ljg2NzE4OCAyLjEzMjgxMiA4Ljk4ODI4MSBDIDIuMTMyODEyIDkuMDU0Njg4IDYuMTg3NSA4Ljg5ODQzOCA3LjQ4ODI4MSA4Ljc4NTE1NiBDIDkuOTI1NzgxIDguNTc4MTI1IDEyLjA0Mjk2OSA4LjEzMjgxMiAxMy4wOTM3NSA3LjYwNTQ2OSBDIDEzLjgwNDY4OCA3LjI0MjE4OCAxMy45MjE4NzUgNi45MDYyNSAxMy41MTk1MzEgNi4zNjMyODEgQyAxMy4xODM1OTQgNS45MDIzNDQgMTIuMjY1NjI1IDUuMDkzNzUgMTEuMzM5ODQ0IDQuNDI5Njg4IEMgMTEuMTk1MzEyIDQuMzI4MTI1IDExLjA4MjAzMSA0LjIxNDg0NCAxMS4wODIwMzEgNC4xODM1OTQgQyAxMS4wODIwMzEgNC4xNDQ1MzEgMTEuMjY1NjI1IDMuNzI2NTYyIDExLjQ4ODI4MSAzLjI0NjA5NCBDIDExLjcxMDkzOCAyLjc2NTYyNSAxMS45NjA5MzggMi4yMjI2NTYgMTIuMDQyOTY5IDIuMDM1MTU2IEwgMTIuMTk1MzEyIDEuNzA3MDMxIEwgMTEuNjUyMzQ0IDEuNzEwOTM4IEMgMTEuMzU1NDY5IDEuNzE0ODQ0IDEwLjg3MTA5NCAxLjc0NjA5NCAxMC41NzgxMjUgMS43ODkwNjIgWiBNIDEwLjU3ODEyNSAxLjc4OTA2MiAiLz4KPHBhdGggc3R5bGU9IiBzdHJva2U6bm9uZTtmaWxsLXJ1bGU6bm9uemVybztmaWxsOnJnYigwJSwwJSwwJSk7ZmlsbC1vcGFjaXR5OjE7IiBkPSJNIDE1LjMwODU5NCAyLjMwMDc4MSBDIDE1LjMyNDIxOSAyLjMzOTg0NCAxNS40MjU3ODEgMi41NzAzMTIgMTUuNTMxMjUgMi44MDg1OTQgQyAxNS45MTAxNTYgMy42NzE4NzUgMTUuOTUzMTI1IDMuOTI1NzgxIDE1Ljk1MzEyNSA1LjMwMDc4MSBDIDE1Ljk1MzEyNSA2LjMzOTg0NCAxNS45Mzc1IDYuNjI1IDE1LjgzNTkzOCA3LjE1MjM0NCBDIDE1LjM5ODQzOCA5LjQ3MjY1NiAxNC4zMjQyMTkgMTAuOTcyNjU2IDEyLjM5NDUzMSAxMS45NDUzMTIgQyAxMC40NDUzMTIgMTIuOTI1NzgxIDguMjIyNjU2IDEzLjMwMDc4MSAzLjUzMTI1IDEzLjQxNzk2OSBDIDIuNjE3MTg4IDEzLjQzNzUgMS44NTU0NjkgMTMuNDc2NTYyIDEuODMyMDMxIDEzLjQ5MjE4OCBDIDEuODE2NDA2IDEzLjUxMTcxOSAxLjg0NzY1NiAxMy43ODkwNjIgMS45MTQwNjIgMTQuMTA1NDY5IEMgMi4zNzEwOTQgMTYuMzc4OTA2IDMuNzUgMTguNjAxNTYyIDUuNjIxMDk0IDIwLjA2MjUgQyA2LjgwNDY4OCAyMC45ODQzNzUgOC4zMTY0MDYgMjEuNzA3MDMxIDkuNzAzMTI1IDIyLjAxMTcxOSBDIDEwLjYwOTM3NSAyMi4yMTA5MzggMTIuNDcyNjU2IDIyLjM1OTM3NSAxMi41ODk4NDQgMjIuMjQyMTg4IEMgMTIuNjA5Mzc1IDIyLjIyNjU2MiAxMi40Mzc1IDIyLjA3MDMxMiAxMi4yMDcwMzEgMjEuOTAyMzQ0IEMgMTEuNTcwMzEyIDIxLjQyNTc4MSAxMC43MTQ4NDQgMjAuNjc1NzgxIDEwLjIwMzEyNSAyMC4xMzI4MTIgQyA5LjY3NTc4MSAxOS41NzAzMTIgOS4wODk4NDQgMTguODY3MTg4IDkuMTIxMDk0IDE4LjgzNTkzOCBDIDkuMTMyODEyIDE4LjgyNDIxOSA5LjM3MTA5NCAxOC45MDIzNDQgOS42NDQ1MzEgMTkgQyAxMS42NjQwNjIgMTkuNzY1NjI1IDE0LjE0MDYyNSAyMC4xNDg0MzggMTUuNDE0MDYyIDE5LjkwNjI1IEMgMTYuNzgxMjUgMTkuNjQ4NDM4IDE4LjI2NTYyNSAxOC44MzU5MzggMTkuNDM3NSAxNy43MTg3NSBDIDIwLjA1MDc4MSAxNy4xMzY3MTkgMjAuODg2NzE5IDE2LjA3ODEyNSAyMC44ODY3MTkgMTUuODkwNjI1IEMgMjAuODg2NzE5IDE1Ljg2MzI4MSAyMC41NTg1OTQgMTUuODA0Njg4IDIwLjE1NjI1IDE1Ljc1NzgxMiBDIDE4LjQ5NjA5NCAxNS41NTA3ODEgMTcuMDY2NDA2IDE1LjE2NDA2MiAxNi4yODkwNjIgMTQuNzAzMTI1IEMgMTUuNDg4MjgxIDE0LjIzNDM3NSAxNC45MTQwNjIgMTMuNDUzMTI1IDE0Ljc1MzkwNiAxMi42NDA2MjUgQyAxNC42Nzk2ODggMTIuMjMwNDY5IDE0LjcxODc1IDExLjMzMjAzMSAxNC44Mzk4NDQgMTAuNzY5NTMxIEMgMTUuMjE0ODQ0IDkuMDExNzE5IDE2LjQ5MjE4OCA2LjgwODU5NCAxNy43NjE3MTkgNS43NSBDIDE4LjE3MTg3NSA1LjQwMjM0NCAxOC42OTkyMTkgNS4xMDkzNzUgMTkuMTUyMzQ0IDQuOTgwNDY5IEwgMTkuNDYwOTM4IDQuODkwNjI1IEwgMTkuMTUyMzQ0IDQuNTk3NjU2IEMgMTguMDg5ODQ0IDMuNjE3MTg4IDE3LjAzOTA2MiAyLjkyOTY4OCAxNS44MTY0MDYgMi40Mjk2ODggQyAxNS4zMDQ2ODggMi4yMTQ4NDQgMTUuMjczNDM4IDIuMjEwOTM4IDE1LjMwODU5NCAyLjMwMDc4MSBaIE0gNi4zMTI1IDE0LjY1MjM0NCBMIDYuOTA2MjUgMTUuMjM4MjgxIEwgNy40ODA0NjkgMTQuNjUyMzQ0IEMgNy44NDc2NTYgMTQuMjczNDM4IDguMDkzNzUgMTQuMDY2NDA2IDguMTY3OTY5IDE0LjA2NjQwNiBDIDguMzE2NDA2IDE0LjA2NjQwNiA5Ljc1IDE1LjQ4NDM3NSA5Ljc1IDE1LjYzMjgxMiBDIDkuNzUgMTUuNzYxNzE5IDguMzMyMDMxIDE3LjIxMDkzOCA4LjIwNzAzMSAxNy4yMTA5MzggQyA4LjE2NDA2MiAxNy4yMTA5MzggNy44NTkzNzUgMTYuOTQxNDA2IDcuNTMxMjUgMTYuNjEzMjgxIEwgNi45MjU3ODEgMTYuMDExNzE5IEwgNi4zMjQyMTkgMTYuNjEzMjgxIEMgNS45OTYwOTQgMTYuOTQxNDA2IDUuNjkxNDA2IDE3LjIxMDkzOCA1LjY0NDUzMSAxNy4yMTA5MzggQyA1LjYwMTU2MiAxNy4yMTA5MzggNS4yMjI2NTYgMTYuODYzMjgxIDQuODAwNzgxIDE2LjQ0NTMxMiBDIDQuMDU4NTk0IDE1LjY5MTQwNiA0LjA0Mjk2OSAxNS42NzE4NzUgNC4xMTMyODEgMTUuNTM5MDYyIEMgNC4yMzQzNzUgMTUuMzA4NTk0IDUuNSAxNC4wNjY0MDYgNS42MDkzNzUgMTQuMDY2NDA2IEMgNS42NzU3ODEgMTQuMDY2NDA2IDUuOTU3MDMxIDE0LjMwMDc4MSA2LjMxMjUgMTQuNjUyMzQ0IFogTSA3Ljc2OTUzMSAxNi45NzY1NjIgQyA4LjI5Mjk2OSAxNy40NzI2NTYgOC41MjczNDQgMTcuNzM0Mzc1IDguNTI3MzQ0IDE3LjgxMjUgQyA4LjUyNzM0NCAxNy44OTQ1MzEgOC4yNzM0MzggMTguMTc5Njg4IDcuNzUzOTA2IDE4LjcwMzEyNSBMIDYuOTgwNDY5IDE5LjQ2ODc1IEwgNi4yNzczNDQgMTguNzg1MTU2IEMgNS4zNDM3NSAxNy44NzEwOTQgNS4zNTE1NjIgMTcuODc4OTA2IDUuNDU3MDMxIDE3LjcxODc1IEMgNS42MjEwOTQgMTcuNDY4NzUgNi44MzU5MzggMTYuMjUzOTA2IDYuOTIxODc1IDE2LjI1MzkwNiBDIDYuOTY4NzUgMTYuMjUzOTA2IDcuMzQ3NjU2IDE2LjU3ODEyNSA3Ljc2OTUzMSAxNi45NzY1NjIgWiBNIDcuNzY5NTMxIDE2Ljk3NjU2MiAiLz4KPHBhdGggc3R5bGU9IiBzdHJva2U6bm9uZTtmaWxsLXJ1bGU6bm9uemVybztmaWxsOnJnYigwJSwwJSwwJSk7ZmlsbC1vcGFjaXR5OjE7IiBkPSJNIDE4LjMzOTg0NCA4Ljc0NjA5NCBDIDE3LjkyNTc4MSA4Ljk1MzEyNSAxNy40MTQwNjIgOS40NTMxMjUgMTcuMjkyOTY5IDkuNzc3MzQ0IEMgMTcuMDM1MTU2IDEwLjQzMzU5NCAxNy40NDkyMTkgMTAuOTA2MjUgMTguNTg5ODQ0IDExLjI2NTYyNSBDIDE5LjAyNzM0NCAxMS40MDIzNDQgMjEuMzkwNjI1IDExLjc3NzM0NCAyMS40NDkyMTkgMTEuNzE4NzUgQyAyMS41MDc4MTIgMTEuNjYwMTU2IDIxLjI1IDEwLjgzMjAzMSAyMS4wNDY4NzUgMTAuNDIxODc1IEMgMjAuNjI4OTA2IDkuNTU0Njg4IDIwLjAxOTUzMSA4LjkzNzUgMTkuMzYzMjgxIDguNjkxNDA2IEMgMTguOTYwOTM4IDguNTM1MTU2IDE4LjcyNjU2MiA4LjU0Njg3NSAxOC4zMzk4NDQgOC43NDYwOTQgWiBNIDE4LjMzOTg0NCA4Ljc0NjA5NCAiLz4KPC9nPgo8L3N2Zz4K',
		);

		add_submenu_page(
			'wp-parsi-settings',
			__( 'Settings', 'wp-parsidate' ),
			__( 'Settings', 'wp-parsidate' ),
			'manage_options',
			'wp-parsi-settings',
			'wpp_render_settings',
		);

		add_action( 'admin_enqueue_scripts', 'wpp_enqueue_setting_page_style' );
	}

	add_action( 'admin_menu', 'wpp_add_settings_menu', 11 );
}

if ( ! function_exists( 'wp_parsi_get_settings' ) ) {

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
				'news_source'          => 'parsi',
			) );
		}

		return apply_filters( 'wpp_get_settings', $settings );
	}
}

if ( ! function_exists( 'wpp_register_settings' ) ) {

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
						'std'     => $option['std'] ?? '',
					),
				);

				register_setting( 'wpp_settings', 'wpp_settings', 'wpp_settings_sanitize' );
			}
		}
	}

	add_action( 'admin_init', 'wpp_register_settings' );
}

if ( ! function_exists( 'wpp_get_tabs' ) ) {

	/**
	 * Gets settings tabs
	 *
	 * @return              array Tabs list
	 * @since               2.0
	 */
	function wpp_get_tabs() {
		return apply_filters(
			'wpp_settings_tabs',
			array(
				'core'    => sprintf( __( '%s Core', 'wp-parsidate' ), '<span class="dashicons dashicons-admin-site"></span>' ),
				'conv'    => sprintf( __( '%s Converts', 'wp-parsidate' ), '<span class="dashicons dashicons-admin-settings"></span>' ),
				'tools'   => sprintf( __( '%s Tools', 'wp-parsidate' ), '<span class="dashicons dashicons-admin-tools"></span>' ),
				'plugins' => sprintf( __( '%s Plugins compatibility', 'wp-parsidate' ), '<span class="dashicons dashicons-admin-plugins"></span>' ),
				'about'   => sprintf( __( '%s About', 'wp-parsidate' ), '<span class="dashicons dashicons-info"></span>' ),
			),
		);
	}
}

if ( ! function_exists( 'wpp_settings_sanitize' ) ) {

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
}

if ( ! function_exists( 'wpp_get_registered_settings' ) ) {

	/**
	 * Get settings fields
	 *
	 * @return          array Fields
	 * @since           2.0
	 */
	function wpp_get_registered_settings() {
		return apply_filters( 'wpp_registered_settings', array(
			'core'    => apply_filters( 'wpp_core_settings', array(
				'localization'     => array(
					'id'   => 'localization',
					'name' => __( 'Localization', 'wp-parsidate' ),
					'type' => 'header'
				),
				'admin_lang'       => array(
					'id'      => 'admin_lang',
					'name'    => __( 'Change Locale in admin', 'wp-parsidate' ),
					'type'    => 'checkbox',
					'options' => 'enable',
					'std'     => 0,
					'desc'    => __( 'This option change WordPress locale to Persian in Admin', 'wp-parsidate' ),
				),
				'user_lang'        => array(
					'id'      => 'user_lang',
					'name'    => __( 'Change Locale in theme', 'wp-parsidate' ),
					'type'    => 'checkbox',
					'options' => 'enable',
					'std'     => 0,
					'desc'    => __( 'This option change WordPress locale to Persian in theme', 'wp-parsidate' ),
				),
				'persian_date'     => array(
					'id'      => 'persian_date',
					'name'    => __( 'Shamsi date', 'wp-parsidate' ),
					'type'    => 'checkbox',
					'options' => 'enable',
					'std'     => 0,
					'desc'    => __( 'By enabling this, Dates will convert to Shamsi (Jalali) dates', 'wp-parsidate' ),
				),
				'months_name_type' => array(
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
				'enable_fonts'     => array(
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
				'sep_digits_footer'  => array(
					'id'   => 'sep_font',
					'type' => 'footer',
				),
				'sep_others'         => array(
					'id'   => 'sep_others',
					'name' => __( 'Others', 'wp-parsidate' ),
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
				'sep_others_footer'  => array(
					'id'   => 'sep_others_footer',
					'type' => 'footer',
				),
			) ),
			'tools'   => apply_filters( 'wpp_tools_settings', array(
				'advanced_tools'          => array(
					'id'   => 'advanced_tools',
					'name' => __( 'Advanced Tools', 'wp-parsidate' ),
					'type' => 'header'
				),
				'disable_widget_block'    => array(
					'id'      => 'disable_widget_block',
					'name'    => __( 'Disable Widget Block', 'wp-parsidate' ),
					'type'    => 'checkbox',
					'options' => 'enable',
					'std'     => 0,
					'desc'    => __( 'By enabling this, Widget Block Editor disabled', 'wp-parsidate' ),
				),
				'dev_mode'                => array(
					'id'      => 'dev_mode',
					'name'    => __( 'Debug Mode', 'wp-parsidate' ),
					'type'    => 'checkbox',
					'options' => 'enable',
					'std'     => 0,
					'desc'    => __( 'By enabling this option, the uncompressed version of the JS and CSS files will be loaded.', 'wp-parsidate' ),
				),
				'date_in_admin_bar'       => array(
					'id'      => 'date_in_admin_bar',
					'name'    => __( "Display date in the admin bar", 'wp-parsidate' ),
					'type'    => 'checkbox',
					'options' => 'enable',
					'std'     => 'disable',
					'desc'    => __( "Display today's Jalali date in the WordPress admin bar.", 'wp-parsidate' ),
				),
				'sep_admin_bar_ate'       => array(
					'id'   => 'sep_admin_bar_ate',
					'type' => 'footer',
				),
				'copy_restriction_header' => array(
					'id'   => 'copy_restriction_header',
					'name' => __( 'Copy Restriction', 'wp-parsidate' ),
					'type' => 'header',
				),
				'disable_copy'            => array(
					'id'      => 'disable_copy',
					'name'    => __( 'Prevent users from copying site content', 'wp-parsidate' ),
					'type'    => 'checkbox',
					'options' => 'enable',
					'std'     => 'disable',
					'desc'    => __( "Simply protect your site's content from those who want to copy it.", 'wp-parsidate' ),
				),
				'disable_right_click'     => array(
					'id'      => 'disable_right_click',
					'name'    => __( 'Disable right click on website pages', 'wp-parsidate' ),
					'type'    => 'checkbox',
					'options' => 'enable',
					'std'     => 0,
					'desc'    => __( "Don't worry about downloading website images and other files anymore, this option prevents users from right clicking", 'wp-parsidate' ),
				),
				'copy_restriction_footer' => array(
					'id'   => 'copy_restriction_footer',
					'type' => 'footer',
				),
			) ),
			'plugins' => apply_filters( 'wpp_plugins_compatibility_settings', array() ),
		) );
	}
}

/**
 * Form Callbacks Made by EDD Development Team
 */

if ( ! function_exists( 'wpp_header_callback' ) ) {

	/**
	 * @param $args
	 */
	function wpp_header_callback( $args ) {
		$name = ! empty( $args['name'] ) ? esc_html__( $args['name'], 'wp-parsidate' ) : '';

		echo '<div class="wpp-settings-section"><h3 class="wpp-settings-section-header">' . $name . '<span class="dashicons dashicons-arrow-up-alt2"></span></h3><div class="wpp-settings-section-content">';
	}
}

if ( ! function_exists( 'wpp_footer_callback' ) ) {

	/**
	 * @param $args
	 */
	function wpp_footer_callback( $args ) {
		echo '</div></div>';
	}
}

if ( ! function_exists( 'wpp_checkbox_callback' ) ) {

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
		                        '<label for="wpp_settings%1$s" class="wpp-checkbox-label %3$s %4$s">%5$s</label><span> %6$s</span>',
			! $is_multiple ? '[' . $args['id'] . ']' : '[' . $args['parent'] . '][' . $args['id'] . ']',
			$checked,
			empty( $args['desc'] ) ? 'empty-label' : '',
			$is_multiple,
			$args['name'],
			$args['desc'],
		);

		echo $html;
	}
}

if ( ! function_exists( 'wpp_multicheck_callback' ) ) {

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
}

if ( ! function_exists( 'wpp_radio_callback' ) ) {

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
}

if ( ! function_exists( 'wpp_text_callback' ) ) {

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
}

if ( ! function_exists( 'wpp_number_callback' ) ) {

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
}

if ( ! function_exists( 'wpp_textarea_callback' ) ) {

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
}

if ( ! function_exists( 'wpp_password_callback' ) ) {

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
}

if ( ! function_exists( 'wpp_missing_callback' ) ) {

	/**
	 * @param $args
	 *
	 * @return false
	 */
	function wpp_missing_callback( $args ) {
		echo '&ndash;';

		return false;
	}
}

if ( ! function_exists( 'wpp_select_callback' ) ) {

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
}

if ( ! function_exists( 'wpp_color_select_callback' ) ) {

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
}

if ( ! function_exists( 'wpp_rich_editor_callback' ) ) {

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
}

if ( ! function_exists( 'wpp_upload_callback' ) ) {

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
}

if ( ! function_exists( 'wpp_file_upload_callback' ) ) {

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
}

if ( ! function_exists( 'wpp_checkout_fields_callback' ) ) {

	/**
	 * Render WooCommerce checkout fields options
	 *
	 * @param $args
	 *
	 * @return void
	 * @sicne 5.1.3
	 */
	function wpp_checkout_fields_callback( $args ) {
		global $wpp_settings;

		$sections = array(
			'billing'  => __( 'Billing', 'wp-parsidate' ),
			'shipping' => __( 'Shipping', 'wp-parsidate' ),
		);

		$value = $wpp_settings[ $args['id'] ] ?? $args['std'];

		$html = '<div class="wpp-checkout-fields-wrapper">';

		foreach ( $sections as $section_id => $section_name ) {
			$html .= sprintf( '<div class="wpp-checkout-section" data-section="%s">', $section_id );
			$html .= sprintf( '<h3>%s</h3>', $section_name );
			$html .= '<div class="wpp-checkout-fields-list" data-sortable="true">';

			if ( ! empty( $value[ $section_id ] ) ) {
				foreach ( $value[ $section_id ] as $field_id => $field ) {
					$html .= sprintf(
						'<div class="wpp-checkout-field" data-field-id="%s">
                        <div class="field-header">
                            <span class="dashicons dashicons-menu"></span>
                            <span class="field-title">%s</span>
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </div>
                        <div class="field-settings">
                            <label>
                                <input type="checkbox" name="wpp_settings[%s][%s][%s][enabled]" value="1" %s>
                                %s
                            </label>
                            <label>
                                <input type="checkbox" name="wpp_settings[%s][%s][%s][required]" value="1" %s>
                                %s
                            </label>
                            <input type="hidden" name="wpp_settings[%s][%s][%s][priority]" class="priority-input" value="%s">
                            <div class="field-width">
                                <label>%s</label>
                                <select name="wpp_settings[%s][%s][%s][width]">
                                    <option value="full" %s>%s</option>
                                    <option value="half" %s>%s</option>
                                </select>
                            </div>
                            <div class="field-position">
                                <label>%s</label>
                                <select name="wpp_settings[%s][%s][%s][position]">
                                    <option value="start" %s>%s</option>
                                    <option value="end" %s>%s</option>
                                </select>
                            </div>
                        </div>
                    </div>',
						$field_id,
						__( ucfirst( str_replace( array( $section_id . '_', '_' ), array( '', ' ' ), $field_id ) ), 'woocommerce' ),
						$args['id'],
						$section_id,
						$field_id,
						checked( ! empty( $field['enabled'] ), true, false ),
						__( 'Enable', 'wp-parsidate' ),
						$args['id'],
						$section_id,
						$field_id,
						checked( ! empty( $field['required'] ), true, false ),
						__( 'Required', 'wp-parsidate' ),
						$args['id'],
						$section_id,
						$field_id,
						$field['priority'],
						__( 'Width', 'wp-parsidate' ),
						$args['id'],
						$section_id,
						$field_id,
						selected( $field['width'], 'full', false ),
						__( 'Full Width', 'wp-parsidate' ),
						selected( $field['width'], 'half', false ),
						__( 'Half Width', 'wp-parsidate' ),
						__( 'Position', 'wp-parsidate' ),
						$args['id'],
						$section_id,
						$field_id,
						selected( $field['position'], 'start', false ),
						__( 'Start', 'wp-parsidate' ),
						selected( $field['position'], 'end', false ),
						__( 'End', 'wp-parsidate' )
					);
				}
			}

			$html .= '</div></div>';
		}

		$html .= '</div>';

		echo $html;
	}
}

if ( ! function_exists( 'wpp_render_settings' ) ) {

	/**
	 * Render WP Parsidate settings
	 *
	 * @return void
	 */
	function wpp_render_settings() {
		$active_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], wpp_get_tabs() ) ? $_GET['tab'] : 'core';

		ob_start();
		?>
		<?php settings_errors( 'wpp-notices' ); ?>

        <header class="wpp-header">
            <h1><?php _e( 'Settings', 'wp-parsidate' ); ?></h1>
        </header>

        <div class="wrap wpp-settings-wrap">
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
							wpp_render_settings_fields( 'wpp_settings_' . $active_tab, 'wpp_settings_' . $active_tab );
							?>
                        </table>
						<?php submit_button(); ?>
                    </form>
				<?php else : ?>
					<?php include WP_PARSI_DIR . 'includes/views/html-about.php'; ?>
				<?php endif; ?>
            </div><!-- #tab_container-->
        </div><!-- .wrap -->

		<?php include WP_PARSI_DIR . 'includes/views/html-settings-sidebar.php'; ?>

		<?php
		echo ob_get_clean();
	}
}

if ( ! function_exists( 'wpp_render_settings_fields' ) ) {
	/**
	 * Render WP Parsidate settings fields
	 *
	 * @param string $page
	 * @param string $section
	 *
	 * @return void
	 */
	function wpp_render_settings_fields( $page, $section ) {
		global $wp_settings_fields;

		if ( ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
			return;
		}

		foreach ( (array) $wp_settings_fields[ $page ][ $section ] as $field ) {
			echo '<div class="wpp-settings-field">';

			call_user_func( $field['callback'], $field['args'] );

			echo '</div>';
		}
	}
}

if ( ! function_exists( 'wpp_is_active' ) ) {

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
}

if ( ! function_exists( 'wpp_get_option' ) ) {

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
}

if ( ! function_exists( 'wpp_enqueue_setting_page_style' ) ) {

	/**
	 * Enqueue setting page style
	 *
	 * @param $hook
	 *
	 * @since 4.0.0
	 */
	function wpp_enqueue_setting_page_style( $hook ) {
		if ( 'toplevel_page_wp-parsi-settings' !== $hook ) {
			return;
		}

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || wpp_is_active( 'dev_mode' ) ? '' : '.min';

		wp_enqueue_style( 'wpp_option_page', WP_PARSI_URL . "assets/css/settings$suffix.css", null, WP_PARSI_VER );
	}
}

if ( ! function_exists( 'wpp_enqueue_setting_page_script' ) ) {
	function wpp_multilingual_compatibility_option( $old_settings ) {
		if ( WP_Parsidate::wpp_multilingual_is_active() ) {
			$settings = array(
				'wpp_multilingual_support' => array(
					'id'      => 'wpp_multilingual_support',
					'name'    => __( 'Multilingual compatibility', 'wp-parsidate' ),
					'type'    => 'checkbox',
					'options' => 1,
					'std'     => 0,
					'desc'    => __( 'By enabling this, ParsiDate options only work in persian locale', 'wp-parsidate' ),
				),
			);

			return array_merge( $old_settings, $settings );
		}

		return $old_settings;
	}

	add_filter( 'wpp_core_settings', 'wpp_multilingual_compatibility_option' );
}