<?php
/**
 * Parsi date main conversation class
 *
 * @author              Mobin Ghasempoor
 * @package             WP-Parsidate
 * @subpackage          DateConversation
 */

/*Special thanks to :
Reza Gholampanahi for convert function*/

class bn_parsidate
{
    protected static $instance;
    public $persian_month_names = array(
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
    public $persian_short_month_names = array(
        '',
        'فرو',
        'ارد',
        'خرد',
        'تیر',
        'مرد',
        'شهر',
        'مهر',
        'آبا',
        'آذر',
        'دی',
        'بهم',
        'اسف'
    );
    public $sesson = array('بهار', 'تابستان', 'پاییز', 'زمستان');

    public $persian_day_names = array('یکشنبه', 'دوشنبه', 'سه شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه');
    public $persian_day_small = array('ی', 'د', 'س', 'چ', 'پ', 'ج', 'ش');

    public $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
    private $j_days_sum_month = array(0, 0, 31, 62, 93, 124, 155, 186, 216, 246, 276, 306, 336);

    private $g_days_sum_month = array(0, 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);


    /**
     * Constructor
     */
    function __construct()
    {
        if ($this->IsLeapYear($this->persian_date("Y", time(), "eng"))) {
            $this->j_days_in_month[11] = 30;
        }
    }

    /**
     * bn_parsidate::IsLeapYear()
     * check year is leap
     * Update in 2.3.4V
     *
     * @param mixed $year
     *
     * @return boolean
     */
    public static function IsLeapYear($year)
    {
        // Update algorithm from https://goo.gl/wZCU76
        $a = 0.025;
        $b = 266;
        if ($year > 0) {
            $leapDays0 = (($year + 38) % 2820) * 0.24219 + $a;  # 0.24219 ~ extra days of one year
            $leapDays1 = (($year + 39) % 2820) * 0.24219 + $a;  # 38 days is the difference of epoch to 2820-year cycle
        } elseif ($year < 0) {
            $leapDays0 = (($year + 39) % 2820) * 0.24219 + $a;
            $leapDays1 = (($year + 40) % 2820) * 0.24219 + $a;
        } else
            return false;

        $frac0 = intval(($leapDays0 - intval($leapDays0)) * 1000);
        $frac1 = intval(($leapDays1 - intval($leapDays1)) * 1000);

        return  ($frac0 <= $b and $frac1 > $b);
    }

    /**
     * bn_parsidate::persian_date()
     * convert gregorian datetime to persian datetime
     *
     * @param mixed $format
     * @param string $date
     * @param string $lang
     *
     * @return datetime
     */
    public function persian_date($format, $date = 'now', $lang = 'per')
    {
        $j_days_in_month = array(31, 62, 93, 124, 155, 186, 216, 246, 276, 306, 336, 365);
        $timestamp = is_numeric($date) && (int)$date == $date ? $date : strtotime($date);

        $date = getdate($timestamp);

        list($date['year'], $date['mon'], $date['mday']) = self::gregorian_to_persian($date['year'], $date['mon'], $date['mday']);
        $date['mon'] = (int)$date['mon'];
        $date['mday'] = (int)$date['mday'];
        $out = '';
        $this->j_days_in_month[11] = $this->IsLeapYear($date['year']) ? 30 : 29;

        for ($i = 0; $i < strlen($format); $i++) {
            Switch ($format[$i]) {
                //day
                case 'd':
                    $out .= ($date['mday'] < 10) ? '0' . $date['mday'] : $date['mday'];
                    break;
                case 'D':
                    $out .= $this->persian_day_small[$date['wday']];
                    break;
                case 'l':
                    $out .= $this->persian_day_names[$date['wday']];
                    break;
                case 'j':
                    $out .= $date['mday'];
                    break;
                case 'N':
                    $out .= $this->week_day($date['wday']) + 1;
                    break;
                case 'w':
                    $out .= $this->week_day($date['wday']);
                    break;
                case 'z':
                    $out .= $this->j_days_in_month[$date['mon']] + $date['mday'];
                    break;
                //week
                case 'W':
                    $yday = $this->j_days_sum_month[$date['mon'] - 1] + $date['mday'];
                    $out .= intval($yday / 7);
                    break;
                //month
                case 'f':
                    $mon = $date['mon'];
                    switch ($mon) {
                        case($mon < 4):
                            $out .= $this->sesson[0];
                            break;
                        case($mon < 7):
                            $out .= $this->sesson[1];
                            break;
                        case($mon < 10):
                            $out .= $this->sesson[2];
                            break;
                        case($mon > 9):
                            $out .= $this->sesson[3];
                            break;
                    }
                    break;
                case'F':
                    $out .= $this->persian_month_names[(int)$date['mon']];
                    break;
                case'm':
                    $out .= ($date['mon'] < 10) ? '0' . $date['mon'] : $date['mon'];
                    break;
                case'M':
                    $out .= $this->persian_short_month_names[(int)$date['mon']];
                    break;
                case'n':
                    $out .= $date['mon'];
                    break;
                case'S':
                    $out .= 'ام';
                    break;
                case't':
                    $out .= $this->j_days_in_month[(int)$date['mon'] - 1];
                    break;
                //year
                case'L':
                    $out .= (($date['year'] % 4) == 0) ? 1 : 0;
                    break;
                case'o':
                case'Y':
                    $out .= $date['year'];
                    break;
                case'y':
                    $out .= substr($date['year'], 2, 2);
                    break;
                //time
                case'a':
                    $out .= ($date['hours'] < 12) ? 'ق.ظ' : 'ب.ظ';
                    break;
                case'A':
                    $out .= ($date['hours'] < 12) ? 'قبل از ظهر' : 'بعد از ظهر';
                    break;
                case'B':
                    $out .= (int)(1 + ($date['mon'] / 3));
                    break;
                case'g':
                    $out .= ($date['hours'] > 12) ? $date['hours'] - 12 : $date['hours'];
                    break;
                case'G':
                    $out .= $date['hours'];
                    break;
                case'h':
                    $hour = ($date['hours'] > 12) ? $date['hours'] - 12 : $date['hours'];
                    $out .= ($hour < 10) ? '0' . $hour : $hour;
                    break;
                case'H':
                    $out .= ($date['hours'] < 10) ? '0' . $date['hours'] : $date['hours'];
                    break;
                case'i':
                    $out .= ($date['minutes'] < 10) ? '0' . $date['minutes'] : $date['minutes'];
                    break;
                case's':
                    $out .= ($date['seconds'] < 10) ? '0' . $date['seconds'] : $date['seconds'];
                    break;
                //full date time
                case'c':
                    $out = $date['year'] . '/' . $date['mon'] . '/' . $date['mday'] . ' ' . $date['hours'] . ':' . (($date['minutes'] < 10) ? '0' . $date['minutes'] : $date['minutes']) . ':' . (($date['seconds'] < 10) ? '0' . $date['seconds'] : $date['seconds']);//2004-02-12T15:19:21+00:00
                    break;
                case'r':
                    $out = $this->persian_day_names[$date['wday']] . ',' . $date['mday'] . ' ' . $this->persian_month_names[(int)$date['mon']] . ' ' . $date['year'] . ' ' . $date['hours'] . ':' . (($date['minutes'] < 10) ? '0' . $date['minutes'] : $date['minutes']) . ':' . (($date['seconds'] < 10) ? '0' . $date['seconds'] : $date['seconds']);//Thu, 21 Dec 2000 16:01:07
                    break;
                case'U':
                    $out = $timestamp;
                    break;
                //others
                case'e':
                case'I':
                case'O':
                case'P':
                case'T':
                case'Z':
                case'u':
                    break;
                default:
                    $out .= $format[$i];
            }
        }
        if ($lang == 'per') {
            return self::trim_number($out);
        } else {
            return $out;
        }
    }

    /**
     * bn_parsidate::gregorian_to_persian()
     * convert gregorian date to persian date
     *
     * @param mixed $gy
     * @param mixed $gm
     * @param mixed $gd
     *
     * @return array
     */
    function gregorian_to_persian($gy, $gm, $gd)
    {
        $dayofyear = $this->g_days_sum_month[(int)$gm] + $gd;
        if (self::IsLeapYear($gy) and $gm > 2) {
            $dayofyear++;
        }
        $d_33 = (int)((($gy - 16) % 132) * 0.0305);
        $leap = $gy % 4;
        $a = (($d_33 == 1 or $d_33 == 2) and ($d_33 == $leap or $leap == 1)) ? 78 : (($d_33 == 3 and $leap == 0) ? 80 : 79);
        $b = ($d_33 == 3 or $d_33 < ($leap - 1) or $leap == 0) ? 286 : 287;
        if ((int)(($gy - 10) / 63) == 30) {
            $b--;
            $a++;
        }
        if ($dayofyear > $a) {
            $jy = $gy - 621;
            $jd = $dayofyear - $a;
        } else {
            $jy = $gy - 622;
            $jd = $dayofyear + $b;
        }
        for ($i = 0; $i < 11 and $jd > $this->j_days_in_month[$i]; $i++) {
            $jd -= $this->j_days_in_month[$i];
        }
        $jm = ++$i;

        return array($jy, strlen($jm) == 1 ? '0' . $jm : $jm, strlen($jd) == 1 ? '0' . $jd : $jd);
    }

    /**
     * Get day of the week shamsi/jalali
     * @author       Parsa Kafi
     *
     * @param        int $wday
     *
     * @return       int
     */
    private function week_day($wday)
    {
        if ($wday == 6) {
            return 0;
        } else {
            return ++$wday;
        }
    }

    /**
     * bn_parsidate::trim_number()
     * convert english number to persian number
     *
     * @param mixed $num
     * @param string $sp
     *
     * @return string
     */
    public function trim_number($num, $sp = '٫')
    {
        $eng = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.');
        $per = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', $sp);
        $number = filter_var($num, FILTER_SANITIZE_NUMBER_INT);

        return empty($number) ? str_replace($per, $eng, $num) : str_replace($eng, $per, $num);
    }

    /**
     * bn_parsidate::getInstance()
     * create instance of bn_parsidate class
     *
     * @return instance
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * bn_parsidate::gregurian_date()
     * convert persian datetime to gregorian datetime
     *
     * @param mixed $format
     * @param mixed $persiandate
     *
     * @return mixed
     */
    public function gregurian_date($format, $persiandate)
    {
        preg_match_all('!\d+!', $persiandate, $matches);
        $matches = $matches[0];
        list($year, $mon, $day) = self::persian_to_gregorian($matches[0], $matches[1], $matches[2]);

        return date($format, mktime((isset($matches[3]) ? $matches[3] : 0), (isset($matches[4]) ? $matches[4] : 0), (isset($matches[5]) ? $matches[5] : 0), $mon, $day, $year));
    }

    /**
     * bn_parsidate::persian_to_gregorian()
     * convert persian date to gregorian date
     *
     * @param mixed $jy
     * @param mixed $jm
     * @param mixed $jd
     *
     * @return array
     */
    public function persian_to_gregorian($jy, $jm, $jd)
    {
        $this->j_days_in_month[11] = $this->IsLeapYear($jy) ? 30 : 29;
        $doyj = ($jm - 2 > -1 ? $this->j_days_sum_month[(int)$jm] + $jd : $jd);
        $d4 = ($jy + 1) % 4;
        $d33 = (int)((($jy - 55) % 132) * .0305);
        $a = ($d33 != 3 and $d4 <= $d33) ? 287 : 286;
        $b = (($d33 == 1 or $d33 == 2) and ($d33 == $d4 or $d4 == 1)) ? 78 : (($d33 == 3 and $d4 == 0) ? 80 : 79);
        if ((int)(($jy - 19) / 63) == 20) {
            $a--;
            $b++;
        }
        if ($doyj <= $a) {
            $gy = $jy + 621;
            $gd = $doyj + $b;
        } else {
            $gy = $jy + 622;
            $gd = $doyj - $a;
        }
        foreach (array(0, 31, ($gy % 4 == 0) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31) as $gm => $days) {
            if ($gd <= $days) {
                break;
            }
            $gd -= $days;
        }

        return array($gy, $gm, $gd);
    }
}

/*
* parsidate function
*/
/**
 * parsidate()
 * convert gregorian datetime to persian datetime
 *
 * @param mixed $input
 * @param string $datetime
 * @param string $lang
 *
 * @return string
 */
function parsidate($input, $datetime = 'now', $lang = 'per')
{
    $bndate = bn_parsidate::getInstance();
    $bndate = $bndate->persian_date($input, $datetime, $lang);

    return $bndate;
}

/**
 * gregdate()
 * convert persian datetime to gregorian datetime
 *
 * @param mixed $input
 * @param mixed $datetime
 *
 * @return string
 */
function gregdate($input, $datetime)
{
    $bndate = bn_parsidate::getInstance();
    $bndate = $bndate->gregurian_date($input, $datetime);

    return $bndate;
}
