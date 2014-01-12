<?php

function php_date_default_timezone_set($GMT) 
{ 
    $timezones = array( 
    '-12:00'=>'Pacific/Kwajalein', 
    '-11:00'=>'Pacific/Samoa', 
    '-10:00'=>'Pacific/Honolulu', 
    '-09:00'=>'America/Juneau', 
    '-08:00'=>'America/Los_Angeles', 
    '-07:00'=>'America/Denver', 
    '-06:00'=>'America/Mexico_City', 
    '-05:00'=>'America/New_York', 
    '-04:00'=>'America/Caracas', 
    '-03:30'=>'America/St_Johns', 
    '-03:00'=>'America/Argentina/Buenos_Aires', 
    '-02:00'=>'Atlantic/Azores',
    '-01:00'=>'Atlantic/Azores', 
    '+00:00'=>'Europe/London', 
    '+01:00'=>'Europe/Paris', 
    '+02:00'=>'Europe/Helsinki',
    '+03:00'=>'Europe/Moscow',
    '+03:30'=>'Asia/Tehran',
    '+04:00'=>'Asia/Baku',
    '+04:30'=>'Asia/Kabul',
    '+05:00'=>'Asia/Karachi',
    '+05:30'=>'Asia/Calcutta',
    '+06:00'=>'Asia/Colombo',
    '+07:00'=>'Asia/Bangkok',
    '+08:00'=>'Asia/Singapore',
    '+09:00'=>'Asia/Tokyo',
    '+09:00'=>'Australia/Darwin', 
    '+10:00'=>'Pacific/Guam', 
    '+11:00'=>'Asia/Magadan', 
    '+12:00'=>'Asia/Kamchatka');
    return  $timezones[$GMT];
}

function per_number($number) 
{
    return str_replace(range(0,9),array('۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'),$number);
}

function eng_number($number) 
{
    return str_replace(array('۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'),range(0,9),$number);
}

function persian_number($content) 
{
    return(isset($content[1])?str_replace(range(0,9),array('۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'),$content[1]):$content[0]);
}

function fixnumber($content) 
{
	return preg_replace_callback( '/(?:&#\d{2,4};)|(?:[0]?[a-z][\x20-\x3B=\x3F-\x7F]*)|(\d+[\.\d]*)|<\s*[^>]+>/i','persian_number',$content);
}

function fixarabic($content)
{
    //return str_replace(array('ي','ك','٤','٥','٦','ة','ئ'),array('ی','ک','۴','۵','۶','ه','ی'),$content);
    return str_replace(array('ي','ك','٤','٥','٦','ة'),array('ی','ک','۴','۵','۶','ه'),$content);
}

function detect_rss()
{
    if(is_feed())
    return true;
    $path = $_SERVER['REQUEST_URI'];
    $prev = array('xsl','xml','gz');
    $ext  = pathinfo($path,PATHINFO_EXTENSION);
    return in_array($ext,$prev);
}
?>