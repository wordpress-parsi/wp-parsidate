<?php

namespace WPParsidate\Tests\Unit;

use WP_UnitTestCase;
use WPParsidate\Core\Calendar;

class CalendarTest extends WP_UnitTestCase {

  public function test_farvardin_caption_uses_selected_jalali_year(): void {
    global $m, $monthnum, $year, $day, $posts;

    $previous = array(
      'm'        => $m ?? null,
      'monthnum' => $monthnum ?? null,
      'year'     => $year ?? null,
      'day'      => $day ?? null,
      'posts'    => $posts ?? null,
    );

    try {
      $m        = '';
      $monthnum = 1;
      $year     = 1405;
      $day      = '';
      $posts    = array( (object) array( 'ID' => 1 ) );

      ob_start();

      try {
        Calendar::printCalendar();
        $html = (string) ob_get_contents();
      } finally {
        ob_end_clean();
      }

      $matches = array();

      $this->assertSame(
        1,
        preg_match( '~<caption>(.*?)</caption>~us', $html, $matches )
      );

      $this->assertSame(
        'فروردین ۱۴۰۵',
        strip_tags( $matches[1] )
      );
    } finally {
      foreach ( $previous as $name => $value ) {
        $GLOBALS[ $name ] = $value;
      }
    }
  }

  public function test_farvardin_last_week_has_correct_padding(): void {
    global $m, $monthnum, $year, $day, $posts;

    $previous = array(
      'm'        => $m ?? null,
      'monthnum' => $monthnum ?? null,
      'year'     => $year ?? null,
      'day'      => $day ?? null,
      'posts'    => $posts ?? null,
    );

    $previous_week_start = get_option( 'start_of_week' );

    try {
      $m        = '';
      $monthnum = 1;
      $year     = 1405;
      $day      = '';
      $posts    = array( (object) array( 'ID' => 1 ) );

      update_option( 'start_of_week', 1 );

      ob_start();

      try {
        Calendar::printCalendar();
        $html = (string) ob_get_contents();
      } finally {
        ob_end_clean();
      }

      $this->assertMatchesRegularExpression(
        '~<td>۳۱</td>\s*<td class="pad" colspan="6">&nbsp;</td>~u',
        $html
      );
    } finally {
      update_option( 'start_of_week', $previous_week_start );

      foreach ( $previous as $name => $value ) {
        $GLOBALS[ $name ] = $value;
      }
    }
  }
}
