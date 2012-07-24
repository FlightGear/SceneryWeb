<?php

// Inserting libs
require_once('../../inc/functions.inc.php');

// Checking DB availability before all
$ok=check_availability();

if(!$ok)
{
    $page_title = "Automated Shared Models Positions Submission Form";
    $error_text = "Sorry, but the database is currently unavailable. We are doing the best to put it back up online. Please come back again soon.";
    include '../../inc/error_page.php';
}
else {
    $page_title = "Automated Shared Models Positions Submission Form";
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

    if (!checkNumeric(form["longitude"],-180,180) ||
        !checkNumeric(form["latitude"],-90,90) ||
        !checkNumeric(form['gndelev'],-10000,10000) ||
        !checkNumeric(form['offset'],-10000,10000) ||
        !checkNumeric(form['heading'],0,359.999) ||
        !checkComment(form['comment']) ||
        (email!="" && !checkEmail(form['email'])))
        return false;

}
/*]]>*/
</script>

<h1>Positions Automated Submission Form</h1>

<p class="center">
<b>Foreword:</b> This automated form goal is to ease the submission of shared models positions into FG Scenery database. <br />There are currently <?php count_objects(); ?>
 objects in the database. Help us to make it more!

<br />Please read <a href="http://scenemodels.flightgear.org/contribute.php">this page</a> in order to understand what recommandations this script is looking for. <br />Also note that all fields are now mandatory.
If you need some more help, just put your mouse over the left column (eg "Elevation Offset").
</p>
<br /><br />
<form id="positions" method="post" action="check_shared.php" onsubmit="return validateForm();">
<table width="400">
    <tr>
        <td><span title="This is the family name of the object you want to add."><a style="cursor: help;">Object's family</a></span></td>
        <td colspan="2">
<?php

                $resource_r = connect_sphere_r();

                // If connection is OK
                if ($resource_r!='0') {

                    // Show all the families other than the static family
                    $result = @pg_query("SELECT mg_id,mg_name FROM fgs_modelgroups WHERE mg_id!='0' ORDER BY mg_name;");

                    // Start the select form
                    echo "<select id=\"family_name\" name=\"family_name\" onchange=\"update_objects();\">";
                    echo "<option selected=\"selected\" value=\"0\">Please select a family</option>\n";
                    while ($row = @pg_fetch_assoc($result)) {
                        $name=preg_replace('/ /',"&nbsp;",$row["mg_name"]);
                        $name=preg_replace('/&/',"&amp;",$name);
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
        <td><span title="This is the name of the object you want to add, ie the name as it's supposed to appear in the .stg file."><a style="cursor: help; ">Model name</a></span></td>
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
    <tr>
        <td><span title="This is the WGS84 longitude of the object you want to add. Has to be between -180.000000 and +180.000000."><a style="cursor: help; ">Longitude</a></span></td>
        <td>
            <input type="text" name="longitude" id="longitude" maxlength="13" value="" onchange="update_map();checkNumeric(this,-180,180);" />
        </td>
    </tr>
    <tr>
        <td><span title="This is the WGS84 latitude of the object you want to add. Has to be between -90.000000 and +90.000000."><a style="cursor: help; ">Latitude</a></span></td>
        <td>
            <input type="text" name="latitude" id="latitude" maxlength="13" value="" onchange="update_map();checkNumeric(this,-90,90);" />
        </td>
    </tr>
    <tr>
        <td>
            Map
        </td>
        <td>
            <iframe id="map" src="http://mapserver.flightgear.org/submap/?zoom=13&amp;lat=0&amp;lon=0" width="300" height="225"></iframe>
        </td>
    </tr>
    <tr>
        <td><span title="This is the ground elevation (in meters) of the position where the object you want to add is located. Warning: if your model is sunk into the ground, use the elevation offset field below."><a style="cursor: help; ">Elevation</a></span></td>
        <td>
            <input type="text" name="gndelev" maxlength="10" value="" onchange="checkNumeric(this,-10000,10000);" />
        </td>
    </tr>
    <tr>
        <td><span title="This is the offset (in meters) between your model 'zero' and the elevation at the considered place (ie if it is sunk into the ground). Let 0 if there is no offset."><a style="cursor: help; ">Elevation Offset</a></span></td>
        <td>
            <input type="text" name="offset" maxlength="10" value="0" onchange="checkNumeric(this,-10000,10000);" />
        </td>
    </tr>
    <tr>
        <td><span title="The orientation (in degrees) for the object you want to add - as it appears in the STG file (this is NOT the true heading). Let 0 if there is no specific orientation."><a style="cursor: help; ">Orientation</a></span></td>
        <td>
            <input type="text" name="heading" maxlength="7" value="" onchange="checkNumeric(this,0,359.999);" />
        </td>
    </tr>
    <tr>
        <td><span title="Please add a short (max 100 letters) statement why you are inserting this data. This will help the maintainers understand what you are doing. eg: I have placed a couple of aircraft shelters and static F16's at EHVK, please commit."><a style="cursor: help">Comment</a></span></td>
        <td>
            <input type="text" name="comment" maxlength="100" size="40" value="" onchange="checkComment(this);" />
            <input name="IPAddr" type="hidden" value="<?php echo $_SERVER[REMOTE_ADDR]?>" />
        </td>
    </tr>
    <tr>
        <td><span title="Please leave YOUR VALID email address over here. This will help you be informed of your submission process."><a style="cursor:help">Email address (not mandatory)</a></span></td>
        <td>
            <input type="text" name="email" maxlength="50" size="40" value="" onchange="checkEmail(this);" />
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <center>
<?php
            // Google Captcha stuff
            require_once('../../inc/captcha/recaptchalib.php');
            $publickey = "6Len6skSAAAAAB1mCVkP3H8sfqqDiWbgjxOmYm_4";
            echo recaptcha_get_html($publickey);
?>
            <br />
            <input type="submit" value="Submit position" />
            </center>
        </td>
    </tr>
</table>
</form>

<?php include '../../inc/footer.php';
}
?>
