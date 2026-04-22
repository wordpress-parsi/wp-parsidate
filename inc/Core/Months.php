<?php
/**
 * Months class
 *
 * Get month names
 */

namespace WPParsidate\Core;

use WPParsidate\Helper\Cache;
use WPParsidate\Settings\Settings;

class Months {
  /**
   * Get month names, Months type is persian, dari, kurdish, pashto
   *
   * @param  string|null  $type  Type of months
   *
   * @return array|mixed|null
   */
  public static function getNames( string $type = null ) {
    if ( is_null( $type ) ) {
      $type = Settings::get( 'months_name_type', 'persian' );
    }

    $cache = Cache::get( 'months_name_' . $type, false );
    if ( is_array( $cache ) ) {
      return $cache;
    }

    if ( $type === 'dari' ) {
      $names = array(
        '',
        'حمل',
        'ثور',
        'جوزا',
        'سرطان',
        'اسد',
        'سنبله',
        'میزان',
        'عقرب',
        'قوس',
        'جدی',
        'دلو',
        'حوت'
      );

    } elseif ( $type === 'kurdish' ) {
      $names = array(
        '',
        'خاکەلێوە',
        'گوڵان',
        'جۆزەردان',
        'پووشپەڕ',
        'گەلاوێژ',
        'خەرمانان',
        'ڕەزبەر',
        'گەڵاڕێزان',
        'سەرماوەز',
        'بەفرانبار',
        'ڕێبەندان',
        'ڕەشەمە'
      );

    } elseif ( $type === 'pashto' ) {
      $names = array(
        '',
        'وری',
        'غويی',
        'غبرګولی',
        'چنګاښ',
        'زمری',
        'وږی',
        'تله',
        'لړم',
        'ليندۍ',
        'مرغومی',
        'سلواغه',
        'كب'
      );

    } else {
      $names = array(
        '',
        'فروردین',
        'اردیبهشت',
        'خرداد',
        'تیر',
        'مرداد',
        'شهریور',
        'مهر',
        'آبان',
        'آذر',
        'دی',
        'بهمن',
        'اسفند'
      );
    }

    $names = apply_filters( 'wp_parsidate_name_of_months', $names, $type );
    Cache::set( 'months_name_' . $type, $names );

    return $names;
  }
}
