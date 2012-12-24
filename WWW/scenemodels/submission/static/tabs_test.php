<?php
    require_once ('../../inc/functions.inc.php');
    $page_title = "Automated Models Submission Form";
    include '../../inc/header.php';
?>
<script type="text/javascript" src="/inc/js/update_objects.js"></script>
<script type="text/javascript" src="/inc/js/check_form.js"></script>
<script type="text/javascript" src="http://code.jquery.com/jquery.min.js"></script>
<script type="text/javascript" src="/inc/js/jquery.multifile.js"></script>
<script type="text/javascript">
/*<![CDATA[*/
function validateForm()
{
    var form = document.getElementById("positions");

    if (!checkNumeric(form["longitude"],-180,180) ||
        !checkNumeric(form["latitude"],-90,90) ||
        !checkComment(form["mo_name"]) ||
        !checkNumeric(form["gndelev"],-10000,10000) ||
        !checkNumeric(form["offset"],-10000,10000) ||
        !checkNumeric(form["heading"],0,359.999) ||
        !checkComment(form["comment"]))
        return false;
}

function  validateTabs()
{
    var form = document.getElementById("positions");
    $( "#tabs" ).tabs({ disabled: false });
    
    // Tab 1
    if (form["mo_name"].value == "" ||
        form["ac3d_file"].value == "" ||
        (form["mo_thumbfile"].value.indexOf("_thumbnail.jpg", value.length - 14) === -1 && 
        form["mo_thumbfile"].value.indexOf("_thumbnail.jpeg", value.length - 14) === -1)) {
        $( "#tabs" ).tabs({ disabled: [1, 2] });
        return false;
    }
    // Tab 2
    if (form["longitude"].value == "" ||
        form["latitude"].value == "" ||
        form["gndelev"].value == "" ||
        form["offset"].value == "" ||
        form["heading"].value == "") {
        $( "#tabs" ).tabs({ disabled: [2] });
        return false;
    }
}
$(function() {
    $( "#tabs" ).tabs({ disabled: [1, 2] });

    $('#mo_thumbfile').MultiFile({
        afterFileRemove: function(element, value, master_element){
            validateTabs();
        },
        afterFileAppend: function(element, value, master_element){
            if (value.indexOf("_thumbnail.jpg", value.length - 14) === -1 && value.indexOf("_thumbnail.jpeg", value.length - 14) === -1) {
                alert("The thumbnail name must end with _thumbnail");
            }
            validateTabs();
        }
    });
});
/*]]>*/
</script>
<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.17/themes/base/jquery-ui.css" />
<style type="text/css">
.ui-widget {
    font-size: 1em;
}
.ui-widget-content {
    background: none;
    border: none;
}
.ui-widget-header {
    background: none;
    border: none;
}
.ui-tabs, .ui-tabs-nav {
    padding: 0px !important;
}
.ui-tabs-nav li a {
    background-color: rgb(224, 224, 255);
}
.ui-tabs-panel, .ui-state-active a {
    background-color: rgb(224, 255, 224) !important;
}
.ui-tabs-panel {
    padding: 0px !important;
}
form {
    margin-top: -14px;
}
</style>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.min.js"></script>

<h1>Submit a model</h1>

<p>
    This automated form goal is to ease the submission of static and shared 3D models into the FlightGear scenery database.
    There are currently <?php $models = count_models(); echo number_format($models, '0', '', ' '); ?> unique models in <a href="http://<?php echo $_SERVER['SERVER_NAME'];?>/models.php">our database</a>. Help us to make it more!
</p>
<p>
    Hover your mouse over the various field titles (left column) to view some information about what to do with that particular field. Please read <a href="http://<?php echo $_SERVER['SERVER_NAME'];?>/contribute.php">this page</a> for a better understanding of the various requirements.
</p>

