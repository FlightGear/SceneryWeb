<?php

// Inserting libs

require_once('../../inc/functions.inc.php');

if((isset($_POST['old_long'])) && (isset($_POST['old_lat'])) && (isset($_POST['old_gndelev'])) && (isset($_POST['old_offset'])) && (isset($_POST['old_orientation'])))
{	

// Captcha stuff

require_once('../../captcha/recaptchalib.php');

// Private key is needed for the server-to-Google auth.

$privatekey = "6Len6skSAAAAACnlhKXCda8vzn01y6P9VbpA5iqi";
$resp = recaptcha_check_answer ($privatekey,
                                $_SERVER["REMOTE_ADDR"],
                                $_POST["recaptcha_challenge_field"],
                                $_POST["recaptcha_response_field"]);

    // What happens when the CAPTCHA was entered incorrectly

	if (!$resp->is_valid)
	{
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
	<title>Automated Shared Models Positions Update Form</title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<link rel="stylesheet" href="../../css/style.css" type="text/css"></link>
	</head>
	<body>
	<?php include '../../inc/header.php'; ?>
	<br />
	<?
	die ("<center>Sorry but the reCAPTCHA wasn't entered correctly. <a href='http://scenemodels.flightgear.org/submission/shared/index_update.php'>Go back and try it again</a>." .
         "<br />(reCAPTCHA complained: " . $resp->error . ")</center>");
	}
  else {

	// Preparing the update request
	
	$query_update="UPDATE fgs_objects SET ob_text='".object_name($_POST['model_name'])."', wkb_geometry=ST_PointFromText('POINT(".$_POST['longitude']." ".$_POST['latitude'].")', 4326), ob_gndelev=".$_POST['gndelev'].", ob_elevoffset=".$_POST['offset'].", ob_heading=".heading_stg_to_true($_POST['orientation']).", ob_model=".$_POST['model_name'].", ob_group=1 where ob_id=".$_POST['id_to_update'].";";
	
	// Generating the SHA-256 hash based on the data we've received + microtime (ms) + IP + request. Should hopefully be enough ;-)

	$sha_to_compute = "<".microtime()."><".$_POST['IPAddr']."><".$query_update.">";
	$sha_hash = hash('sha256', $sha_to_compute);
	
	// Zipping the Base64'd request.
	
	$zipped_base64_update_query = gzcompress($query_update,8);
	
	// Coding in Base64.
	
	$base64_update_query = base64_encode($zipped_base64_update_query);
	
	// Opening database connection...

	$resource_rw = connect_sphere_rw();
	
	// Sending the request...
	
	$query_rw_pending_request = "INSERT INTO fgs_position_requests (spr_hash, spr_base64_sqlz) VALUES ('".$sha_hash."', '".$base64_update_query."');";
	$resultrw = @pg_query($resource_rw, $query_rw_pending_request);
	
	// Closing the connection.

	@pg_close($resource_rw);
	
	// Talking back to submitter.
?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
			"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title>Automated Shared Models Positions Update Form</title>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
		<link rel="stylesheet" href="../../css/style.css" type="text/css"></link>
	</head>
	<body>
	<?php include '../../inc/header.php'; ?>
	<br /><br />
<?php
	if(!$resultrw)
	{
	echo "Sorry, but the query could not be processed. Please ask for help on the <a href='http://www.flightgear.org/forums/viewforum.php?f=5'>Scenery forum</a> or on the devel list.<br />";
	exit;
	}
	else
	{
	echo "Your update request has been successfully queued into the FG scenery database update requests!<br />";
	echo "Unless it's rejected, the object should be updated in Terrasync within a few days.<br />";
	echo "The FG community would like to thank you for your contribution!<br />";
	echo "Want to update, delete or submit another position ?<br /> <a href=\"http://scenemodels.flightgear.org/submission/\">Click here to go back to the submission page.</a>";

	// Sending mail if there is no false and SQL was correctly inserted.

	// Sets the time to UTC.

	date_default_timezone_set('UTC');
	$dtg = date('l jS \of F Y h:i:s A');

	// Retrieving the IP address of the submitter (takes some time to resolve the IP address though).

	$ipaddr = pg_escape_string(stripslashes($_POST['IPAddr']));
	$host = gethostbyaddr($ipaddr);

	// OK, let's start with the mail redaction.

	// Who will receive it ?
	
	$to = "\"Olivier JACQ\" <olivier.jacq@free.fr>" . ", ";
	$to .= "\"Martin SPOTT\" <martin.spott@mgras.net>";

	// What is the subject ?

	$subject = "[FG Scenery Submission forms] Automatic shared model update request: needs validation.";

	// Correctly format the data for the mail.
	
	$object_url = "http://scenemodels.flightgear.org/modeledit.php?id=".$_POST['model_name'];
	$html_object_url = htmlspecialchars($object_url);
	
	// Generating the message and wrapping it to 77 signs per HTML line (asked by Martin). But warning, this must NOT cut an URL, or this will not work.

	$message0 = "Hi," . "\r\n" .
	            "This is the automated FG scenery update PHP form at:" . "\r\n" .
				"http://scenemodels.flightgear.org/submission/check_update_shared.php" . "\r\n" .
			    "I just wanted to let you know that a new shared object position update request is pending." . "\r\n" .
			    "On ".$dtg." UTC, user with the IP address ".$ipaddr." (".$host.") issued the following request:" . "\r\n";
		   
	$message077 = wordwrap($message0, 77, "\r\n");

	// There is no possibility to wrap the URL or it will not work, nor the rest of the message (short lines), or it will not work.

	$message1 = "Object #: ".$_POST['id_to_update']."\r\n" .
		    "Family: ".$_POST['old_family']." => ".family_name($_POST['family_name'])."\r\n" .
		    "Object: ".$_POST['old_model_name']." => ".object_name($_POST['model_name'])."\r\n" .
                    "[ ".$html_object_url." ]" . "\r\n" .
		    "Latitude: ". $_POST['old_lat'] . "  => ".$_POST['latitude']."\r\n" .
		    "Longitude: ". $_POST['old_long'] . " => ".$_POST['longitude']."\r\n" .
		    "Ground elevation: ". $_POST['old_gndelev'] . " => ".$_POST['gndelev']."\r\n" .
		    "Elevation offset: ". $_POST['old_offset'] . " => ".$_POST['offset']."\r\n" .
		    "True (DB) orientation: ". $_POST['old_orientation'] . " => ".heading_stg_to_true($_POST['orientation'])."\r\n" .
		    "Comment: ". strip_tags($_POST['comment']) ."\r\n" .
		    "Please click:" . "\r\n" .
		    "http://mapserver.flightgear.org/map/?lon=". $_POST['longitude'] ."&lat=". $_POST['latitude'] ."&zoom=14&layers=000B0000TFFFTFFFTFTFTFFF" . "\r\n" .
		    "to locate the object on the map (eventually new position)." ;

				
	$message2 = "\r\n".
		    "Now please click:" . "\r\n" .
		    "http://scenemodels.flightgear.org/submission/shared/submission.php?action=confirm&sig=". $sha_hash ."\r\n" .
		    "to confirm the update" . "\r\n" .
		    "or" . "\r\n" .
		    "http://scenemodels.flightgear.org/submission/shared/submission.php?action=reject&sig=". $sha_hash ."\r\n" .
 		    "to reject the update." . "\r\n" . "\r\n" .
		    "Thanks!" ;

	// Preparing the headers.

	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "From: \"FG Scenery Update forms\" <martin.spott@mgras.net>" . "\r\n";
	$headers .= "X-Mailer: PHP-" . phpversion() . "\r\n";

	// Let's send it ! No management of mail() errors to avoid being too talkative...

	$message = $message077.$message1.$message2;

	@mail($to, $subject, $message, $headers);
	exit;
	}
}
}

