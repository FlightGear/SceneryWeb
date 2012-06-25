<?php

// Inserting libs

require_once('../../inc/functions.inc.php');

// Checking DB availability before all

$ok=check_availability();

if(!$ok)
{
?>
<?php
    $page_title = "Automated Shared Models Positions Submission Form";
    include '../../inc/header.php';
?>
<br /><br />
<center><font color="red">Sorry, but the database is currently unavailable. We are doing the best to put it back up online. Please come back again soon.</font></center>
<br /><center>The FlightGear team.</center>
<?php include '../../inc/footer.php'; ?>
<?php
}

else
{
?>

<?php
    $page_title = "Automated Shared Models Positions Submission Form";
    $body_onload = "update_objects();";
    include '../../inc/header.php';
?>
<script src="../ajax/update_objects.js" type ="text/javascript"></script>
<script type="text/javascript">
<!-- This script is here to check for the consistency of the different fields of the form -->

function checkNumeric(objName,minval,maxval,period)
{
    var numberfield = objName;
    if (chkNumeric(objName,minval,maxval,period) == false)
    {
        numberfield.select();
        numberfield.focus();
        return false;
    }
    else
    {
        return true;
    }
}

function chkNumeric(objName,minval,maxval,period)
{
    var checkOK = "-0123456789.";
    var checkStr = objName;
    var allValid = true;
    var decPoints = 0;
    var allNum = "";

    for (i = 0;  i < checkStr.value.length;  i++)
    {
        ch = checkStr.value.charAt(i);
        for (j = 0;  j < checkOK.length;  j++)
            if (ch == checkOK.charAt(j))
                break;
        if (j == checkOK.length)
        {
            allValid = false;
            break;
        }
        if (ch != ",")
            allNum += ch;
    }
    
    if (!allValid)
    {
        alertsay = "Please enter only the values :\""
        alertsay = alertsay + checkOK + "\" in the \"" + checkStr.name + "\" field."
        alert(alertsay);
        return (false);
    }

    // Sets minimum and maximums
    var chkVal = allNum;
    var prsVal = parseInt(allNum);
    if (chkVal != "" && !(prsVal >= minval && prsVal <= maxval))
    {
        alertsay = "Please enter a value greater than or "
        alertsay = alertsay + "equal to \"" + minval + "\" and less than or "
        alertsay = alertsay + "equal to \"" + maxval + "\" in the \"" + checkStr.name + "\" field."
        alert(alertsay);
        return (false);
    }
}
//  End -->
</script>

<p>
<h1>Positions Automated Submission Form</h1>
<center><b>Foreword:</b> This automated form goal is to ease the submission of shared models positions into FG Scenery database. <br />There are currently <?php count_objects(); ?>
 objects in the database. Help us to make it more!

<br />Please read <a href="http://scenemodels.flightgear.org/contribute.php">this page</a> in order to understand what recommandations this script is looking for. <br />Also note that all fields are now mandatory.
If you need some more help, just put your mouse over the left column (eg "Elevation Offset").</center>
<br /><br />
<form name="positions" method="post" action="check_shared.php">
<table width="400">
    <tr>
        <td><span title="This is the family name of the object you want to add."><a style="cursor: help;">Object's family</a></span></td>
        <td colspan="2">
<?php

                $resource_r = connect_sphere_r();
                
                // If connection is OK

                if($resource_r!='0')
                {

                    // Show all the families other than the static family

                    $result = @pg_query("select mg_id,mg_name from fgs_modelgroups where mg_id!='0' order by mg_name;");
                
                    // Start the select form

                    echo "<select id=\"family_name\" name=\"family_name\" onchange=\"update_objects();\">";
                    echo "<option selected value=\"0\">Please select a family</option>\n";
                    while ($row = @pg_fetch_assoc($result))
                    {
                        $name=preg_replace('/ /',"&nbsp;",$row["mg_name"]);        
                        echo "<option value=\"".$row["mg_id"]."\">".$name."</option>\n";
                    };
                    echo "</select>";

                    // Close the database resource

                    @pg_close($resource_r);
                }

                // Else, write message.

                else
                {
                    echo "<br /><font color='red'>Sorry but the database is currently unavailable, please come again soon.</font>";
                }
?>
        </td>
    </tr>
    <tr>
        <td><span title="This is the name of the object you want to add, ie the name as it's supposed to appear in the .stg file."><a style="cursor: help; ">Model name</a></span></td>
        <td>
<?php
                
                // Now everything is done via the Ajax stuff, and the results inserted here.

                echo "<div id=\"form_objects\"></div>";

?>
        </td>
    </tr>
    <tr>
        <td><span title="This is the WGS84 longitude of the object you want to add. Has to be between -180.000000 and +180.000000."><a style="cursor: help; ">Longitude</a></span></td>
        <td>
            <input type="text" name="longitude" maxlength="13" value="" onBlur="checkNumeric(this,-180,180,'.');" />
        </td>
    </tr>
    <tr>
        <td><span title="This is the WGS84 latitude of the object you want to add. Has to be between -90.000000 and +90.000000."><a style="cursor: help; ">Latitude</a></span></td>
        <td>
            <input type="text" name="latitude" maxlength="13" value="" onBlur="checkNumeric(this,-90,90,'.');" />
        </td>
    </tr>
    <tr>
        <td><span title="This is the ground elevation (in meters) of the position where the object you want to add is located. Warning: if your model is sunk into the ground, use the elevation offset field below."><a style="cursor: help; ">Elevation</a></span></td>
        <td>
            <input type="text" name="gndelev" maxlength="10" value="" onBlur="checkNumeric(this,-10000,10000,'.');" />
        </td>
    </tr>
    <tr>
        <td><span title="This is the offset (in meters) between your model 'zero' and the elevation at the considered place (ie if it is sunk into the ground). Let 0 if there is no offset."><a style="cursor: help; ">Elevation Offset</a></span></td>
        <td>
            <input type="text" name="offset" maxlength="10" value="0" onBlur="checkNumeric(this,-10000,10000,'.');" />
        </td>
    </tr>
    <tr>
        <td><span title="The orientation (in degrees) for the object you want to add - as it appears in the STG file (this is NOT the true heading). Let 0 if there is no specific orientation."><a style="cursor: help; ">Orientation</a></span></td>
        <td>
            <input type="text" name="heading" maxlength="7" value="" onBlur="checkNumeric(this,0,359.999,'.');" />
        </td>
    </tr>
    <tr>
        <td><span title="Please add a short (max 100 letters) statement why you are inserting this data. This will help the maintainers understand what you are doing. eg: I have placed a couple of aircraft shelters and static F16's at EHVK, please commit."><a style="cursor: help">Comment</a></span></td>
        <td>
            <input type="text" name="comment" maxlength="100" size="40" value="" />
            <input name="IPAddr" type="hidden" value="<?php echo $_SERVER[REMOTE_ADDR]?>" />
        </td>
    </tr>
    <tr>
        <td><span title="Please live YOUR VALID email address over here. This will help you be informed of your submission process. EXPERIMENTAL"><a style="cursor:help">Email address (EXPERIMENTAL and not mandatory)</a></span></td>
        <td>
            <input type="text" name="email" maxlength="50" size="40" value="" />
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <center>
<?php
                        // Google Captcha stuff
                        require_once('../../captcha/recaptchalib.php');
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
</p>
<?php include '../../inc/footer.php'; ?>
<?php
}
?>
