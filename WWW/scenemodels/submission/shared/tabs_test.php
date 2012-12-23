<?php

// Inserting libs
require_once('../../inc/functions.inc.php');

// Checking DB availability before all
$ok=check_availability();

if(!$ok)
{
    $page_title = "Automated Objects Submission Form";
    $error_text = "Sorry, but the database is currently unavailable. We are doing the best to put it back up online. Please come back again soon.";
    include '../../inc/error_page.php';
}
else {
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

    if (!checkStringNotDefault(form["family_name"], 0) ||
        !checkStringNotDefault(form["longitude"], "") || !checkNumeric(form["longitude"],-180,180) ||
        !checkStringNotDefault(form["latitude"], "") || !checkNumeric(form["latitude"],-90,90) ||
        !checkStringNotDefault(form["gndelev"], "") || !checkNumeric(form['gndelev'],-10000,10000) ||
        !checkNumeric(form['offset'],-10000,10000) ||
        !checkStringNotDefault(form["heading"], "") || !checkNumeric(form['heading'],0,359.999) ||
        !checkStringNotDefault(form["comment"], "") || !checkComment(form['comment']) ||
        (form['email'].value!="" && !checkEmail(form['email'])))
        return false;
}

function  validateTabs()
{
    var form = document.getElementById("positions");
    $( "#tabs" ).tabs({ disabled: false });
    
    // Tab 1
    if (form["family_name"].value == 0) {
        $( "#tabs" ).tabs({ disabled: [1, 2] });
        return false;
    }
    // Tab 2
    if (form["longitude"].value == "" ||
        form["latitude"].value == "" ||
        form["gndelev"].value == "" ||
        form["heading"].value == "") {
        $( "#tabs" ).tabs({ disabled: [2] });
        return false;
    }
}
$(function() {
    $( "#tabs" ).tabs({ disabled: [1, 2] });
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
.ui-tabs, .ui-tabs-panel {
    padding: 0px !important;
}
.ui-tabs-nav {
    margin-bottom: -30px !important;
}
.ui-state-active a {
    background-color: rgb(224, 255, 224);
}
</style>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.min.js"></script>

<h1>Automated Objects Submission Form</h1>

<p>
    This automated form's goal is to ease the submission of objects into the FlightGear Scenery database. There are currently <?php $objects = count_objects(); echo number_format($objects, '0', '', ' ');?> objects in the database. Help us to make it more!<br/>
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

    <form id="positions" method="post" action="check_shared.php" onsubmit="return validateForm();">
        <div id="tabs-1">
            <table width="400">
                <tr>
                    <td><span title="This is the family name of the object you want to add."><label for="family_name">Object's family<em>*</em></label></span></td>
                    <td colspan="2">
            <?php
                            $resource_r = connect_sphere_r();

                            // If connection is OK
                            if ($resource_r!='0') {

                                // Show all the families other than the static family
                                $result = @pg_query("SELECT mg_id,mg_name FROM fgs_modelgroups WHERE mg_id!='0' ORDER BY mg_name;");

                                // Start the select form
                                echo "<select id=\"family_name\" name=\"family_name\" onchange=\"update_objects(); validateTabs();\">";
                                echo "<option selected=\"selected\" value=\"0\">Please select a family</option>\n";
                                while ($row = @pg_fetch_assoc($result)) {
                                    $name=preg_replace('/&/',"&amp;",$row["mg_name"]);
                                    $name=preg_replace('/ /',"&nbsp;",$name);
                                    echo "<option value=\"".$row["mg_id"]."\">".$name."</option>\n";
                                }
                                echo "</select>";

                                // Close the database resource
                                @pg_close($resource_r);
                            }

                            // Else, write message.
                            else {
                                echo "<br /><p class='warning'>Sorry but the database is currently unavailable, please come again soon.</p>";
                            }
            ?>
                    </td>
                </tr>
                <tr>
                    <td><span title="This is the name of the object you want to add, ie the name as it's supposed to appear in the .stg file."><label for="model_name">Model name<em>*</em></label></span></td>
                    <td>
                        <!--Now everything is done via the Ajax stuff, and the results inserted here.-->

                        <div id="form_objects"></div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Model overview
                    </td>
                    <td>
                        <img id="form_objects_thumb" src="" alt=""/>
                    </td>
                </tr>
            </table>
        </div>
        <div id="tabs-2">
            <table width="400">
                <tr>
                    <td><span title="This is the WGS84 longitude of the object you want to add. Has to be between -180.000000 and +180.000000."><label for="longitude">Longitude<em>*</em></label></span></td>
                    <td>
                        <input type="text" name="longitude" id="longitude" maxlength="13" value="" onchange="update_map('longitude','latitude');checkNumeric(this,-180,180);update_country();validateTabs();" />
                    </td>
                </tr>
                <tr>
                    <td><span title="This is the WGS84 latitude of the object you want to add. Has to be between -90.000000 and +90.000000."><label for="latitude">Latitude<em>*</em></label></span></td>
                    <td>
                        <input type="text" name="latitude" id="latitude" maxlength="13" value="" onchange="update_map('longitude','latitude');checkNumeric(this,-90,90);update_country();validateTabs();" />
                    </td>
                </tr>
                <tr>
                    <td><span title="This is the country code where the model is located."><label for="ob_country">Country<em>*</em></label></span></td>
                    <td>
                        <select name="ob_country" id="ob_country">
                            <?php list_countries(); ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        Map
                    </td>
                    <td>
                        <object id="map" data="" type="text/html" width="300" height="225"></object>
                    </td>
                </tr>
                <tr>
                    <td><span title="This is the ground elevation (in meters) of the position where the object you want to add is located. Warning: if your model is sunk into the ground, use the elevation offset field below."><label for="gndelev">Elevation<em>*</em></label></span></td>
                    <td>
                        <input type="text" name="gndelev" id="gndelev" maxlength="10" value="" onchange="checkNumeric(this,-10000,10000);validateTabs();" />
                    </td>
                </tr>
                <tr>
                    <td><span title="This is the offset (in meters) between your model 'zero' and the elevation at the considered place (ie if it is sunk into the ground). Let 0 if there is no offset."><label for="offset">Elevation offset<em>*</em></label></span> (see <a href="../../contribute.php#offset">here</a> for more help)</td>
                    <td>
                        <input type="text" name="offset" id="offset" maxlength="10" value="0" onchange="checkNumeric(this,-10000,10000);validateTabs();" />
                    </td>
                </tr>
                <tr>
                    <td><span title="The orientation (in degrees) for the object you want to add - as it appears in the STG file (this is NOT the true heading). Let 0 if there is no specific orientation."><label for="heading">Orientation<em>*</em></label></span></td>
                    <td>
                        <input type="text" name="heading" id="heading" maxlength="7" value="" onchange="checkNumeric(this,0,359.999);validateTabs();" />
                    </td>
                </tr>
            </table>
        </div>
        <div id="tabs-3">
            <table width="400">
                <tr>
                    <td><span title="Please add a short (max 100 letters) statement why you are inserting this data. This will help the maintainers understand what you are doing. eg: I have placed a couple of aircraft shelters and static F16's at EHVK, please commit."><label for="comment">Comment<em>*</em></label></span></td>
                    <td>
                        <input type="text" name="comment" id="comment" maxlength="100" size="100" value="" onchange="checkComment(this);" />
                        <input name="IPAddr" type="hidden" value="<?php echo $_SERVER['REMOTE_ADDR']?>" />
                    </td>
                </tr>
                <tr>
                    <td><span title="Please leave YOUR VALID email address over here. This will help you be informed of your submission process."><label for="email">Email address</label></span></td>
                    <td>
                        <input type="text" name="email" id="email" maxlength="50" size="40" value="" onchange="checkEmail(this);" />
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="submit">
            <?php
                        // Google Captcha stuff
                        require_once('../../inc/captcha/recaptchalib.php');
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
}
?>
