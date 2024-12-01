<?php

namespace WP_Parsidate\Controls;

use Elementor\Base_Data_Control;
use Elementor\Modules\DynamicTags\Module as TagsModule;

defined( 'ABSPATH' ) || exit;

class WPP_Elementor_Date_Time_Control extends Base_Data_Control {
	public function get_type() {
		return 'date_time';
	}

	/**
	 * Register WPP date picker
	 *
	 * @return array
	 * @sicne 5.1.3
	 */
	protected function get_default_settings() {
		return array(
			'label_block'    => true,
			'picker_options' => array(
				'locale'     => 'fa',
				'altFormat'  => 'Y/m/d H:i',
				'dateFormat' => 'Y/m/d H:i',
				'enableTime' => true,
				'time_24hr'  => true,
			),
			'dynamic'        => array(
				'categories' => array(
					TagsModule::DATETIME_CATEGORY,
				),
			),
		);
	}

	/**
	 * Render our control to replace it with the Elementor's date_time control
	 *
	 * @return void
	 * @sicne 5.1.3
	 */
	public function content_template() {
		?>
        <div class="elementor-control-field">
            <label for="<?php $this->print_control_uid(); ?>" class="elementor-control-title">{{{ data.label }}}</label>
            <div class="elementor-control-input-wrapper elementor-control-dynamic-switcher-wrapper">
                <input
                        id="<?php $this->print_control_uid(); ?>"
                        placeholder="{{ view.getControlPlaceholder() }}"
                        class="elementor-control-tag-area date-picker"
                        type="text"
                        data-jdp
                        data-setting="{{ data.name }}"
                >
            </div>
        </div>
        <# if ( data.description ) { #>
        <div class="elementor-control-field-description">{{{ data.description }}}</div>
        <# } #>
		<?php
	}

	/**
	 * Sanitize the date
	 *
	 * @param $input
	 *
	 * @return false|string
	 * @sicne 5.1.3
	 */
	public function sanitize( $input ) {
		if ( empty( $input ) ) {
			return '';
		}

		try {
			return gregdate( 'Y/m/d H:i', $input );
		} catch ( \Exception $e ) {
			try {
				return gregdate( 'Y/m/d H:i', $input );
			} catch ( \Exception $e ) {
				return '';
			}
		}
	}

	/**
	 * Convert Jalali date to Gregorian
	 *
	 * @param $control
	 * @param $settings
	 *
	 * @return false|mixed|string
	 * @sicne 5.1.3
	 */
	public function get_value( $control, $settings ) {
		if ( isset( $settings[ $control['name'] ] ) ) {
			try {
				$value = gregdate( 'Y/m/d H:i', $settings[ $control['name'] ] );
			} catch ( \Exception $e ) {
				$value = $control['default'];
			}
		} else {
			$value = $control['default'];
		}

		return $value;
	}
}

new WPP_Elementor_Date_Time_Control();