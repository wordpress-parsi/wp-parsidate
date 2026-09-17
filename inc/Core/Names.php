<?php
/**
 * Months class
 *
 * Get month names
 */

namespace WPParsidate\Core;

use WPParsidate\Helper\Cache;
use WPParsidate\Settings\Settings;

class Names {
  /**
   * Get Gregorian month names, Months type is persian, dari, kurdish, pashto, english
   *
   * The "english" type returns the English names WordPress itself uses, which
   * are needed to detect Gregorian dates in third party content.
   *
   * @param string|null $type Type of months name
   * @param bool $short Return short month names
   *
   * @return array|mixed|null
   */
  public static function getGregorianMonths( ?string $type = null, bool $short = false ) {
    if ( is_null( $type ) ) {
      $type = Settings::get( 'months_name_type', 'persian' );
    }

    $type = strtolower($type);
    $cacheKey = 'gregorian_months_name_' . $type . ( $short ? '_short' : '' );
    $cache    = Cache::get( $cacheKey, false );
    if ( is_array( $cache ) ) {
      return $cache;
    }

    if ( $type === 'dari' ) {
      $names = array(
        '',
        'جنوری',
        'فبروری',
        'مارچ',
        'اپریل',
        'می',
        'جون',
        'جولای',
        'آگست',
        'سپتمبر',
        'اکتوبر',
        'نومبر',
        'دسمبر'
      );

    } elseif ( $type === 'kurdish' ) {
      $names = array(
        '',
        'کانوونی دووەم',
        'شوبات',
        'ئازار',
        'نیسان',
        'ئایار',
        'حوزەیران',
        'تەممووز',
        'ئاب',
        'ئەیلوول',
        'تشرینی یەکەم',
        'تشرینی دووەم',
        'کانوونی یەکەم'
      );

    } elseif ( $type === 'pashto' ) {
      $names = array(
        '',
        'جنوري',
        'فبروري',
        'مارچ',
        'اپرېل',
        'مې',
        'جون',
        'جولای',
        'اګست',
        'سپتمبر',
        'اکتوبر',
        'نومبر',
        'دسمبر'
      );

    } elseif ( $type === 'english' ) {
      $names = self::getEnglishGregorianMonths();

    } else {
      $names = array(
        '',
        'ژانویه',
        'فوریه',
        'مارس',
        'آوریل',
        'مه',
        'ژوئن',
        'ژوئیه',
        'اوت',
        'سپتامبر',
        'اکتبر',
        'نوامبر',
        'دسامبر'
      );
    }

    if ( $short ) {
      $names = array_map(
        static fn( $name ) => mb_substr( $name, 0, 3 ),
        $names
      );
    }

    $names = apply_filters( 'wp_parsidate_name_of_gregorian_months', $names, $type, $short );
    Cache::set( $cacheKey, $names );

    return $names;
  }

  /**
   * Get English Gregorian month names
   *
   * WordPress' WP_Locale holds the month names of the active locale, so it is
   * only a source of English names on English sites. Any other locale falls
   * back to the plugin's own list.
   *
   * @return array<int,string> 1-based month names with an empty leading item
   */
  private static function getEnglishGregorianMonths(): array {
    global $wp_locale;

    if ( isset( $wp_locale->month ) && method_exists( $wp_locale, 'get_month' ) ) {
      $names = array( '' );

      for ( $month = 1; $month <= 12; $month ++ ) {
        $names[] = (string) $wp_locale->get_month( $month );
      }

      // Translated locales return non-ASCII names, which can't match English dates
      if ( ! preg_match( '/[^\x20-\x7E]/', implode( '', $names ) ) ) {
        return $names;
      }
    }

    return array(
      '',
      'January',
      'February',
      'March',
      'April',
      'May',
      'June',
      'July',
      'August',
      'September',
      'October',
      'November',
      'December'
    );
  }

  /**
   * Get month names, Months type is persian, dari, kurdish, pashto
   *
   * @param string|null $type Type of months name
   *
   * @return array|mixed|null
   */
  public static function getMonths( ?string $type = null ) {
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

  /**
   * Get week day names, Week day type is persian, dari, kurdish, pashto
   *
   * @param string|null $type Type of week day name
   *
   * @return array|mixed|null
   */
  public static function getWeekDays( ?string $type = null ) {
    if ( is_null( $type ) ) {
      $type = Settings::get( 'months_name_type', 'persian' );
    }

    $cache = Cache::get( 'week_day_names_' . $type, false );
    if ( is_array( $cache ) ) {
      return $cache;
    }

    if ( $type === 'kurdish' ) {
      $names = array(
        'یه‌شمه',
        'دوشَمه',
        'سه‌شمه',
        'چوارشمه',
        'پنشمه',
        'جــِـمَه',
        'شَمَه',
      );

    } elseif ( $type === 'pashto' ) {
      $names = array(
        'یونۍ',
        'دونۍ',
        'درېنۍ',
        'څلورنۍ',
        'پنځنۍ',
        'جمعه',
        'خالي',
      );

    } else {
      // Persian and Dari is equal names
      $names = array(
        'یکشنبه',
        'دوشنبه',
        'سه‌شنبه',
        'چهارشنبه',
        'پنجشنبه',
        'جمعه',
        'شنبه'
      );
    }

    $names = apply_filters( 'wp_parsidate_name_of_week_days', $names, $type );
    Cache::set( 'week_day_names_' . $type, $names );

    return $names;
  }

  /**
   * Get Gregorian week day names, Week day type is persian, dari, kurdish, pashto
   *
   * @param string|null $type Type of week day name
   * @param bool $short Return short week day names
   *
   * @return array|mixed|null
   */
  public static function getGregorianWeekDays( ?string $type = null, bool $short = false ) {
    if ( is_null( $type ) ) {
      $type = Settings::get( 'months_name_type', 'persian' );
    }

    $cacheKey = 'gregorian_week_day_names_' . $type . ( $short ? '_short' : '' );
    $cache    = Cache::get( $cacheKey, false );
    if ( is_array( $cache ) ) {
      return $cache;
    }

    if ( $type === 'kurdish' ) {
      $names = array(
        'یەکشەممە',
        'دووشەممە',
        'سێشەممە',
        'چوارشەممە',
        'پێنجشەممە',
        'هەینی',
        'شەممە',
      );

    } elseif ( $type === 'pashto' ) {
      $names = array(
        'یکشنبه',
        'دوشنبه',
        'سې شنبه',
        'چهارشنبه',
        'پنجشنبه',
        'جمعه',
        'شنبه',
      );

    } else {
      // Persian and Dari is equal names
      $names = array(
        'یکشنبه',
        'دوشنبه',
        'سه‌شنبه',
        'چهارشنبه',
        'پنجشنبه',
        'جمعه',
        'شنبه'
      );
    }

    if ( $short ) {
      $names = array_map(
        static fn( $name ) => mb_substr( $name, 0, 2 ),
        $names
      );
    }

    $names = apply_filters( 'wp_parsidate_name_of_gregorian_week_days', $names, $type, $short );
    Cache::set( $cacheKey, $names );

    return $names;
  }
}
