<?php
require_once '../../autoload.php';
$modelDaoRO = \dao\DAOFactory::getInstance()->getModelDaoRO();
$objectDaoRO = \dao\DAOFactory::getInstance()->getObjectDaoRO();

$page_title = "Automated Objects Submission Form";
$body_onload = "update_objects();";
include '../../inc/header.php';
?>
<script src="/inc/js/update_objects.js" type ="text/javascript"></script>
<script src="/inc/js/check_form.js" type="text/javascript"></script>
<script type="text/javascript">
/*<![CDATA[*/
function validateForm()
{
    var form = document.getElementById("positions");

    if (!checkStringNotDefault(form["longitude"], "") || !checkNumeric(form["longitude"],-180,180) ||
        !checkStringNotDefault(form["latitude"], "") || !checkNumeric(form["latitude"],-90,90) ||
        !checkNumeric(form['offset'],-999,999) ||
        !checkStringNotDefault(form["heading"], "") || !checkNumeric(form['heading'],0,359.999) ||
        !checkStringNotDefault(form["comment"], "") || !checkComment(form['comment']) ||
        (form['email'].value!=="" && !checkEmail(form['email'])))
        return false;
}

function validateTabs()
{
    var form = document.getElementById("positions");
    $( "#tabs" ).tabs({ disabled: false });

    // Tab 1
    if (form["model_group_id"].value === 0) {
        $( "#tabs" ).tabs({ disabled: [1, 2] });
        return false;
    }
    // Tab 2
    if (form["longitude"].value === "" || !checkNumeric(form["longitude"],-180,180) ||
        form["latitude"].value === "" || !checkNumeric(form["latitude"],-90,90) ||
        form["offset"].value === "" || !checkNumeric(form["offset"],-10000,10000) ||
        form["heading"].value === "" ||  !checkNumeric(form["heading"],0,359.999)) {
        $( "#tabs" ).tabs({ disabled: [2] });
        return false;
    }
}
$(function() {
    $( "#tabs" ).tabs({ disabled: [1, 2] });
});
/*]]>*/
</script>
<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.17/themes/base/jquery-ui.css" type="text/css"/>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.min.js" type="text/javascript"></script>

<h1>Automated Objects Submission Form</h1>

<p>
    This automated form's goal is to ease the submission of objects into the FlightGear Scenery database. There are currently <?php $objects = $objectDaoRO->countObjects(); echo number_format($objects, '0', '', ' ');?> objects in the database. Help us to make it more!<br/>
    Please read <a href="http://<?php echo $_SERVER['SERVER_NAME'];?>/contribute.php">this page</a> in order to understand what recommandations this script is looking for.<br />
    If you need some more help, just place your mouse over the left column (eg "Elevation Offset").
</p>
<p>
    <em style="color: red">*</em> mandatory field
</p>

