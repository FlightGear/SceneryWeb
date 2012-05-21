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
<title>Automated Shared Models Positions Mass Import Submission Form</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<link rel="stylesheet" href="../../style.css" type="text/css"></link>
</head>
<body>
<?php include '/home/jstockill/scenemodels/header.php'; ?>
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
	<title>Automated Shared Models Positions Mass Import Submission Form</title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<link rel="stylesheet" href="../../style.css" type="text/css"></link>
	<script src="../ajax/update_objects.js" type ="text/javascript"></script>
<script type='text/javascript'>
function validField(fld) {
if (fld == '') return false;
return true;
} 
</script>
</head>
<body>
<?php include '/home/jstockill/scenemodels/header.php'; ?>
<p>
<h1 align="center">Positions Automated Mass Import Submission Form</h1>
<b>Foreword:</b> This automated mass import form goal is to ease the submission when submitter want to add a lot of shared models positions into FG Scenery database. <br />There are currently <?php //count_objects(); ?>
 objects in the database. Help us to make it more! Simply copy/paste the content of your STG files below.<br /><b>WARNING: please only add NEW objects or you will encounter errors!!</b>

<br />Please read <a href="http://scenemodels.flightgear.org/contribute.php">this page</a> in order to understand what recommandations this script is looking for. <br />
Also note that all fields are now mandatory. Do not insert models not existing in the scenery objects database, nor OBJECT_SIGN, nor static objects.
100 lines maximum!
<br /><br />
<form name="positions" method="post" action="check_mass_import.php">
<table width="400">
	<tr>
		<td><span title="This is the content of the STG file you want to add."><a style="cursor: help;">Content to add</a></span></td>
		<td><textarea name="stg" rows="30" cols="100" onblur="if (!validField(this.value)) alert('Please enter a value in STG field!');"></textarea>
		</td>
	</tr>
	<tr>
		<td><span title="Please add a short (max 100 letters) statement why you are inserting this data. This will help the maintainers understand what you are doing. eg: I have placed a couple of aircraft shelters and static F16's at EHVK, please commit"><a style="cursor: help">Comment</a></span></td>
		<td>
			<input type="text" name="comment" maxlength="100" size="40" value="Comment" />
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
			<input type="submit" value="Submit mass import" />
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