// Getting back the update_choice

if((isset($_POST['update_choice'])) && ($_POST['update_choice']>'0'))
{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Automated Shared Models Positions Update Form</title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<link rel="stylesheet" href="../../css/style.css" type="text/css"></link>
	<script src="../ajax/update_objects.js" type ="text/javascript"></script>
</head>
<body onload='update_objects();'>
<?php include '../../inc/header.php'; ?>
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
alertsay = "Please enter only the values:\""
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
<br /><br />
<?php
	$id_to_update = pg_escape_string(stripslashes($_POST['update_choice']));
	echo "You have asked to update object #".$id_to_update."<br /><br />";
	
?>
		<form name="update" method="post" action="check_update_shared.php">
		<table>
		<tr>
		<td></td>
		<td>Actual value</td>
		<td>New value</td>
		<input type="hidden" name="id_to_update" value="<?php echo $_POST['update_choice']?>" />
		</tr>
		<tr>
		<td>
		<span title="This is the family name of the object you want to update."><a style="cursor: help;">Object's family</a></span>
		</td>
		<td>
		<?php $old_family=family_name_from_object_id($_POST['update_choice']); echo $old_family; ?>
		<input type="hidden" name="old_family" value="<?php echo $old_family; ?>" />
		</td>
		<td>
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
		echo "<br /><center><font color='red'>Sorry but the database is currently unavailable, please come again soon.</font></center>";
		}
		?>
					</td>
					</tr>
					<tr>
					<td>
					<span title="This is the name of the object you want to update, ie the name as it's supposed to appear in the .stg file."><a style="cursor: help; ">Model name</a></span>
					</td>
					<td>
					<?php $old_model_name=object_name($_POST['model_id']);  echo $old_model_name; ?>
					<input type="hidden" name="old_model_name" value="<?php echo $old_model_name; ?>" />
					</td>
					<td>
					<?php echo "<div id=\"form_objects\"></div>"; ?>
					</td>
					</tr>
					<tr>
					<td>
					<span title="This is the WGS84 longitude of the object you want to add. Has to be between -180.000000 and +180.000000."><a style="cursor: help; ">Longitude</a></span>
					</td>
					<td>
					<?php $old_long = $_POST[long]; echo $old_long; ?>
					<input type="hidden" name="old_long" value="<?php echo $old_long; ?>" />
					</td>
					<td>
					<input type="text" name="longitude" maxlength="13" value="<?php echo $old_long; ?>" onBlur="checkNumeric(this,-180,180,'.');" />
					</td>
					</tr>
					<tr>
					<td>
					<span title="This is the WGS84 latitude of the object you want to add. Has to be between -90.000000 and +90.000000."><a style="cursor: help; ">Latitude</a></span>
					</td>
					<td>
					<?php $old_lat = $_POST[lat]; echo $old_lat; ?>
					<input type="hidden" name="old_lat" value="<?php echo $old_lat; ?>" />
					</td>
					<td>
					<input type="text" name="latitude" maxlength="13" value="<?php echo $old_lat; ?>" onBlur="checkNumeric(this,-90,90,'.'); /">
					</td>
					</tr>
					<tr>
					<td>
					<span title="This is the ground elevation (in meters) of the position where the object you want to update is located. Warning : if your model is sunk into the ground, the Elevation offset field is set below."><a style="cursor: help; ">Elevation</a></span>
					</td>
					<td>
					<?php $actual_elevation = get_elevation_from_id($id_to_update); ?>
					<input type="hidden" name="old_gndelev" maxlength="10" value="<?php echo $actual_elevation; ?>" />
					</td>
					<td>
					<input type="text" name="gndelev" maxlength="10" value="<?php echo $actual_elevation; ?>" onBlur="checkNumeric(this,-10000,10000,'.');" />
					</td>
					</tr>
					<tr>
					<td>
					<span title="This is the offset (in meters) between your model 'zero' and the elevation at the considered place (ie if it is sunk into the ground)."><a style="cursor: help; ">Elevation Offset</a></span>
					</td>
					<td>
					<?php $actual_offet = get_offset_from_id($id_to_update); ?>
					<input type="hidden" name="old_offset" value="<?php echo $actual_offset; ?>" />
					</td>
					<td>
					<input type="text" name="offset" maxlength="10" value="<?php echo $actual_offset; ?>" onBlur="checkNumeric(this,-10000,10000,'.');" />
					</td>
					</tr>
					<tr>
					<td>
					<span title="The orientation of the object you want to update - as it appears in the STG file (this is NOT the true heading). Let 0 if there is no specific orientation."><a style="cursor: help; ">Orientation</a></span>
					</td>
					<td>
					<?php $actual_orientation = heading_true_to_stg(get_true_orientation_from_id($id_to_update)); echo $actual_orientation; ?>
					<input type="hidden" name="old_orientation" value="<?php echo $actual_orientation; ?>" />
					</td>
					<td>
					<input type="text" name="orientation" maxlength="7" value="<?php echo $actual_orientation; ?>" onBlur="checkNumeric(this,0,359.999,'.');" />
					</td>
					</tr>
					<tr>
					<td><span title="Please add a short (max 100 letters) statement why you are updating this data. This will help the maintainers understand what you are doing. eg: this model was misplaced, so I'm updating it"><a style="cursor: help">Comment</a></span></td>
					<td>
					<input type="text" name="comment" maxlength="100" size="40" value="" />
					</td>
					</tr>
					<tr>
						<td colspan="4">
						<center>
						<?php
						// Google Captcha stuff
						require_once('../../captcha/recaptchalib.php');
						$publickey = "6Len6skSAAAAAB1mCVkP3H8sfqqDiWbgjxOmYm_4";
						echo recaptcha_get_html($publickey);
						?>
						<input name="IPAddr" type="hidden" value="<?php echo $_SERVER[REMOTE_ADDR]?>" />
						<input type="submit" name="submit" value="Update this object!" />
						<input type="button" name="cancel" value="Cancel - Do not update!" onclick="history.go(-1)"/>
						</center>
						</td>
					</tr>
				</table>
				</form>
