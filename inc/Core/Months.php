<?php

namespace WPParsidate\Core;

use WPParsidate\Helper\Cache;
use WPParsidate\Settings\Settings;

class Months {
  public static function getNames() {
    $cache = Cache::get( 'months_name', false );
    if ( is_array( $cache ) ) {
      return $cache;
    }

    $type = Settings::get( 'months_name_type', 'persian' );

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
    Cache::set( 'months_name', $names );

    return $names;
  }
}