<div id="tabs">
    <ul>
        <li><a href="#tabs-1">1: Model</a></li>
        <li><a href="#tabs-2">2: Location</a></li>
        <li><a href="#tabs-3">3: Submit</a></li>
    </ul>

    <form id="positions" method="post" action="check_add.php" onsubmit="return validateForm();">
        <div id="tabs-1">
            <table>
                <tr>
                    <td><label for="model_group_id">Object's family<em>*</em><span>This is the family name of the object you want to add.</span></label></td>
                    <td colspan="2">
            <?php
                        // Show all the families other than the static family
                        $modelsGroups = $modelDaoRO->getModelsGroups();

                        // Start the select form
                        echo "<select id=\"model_group_id\" name=\"model_group_id\" onchange=\"update_objects(); validateTabs();\">" .
                             "<option selected=\"selected\" value=\"\">Please select a family</option>" .
                             "<option value=\"\">----</option>";
                        foreach ($modelsGroups as $modelsGroup) {
                            echo "<option value=\"".$modelsGroup->getId()."\">".$modelsGroup->getName()."</option>";
                        }
                        echo "</select>";

            ?>
                    </td>
                </tr>
                <tr>
                    <td><label for="modelId">Model name<em>*</em><span>This is the name of the object you want to add, ie the name as it's supposed to appear in the .stg file.</span></label></td>
                    <td id="form_objects">
                        <!--Now everything is done via the Ajax stuff, and the results inserted here.-->
                    </td>
                </tr>
                <tr>
                    <td>
                        Model thumbnail
                    </td>
                    <td>
                        <img id="form_objects_thumb" src="" alt=""/>
                    </td>
                </tr>
            </table>
        </div>
        <div id="tabs-2">
            <table>
                <tr>
                    <td><label for="longitude">Longitude<em>*</em><span>This is the WGS84 longitude of the object you want to add. Has to be between -180 and 180.</span></label></td>
                    <td>
                        <input type="text" name="longitude" id="longitude" maxlength="13" value="" onkeyup="checkNumeric(form['longitude'],-180,180);update_map('longitude','latitude');validateTabs();" onchange="update_map('longitude','latitude');" />
                    </td>
                    <td rowspan="6" style="width: 300px; height: 225px;">
                        <object id="map" data="http://mapserver.flightgear.org/popmap/?zoom=1&lat=0&lon=0" type="text/html" width="300" height="225"></object>
                    </td>
                </tr>
                <tr>
                    <td><label for="latitude">Latitude<em>*</em><span>This is the WGS84 latitude of the object you want to add. Has to be between -90 and 90.</span></label></td>
                    <td>
                        <input type="text" name="latitude" id="latitude" maxlength="13" value="" onkeyup="checkNumeric(form['latitude'],-90,90);update_country();validateTabs();" onchange="update_map('longitude','latitude');" />
                    </td>
                </tr>
                <tr>
                    <td><label for="ob_country">Country<em>*</em><span>This is the country where the model is located.</span></label></td>
                    <td>
                        <select name="ob_country" id="ob_country">
                            <?php
                                $countries = $objectDaoRO->getCountries();
                                
                                foreach($countries as $country) {
                                    echo "<option value=\"".$country->getCode()."\">".$country->getName()."</option>";
                                }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="offset">Elevation offset<em>*</em><span>This is the vertical offset (in meters) between your model 'zero' (usually the bottom) and the terrain elevation at the specified coordinates. Use negative numbers to sink it into the ground, positive numbers to make it float, or 0 if there's no offset.</span></label> (see <a href="../../contribute.php#offset">here</a> for more help)
                    </td>
                    <td>
                        <input type="text" name="offset" id="offset" maxlength="10" value="0" onkeyup="checkNumeric(form['offset'],-10000,10000);validateTabs();" />
                    </td>
                </tr>
                <tr>
                    <td><label for="heading">Orientation<em>*</em><span>The orientation (in degrees) for the object you want to add - as it appears in the STG file (this is NOT the true heading). Let 0 if there is no specific orientation.</span></label></td>
                    <td>
                        <input type="text" name="heading" id="heading" maxlength="7" value="" onkeyup="checkNumeric(form['heading'],0,359.999);validateTabs();" />
                    </td>
                </tr>
            </table>
        </div>
        <div id="tabs-3">
            <table>
                <tr>
                    <td><label for="comment">Comment<em>*</em><span>Please add a short (max 100 letters) statement why you are inserting this data. This will help the maintainers understand what you are doing. eg: I have placed a couple of aircraft shelters and static F16's at EHVK, please commit. Only alphanumerical, colon, semi colon, question and exclamation mark, arobace, minus, underscore, antislash and point are granted.</span></label></td>
                    <td>
                        <input type="text" name="comment" id="comment" maxlength="100" style="width: 100%;" value="" onkeyup="checkComment(this);" />
                    </td>
                </tr>
                <tr>
                    <td><label for="email">Email address<span>Please leave YOUR VALID email address over here. This will help you be informed of your submission process.</span></label></td>
                    <td>
                        <input type="text" name="email" id="email" maxlength="50" size="40" value="" onkeyup="checkEmail(this);" />
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="submit">
            <?php
                        // Google Captcha stuff
                        require_once '../../inc/captcha/recaptchalib.php';
                        $publickey = "6Len6skSAAAAAB1mCVkP3H8sfqqDiWbgjxOmYm_4";
                        echo recaptcha_get_html($publickey);
            ?>
                        <br />
                        <input type="submit" value="Submit position" />
                    </td>
                </tr>
            </table>
        </div>
    </form>
</div>

<?php include '../../inc/footer.php';
?>