<?php
}
else
{

// Checking DB availability before all

$ok=check_availability();

if(!$ok)
{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Automated Shared Models Positions Update Form</title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<link rel="stylesheet" href="../../css/style.css" type="text/css"></link>
</head>
<body>
<?php include '../../inc/header.php'; ?>
<br /><br />
<center><font color="red">Sorry, but the database is currently unavailable. We are doing the best to put it back up online. Please come back again soon.</font></center>
<br /><center>The FlightGear team.</center>
<?php include '../../inc/footer.php'; ?>
<?
}

else
{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Automated Shared Models Positions Update Form</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<link rel="stylesheet" href="../../css/style.css" type="text/css"></link>
</head>
<body>
<?php include '../../inc/header.php'; ?>
<br />
<?php
$false='0';
global $false;

// Checking that latitude exists and is containing only digits, - or ., is >=-90 and <=90 and with correct decimal format.

// (preg_match('/^[0-9\-\.]+$/u',$_POST['latitude']))
if((isset($_POST['latitude'])) && ((strlen($_POST['latitude'])<=13)) && ($_POST['latitude']<='90') && ($_POST['latitude']>='-90'))
	{
	$lat = number_format(pg_escape_string(stripslashes($_POST['latitude'])),7,'.','');
	echo "<font color=\"green\">Latitude: ".$lat."</font><br />";
	}
else
{
	echo "<font color=\"red\">Latitude mismatch!</font><br />";
	$false='1';
}

// Checking that longitude exists and is containing only digits, - or ., is >=-180 and <=180 and with correct decimal format.

// (preg_match('/^[0-9\-\.]+$/u',$_POST['longitude'])) 
if((isset($_POST['longitude'])) && ((strlen($_POST['longitude']))<=13) && ($_POST['longitude']>='-180') && ($_POST['longitude']<='180'))
	{
	$long = number_format(pg_escape_string(stripslashes($_POST['longitude'])),7,'.','');
	echo "<font color=\"green\">Longitude: ".$long."</font><br />";
	}
else
{
	echo "<font color=\"red\">Longitude mismatch!</font><br />";
	$false = '1';
}
	
// If there is no false, generating SQL to check for object.

if ($false==0)
{
	echo "<br /><font color=\"green\">Data seems to be OK to be updated from the database</font><br />";
	
	// Opening database connection...

	$resource_r_update = connect_sphere_r();
	
	// Let's see in the database if something exists at this position
	
	$query_pos="SELECT ob_id, ob_modified, ob_gndelev, ob_elevoffset, ob_heading, ob_model FROM fgs_objects WHERE wkb_geometry = ST_PointFromText('POINT(".$long." ".$lat.")', 4326);";
	$result = @pg_query($resource_r_update, $query_pos);
	
	$returned_rows = pg_num_rows($result);
	
	if ($returned_rows == '0')
	{
		echo "<br /><font color=\"red\">Sorry, but no object was found at position longitude: ".$long.", latitude: ".$lat.". Please <a href=\"index_update.php\">go back and check your position</a> (see in the relevant STG file).</font><br/>";
		exit;
	}
	else
	{
		if($returned_rows == '1') // If we have just an answer...
		{
		while($row = pg_fetch_row($result))
		{
		echo "<br /><center>One object (#".$row[0].") with WGS84 coordinates longitude: ".$long.", latitude: ".$lat." has been found in the database.</center><br /><br />";
		?>
				<form name="update_position" method="post" action="http://scenemodels.flightgear.org/submission/shared/check_update_shared.php">
				<table>
					<tr>
						<td><span title="This is the family name of the object you want to update."><a style="cursor: help;">Object's family</a></span></td>
						<td colspan="4"><?php $family_name = family_name_from_object_id($row[0]); echo $family_name; ?></td>		
					</tr>
					<tr>
						<td><span title="This is the name of the object you want to update, ie the name as it's supposed to appear in the .stg file."><a style="cursor: help; ">Model name</a></span></td>
						<td colspan="4"><?php $real_name=object_name($row[5]); echo $real_name; ?></td>
						<input name="model_id" type="hidden" value="<?php echo $row[5]; ?>" />
					</tr>
					<tr>
						<td><span title="This is the last update or submission date/time of the corresponding object."><a style="cursor: help; ">Date/Time of last update</a></span></td>
						<td colspan="4"><?php echo $row[1]; ?></td>
					</tr>
					<tr>
						<td><span title="This is the ground elevation (in meters) of the position where the object you want to update is located. Warning : if your model is sunk into the ground, the Elevation offset field is set below."><a style="cursor: help; ">Elevation</a></span></td>
						<td colspan="4"><?php echo $row[2]; ?></td>
						<input name="long" type="hidden" maxlength="13" value="<?php echo $long; ?>" />
						<input name="lat" type="hidden" maxlength="13" value="<?php echo $lat; ?>" />
						<input name="gnd_elev" type="hidden" maxlength="10" value="<?php echo $row[2]; ?>" />
					</tr>
					<tr>
						<td><span title="This is the offset (in meters) between your model 'zero' and the elevation at the considered place (ie if it is sunk into the ground)."><a style="cursor: help; ">Elevation Offset</a></span></td>
						<td colspan="4"><?php if ($row[3]=="") echo "0"; else echo $row[2]; ?></td>
						<input name="offset" maxlength="10" type="hidden" value="<?php if ($row[3]=="") echo "0"; else echo $row[3]; ?>" />
					</tr>
					<tr>
						<td><span title="The orientation of the object you want to update - as it appears in the STG file (this is NOT the true heading). Let 0 if there is no specific orientation."><a style="cursor: help; ">Orientation</a></span></td>
						<td colspan="4"><?php $temp=heading_true_to_stg($row[4]); echo $temp; ?></td>
						<input name="orientation" maxlength="7" type="hidden" value="<?php echo $temp; ?>" />
					</tr>
					<tr>
						<td><span title="This is the picture of the object you want to update"><a style="cursor: help; ">Picture</a></span></td>
						<td><a href="http://scenemodels.flightgear.org/modeledit.php?id=<?php echo $row[5]; ?>"><img src="http://scenemodels.flightgear.org/modelthumb.php?id=<?php echo $row[5]; ?>"></a></td>
						<td><span title="This is the map around the object you want to update"><a style="cursor: help; ">Map</a></span></td>
						<td><iframe src="http://mapserver.flightgear.org/map/?lon=<? echo $long; ?>&lat=<? echo $lat; ?>&zoom=14&layers=000B0000TFFFTFFFTFTFTFFF" width="300" height="225" scrolling="auto" marginwidth="2" marginheight="2" frameborder="0">
							</iframe>
						</td>
					</tr>
					<input name="update_choice" type="hidden" value="<?php echo $row[0]; ?>" />
					<input name="IPAddr" type="hidden" value="<?php echo $_SERVER[REMOTE_ADDR]; ?>" />
					<input name="comment" type="hidden" value="<?php echo $_POST['comment']; ?>" />
					<tr>
						<td colspan="4">
						<center>
						<input name="IPAddr" type="hidden" value="<?php echo $_SERVER[REMOTE_ADDR]; ?>" />
						<br />
						<input type="submit" name="submit" value="I want to update this object!" />
						<input type="button" name="cancel" value="Cancel, I made a mistake!" onclick="history.go(-1)"/>
						</center>
						</td>
					</tr>
				</table>
				</form>
				<?php include '../../inc/footer.php'; ?>
		<?php
		}
		exit;
		}
		else if($returned_rows > '1') // If we have more than one, the user has to choose...
		{
			echo "<br /><center>".$returned_rows." objects with WGS84 coordinates longitude: ".$long.", latitude: ".$lat." have been found in the database.<br />Please select with the left radio button the one you want to update.</center><br /><br />";
		
			// Starting multi-solutions form
		
			echo "<form name=\"update_position\" method=\"post\" action=\"http://scenemodels.flightgear.org/submission/shared/check_update_shared.php\"\">";
			echo "<table>";
			
			// Just used to put the selected button on the first entry
			
			$i = 1;
			while($row = pg_fetch_row($result))
			{
				?>
					<tr>
						<td colspan="5" background="white"><center><b>Object number #<?php echo $row[0]; ?></b></center>
						</td>
					</tr>
					<tr>
						<th rowspan="6">
						<?php
						if ($i == 1)
							{
							echo "<input type=\"radio\" name=\"update_choice\" value=\"".$row[0]."\" checked />";
							}
							else echo "<input type=\"radio\" name=\"update_choice\" value=\"".$row[0]."\" />";
							?>
						</th>
						<td><span title="This is the family name of the object you want to update."><a style="cursor: help;">Object's family</a></span></td>
						<td colspan="4"><?php $family_name = family_name_from_object_id($row[0]); echo $family_name; ?></td>
					</tr>
					<tr>
						<td><span title="This is the name of the object you want to update, ie the name as it's supposed to appear in the .stg file."><a style="cursor: help; ">Model name</a></span></td>
						<td colspan="4"><?php $real_name=object_name($row[5]); echo $real_name; ?></td>
						<input name="model_id" type="hidden" value="<?php echo $row[5]; ?>" />
					</tr>
					<tr>
						<td><span title="This is the last update or submission date/time of the corresponding object."><a style="cursor: help; ">Date/Time of last update</a></span></td>
						<td colspan="4"><?php echo $row[1]; ?></td>
					</tr>
					<tr>
						<td><span title="This is the ground elevation (in meters) of the position where the object you want to update is located. Warning : if your model is sunk into the ground, the Elevation offset field is set below."><a style="cursor: help; ">Elevation</a></span></td>
						<td colspan="4"><?php echo $row[2]; ?></td>
						<input name="long" type="hidden" maxlength="13" value="<?php echo $long; ?>" />
						<input name="lat" type="hidden" maxlength="13" value="<?php echo $lat; ?>" />
						<input name="gnd_elev" type="hidden" maxlength="10" value="<?php echo $row[2]; ?>" />
					</tr>
					<tr>
						<td><span title="This is the offset (in meters) between your model 'zero' and the elevation at the considered place (ie if it is sunk into the ground)."><a style="cursor: help; ">Elevation Offset</a></span></td>
						<td colspan="4"><?php if ($row[2]=="") echo "0"; else echo $row[3]; ?></td>
						<input name="offset" type="hidden" maxlength="10" value="<?php if ($row[3]=="") echo "0"; else echo $row[3]; ?>" />
					</tr>
					<tr>
						<td><span title="The orientation of the object you want to update - as it appears in the STG file (this is NOT the true heading). Let 0 if there is no specific orientation."><a style="cursor: help; ">Orientation</a></span></td>
						<td colspan="4"><?php $temp=heading_true_to_stg($row[4]); echo $temp; ?></td>
						<input name="orientation" type="hidden" maxlength="7" value="<?php echo $temp; ?>" />
					</tr>
					<tr>
						<td><span title="This is the picture of the object you want to update"><a style="cursor: help; ">Picture</a></span></td>
						<td><a href="http://scenemodels.flightgear.org/modeledit.php?id=<?php echo $row[5]; ?>"><img src="http://scenemodels.flightgear.org/modelthumb.php?id=<?php echo $row[5]; ?>"></a></td>
						<td><span title="This is the map around the object you want to update"><a style="cursor: help; ">Map</a></span></td>
						<td><iframe src="http://mapserver.flightgear.org/map/?lon=<? echo $long; ?>&lat=<? echo $lat; ?>&zoom=14&layers=000B0000TFFFTFFFTFTFTFFF" width="300" height="225" scrolling="no" marginwidth="2" marginheight="2" frameborder="0">
							</iframe>
						</td>
					</tr>
					<?php
				$i++;
				}
				?>
					<tr>
						<td colspan="5">
						<center>
						<input name="IPAddr" type="hidden" value="<?php echo $_SERVER[REMOTE_ADDR]; ?>" />
						<input name="comment" type="hidden" value="<?php echo $_POST['comment']; ?>" />
						<br />
						<input name="IPAddr" type="hidden" value="<?php echo $_SERVER[REMOTE_ADDR]; ?>" />
						<input name="comment" type="hidden" value="<?php echo $_POST['comment']; ?>" />
						<input type="submit" name="submit" value="I want to update the selected object!" />
						<input type="button" name="cancel" value="Cancel - I made a mistake!" onclick="history.go(-1)"/>
						</center>
						</td>
					</tr>
				</table>
				</form>
			<?php
		exit();
		}
	}
	}
?>
<?php include '../../inc/footer.php'; ?>
<?php
}
}
?>
