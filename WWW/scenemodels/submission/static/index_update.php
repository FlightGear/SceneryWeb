<?php
    require_once ('../../inc/functions.inc.php');
    $page_title = "Automated Models Submission Form";
    include '../../inc/header.php';
?>
<script type="text/javascript" src="/inc/js/check_form.js"></script>
<script type="text/javascript" src="http://code.jquery.com/jquery.min.js"></script>
<script type="text/javascript" src="/inc/js/jquery.multifile.js"></script>
<script type="text/javascript">
/*<![CDATA[*/
function validateForm()
{
    var form = document.getElementById("positions");

    if (!checkComment(form["comment"])
        return false;

}
/*]]>*/
</script>

<h1>Models Automated Update Form</h1>

<p class="center">
<b>Foreword:</b> This automated form goal is to ease the update of static and shared 3D models into FG Scenery database.
There are currently <?php $models = count_models(); echo number_format($models, '0', '', ' '); ?> models in <a href="http://<?php echo $_SERVER['SERVER_NAME'];?>/models.php">our database</a>.
Help us to make it more!
Please, read the following:
<ul class="warning">
    <li>You HAVE TO add at least 2 files: an AC3D file of your model and a JPEG thumbnail (PNG texture(s) and XML file if any). Files have to share a common name, for instance Rochester_Castle_Keep.ac, Rochester_Castle_Keep.xml, Rochester_Castle_Keep_thumbnail.jpg (thumbnail written as is)). If you have multiple textures, name them Rochester_Castle_Keep1.png, with an increasing figure.</li>
    <li>JPEG has to be a 320*240 exciting thumbnail.</li>
    <li>PNG size must be a power of 2 in width and height.</li>
    <li>XML file must start with a classic XML header, such as: &lt;?xml version="1.0" encoding="UTF-8" ?&gt;. See <a href="TheNameOfYourACFile.xml">here</a> for a quick example. Only send XML if necessary to the model, as it has a performance impact.</li>
    <li>Please also read <a href="http://<?php echo $_SERVER['SERVER_NAME'];?>/contribute.php">this page</a> in order to understand what recommandations this script is looking for.</li>
    <li>Don't forget to feed the Captcha, it's a mandatory item as well. Don't know what a Captcha is or what its goal is? Learn more <a href="http://en.wikipedia.org/wiki/Captcha">here</a></li>
    <li>In this form, you need to add ALL files of the model, even if all are not updated.</li>
    <li>I think that's all, folks ;-) Ah yes, be patient, there are human beings with real life constraints behind, and don't feel blamed if your models are rejected, but try to understand why.</li>
</ul>
</p>
    <form id="positions" method="post" action="check_update_static.php" enctype="multipart/form-data" onsubmit="return validateForm();">
    <table>
        <tr>
            <td>
                <label for="mo_shared">Model's family<span>This is the family name of the model you want to add. If your 3D model is going to be shared, use the proper family. If it's going to be a static one, then choose the static family.</span></label>
            </td>
            <td colspan="2">
                <select name="mo_shared" id="mo_shared">
                <?php
                $resource_r = connect_sphere_r();
                $result = pg_query("SELECT mg_id, mg_name FROM fgs_modelgroups ORDER BY mg_name;");

                while ($row = pg_fetch_assoc($result)) {
                    $name = preg_replace('/ /',"&nbsp;", $row["mg_name"]);
                    // Selecting static family by default
                    if(($row["mg_id"]) == 0)
                        echo "<option value=\"".$row["mg_id"]."\" selected=\"selected\">".$name."</option>\n";
                    else echo "<option value=\"".$row["mg_id"]."\">".$name."</option>\n";
                }
                pg_close ($resource_r);
                ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>
                <label for="mo_thumbfile">Corresponding 320x240 JPEG thumbnail<span>This is a nice picture representing your model in FG the best way (eg: tower_thumbnail.jpeg).</span></label>
            </td>
            <td>
                <input type="file" name="mo_thumbfile" id="mo_thumbfile" class="multi" maxlength="1" accept="image/jpg, image/jpeg" />
            </td>
        </tr>
        <tr>
            <td>
                <label for="ac3d_file">Corresponding AC3D File<span>This is the AC3D file of your model (eg: tower.ac).</span></label>
            </td>
            <td>
                <input type="file" name="ac3d_file" id="ac3d_file" class="multi" maxlength="1" accept="ac"/>
            </td>
        </tr>
        <tr>
            <td>
                <label for="xml_file">Corresponding XML File<span>This is the XML file of your model (eg: tower.xml)</span></label>
            </td>
            <td>
                <input type="file" name="xml_file" id="xml_file" class="multi" maxlength="1" accept="text/xml" />
            </td>
        </tr>
        <tr>
            <td>
                <label for="png_files">Corresponding PNG Texture Files<span>This (Those) is (are) the PNG texture(s) file(s) of your model. Has to be a factor of 2 in height and length.</span></label>
            </td>
            <td>
                <input type="file" name="png_file[]" id="png_files" class="multi" maxlength="12" accept="image/png" />
            </td>
        </tr>
        <tr>
            <td colspan="2" class="submit">
                <input type="checkbox" name="gpl"/> I accept to release all my contribution under <a href="http://www.gnu.org/licenses/gpl-2.0.html">GNU GENERAL PUBLIC LICENSE Version 2, June 1991.</a><br/>
                <?php
                // Google Captcha stuff
                require_once('../../inc/captcha/recaptchalib.php');
                $publickey = "6Len6skSAAAAAB1mCVkP3H8sfqqDiWbgjxOmYm_4";
                echo recaptcha_get_html($publickey);
                ?>
                <br />
                <input type="hidden" name="MAX_FILE_SITE" value="2000000" />
                <input name="IPAddr" type="hidden" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>" />
                <input type="submit" value="Submit model" />
            </td>
        </tr>
    </table>
    </form>

<script type="text/javascript">
$(document).ready(function(){
  // Checks if the GPL checkbox is checked
  $('input[type="submit"]').attr('disabled','disabled');

  $('input[name="gpl"]').change(function(){
    if($('input[name="gpl"]').is(':checked')){
      $('input[type="submit"]').removeAttr('disabled');
    }else{
      $('input[type="submit"]').attr('disabled','disabled');
    }
  });
});
</script>
<?php include '../../inc/footer.php'; ?>
