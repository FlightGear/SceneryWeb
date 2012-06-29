<?php
    require_once ('../../inc/functions.inc.php');
    $page_title = "Automated Models Submission Form";
    include '../../inc/header.php';
?>
    <script src="../ajax/check_form.js" type="text/javascript"></script>
    <script type="text/javascript" src="http://code.jquery.com/jquery.min.js"></script>
    <script type="text/javascript" src="../../inc/js/jquery.multifile.js"></script>
    <noscript>
        <meta http-equiv="refresh" content="0; URL=../../inc/js/nojs.php"/>
    </noscript>
  <h1>Models Automated Submission Form</h1>
  <p align="center">
  <b>Foreword:</b> This automated form goal is to ease the submission of static models into FG Scenery database.
  There are currently <?php count_models(); ?> models in <a href="http://scenemodels.flightgear.org/models.php">our database</a>.
  Please help us to make it more!

  Please read <a href="http://scenemodels.flightgear.org/contribute.php">this page</a> in order to understand what recommandations this script is looking for.
  Please note that all fields are now mandatory.
  </p>
  <p align="center">
  Note this page is under HEAVY DEVELOPMENT and links to nowhere. Please do NOT use it unless we ask you for. It'll be for a bright future.
  </p>
  <p style="color:red;" align="center">Files <u>must have the same name</u> except for thumbnail file. eg: XXXX_thumbnail.png (thumbnail file), XXXX.ac (AC3D file), XXXX.xml (XML file), XXXX.png (texture file)</p>

    <form name="positions" method="post" action="check_static.php" enctype="multipart/form-data">
    <table>
        <tr>
            <td>
            <span title="This is the family name of the object you want to add. If your 3D model is going to be shared, use the proper family. If it's going to be a static one, then choose the static family."><a style="cursor: help;">Object's family</a></span>
            </td>
            <td colspan="2">
            <select name="mo_shared">
            <?php
            $resource_r = connect_sphere_r();
            $result = pg_query("SELECT mg_id, mg_name FROM fgs_modelgroups ORDER BY mg_name;");

            while ($row = pg_fetch_assoc($result)) {
                $name = preg_replace('/ /',"&nbsp;", $row["mg_name"]);
                echo "<option value=\"".$row["mg_id"]."\">".$name."</option>\n";
            }
            pg_close ($resource_r);
            ?>
            </select>
            </td>
        </tr>
        <tr>
            <td>
            <span title="This is the model path name, ie the name as it's supposed to appear in the .stg file. Please use English language for this path name, unless it's a local specific name. Do NOT use spaces, use - or _ for spaces.">
            <a style="cursor: help; ">Model path name</a>
            </span>
            </td>
            <td>
            <input type="text" name ="mo_path" />
            </td>
        </tr>
        <tr>
            <td>
            <span title="This is the name of the author. If the author does not exist, please ask the scenery mantainers to add it. This name is the author of the true creator of the model, if you just converted a model and were granted to do so, then use the line below.">
            <a style="cursor: help; ">Author</a>
            </span>
            </td>
            <td>
            <select name="mo_author">
            <?php list_authors(); ?>
            </select>
        </td>
        </tr>
        <tr>
            <td>
            <span title="If you did not make the 3D model yourself, but were granted to do so, then you're a contributor. Please enter your name here.">
            <a style="cursor: help; ">Contributor</a>
            </span>
            </td>
            <td>
            <input type="text" name="contributor" maxlength="100" size="40" value="Your name" />
            </td>
        </tr>
        <tr>
            <td>
            <span title="Please leave YOUR VALID email address over here. This will help you be informed of your submission process. EXPERIMENTAL">
            <a style="cursor: help; ">Email address</a>
            </span>
            </td>
            <td>
            <input type="text" name="email" maxlength="100" size="40" value="Your name" onblur="checkEmail(this);" />
            </td>
        </tr>
        <tr>
            <td>
            <span title="This is the two-letter country code where the model is located. If the author does not exist, please ask the scenery mantainers to add it.">
            <a style="cursor: help; ">Country</a>
            </span>
            </td>
            <td>
            <select name="ob_country">
            <?php list_countries(); ?>
            </select>
            </td>
        </tr>
        <tr>
            <td>
            <span title="Please add a short (max 100 letters) name of your model (eg : Cornet antenna radome - Brittany - France">
            <a style="cursor: help">Description</a>
            </span>
            </td>
            <td>
            <input type="text" name="mo_name" maxlength="100" size="40" value="Tell us more about your model." />
            </td>
        </tr>
        <tr>
            <td>
            <span title="This is the WGS84 longitude of the model you want to add. Has to be between -180.000000 and +180.000000.">
            <a style="cursor: help; ">Longitude</a>
            </span>
            </td>
            <td>
            <input type="text" name="longitude" maxlength="11" value="" onblur="checkNumeric(this,-180,180,'.');" />
            </td>
        </tr>
        <tr>
            <td>
            <span title="This is the WGS84 latitude of the model you want to add. Has to be between -90.000000 and +90.000000.">
            <a style="cursor: help; ">Latitude</a>
            </span>
            </td>
            <td>
            <input type="text" name="latitude" maxlength="10" value="" onBlur="checkNumeric(this,-90,90,'.');" />
            </td>
        </tr>
        <tr>
            <td>
            <span title="This is the ground elevation (in meters) of the position where the model you want to add is located. Warning: if your model is sunk into the ground, use the elevation offset field below.">
            <a style="cursor: help; ">Elevation</a>
            </span>
            </td>
            <td>
            <input type="text" name="gndelev" maxlength="10" value="" onBlur="checkNumeric(this,-10000,10000,'.');" />
            </td>
        </tr>
        <tr>
            <td>
            <span title="This is the offset (in meters) between your model 'zero' and the elevation at the considered place (ie if it is sunk into the ground). Let 0 if there is no offset.">
            <a style="cursor: help; ">Elevation offset</a>
            </span>
            </td>
            <td>
            <input type="text" name="offset" maxlength="10" value="0" onBlur="checkNumeric(this,-10000,10000,'.');" />
            </td>
        </tr>
        <tr>
            <td>
            <span title="The orientation (in degrees) of the object you want to add - as it appears in the .stg file (this is NOT the true heading). Let 0 if there is no specific orientation.">
            <a style="cursor: help; ">Orientation</a>
            </span>
            </td>
            <td>
            <input type="text" name="heading" maxlength="7" value="" onBlur="checkNumeric(this,0,359.999,'.');" />
            </td>
        </tr>
        <tr>
            <td>
            <span title="Please add a short (max 100 letters) statement why you are inserting this data. This will help the maintainers understand what you are doing. eg: Hi, this is a new telecommunications model in Brittany, please commit.">
            <a style="cursor: help">Comment</a>
            </span>
            </td>
            <td>
            <input type="text" name="comment" maxlength="100" size="40" value="" onblur="checkComment(this);" />
            </td>
        </tr>
        <tr>
            <td>
            <span title="This is a nice picture representing your model in FG the best way (eg: tower_thumbnail.jpeg).">
            <a style="cursor: help; ">Corresponding 320x240 JPEG thumbnail</a>
            </span>
            </td>
            <td>
            <input type="file" name="mo_thumbfile" class="multi" maxlength="1" accept="jpg|jpeg" />
            </td>
        </tr>
        <tr>
            <td>
            <span title="This is the AC3D file of your model (eg: tower.ac).">
            <a style="cursor: help; ">Corresponding AC3D File</a>
            </span>
            </td>
            <td>
            <input type="file" name="ac3d_file" class="multi" maxlength="1" accept="ac" />
            </td>
        </tr>
        <tr>
            <td>
            <span title="This is the XML file of your model (eg: tower.xml).">
            <a style="cursor: help; ">Corresponding XML File</a>
            </span>
            </td>
            <td>
            <input type="file" name="xml_file" class="multi" maxlength="1" accept="xml" />
            </td>
        </tr>
        <tr>
            <td>
            <span title="This(Those) is(are) the PNG texture(s) file(s) of your model. Has to be a factor of 2 in height and length.">
            <a style="cursor: help; ">Corresponding PNG Files</a>
            </span>
            </td>
            <td>
            <input type="file" name="png_file[]" class="multi" maxlength="12" accept="png" />
            </td>
        </tr>
        <tr>
            <td colspan="2">
            <center>
            <input type="checkbox" name="gpl"/> I accept to release all my contribution under <a href="http://www.gnu.org/licenses/gpl-2.0.html">GNU GENERAL PUBLIC LICENSE Version 2, June 1991.</a><br/>
            <?php
            // Google Captcha stuff
            require_once('../../inc/captcha/recaptchalib.php');
            $publickey = "6Len6skSAAAAAB1mCVkP3H8sfqqDiWbgjxOmYm_4";
            //echo recaptcha_get_html($publickey);
            ?>
            <br />
            <input type="hidden" name="MAX_FILE_SITE" value="2000000" />
            <input name="IPAddr" type="hidden" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>" />
            <input type="submit" value="Submit model" />
            </center>
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
