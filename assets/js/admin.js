jQuery(document).ready(function(){

    jQuery('.wp-editor-wrap.html-active #content').css("direction","ltr");

function IsLeapYear( year ){
    if(((year%4)==0&&(year%100)!=0)||((year%400)==0)&&(year%100)==0)return true;else return false;
}
function persian_to_gregorian(jy,jm,jd){var gd;j_days_sum_month=[0,0,31,62,93,124,155,186,216,246,276,306,336,365];g_days_in_month=[31,28,31,30,31,30,31,31,30,31,30,31];g_days_leap_month=[31,29,31,30,31,30,31,31,30,31,30,31];gd=j_days_sum_month[parseInt(jm)]+parseInt(jd);gy=parseInt(jy)+621;if(gd>286)gy++;if(IsLeapYear(gy-1)&&286<gd)gd--;if(gd>286)gd-=286;else gd+=79;if(IsLeapYear(gy)){for(gm=0;gd>g_days_leap_month[gm];gm++){gd-=g_days_leap_month[gm];}} else{for(gm=0;gd>g_days_in_month[gm];gm++)gd-=g_days_in_month[gm];}gm++;if(gm<10)gm='0'+gm;return [gy,gm,gd];}
function gregorian_to_persian(gy, gm, gd){j_days_in_month = [31,31,31,31,31,31,30,30,30,30,30,29];g_days_sum_month = [0,0,31,59,90,120,151,181,212,243,273,304,334,365];dayofyear = g_days_sum_month[parseInt(gm)] + parseInt(gd);leab=IsLeapYear(gy);leap=IsLeapYear(gy-1);if (dayofyear > 79){jd =(leab?dayofyear - 78:dayofyear - 79);jy = gy - 621;for (i = 0; jd > j_days_in_month[i]; i++)jd -= j_days_in_month[i];}else{jd =((leap || (leab && gm>2))?287 + dayofyear:286 + dayofyear);jy = gy - 622;if (leap == 0 && jd == 366)return [jy,12,30];for (i = 0; jd > j_days_in_month[i]; i++)jd -= j_days_in_month[i];}jm = ++i;jm=(jm<10?jm='0'+jm:jm);return [jy,jm,jd];}

var persian_month_names =['','فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];


/*
* Edit inline
*/
function create_stampdiv(year,mon,day,hour,minu)
{
    div='<div class="timestamp-wrap persian">'+
    '<select id="mma" name="mma">';
    for(var i=1;i<13;i++)
    {
        if(i==mon)
        div+='<option value="'+i+'" selected="selected">'+persian_month_names[i]+'</option>';
        else
        div+='<option value="'+i+'">'+persian_month_names[i]+'</option>';
    }
	div+='</select>'+
    '<input type="text" id="jja" name="jja" value="'+day+'" size="2" maxlength="2" autocomplete="off" />,'+
    '<input type="text" id="aaa" name="aaa" value="'+year+'" size="4" maxlength="4" autocomplete="off" /> @'+
    '<input type="text" id="hha" name="hha" value="'+hour+'" size="2" maxlength="2" autocomplete="off" /> : '+
    '<input type="text" id="mna" name="mna" value="'+minu+'" size="2" maxlength="2" autocomplete="off" />'+
    '</div>';
    return div;
}

jQuery('a.edit-timestamp').on('click',function()
{
    jQuery('.persian').remove();
    var date = gregorian_to_persian(jQuery('#aa').val(),jQuery('#mm').val(),jQuery('#jj').val());
    var div  = create_stampdiv(date[0],date[1],date[2],jQuery('#hh').val(),jQuery('#mn').val());
    jQuery('#timestampdiv').prepend(div);
    jQuery('#timestampdiv .timestamp-wrap:eq(1)').hide();
});

jQuery('#the-list').on('click','.editinline',function()
{
    var tr=jQuery(this).closest('td');
    var year=tr.find('.aa').html();
    if(year>1400)
    {
        var month=tr.find('.mm').html();
        var day=tr.find('.jj').html();
        var hour=tr.find('.hh').html();
        var minu=tr.find('.mn').html();
        var date=gregorian_to_persian(year,month,day);
        jQuery('.persian').remove();
        jQuery('.inline-edit-date').prepend(create_stampdiv(date[0],date[1],date[2],hour,minu));
        jQuery('.inline-edit-date div:eq(1)').hide();
    }
});


jQuery('#timestampdiv,.inline-edit-date').on('keyup','#hha',function(e)
{
    jQuery('input[name=hh]').val(jQuery(this).val());
});

jQuery('#timestampdiv,.inline-edit-date').on('keyup','#mna',function(e)
{
    jQuery('input[name=mn]').val(jQuery(this).val());
});

jQuery('#timestampdiv,.inline-edit-date').on('keyup','#aaa , #jja',function(e)
{
    year = jQuery('#aaa').val();
    mon  = jQuery('#mma').val();
    day  = jQuery('#jja').val();
    date=persian_to_gregorian(year,mon,day);
    jQuery('input[name=aa]').val(date[0]);
    jQuery('select[name=mm]').val(date[1]);
    jQuery('input[name=jj]').val(date[2]);
});

jQuery('#timestampdiv,.inline-edit-date').on('change','#mma',function()
{
    year = jQuery('#aaa').val();
    mon  = jQuery('#mma').val();
    day  = jQuery('#jja').val();
    date=persian_to_gregorian(year,mon,day);
    jQuery('input[name=aa]').val(date[0]);
    jQuery('select[name=mm]').val(date[1]);
    jQuery('input[name=jj]').val(date[2]);
});


/*
* Filter on post screen dates
*/
jQuery('select[name=m]').hide()
var timer;

function change_date()
{
    old=jQuery('#timestamp b').text();
    var info=jQuery('#mma option:selected').text()+' '+jQuery('#jja').val()+', '+jQuery('#aaa').val()+'@ '+jQuery('#hha').val()+':'+jQuery('#mna').val();
    info=info.replace(/\d+/g, function(digit) {
        var ret = '';
        for (var i = 0, len = digit.length; i < len; i++)
        {
            ret += String.fromCharCode(digit.charCodeAt(i) + 1728);
        }
        return ret;
    });
    if(old!=info)
    {
        jQuery('#timestamp b').html(info);
        clearInterval(timer);
    }
}

jQuery('#timestampdiv').on('keypress',function(e)
{
    if(e.which==13)
    timer=setInterval(function(){change_date();},50);
});

jQuery('.save-timestamp , .cancel-timestamp , #publish').on('click',function()
{
    if(jQuery('#aaa').length)
    timer=setInterval(function(){change_date();},50);
});
});
