<?php
/**
 * @author lord_viper
 * @copyright 2013
 */
function parsi_plugin_page()
{
    $val=get_option('parsidate_option',array());

    ?>
<div class="wrap">
    <h2>تنظیمات پارسی</h2>
    <?php
    echo(isset($_POST['save'])?'<div id="message" class="updated"><p>تنظیمات با موفقیت ذخیره شد</p></div>':'');
    ?>
    <form method="post">
    <table class="form-table">
    	<tr valign="top">
    	    <th scope="row"><label for="">نمایش تاریخ پارسی</label></th>
    	    <td>
    	        <fieldset>
    	            <label for="sep_fixdate_yes"><input type="radio" name="sep_fixdate" id="sep_fixdate_yes" value="بلی" <?php echo (empty($val['sep_fixdate'])?'checked':checked($val['sep_fixdate'],'بلی',false)); ?>/>بلی</label>
    	            <label for="sep_fixdate_no"><input type="radio" name="sep_fixdate" id="sep_fixdate_no" value="خیر" <?php checked($val['sep_fixdate'],'خیر'); ?>/>خیر</label>
    	            <p class="description">این گزینه تاریخ پارسی(جلالی) را در  بخشهای مدیریت و پوسته وردپرس فعال می کند. پیش فرض تاریخ پارسی(جلالی) فعال است.</p>
    	        </fieldset>
    	    </td>
    	</tr>
    	<tr valign="top">
    	    <th scope="row"><label for="">زبان پارسی</label></th>
    	    <td>
    	        <fieldset>
    	            <label for="sep_persian_yes"><input type="radio" name="sep_persian" id="sep_persian_yes" value="بلی" <?php echo (empty($val['sep_persian'])?'checked':checked($val['sep_persian'],'بلی',false)); ?>/>بلی</label>
    	            <label for="sep_persian_no"><input type="radio" name="sep_persian" id="sep_persian_no" value="خیر" <?php checked($val['sep_persian'],'خیر'); ?>/>خیر</label>
    	            <p class="description">این گزینه زبان سیستم را پارسی و جهت متون را راست چین می کند. پیش فرض این گزینه فعال است.</p>
    	        </fieldset>
    	    </td>
    	</tr>
    	<tr valign="top">
    	    <th scope="row"><label for="">نمایش پارسی اعداد</label></th>
    	    <td>
                <fieldset>
                    <label for="sep_titlenum"><input type="checkbox" name="sep_titlenum" id="sep_titlenum" <?php echo $val['sep_titlenum']; ?>/>عنوان نوشته ها</label><br>
                    <label for="sep_postnum"><input type="checkbox" name="sep_postnum" id="sep_postnum" <?php echo $val['sep_postnum']; ?>/>متن نوشته ها</label><br>
                    <label for="sep_commentnum"><input type="checkbox" name="sep_commentnum" id="sep_commentnum" <?php echo $val['sep_commentnum']; ?>/>متن نظرها</label><br>
                    <label for="sep_commentcnt"><input type="checkbox" name="sep_commentcnt" id="sep_commentcnt" <?php echo $val['sep_commentcnt']; ?>/>تعداد نظرها</label><br>
                    <label for="sep_datesnum"><input type="checkbox" name="sep_datesnum" id="sep_datesnum" <?php echo $val['sep_datesnum']; ?>/>تاریخ ها</label><br>
                    <label for="sep_catnum"><input type="checkbox" name="sep_catnum" id="sep_catnum" <?php echo $val['sep_catnum']; ?>/>دسته ها</label><br>
                    <p class="description">این گزینه بر روی اعداد انگلیسی در بخشهای بالا تاثیر گذاشته و آن را تبدیل به عدد پارسی می کند.</p>
                </fieldset>
    	    </td>
    	</tr>
    	<tr valign="top">
    	    <th scope="row"><label for="">تصحیح عربی به پارسی</label></th>
    	    <td>
    	        <fieldset>
                    <label for="sep_fixarabic_yes"><input type="radio" name="sep_fixarabic" id="sep_fixarabic_yes" value="بلی" <?php echo (empty($val['sep_fixarabic'])?'checked':checked($val['sep_fixarabic'],'بلی',false)); ?>/>بلی</label>
                    <label for="sep_fixarabic_no"><input type="radio" name="sep_fixarabic" id="sep_fixarabic_no" value="خیر" <?php checked($val['sep_fixarabic'],'خیر'); ?>/>خیر</label>
                    <p class="description">این گزینه حروف عربی مانند (ي , ك) را به (ی , ک) در متن نوشته ها و دیدگاه ها برگردان می کند.</p>
                    <p class="description">همچنین گزینه فوق اعداد ٤ / ٥ / ٦ عربی را به ۴ / ۵ / ۶ پارسی برگردان می کند.</p>
                </fieldset>
    	    </td>
    	</tr>
    	<tr valign="top">
    	    <th scope="row"><label for="">تبدیل تاریخ در پیوند یکتا</label></th>
    	    <td>
    	        <fieldset>
    	            <label for="sep_fixurl_yes"><input type="radio" name="sep_fixurl" id="sep_fixurl_yes" value="بلی" <?php echo (empty($val['sep_fixurl'])?'checked':checked($val['sep_fixurl'],'بلی',false)); ?>/>بلی</label>
    	            <label for="sep_fixurl_no"><input type="radio" name="sep_fixurl" id="sep_fixurl_no" value="خیر" <?php checked($val['sep_fixurl'],'خیر'); ?>/>خیر</label>
    	            <p class="description">با فعال کردن گزینه بالا تاریخ در پیوند یکتا به تاریخ شمسی برگردان می شود که شامل حالت های پیش فرض زیر هم می شود:</p>
    	            <label for="">- روز و نام : <code>http://127.0.0.1/38/2013/12/26/نوشته-نمونه/</code></label><br>
    	            <label for="">- ماه و نام : <code>http://127.0.0.1/38/2013/12/نوشته-نمونه/</code></label><br>
    	            <label for="">- ساختار دل‌خواه</label>
    	        </fieldset>
    	    </td>
    	</tr>
    	<tr valign="top">
    	    <th scope="row"><label for="">آخرین های  سیاره</label></th>
    	    <td>
    	        <fieldset>
                    <select name="sep_planet">
                        <option value="0">انتخاب</option>
                        <option value="1"<?php selected($val['sep_planet'],1); ?>>نمایش اخبار وردپرس پارسی</option>
                        <option value="2"<?php selected($val['sep_planet'],2); ?>>نمایش اخبار سایت اصلی وردپرس</option>
                    </select>
                    <p class="description">از طریق گزینه بالا می توانید انتخاب کنید که اخبار در صفحه پیشخوان وردپرس از خوراک سایت <code>وردپرس پارسی</code> فراخوانی شود و یا اینکه از خوراک سایت <code>اصلی وردپرس</code> فراخوانی شود.</p>
    	        </fieldset>
    	    </td>
    	</tr>
    	<tr valign="top">
    	    <th scope="row"><input type="submit" name="wp_parsidate_save" value="ذخیره" class="button-primary"/></th>
    	    <td></td>
    	</tr>
	</table>
	</form>
</div>
<?php
}
?>