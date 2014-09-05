<?php
ob_start();

class bn_parsidate
{
    protected static $instance;
    private $persian_month_names=array('','فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند');
    private $persian_short_month_names=array('','فرو','ارد','خرد','تیر','مرد','شهر','مهر','آبا','آذر','دی','بهم','اسف');
    private $sesson=array('بهار','تابستان','پاییز','زمستان');
    
    private $persian_day_names=array('یکشنبه','دوشنبه','سه شنبه','چهارشنبه','پنجشنبه','جمعه','شنبه');
    private $persian_day_small=array("ی","د","س","چ","پ","ج","ش");
    
    private $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
    private $j_days_sum_month = array(0,0,31, 62, 93, 124, 155, 186, 216, 246, 276, 306, 336, 365);
    
    private $g_days_in_month  = array(31, 28, 31,  30,  31,  30,  31,  31,  30,  31,  30,  31);
    private $g_days_leap_month  = array(31, 29, 31,  30,  31,  30,  31,  31,  30,  31,  30,  31);
    private $g_days_sum_month = array(0,0,31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334, 365);
    
    public static function getInstance()
    {
        if (!isset(self::$instance))
        self::$instance = new self();
        return self::$instance;
    }
    
    private function IsLeapYear($year)
    {
        if((($year%4)==0 && ($year%100)!=0)||(($year%400)==0)&&($year%100)==0)
        return true;
        else
        return false;
    }
    
    public function persian_to_gregorian($jy,$jm,$jd)
    {
       $gd=($jm-2>-1?$this->j_days_sum_month[intval($jm)]+$jd:$jd);
       $gy=$jy+621;
       if($gd>286)
       $gy++;
       if(self::IsLeapYear($gy-1)&& 286<$gd)
       $gd--;
       if($gd>286) 
         $gd-=286;
       else
         $gd+=79;
       if(self::IsLeapYear($gy))
       {
          for($gm=0;$gd>$this->g_days_leap_month[$gm];$gm++)
              $gd-=$this->g_days_leap_month[$gm];
       }
       else
       {
          for($gm=0;$gd>$this->g_days_in_month[$gm];$gm++)
              $gd-=$this->g_days_in_month[$gm];       
       }
       $gm++;
       return array($gy,$gm,$gd); 
    }
    
    public function gregorian_to_persian($gy,$gm,$gd)
    {       
        $dayofyear=$this->g_days_sum_month[$gm]+$gd;
        $leap=self::IsLeapYear($gy-1);
        $leab=self::IsLeapYear($gy);
        if($dayofyear>79)
        {
         $jd=($leab)?$dayofyear-78:$dayofyear-79;
         $jy=$gy-621;
        }
        else
        {
         $jd=($leap||($leab&&$gm>2))?287+$dayofyear:286+$dayofyear;
         $jy=$gy-622; 
        }
        for($i=0;$i<11 and $jd>$this->j_days_in_month[$i];$i++)
        $jd-=$this->j_days_in_month[$i];
        $jm=++$i; 
        return array($jy,$jm,$jd);   
    } 
    
    public function trim_number($num,$sp='٫')
    {
         $eng=array('0','1','2','3','4','5','6','7','8','9','.');
         $per=array('۰','۱','۲','۳','۴','۵','۶','۷','۸','۹',$sp);
         $number=filter_var($num, FILTER_SANITIZE_NUMBER_INT);
        return empty($number)?str_replace($per,$eng,$num):str_replace($eng,$per,$num); 
    }   

