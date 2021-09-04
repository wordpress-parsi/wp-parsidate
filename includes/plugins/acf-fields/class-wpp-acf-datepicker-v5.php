<?php

defined( 'ABSPATH' ) || exit( 'No direct script access allowed' );

/**
 * Adds Jalali Datepicker field to ACF
 *
 * @package             WP-Parsidate
 * @subpackage          Plugins/ACF/WPP_acf_field_jalali_datepicker
 *
 * @since 4.0.0
 */
class WPP_acf_field_jalali_datepicker extends acf_field {

    /**
     * Hooks required tags
     */
    function __construct( $settings ) {
        $this->name = 'jalali_datepicker';

        $this->label = __( 'Date', 'wp-parsidate' );

        $this->category = __( 'Parsidate', 'wp-parsidate' );

        $this->defaults = array(
                'placeholder' => 'YYYY-MM-DD',
        );

        $this->l10n = array(
                'error' => __( 'Error! Please select a valid date.', 'wp-parsidate' ),
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
        <input type="text" name="<?php echo esc_attr( $field['name'] ) ?>"
               value="<?php echo esc_attr( $field['value'] ) ?>" class="date-picker" autocomplete="off"
               placeholder="<?php echo $field['placeholder'] ?>"/>
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

        if ( ! wp_script_is( 'wpp-jalali-datepicker' ) ) {
            wp_enqueue_script( 'wpp_jalali_datepicker', WP_PARSI_URL . 'assets/js/jalalidatepicker.min.js', array( 'acf-input' ), WP_PARSI_VER );
            wp_enqueue_style( 'wpp_jalali_datepicker', WP_PARSI_URL . "assets/css/jalalidatepicker$suffix.css", array( 'acf-input' ), WP_PARSI_VER );
        }
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
        if ( ! wpp_is_active( 'acf_persian_date' ) ) {
            $value = parsidate( 'Y-m-d', $value );
        }

        return apply_filters( 'wpp_acf_after_load_jalali_date', $value );
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
        if ( ! wpp_is_active( 'acf_persian_date' ) ) {
            $value = gregdate( 'Y-m-d', $value );
        }

        return apply_filters( 'wpp_acf_before_update_jalali_date', $value );
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

        return apply_filters( 'wpp_acf_before_jalali_date_render', $value );
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
        return apply_filters( 'wpp_acf_validate_date_value', $valid, $value, $field, $input );
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
        return apply_filters( 'wpp_acf_after_load_date_field', $field );
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
        return apply_filters( 'wpp_acf_before_save_date_field', $field );
    }
}

new WPP_acf_field_jalali_datepicker( $this->settings );