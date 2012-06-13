<?php

// Inserting libs

require_once('../inc/functions.inc.php');

// Checking DB availability before all

$ok=check_availability();

if(!$ok)
{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Automated Shared Models Positions Deletion Form</title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<link rel="stylesheet" href="../../style.css" type="text/css"></link>
</head>
<body>
<?php include '../../header.php'; ?>
<br /><br />
<center><font color="red">Sorry, but the database is currently unavailable. We are doing the best to put it back up online. Please come back again soon.</font></center>
<br /><center>The FlightGear team.</center>
</body>
</html>
<?
}

else
{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Automated Shared Models Positions Deletion Form</title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<link rel="stylesheet" href="../../style.css" type="text/css"></link>
</head>
<body>
<?php include '../../header.php'; ?>
<script language="JavaScript">
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
<b>Foreword:</b> This automated form goal is to ease the deletion of shared models positions within FG Scenery database. <br />There are currently <?php count_objects(); ?>
 objects in the database.
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
		<td><span title="Please add a short (max 100 letters) statement why you are deleting this data. This will help the maintainers understand what you are doing. eg: this model is in a river, so please delete it"><a style="cursor: help">Comment</a></span></td>
		<td>
			<input type="text" name="comment" maxlength="100" size="40" value="" />
			<input name="IPAddr" type="hidden" value="<?php echo $_SERVER[REMOTE_ADDR]?>" />
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<center>
			<?php
			// Google Captcha stuff
			require_once('../captcha/recaptchalib.php');
			$publickey = "6Len6skSAAAAAB1mCVkP3H8sfqqDiWbgjxOmYm_4";
			echo recaptcha_get_html($publickey);
			?>
			</center>
			<br />
			<input type="submit" value="Check for objects at this position" />
		</td>
	</tr>
</table>
</form>
</p>
</body>
</html>
<?php
}
?>
