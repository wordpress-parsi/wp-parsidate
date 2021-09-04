<?php

defined( 'ABSPATH' ) || exit( 'No direct script access allowed' );

/**
 * Adds Timepicker field to ACF
 *
 * @package             WP-Parsidate
 * @subpackage          Plugins/ACF/WPP_acf_field_wpp_timepicker
 *
 * @since 4.0.0
 */
class WPP_acf_field_wpp_timepicker extends acf_field {

    /**
     * Hooks required tags
     */
    function __construct( $settings ) {
        $this->name = 'wpp_timepicker';

        $this->label = __( 'Time', 'wp-parsidate' );

        $this->category = __( 'Parsidate', 'wp-parsidate' );

        $this->defaults = array(
                'time-format' => '12 hours',
                'placeholder' => 'HH:MM',
        );

        $this->l10n = array(
                'error' => __( 'Error! Please select a valid time.', 'wp-parsidate' ),
        );

        $this->settings = $settings;

        parent::__construct();
    }

    /**
     *  Create extra settings for your field. These are visible when editing a field
     *
     * @param           $field (array) the $field being edited
     *
     * @since           4.0.0
     */
    function render_field_settings( $field ) {
        acf_render_field_setting( $field, array(
                'label'        => __( 'Time Format', 'wp-parsidate' ),
                'instructions' => __( 'Display time picker in 24 or 12 hours format', 'wp-parsidate' ),
                'type'         => 'select',
                'name'         => 'time-format',
                'choices'      => array(
                        '12hours'  => '12 hours',
                        '24hours' => '24 hours',
                )
        ) );

        acf_render_field_setting( $field, array(
                'label'        => __( 'Placeholder', 'wp-parsidate' ),
                'instructions' => __( 'Show custom placeholder', 'wp-parsidate' ),
                'type'         => 'text',
                'name'         => 'placeholder',
        ) );
    }

    /**
     *  Create the HTML interface for your field
     *
     * @param           $field (array) the $field being edited
     *
     * @since           4.0.0
     */
    function render_field( $field ) { ?>
        <div class="wpp-time-picker">
            <input type="text" id="<?php echo esc_attr( $field['key'] ) ?>"
                   name="<?php echo esc_attr( $field['name'] ) ?>" dir="ltr"
                   value="<?php echo esc_attr( $field['value'] ) ?>" autocomplete="off"
                   placeholder="<?php echo $field['placeholder'] ?>"/>
            <script>
                jQuery(document).ready(function ($) {
                    $('#<?php echo esc_attr( $field['key'] ) ?>').timepicki({
                        show_meridian:<?php echo '24hours' === $field['time-format'] ? 'true' : 'false'; ?>,
                        disable_keyboard_mobile: true
                    })
                })
            </script>
        </div>
        <?php
    }

    /**
     *  This action is called in the admin_enqueue_scripts action on the edit screen where your field is created.
     *  Use this action to add CSS + JavaScript to assist your render_field() action.
     *
     * @since           4.0.0
     */
    function input_admin_enqueue_scripts() {
        $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || wpp_is_active( 'dev_mode' ) ? '' : '.min';

        wp_enqueue_script( 'wpp_wpp_timepicker', WP_PARSI_URL . "assets/js/timepicki$suffix.js", array( 'acf-input' ), WP_PARSI_VER );
        wp_enqueue_style( 'wpp_wpp_timepicker', WP_PARSI_URL . "assets/css/timepicki$suffix.css", array( 'acf-input' ), WP_PARSI_VER );

        // Remove jquery time picker to avoid conflict with woocommerce
        wp_dequeue_style( 'acf-timepicker' );
        wp_dequeue_script( 'acf-timepicker' );
    }

    /**
     *  This filter is applied to the $value after it is loaded from the db
     *
     * @param           $value (mixed) the value found in the database
     * @param           $post_id (mixed) the $post_id from which the value was loaded
     * @param           $field (array) the field array holding all the field options
     *
     * @return          int|string $value
     * @since           4.0.0
     */
    function load_value( $value, $post_id, $field ) {
        return apply_filters( 'wpp_acf_after_load_time', $value );
    }

    /**
     *  This filter is applied to the $value before it is saved in the db
     *
     * @param           $value (mixed) the value found in the database
     * @param           $post_id (mixed) the $post_id from which the value was loaded
     * @param           $field (array) the field array holding all the field options
     *
     * @return          false|string $value
     * @since           4.0.0
     */
    function update_value( $value, $post_id, $field ) {
        return apply_filters( 'wpp_acf_before_update_time', $value );
    }

    /**
     *  This filter is applied to the $value after it is loaded from the db and, before it is returned to the template
     *
     * @param           $value (mixed) the value which was loaded from the database
     * @param           $post_id (mixed) the $post_id from which the value was loaded
     * @param           $field (array) the field array holding all the field options
     *
     * @return          mixed $value (mixed) the modified value
     * @since           4.0.0
     */
    function format_value( $value, $post_id, $field ) {
        if ( empty( $value ) ) {
            return $value;
        }

        return apply_filters( 'wpp_acf_before_time_render', $value );
    }

    /**
     *  This filter is used to perform validation on the value prior to saving.
     *  All values are validated regardless of the field's required setting. This allows you to validate and return
     *  messages to the user if the value is not correct
     *
     * @param            $valid (boolean) validation status based on the value and the field's required setting
     * @param            $value (mixed) the $_POST value
     * @param            $field (array) the field array holding all the field options
     * @param            $input (string) the corresponding input name for $_POST value
     *
     * @return            true|false
     * @since           4.0.0
     *
     */
    function validate_value( $valid, $value, $field, $input ) {
        return apply_filters( 'wpp_acf_validate_time_value', $valid, $value, $field, $input );
    }

    /**
     *  This filter is applied to the $field after it is loaded from the database
     *
     * @param           $field (array) the field array holding all the field options
     *
     * @return          mixed $field
     * @since           4.0.0
     */
    function load_field( $field ) {
        return apply_filters( 'wpp_acf_after_load_time_field', $field );
    }

    /**
     *  This filter is applied to the $field before it is saved to the database
     *
     * @param           $field (array) the field array holding all the field options
     *
     * @return          mixed $field
     * @since           4.0.0
     */
    function update_field( $field ) {
        return apply_filters( 'wpp_acf_before_save_time_field', $field );
    }
}

new WPP_acf_field_wpp_timepicker( $this->settings );