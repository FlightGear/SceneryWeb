<?php

// Inserting libs

require_once('../inc/functions.inc.php');

// Getting back the delete_choice

if((isset($_POST['delete_choice'])) && ($_POST['delete_choice']>'0') && (preg_match('/^[0-9]+$/u',$_POST['delete_choice']))
{
// Captcha stuff

require_once('../captcha/recaptchalib.php');

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
	<title>Automated Shared Models Positions Deletion Form</title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<link rel="stylesheet" href="../../style.css" type="text/css"></link>
	</head>
	<body>
	<?php include '../../header.php'; ?>
	<br />
	<?
	die ("Sorry but the reCAPTCHA wasn't entered correctly. <a href='http://scenemodels.flightgear.org/submission/shared/index_delete.php'>Go back and try it again</a>." .
         "<br />(reCAPTCHA complained: " . $resp->error . ")");
	}
  else {

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
<?php
	$id_to_delete = pg_escape_string(stripslashes($_POST['delete_choice']));
	echo "<font color=\"green\">You have asked to delete object #".$id_to_delete."</font><br />";
		
	// Preparing the deletion request
	
	$query_delete="DELETE from fgs_objects where ob_id=".$id_to_delete.";";
	
	// Generating the SHA-256 hash based on the data we've received + microtime (ms) + IP + request. Should hopefully be enough ;-)

	$sha_to_compute = "<".microtime()."><".$_POST['IPAddr']."><".$query_delete.">";
	$sha_hash = hash('sha256', $sha_to_compute);
	
	// Zipping the Base64'd request.
	
	$zipped_base64_delete_query = gzcompress($query_delete,8);
	
	// Coding in Base64.
	
	$base64_delete_query = base64_encode($zipped_base64_delete_query);
	
	// Opening database connection...

	$resource_rw = connect_sphere_rw();
	
	// Sending the request...
	
	$query_rw_pending_request = "INSERT INTO fgs_position_requests (spr_hash, spr_base64_sqlz) VALUES ('".$sha_hash."', '".$base64_delete_query."');";
	$resultrw = @pg_query($resource_rw, $query_rw_pending_request);
	
	// Closing the connection.

	@pg_close($resource_rw);
	
	// Talking back to submitter.

	if(!$resultrw)
	{
	echo "Sorry, but the query could not be processed. Please ask for help on the <a href='http://www.flightgear.org/forums/viewforum.php?f=5'>Scenery forum</a> or on the devel list.<br />";
	}
	else
	{
	echo "<br />Your position has been successfully queued into the FG scenery database deletion requests!<br />";
	echo "Unless it's rejected, the object should be dropped in Terrasync within a few days.<br />";
	echo "The FG community would like to thank you for your contribution!<br />";
	echo "Want to delete or submit another position ?<br /> <a href=\"http://scenemodels.flightgear.org/submission/shared/\">Click here to go back to the submission page.</a>";

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

	$subject = "[FG Scenery Submission forms] Automatic shared model position DELETION request: needs validation.";

	// Correctly format the data for the mail.
	
	$family_name = family_name_from_object_id($id_to_delete);
	$model_id = object_model_from_object_id($id_to_delete);
	$model_name = object_name($model_id);
	$object_url = "http://scenemodels.flightgear.org/modeledit.php?id=".$model_id;
	$html_object_url = htmlspecialchars($object_url);
	
	// Generating the message and wrapping it to 77 signs per HTML line (asked by Martin). But warning, this must NOT cut an URL, or this will not work.

	$message0 = "Hi," . "\r\n" .
	            "This is the automated FG scenery deletion PHP form at:" . "\r\n" .
				"http://scenemodels.flightgear.org/submission/check_delete_shared.php" . "\r\n" .
			    "I just wanted to let you know that a new shared object position DELETION request is pending." . "\r\n" .
			    "On ".$dtg." UTC, user with the IP address ".$ipaddr." (".$host.") issued the following request:" . "\r\n";
		   
	$message077 = wordwrap($message0, 77, "\r\n");

	// There is no possibility to wrap the URL or it will not work, nor the rest of the message (short lines), or it will not work.

	$message1 = "Family: ".$family_name."\r\n" .
			    "Object: ".$model_name."\r\n" .
				"[ ".$html_object_url." ]" . "\r\n" .
			    "Latitude: ". $_POST['lat'] . "\r\n" .
			    "Longitude: ". $_POST['long'] . "\r\n" .
			    "Ground elevation: ". $_POST['gnd_elev'] . "\r\n" .
			    "Elevation offset: ". $_POST['offset'] . "\r\n" .
			    "True (DB) orientation: ". $_POST['orientation'] . "\r\n" .
				"Comment: ". strip_tags($_POST['comment']) ."\r\n" .
			    "Please click:" . "\r\n" .
				"http://mapserver.flightgear.org/map/?lon=". $_POST['long'] ."&lat=". $_POST['lat'] ."&zoom=15&layers=00B00000TFFFFFFFTFTFTFFF" . "\r\n" .
			    "to locate the object on the map." ;

	$message2 = "\r\n".
				"Now please click:" . "\r\n" .
				"http://scenemodels.flightgear.org/submission/shared/submission.php?action=confirm&sig=". $sha_hash ."\r\n" .
				"to confirm the deletion" . "\r\n" .
				"or" . "\r\n" .
				"http://scenemodels.flightgear.org/submission/shared/submission.php?action=reject&sig=". $sha_hash ."\r\n" .
 				"to reject the deletion." . "\r\n" . "\r\n" .
				"Thanks!" ;

	// Preparing the headers.

	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "From: \"FG Scenery Deletion forms\" <martin.spott@mgras.net>" . "\r\n";
	$headers .= "X-Mailer: PHP-" . phpversion() . "\r\n";

	// Let's send it ! No management of mail() errors to avoid being too talkative...

	$message = $message077.$message1.$message2;

	@mail($to, $subject, $message, $headers);
	}
}
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
<br />
<?php
$false='0';
global $false;

// Checking that latitude exists, is of good length and is containing only digits, - or ., is >=-90 and <=90 and with correct decimal format.

if((isset($_POST['latitude'])) && (strlen($_POST['latitude'])<=13) && (preg_match('/^[0-9\-\.]+$/u',$_POST['latitude'])) && ($_POST['latitude']<='90') && ($_POST['latitude']>='-90'))
	{
	$lat = number_format(pg_escape_string(stripslashes($_POST['latitude'])),7,'.','');
	echo "<font color=\"green\">Latitude: ".$lat."</font><br />";
	}
else
{
	echo "<font color=\"red\">Latitude mismatch!</font><br />";
	$false='1';
}

// Checking that longitude exists, if of good length and is containing only digits, - or ., is >=-180 and <=180 and with correct decimal format.

if((isset($_POST['longitude'])) && (strlen($_POST['longitude'])<=13) && (preg_match('/^[0-9\-\.]+$/u',$_POST['longitude'])) && ($_POST['longitude']<='180') && ($_POST['longitude']>='-180'))
	{
	$long = number_format(pg_escape_string(stripslashes($_POST['longitude'])),7,'.','');
	echo "<font color=\"green\">Longitude: ".$long."</font><br />";
	}
else
{
	echo "<font color=\"red\">Longitude mismatch!</font><br />";
	$false = '1';
}

// Checking that comment exists. Just a small verification as it's not going into DB.

if((isset($_POST['comment'])) && (strlen($_POST['comment'])>0) && (preg_match('/^[A-Za-z0-9 \-\.\,]+$/u',$_POST['comment'])) && (strlen($_POST['comment'])<=100))
	{
	$sent_comment = pg_escape_string(stripslashes($_POST['comment']));
	}
else
	{
	echo "<font color=\"red\">Comment mismatch!</font><br />";
	$false='1';
	}
	
// If there is no false, generating SQL to be inserted into the database pending requests table.

if ($false==0)
{
	echo "<br /><font color=\"green\">Data seems to be OK to be deleted from the database</font><br />";
	
	// Opening database connection...

	$resource_r_deletion = connect_sphere_r();
	
	// Let's see in the database if something exists at this position
	
	$query_pos="SELECT ob_id, ob_gndelev, ob_elevoffset, ob_heading, ob_model FROM fgs_objects WHERE wkb_geometry = ST_PointFromText('POINT(".$long." ".$lat.")', 4326);";
	$result = @pg_query($resource_r_deletion, $query_pos);
	
	$returned_rows = pg_num_rows($result);
	
	if ($returned_rows == '0')
	{
		echo "<br /><font color=\"red\">Sorry, but no object was found at position longitude: ".$long.", latitude: ".$lat.". Please <a href=\"index_delete.php\">go back and check your position</a> (see in the relevant STG file).</font><br/>";
		exit;
	}
	else
	{
		if($returned_rows == '1')
		{
		while($row = pg_fetch_row($result))
		{
		echo "<br />One object (#".$row[0].") with WGS84 coordinates longitude: ".$long.", latitude: ".$lat." has been found in the database.<br /><br />";
		?>
				<form name="delete_position" method="post" action="http://scenemodels.flightgear.org/submission/shared/check_delete_shared.php">
				<table>
					<tr>
						<td><span title="This is the family name of the object you want to delete."><a style="cursor: help;">Object's family</a></span></td>
						<td colspan="4"><?php $family_name = family_name_from_object_id($row[0]); echo $family_name; ?></td>		
					</tr>
					<tr>
						<td><span title="This is the name of the object you want to delete, ie the name as it's supposed to appear in the .stg file."><a style="cursor: help; ">Model name</a></span></td>
						<td colspan="4"><?php $real_name=object_name($row[4]); echo $real_name; ?></td>
					</tr>
						<td><span title="This is the ground elevation (in meters) of the position where the object you want to delete is located. Warning: if your model is sunk into the ground, the Elevation offset field is set below."><a style="cursor: help; ">Elevation</a></span></td>
						<td colspan="4"><?php echo $row[1]; ?></td>
						<input name="long" type="hidden" maxlength="13" value="<?php echo $long; ?> />
						<input name="lat" type="hidden" maxlength="13" value="<?php echo $lat; ?> />
						<input name="gnd_elev" type="hidden" maxlength="10" value="<?php echo $row[1]; ?>" />
					</tr>
					<tr>
						<td><span title="This is the offset (in meters) between your model 'zero' and the elevation at the considered place (ie if it is sunk into the ground)."><a style="cursor: help; ">Elevation Offset</a></span></td>
						<td colspan="4"><?php if ($row[2]=="") echo "0"; else echo $row[2]; ?></td>
						<input name="offset" type="hidden" maxlength="10" value="<?php if ($row[2]=="") echo "0"; else echo $row[2]; ?>" />
					</tr>
					<tr>
						<td><span title="The orientation of the object you want to delete - as it appears in the STG file (this is NOT the true heading). Let 0 if there is no specific orientation."><a style="cursor: help; ">Orientation</a></span></td>
						<td colspan="4"><?php echo $row[3]; ?></td>
						<input name="orientation" type="hidden" maxlength="7" value="<?php echo $row[3]; ?>" />
					</tr>
					<tr>
						<td><span title="This is the picture of the object you want to delete"><a style="cursor: help; ">Picture</a></span></td>
						<td><a href="http://scenemodels.flightgear.org/modeledit.php?id=<?php echo $row[4]; ?>"><img src="http://scenemodels.flightgear.org/modelthumb.php?id=<?php echo $row[4]; ?>"></a></td>
						<td><span title="This is the map around the object you want to delete"><a style="cursor: help; ">Map</a></span></td>
						<td><iframe src="http://mapserver.flightgear.org/map/?lon=<? echo $long; ?>&lat=<? echo $lat; ?>&zoom=14&layers=00B00000TFFFFFFFTFTFTFFF" width="300" height="225" scrolling="auto" marginwidth="2" marginheight="2" frameborder="0">
							</iframe>
						</td>
					</tr>
					<input name="delete_choice" type="hidden" value="<?php echo $row[0]; ?>" />
					<input name="IPAddr" type="hidden" value="<?php echo $_SERVER[REMOTE_ADDR]; ?>" />
					<input name="comment" type="hidden" value="<?php echo $_POST['comment']; ?>" />
					<tr>
						<td colspan="4">
						<center>
						<?php
						// Google Captcha stuff
						require_once('../captcha/recaptchalib.php');
						$publickey = "6Len6skSAAAAAB1mCVkP3H8sfqqDiWbgjxOmYm_4";
						echo recaptcha_get_html($publickey);
						?>
						</center>
						<br />
						<input type="submit" name="submit" value="Delete this object!" />
						<input type="button" name="cancel" value="Cancel - Do not delete!" onclick="history.go(-1)"/>
						</center>
						</td>
					</tr>
				</table>
				</form>
		<?php
		}
		exit;
		}
		else if($returned_rows > '1')
		{
			echo "<br />".$returned_rows." objects with WGS84 coordinates longitude: ".$long.", latitude: ".$lat." have been found in the database.<br />Please select with the left radio button the one you want to delete.<br /><br />";
		
			// Starting multi-solutions form
		
			echo "<form name=\"delete_position\" method=\"post\" action=\"http://scenemodels.flightgear.org/submission/shared/check_delete_shared.php\"\">";
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
							echo "<input type=\"radio\" name=\"delete_choice\" value=\"".$row[0]."\" checked />";
							}
							else echo "<input type=\"radio\" name=\"delete_choice\" value=\"".$row[0]."\" />";
							?>
						</th>
						<td><span title="This is the family name of the object you want to delete."><a style="cursor: help;">Object's family</a></span></td>
						<td colspan="4"><?php $family_name = family_name_from_object_id($row[0]); echo $family_name; ?></td>
					</tr>
					<tr>
						<td><span title="This is the name of the object you want to delete, ie the name as it's supposed to appear in the .stg file."><a style="cursor: help; ">Model name</a></span></td>
						<td colspan="4"><?php $real_name=object_name($row[4]); echo $real_name; ?></td>
					</tr>
					<tr>
						<td><span title="This is the ground elevation (in meters) of the position where the object you want to delete is located. Warning : if your model is sunk into the ground, the Elevation offset field is set below."><a style="cursor: help; ">Elevation</a></span></td>
						<td colspan="4"><?php echo $row[1]; ?></td>
						<input name="long" type="hidden" maxlength="13" value="<?php echo $long; ?>" />
						<input name="lat" type="hidden" maxlength="13" value="<?php echo $lat; ?>" />
						<input name="gnd_elev" type="hidden" maxlength="10" value="<?php echo $row[1]; ?>" />
					</tr>
					<tr>
						<td><span title="This is the offset (in meters) between your model 'zero' and the elevation at the considered place (ie if it is sunk into the ground)."><a style="cursor: help; ">Elevation Offset</a></span></td>
						<td colspan="4"><?php if ($row[2]=="") echo "0"; else echo $row[2]; ?></td>
						<input name="offset" type="hidden" maxlength="10" value="<?php if ($row[2]=="") echo "0"; else echo $row[2]; ?>" />
					</tr>
					<tr>
						<td><span title="The orientation of the object you want to delete - as it appears in the STG file (this is NOT the true heading). Let 0 if there is no specific orientation."><a style="cursor: help; ">Orientation</a></span></td>
						<td colspan="4"><?php echo $row[3]; ?></td>
						<input name="orientation" type="hidden" maxlength="7" value="<?php echo $row[3]; ?>" />
					</tr>
					<tr>
						<td><span title="This is the picture of the object you want to delete"><a style="cursor: help; ">Picture</a></span></td>
						<td><a href="http://scenemodels.flightgear.org/modeledit.php?id=<?php echo $row[4]; ?>"><img src="http://scenemodels.flightgear.org/modelthumb.php?id=<?php echo $row[4]; ?>"></a></td>
						<td><span title="This is the map around the object you want to delete"><a style="cursor: help; ">Map</a></span></td>
						<td><iframe src="http://mapserver.flightgear.org/map/?lon=<? echo $long; ?>&lat=<? echo $lat; ?>&zoom=14&layers=00B00000TFFFFFFFTFTFTFFF" width="300" height="225" scrolling="no" marginwidth="2" marginheight="2" frameborder="0">
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
						<?php
						// Google Captcha stuff
						require_once('../captcha/recaptchalib.php');
						$publickey = "6Len6skSAAAAAB1mCVkP3H8sfqqDiWbgjxOmYm_4";
						echo recaptcha_get_html($publickey);
						?>
						</center>
						<br />
						<input type="submit" name="submit" value="Delete this object!" />
						<input type="button" name="cancel" value="Cancel - Do not delete!" onclick="history.go(-1)"/>
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
</body>
</html>
<?php
}
}
?>
