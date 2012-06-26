<?php

// Inserting libs

require_once('../../inc/functions.inc.php');

// Checking DB availability before all

$ok=check_availability();

if(!$ok)
{
?>
<?php
    $page_title = "Automated Shared Models Positions Deletion Form";
    include '../../inc/header.php';
?>
<br /><br />
<center><font color="red">Sorry, but the database is currently unavailable. We are doing the best to put it back up online. Please come back again soon.</font></center>
<br /><center>The FlightGear team.</center>
<?php include '../../inc/footer.php'; ?>
}
else
{
    $page_title = "Automated Shared Models Positions Deletion Form";
    include '../../inc/header.php';
?>
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
<h1 align="center">Positions Automated Deletion Form</h1>
<center><b>Foreword:</b> This automated form goal is to ease the deletion of shared models positions within FG Scenery database. <br />There are currently <?php count_objects(); ?>  objects in the database.</center>
<br /><br />
<form name="deletion" method="post" action="check_delete_shared.php">
<table>
	<tr>
		<td><span title="This is the WGS84 longitude of the object you want to delete. Has to be between -180.000000 and +180.000000."><a style="cursor: help; ">Longitude</a></span></td>
		<td>
			<input type="text" name="longitude" maxlength="13" value="0" onBlur="checkNumeric(this,-180,180,'.');" />
		</td>
	</tr>
	<tr>
		<td><span title="This is the WGS84 latitude of the object you want to delete. Has to be between -90.000000 and +90.000000."><a style="cursor: help; ">Latitude</a></span></td>
		<td>
			<input type="text" name="latitude" maxlength="13" value="0" onBlur="checkNumeric(this,-90,90,'.');" />
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<center>
			<input type="submit" value="Check for objects at this position" />
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
