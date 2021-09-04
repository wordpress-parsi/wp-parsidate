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

        parent::__construct();

        $this->settings = $settings;
    }

    /**
     *  Create extra settings for your field. These are visible when editing a field
     *
     * @param           $field (array) the $field being edited
     *
     * @since           4.0.0
     */
    function create_options( $field ) {
        $key = $field['name'];
        ?>
        <tr class="field_option field_option_<?php echo $this->name; ?>">
            <td class="label">
                <label><?php __( 'Placeholder', 'wp-parsidate' ); ?></label>
                <p class="description"><?php _e( 'Show custom placeholder', 'wp-parsidate' ); ?></p>
            </td>
            <td>
                <?php
                do_action( 'acf/create_field', array(
                        'type'    => 'text',
                        'name'    => 'fields[' . $key . '][placeholder]',
                        'value'   => $field['placeholder'],
                        'layout'  => 'horizontal'
                ) );
                ?>
            </td>
        </tr>
        <?php
    }

    /**
     *  Create the HTML interface for your field
     *
     * @param           $field (array) the $field being edited
     *
     * @since           4.0.0
     */
    function create_field( $field ) {
        ?>
        <div>
            <input type="text" name="<?php echo esc_attr( $field['name'] ) ?>"
                   value="<?php echo esc_attr( $field['value'] ) ?>" class="date-picker" autocomplete="off"
                   placeholder="<?php echo $field['placeholder'] ?>"/>
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

        wp_enqueue_script( 'wpp_jalali_datepicker', WP_PARSI_URL . 'assets/js/jalalidatepicker.min.js', array( 'acf-input' ), WP_PARSI_VER );
        wp_enqueue_style( 'wpp_jalali_datepicker', WP_PARSI_URL . "assets/css/jalalidatepicker$suffix.css", array( 'acf-input' ), WP_PARSI_VER );
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
     *  This filter is applied to the $value after it is loaded from the db and, before it is passed back to the API functions such as the_field
     *
     * @param    $value - the value which was loaded from the database
     * @param    $post_id - the $post_id from which the value was loaded
     * @param    $field - the field array holding all the field options
     *
     * @return mixed|void $value    - the modified value
     * @since    4.0.0
     */
    function format_value_for_api( $value, $post_id, $field ) {
        if ( ! wpp_is_active( 'acf_persian_date' ) ) {
            $value = parsidate( 'Y-m-d', $value );
        }

        return apply_filters( 'wpp_acf_after_load_jalali_date', $value );
    }
}

new WPP_acf_field_jalali_datepicker( $this->settings );