    public function persian_date($format,$date='now',$lang='per')
    {
      $j_days_in_month = array(31, 62, 93, 124, 155, 186, 216, 246, 276, 306, 336, 365);
	  $timestamp = is_numeric($date) && (int)$date == $date?$date:strtotime($date);
	  
      $date=getdate($timestamp);
      list($date['year'],$date['mon'],$date['mday'])=self::gregorian_to_persian($date['year'],$date['mon'],$date['mday']);
      	  
	  $out='';
      for($i=0;$i<strlen($format);$i++)
      {
        Switch($format[$i])
        {
            //day
            case 'd':
                    $out.=($date['mday']<10)?'0'.$date['mday']:$date['mday'];
            break;
            case 'D':
                    $out.=$this->persian_day_small[$date['wday']];
            break;
            case'l':
                    $out.=$this->persian_day_names[$date['wday']];
            break;
            case 'j':
                    $out.=$date['mday'];
            break;
            case'N':
                    $out.=$date['mday']+1;
            break;
            case'w':
                    $out.=$date['mday'];
            break;
            case'z':
                    $out.=$this->j_days_in_month[$date['mon']]+$date['mday'];
            break;
            //week
            case'W':
                   $yday=$this->j_days_sum_month[$date['mon']-1]+$date['mday'];
                   $out.=intval($yday/7);
            break;
            //month
            case'f':
                    $mon=$date['mon'];
                    switch($mon)
                    {
                        case($mon<4):
                        $out.=$this->sesson[0];
                        break;
                        case($mon<7):
                        $out.=$this->sesson[1];
                        break;
                        case($mon<10):
                        $out.=$this->sesson[2];
                        break;
                        case($mon>9):
                        $out.=$this->sesson[3];
                        break; 
                    }
            break;
            case'F':
                   $out.=$this->persian_month_names[$date['mon']];
            break;
            case'm':
                   $out.=($date['mon']<10)?'0'.$date['mon']:$date['mon'];
            break;
            case'M':
                   $out.=$this->persian_short_month_names[$date['mon']];
            break;
            case'n':
                   $out.=$date['mon'];
            break;
            case'S':
                   $out.='ام';
            break;
            case't':
                   $out.=$this->j_days_in_month[$date['mon']];
            break;
            //year
            case'L':
                   $out.=(($date['year']%4)==0)?1:0;
            break;
            case'o':case'Y':
                   $out.=$date['year'];
            break;
            case'y':
                   $out.=substr($date['year'],2,2);
            break;
            //time
            case'a':
                   $out.=($date['hours']<12)?'ق.ظ':'ب.ظ';
            break;
            case'A':
                   $out.=($date['hours']<12)?'قبل از ظهر':'بعد از ظهر';
            break;
            case'B':
                   $out.=(int)(1+($date['mon']/3));
            break;
            case'g':
                   $out.=($date['hours']>12)?$date['hours']-12:$date['hours'];
            break;
            case'G':
                   $out.=$date['hours'];
            break;
            case'h':
                   $hour=($date['hours']>12)?$date['hours']-12:$date['hours'];
                   $out.=($hour<10)?"0$hour":$hour;
            break;
            case'H':
                   $out.=($date['hours']<10)?'0'.$date['hours']:$date['hours'];
            break;
            case'i':
                   $out.=($date['minutes']<10)?'0'.$date['minutes']:$date['minutes'];
            break;
            case's':
                   $out.=($date['seconds']<10)?'0'.$date['seconds']:$date['seconds'];
            break;
            //full date time
            case'c':
                   $out=$date['year'].'/'.$date['mon'].'/'.$date['mday'].' '.$date['hours'].':'.(($date['minutes']<10)?'0'.$date['minutes']:$date['minutes']).':'.(($date['seconds']<10)?'0'.$date['seconds']:$date['seconds']);//2004-02-12T15:19:21+00:00
            break;
            case'r':
                   $out=$this->persian_day_names[$date['wday']].','.$date['mday'].' '.$this->persian_month_names[$date['mon']].' '.$date['year'].' '.$date['hours'].':'.(($date['minutes']<10)?'0'.$date['minutes']:$date['minutes']).':'.(($date['seconds']<10)?'0'.$date['seconds']:$date['seconds']) ;//Thu, 21 Dec 2000 16:01:07
            break;
            case'U':
                   $out=$timestamp;
            break;
            //others
            case'e':case'I':case'i':case'O':case'P':case'T':case'Z':case'u':break;
            default:$out.=$format[$i];
        }
      }
      if($lang=='per')
      return self::trim_number($out);
      else
      return $out; 
    }
    
    public function gregurian_date($format,$persiandate)
    {
        preg_match_all('!\d+!', $persiandate, $matches);
        $matches=$matches[0];
        list($year,$mon,$day)=self::persian_to_gregorian($matches[0],$matches[1],$matches[2]);
        return date($format,mktime($matches[3],$matches[4],$matches[5],$mon,$day,$year,-1));
    }
}


/*
* parsidate function
*/
function parsidate($input,$datetime='now',$lang='per')
{
   	$bndate =bn_parsidate::getInstance();
	$bndate = $bndate->persian_date($input,$datetime,$lang);
    return $bndate;
}

function gregdate($input,$datetime)
{
   	$bndate =bn_parsidate::getInstance();
	$bndate = $bndate->gregurian_date($input,$datetime);
    return $bndate;    
}
?>