<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

/**
 * WP-Parsidate Tools
 *
 * @author HamidReza Yazdani
 * @sicne 5.1.0
 */

if ( wpp_is_active( 'date_in_admin_bar' ) ) {

	if ( ! function_exists( 'wpp_add_date_to_admin_bar' ) ) {
		/**
		 * Add Jalali date to WordPress admin bar
		 *
		 * @param $wp_admin_bar
		 *
		 * @return void
		 */
		function wpp_add_date_to_admin_bar( $wp_admin_bar ) {
			$current_date = parsidate( 'l Y/m/d', date( 'Y-m-d' ) );

			$args = array(
				'id'    => 'wpp_current_date',
				'title' => esc_html__( 'Today:&nbsp;', 'wp-parsidate' ) . $current_date,
				'meta'  => array( 'class' => 'wpp-admin-bar-date' ),
			);

			$wp_admin_bar->add_node( $args );
		}

		add_action( 'admin_bar_menu', 'wpp_add_date_to_admin_bar', PHP_INT_MAX );
	}

	/**
	 * Style Wp-Parsidate admin bar date
	 */
	if ( ! function_exists( 'wpp_admin_bar_date_style' ) ) {
		function wpp_admin_bar_date_style( $wp_admin_bar ) {
			echo '<style>
			.wpp-admin-bar-date {
			    color: #fff;
			    background-color: var(--e-context-primary-color) !important;
			    border-radius: 5px 5px 0 0 !important;
			    unicode-bidi: embed !important;
			}
			</style>';
		}

		add_action( 'admin_head', 'wpp_admin_bar_date_style' );
	}
}

if (!function_exists('wpp_is_postal_code_validate')) {
    function wpp_is_postal_code_validate($postalCode, $checkSum = false): bool
    {
        // Convert to english
        $postalCode = eng_number($postalCode);

        // Remove space and special character
        $cleanedCode = preg_replace('/[-\s]/', '', $postalCode);
        if (!preg_match("/^\d{10}$/", $cleanedCode)) {
            return false;
        }

        // Postal code not start with zero
        if ($cleanedCode[0] === '0') {
            return false;
        }

        // Checksum Control
        if ($checkSum) {

            $checkDigit = (int)$cleanedCode[9];
            $sum = 0;
            for ($i = 0; $i < 9; $i++) {
                $sum += (int)$cleanedCode[$i] * (10 - $i);
            }
            $remainder = $sum % 11;
            $calculatedCheckDigit = ($remainder < 2) ? $remainder : 11 - $remainder;
            return $checkDigit === $calculatedCheckDigit;
        }

        return true;
    }
}

if (!function_exists('wpp_is_time_validate')) {
    function wpp_is_time_validate($time, $default_seconds = '00')
    {
        if (!is_string($time)) {
            return false;
        }

        if (preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9](?::[0-5][0-9])?$/', $time)) {
            if (substr_count($time, ':') === 1) {
                $time .= ':' . $default_seconds;
            }

            return $time;
        }

        return false;
    }
}

/*if ( wpp_is_active( 'disable_copy' ) ) {
	if ( ! function_exists( 'wpp_disable_copy' ) ) {
		function wpp_disable_copy() {
			echo '<script>
		document.addEventListener("DOMContentLoaded", (event) => {
		    document.body.onselectstart = () => false;
		    document.body.oncopy = (e) => {
		        e.preventDefault();
		        return false;
		    };
		});
		</script>';
		}

		add_action( 'wp_footer', 'wpp_disable_copy' );
	}
}

if ( wpp_is_active( 'disable_right_click' ) ) {
	if ( ! function_exists( 'wpp_disable_right_click' ) ) {
		function wpp_disable_right_click() {
			echo '<script>
			  document.addEventListener("contextmenu", function(e) {
			    e.preventDefault();
			  });
			</script>';
		}

		add_action( 'wp_footer', 'wpp_disable_right_click' );
	}
}*/