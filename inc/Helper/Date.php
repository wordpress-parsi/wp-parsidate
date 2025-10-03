<?php

namespace WPParsidate\Helper;

class Date {
	public static function isDateString( $dateString, $format = 'Y-m-d\TH:i:sP' ): array {
		$default = [
			'status' => false,
			'type'   => null,
			'value'  => ''
		];

		$dateString = eng_number( $dateString );
		$dateParts  = date_parse_from_format( $format, $dateString );
		if ( $dateParts['error_count'] > 0 || $dateParts['warning_count'] > 0 ) {
			return $default;
		}

		$year  = $dateParts['year'];
		$month = $dateParts['month'];
		$day   = $dateParts['day'];

		if ( $year > 1900 ) {
			if ( checkdate( $month, $day, $year ) ) {
				return [
					'status' => true,
					'type'   => 'gregorian',
					'value'  => $dateString
				];
			}
		} elseif ( $year < 1500 ) {
			if ( $month >= 1 && $month <= 12 && $day >= 1 && $day <= 31 ) {
				if ( $month > 6 && $day > 30 ) {
					return $default;
				}

				return [
					'status' => true,
					'type'   => 'jalali',
					'value'  => $dateString
				];
			}
		}

		return $default;
	}

	public static function isTimeString( $time, $seconds = '00' ) {
		if ( ! is_string( $time ) ) {
			return false;
		}

		if ( preg_match( '/^(?:2[0-3]|[01][0-9]):[0-5][0-9](?::[0-5][0-9])?$/', $time ) ) {
			if ( substr_count( $time, ':' ) === 1 ) {
				$time .= ':' . $seconds;
			}

			return $time;
		}

		return false;
	}
}