<div id="tabs">
    <ul>
        <li><a href="#tabs-1">1: Model</a></li>
        <li><a href="#tabs-2">2: Location</a></li>
        <li><a href="#tabs-3">3: Submit</a></li>
    </ul>
    
    <form id="positions" method="post" action="check_static.php" enctype="multipart/form-data" onsubmit="return validateForm();">
        <div id="tabs-1">
            <ul>
                <li>Files have to share a common name, for instance: modelname.ac, modelname.xml, modelname.png and modelname_thumbnail.jpg (the _thumbnail extension is required).</li>
                <li>Please do not group separate buildings into one AC file. The terrain elevation is subject to updates, so this could lead to inaccuracies.</li>
                <li>Do not add trees or flat surfaces (such as soccer fields) into your AC file.</li>
                <li>PNG resolution must be a power of 2 in width and height.</li>
                <li>If you have multiple textures, name them modelname1.png, modelname2.png etc.</li>
                <li>XML file must start with a classic XML header, such as: &lt;?xml version="1.0" encoding="UTF-8" ?&gt;. See <a href="TheNameOfYourACFile.xml">here</a> for a quick example. Only include XML if necessary for the model.</li>
                <li>The thumbnail must be in JPEG and 320*240 resolution. Filename must end on _thumbnail.</li>
            </ul>
            <table style="width: auto; margin-left: auto; margin-right: auto;">
                <tr>
                    <td style="width: 200px;">
                        <span title="This is the family name of the model you want to add. If your 3D model is going to be shared, use the proper family. If it's going to be a static one, then choose the static family."><label for="mo_shared">Model's family<em>*</em></label></span>
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
                        <span title="Please add a short (max 100 letters) name of your model (eg : Cornet antenna radome - Brittany - France).">
                            <label for="mo_name">Model name<em>*</em></label>
                        </span>
                    </td>
                    <td>
                        <input type="text" name="mo_name" id="mo_name" maxlength="100" size="40" onchange="checkComment(this);validateTabs();"/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span title="Please add a short statement giving more details on this data. eg: The Cite des Telecoms, colocated with the cornet radome, is a telecommunications museum.">
                            <label for="comment">Model description</label>
                        </span>
                    </td>
                    <td>
                        <input type="text" name="comment" id="comment" maxlength="500" size="40" value="" onchange="checkComment(this);validateTabs();" />
                    </td>
                </tr>
                <tr>
                    <td>
                        <span title="This is the AC3D file of your model (eg: tower.ac).">
                            <label for="ac3d_file">AC3D file<em>*</em></label>
                        </span>
                    </td>
                    <td>
                        <input type="file" name="ac3d_file" id="ac3d_file" class="multi" maxlength="1" accept="ac" onchange="validateTabs();" />
                    </td>
                </tr>
                <tr>
                    <td>
                        <span title="This is the XML file of your model (eg: tower.xml).">
                            <label for="xml_file">XML file</label>
                        </span>
                    </td>
                    <td>
                        <input type="file" name="xml_file" id="xml_file" class="multi" maxlength="1" accept="text/xml" />
                    </td>
                </tr>
                <tr>
                    <td>
                        <span title="This (Those) is (are) the PNG texture(s) file(s) of your model. Has to be a power of 2 in width and height.">
                            <label for="png_files">PNG texture file(s)</label>
                        </span>
                    </td>
                    <td>
                        <input type="file" name="png_file[]" id="png_files" class="multi" maxlength="12" accept="image/png" />
                    </td>
                </tr>
                <tr>
                    <td>
                        <span title="This is a nice picture representing your model in FG the best way (eg: tower_thumbnail.jpeg).">
                            <label for="mo_thumbfile">320x240 JPEG thumbnail<em>*</em></label>
                        </span>
                    </td>
                    <td>
                        <input type="file" name="mo_thumbfile" id="mo_thumbfile" maxlength="1" accept="image/jpg, image/jpeg" onchange="validateTabs();" />
                    </td>
                </tr>
            </table>
        </div>
        <div id="tabs-2">
            <ul>
                <li>Please locate your model, even when you are adding a shared model.</li>
                <li>The country is the one where the model is located. After entering longitude and latitude, we will try to propose a country. Please check if it is correct.</li>
                <li>For the elevation, use the terrain shipped with FlightGear/Terrasync, else the model may be sunk or floating. Alternatively enter -9999 to place the object at ground level (this is then automatically calculated from TerraSync terrain).</li>
            </ul>
            <table style="width: auto; margin-left: auto; margin-right: auto;">
                <tr>
                    <td style="width: 200px;">
                        <span title="This is the WGS84 longitude of the object you want to add. Has to be between -180.000000 and +180.000000 and not O.">
                            <label for="longitude">Longitude<em>*</em></label>
                        </span>
                    </td>
                    <td>
                        <input type="text" name="longitude" id="longitude" maxlength="11" value="" onchange="checkNumeric(this,-180,180);update_country();update_map('longitude','latitude');validateTabs();" />
                    </td>
                    <td rowspan="6">
                        <object id="map" data="" type="text/html" width="300" height="225"></object>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span title="This is the WGS84 latitude of the object you want to add. Has to be between -90.000000 and +90.000000 and not 0.">
                            <label for="latitude">Latitude<em>*</em></label>
                        </span>
                    </td>
                    <td>
                        <input type="text" name="latitude" id="latitude" maxlength="10" value="" onchange="checkNumeric(this,-90,90);update_country();update_map('longitude','latitude');validateTabs();" />
                    </td>
                </tr>
                <tr>
                    <td>
                        <span title="This is the country code where the model is located (for static models only).">
                            <label for="ob_country">Country</label>
                        </span>
                    </td>
                    <td>
                        <select name="ob_country" id="ob_country">
                            <?php list_countries(); ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span title="This is the ground elevation (in meters) of the position where the object you want to add is located. Warning: if your object is sunk into the ground, use the elevation offset field below.">
                            <label for="gndelev">Elevation<em>*</em></label>
                        </span>
                    </td>
                    <td>
                        <input type="text" name="gndelev" id="gndelev" maxlength="10" value="" onchange="checkNumeric(this,-10000,10000);validateTabs();" />
                    </td>
                </tr>
                <tr>
                    <td>
                        <span title="This is the offset (in meters) between your object 'zero' and the elevation at the considered place (ie if it is sunk into the ground). Let 0 if there is no offset.">
                            <label for="offset">Elevation offset</label> (see <a href="../../contribute.php#offset">here</a> for more help)
                        </span>
                    </td>
                    <td>
                        <input type="text" name="offset" id="offset" maxlength="10" value="0" onchange="checkNumeric(this,-10000,10000);validateTabs();" />
                    </td>
                </tr>
                <tr>
                    <td>
                        <span title="The orientation (in degrees) of the object you want to add - as it appears in the .stg file (this is NOT the true heading). Let 0 if there is no specific orientation.">
                            <label for="heading">Orientation<em>*</em></label>
                        </span>
                    </td>
                    <td>
                        <input type="text" name="heading" id="heading" maxlength="7" value="" onchange="checkNumeric(this,0,359.999);validateTabs();" />
                    </td>
                </tr>
            </table>
        </div>
        <div id="tabs-3">
            <ul>
                <li>Choose the author for the model. Please contact us if you're not listed here. If you are building a new model based on another one, put your name here, and the original author's one into the "Model description" field.</li>
                <li>Don't forget to feed the Captcha, it's a mandatory item as well. Don't know what a Captcha is or what its goal is? Learn more <a href="http://en.wikipedia.org/wiki/Captcha">here</a></li>
                <li>Be patient, there are human beings with real life constraints behind, and don't feel blamed if your models are rejected, but try to understand why.</li>
            </ul>
            <table style="width: auto; margin-left: auto; margin-right: auto;">
                <tr>
                    <td>
                        <span title="This is the name of the author. If the author does not exist, please ask the scenery maintainers to add it. This name is the author of the true creator of the model, if you just converted a model and were granted to do so, then also use the line below.">
                            <label for="mo_author">Author<em>*</em></label>
                        </span>
                    </td>
                    <td>
                        <select name="mo_author" id="mo_author">
                            <?php list_authors(); ?>
                        </select>
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
        </div>
    </form>
</div>

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
