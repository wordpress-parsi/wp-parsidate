<?php

namespace WPParsidate\App\Core;

use WPParsidate\Settings\Settings;

class Locale {
	public function __construct() {
		add_filter( 'locale', [ $this, 'setLocale' ], 0 );
	}

	/**
	 * Change Locale WordPress Admin and Front-end user
	 *
	 * @param  String  $locale
	 *
	 * @return              String
	 */
	public function setLocale( $locale ): string {
		if ( Settings::get( 'admin_lang', false ) ) {
			$adminLocale = "fa_IR";
		} else {
			$adminLocale = $locale;
		}

		if ( Settings::get( 'user_lang', false ) ) {
			$userLocale = "fa_IR";
		} else {
			$userLocale = $locale;
		}

		$selectedLocale = is_admin() ? $adminLocale : $userLocale;

		if ( ! empty( $selectedLocale ) ) {
			$locale = $selectedLocale;
		}

		setlocale( LC_ALL, $locale );

		return $locale;
	}
}