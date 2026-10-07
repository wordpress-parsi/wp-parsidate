<?php

namespace WPParsidate\App\Integration;

defined( 'ABSPATH' ) || exit;

use WPParsidate\Addons\Addon;

class Booked extends Addon {
  public string $addonID = 'booked';

  public function initAction(): void {
    add_filter( 'wp_parsidate_hook_deactivator_raw_list', [ $this, 'addDateHooksToDeactivator' ] );
  }

  public function addDateHooksToDeactivator( $rawList ): string {
    $rawList .= "\ndate_i18n,form,Booked_Calendar_Widget\ndate_i18n,booked_admin_add_appt,Booked_Admin_AJAX\ndate_i18n,booked_add_appt,Booked_AJAX\ndate_i18n,user_reminders,booked_plugin\ndate_i18n,admin_reminders,booked_plugin\ndate_i18n,booked_appointments_available\ndate_i18n,booked_fe_calendar\ndate_i18n,booked_fe_calendar_date_content\ndate_i18n,booked_appt_is_available\ndate_i18n,booked_fe_appointment_list_content";

    return $rawList;
  }

  public function info(): array {
    return array(
      'id'               => $this->addonID,
      'title'            => esc_html__( 'Booked', 'wp-parsidate' ),
      'desc'             => esc_html__( 'ParsiDate integration for Booked', 'wp-parsidate' ),
      'force_enable'     => true,
      'image_link'       => 'https://boxystudio.ticksy.com',
      'tags'             => [ esc_html__( 'Booking', 'wp-parsidate' ) ],
      'cat'              => 'integration',
      'settings_key'     => $this->addonID,
      'requires_plugins' => [
        'booked/booked.php' => array(
          'is_wp_plugin' => false,
          'is_free'      => false,
          'plugin_link'  => 'https://boxystudio.ticksy.com',
          'class_check'  => 'booked_plugin',
          'define_check' => 'BOOKED_VERSION',
        )
      ]
    );
  }
}
