<?php

// Inserting libs

require_once('../../inc/functions.inc.php');

// Checking DB availability before all

$ok=check_availability();

if(!$ok)
{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Automated Shared Models Positions Submission Form</title>
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
<?php

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
	<title>Automated Shared Models Positions Submission Form</title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<link rel="stylesheet" href="../../css/style.css" type="text/css"></link>
	</head>
	<body>
	<?php include '../../inc/header.php'; ?>
	<br />
	<?
	die ("Sorry but the reCAPTCHA wasn't entered correctly. <a href=\"javascript:history.back()\">Go back and try it again</a>." .
         "<br />(reCAPTCHA complained: " . $resp->error . ")");
	<?php include '../../inc/footer.php'; ?>
	}
else {
	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Automated Shared Models Positions Submission Form</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<link rel="stylesheet" href="../../css/style.css" type="text/css"></link>
</head>
<body>
<?php include '../../inc/header.php'; ?>
<br />
<?php
$false='0';
global $false;

// Checking that comment exists. Just a small verification as it's not going into DB.

if(isset($_POST['comment']) && (strlen($_POST['comment']>0)) && (preg_match('/^[A-Za-z0-9 \-\.\,]+$/u',$_GET['description'])) && (strlen($_POST['comment']<=100)))
	{
	$sent_comment = pg_escape_string(stripslashes($_POST['comment']));
	}
else
	{
	echo "<font color=\"red\">Comment mismatch!</font><br />";
	$false='1';
	exit;
	}

// Checking that stg exists and is containing only letters or figures.

if((isset($_POST['stg'])) && (preg_match('/^[a-zA-Z0-9\_\.\-\,\/]+$/u',$_POST['stg']))) {
	echo "<font color=\"red\">I'm sorry, but it seems that the content of your STG file is not correct (bad characters?). Please check again.</font><br />";
	$false='1';
	exit;
	}
else
	{
	echo "<font color=\"green\">Content of the STG file seems correct, now proceeding with in-depth checks...</font><br /></br>";
	}
	
// If there is no false, generating SQL to be inserted into the database pending requests table.

if ($false==0)
{
	$tab_lignes = explode("\n",$_POST['stg']);		// Exploding lines by carriage return (\n) in submission input.
	$tab_lignes = array_map('trim',$tab_lignes);		// Removes blank lines.
	$tab_lignes = array_filter($tab_lignes);		// Removes blank lines.
	$tab_lignes = array_slice($tab_lignes,0,100);		// Sélectionne les 15 premiers éléments du tableau (soit les 15 premières lignes non vides)

	$nb_lignes = count($tab_lignes);
	$global_ko = 0;						// Validates - or no - the right to go further.
	$cpt_err = 0;						// Counts the number of errors.
	
	echo '<center>Counted a number of '.$nb_lignes.' lines submitted.</center><br />';
	
	// Limit the line numbers to 
	
	if ($nb_lignes>'100') { echo "<font color=\"red\">Too many lines submitted: 100 lines maximum per submission!</font>"; exit; }
	if ($nb_lignes<'1') { echo "<font color=\"red\">No enough lines submitted: 1 line minimum per submission!</font>"; exit; }
	$i = 0;
	$ko = 0;
	echo "<center>Now proceeding with in-depth checks!</center><br />";
	echo "<center>\n";
	echo "<table>\n";
	echo "<tr>\n<td><center>Line #</center></td>\n<td><center>Type</center></td>\n<td><center>Model</center></td>\n<td><center>Longitude</center></td>\n<td><center>Latitude</center></td>\n<td><center>Elevation</center></td>\n<td><center>Orientation</center></td>\n<td><center>Result</center></td>\n</tr>\n";
	
	foreach ($tab_lignes as $value) // Now printing the lines...
	{
	echo "<tr>";
	echo "<td><center>".($i+1)."</center></td>";
	$tab_tags = explode(" ",$value);
		$j=1;
		foreach ($tab_tags as $value_tag) // !=> Have also to check the number of tab_tags returned!
		{
		if($j=="1") // Checking Label (must contain only letters and be strictly labelled OBJECT_SHARED for now)
			{
			if(!strcmp($value_tag,"OBJECT_SHARED"))	echo "<td><center>".$value_tag."</center></td> ";
			else
				{
				echo "<td><font color=\"red\"><center>Object type Error!</center></font></td>";
				$ko=1;
				$global_ko=1;
				$cpt_err++;
				}
			}
			else if($j=="2") // Checking Shared model (Contains only figures, letters, _/. and must exist in DB)
				{
					if(!(preg_match("/^[a-z0-9_/.-]$/i",$value_tag)))
					{
					$return_value = model_exists($value_tag);
					if ($return_value == '0') { echo "<td><center>".$value_tag."</td></center>"; $model_id=ob_model_from_name($value_tag); }
						else if($return_value == '1') { echo "<td><center><font color=\"red\">Bad model label!</font></td></center>"; $ko=1; $global_ko=1; $cpt_err++; }
							else if($return_value == '2') { echo "<td><center><font color=\"red\">Object unknown!</font></center></td>"; $ko=1; $global_ko=1; $cpt_err++; }
								else if($return_value == '3') { echo "<td><center><font color=\"red\">Family unknown!</font></center></td>"; $ko=1; $global_ko=1; $cpt_err++; }
					}
					else
					{
					echo "<td><font color=\"red\"><center>Object Error!</center></font></td>";
					$ko=1;
					$global_ko=1;
					$cpt_err++;
					}
				} 
				else if ($j=="3") // Checking Longitude, must contain only figures and ., be >-180 and <180, be 13 characters max.
				{
					if((strlen($value_tag)<='13') && ($value_tag<='180') && ($value_tag>='-180') && (preg_match('/^[0-9\-\.]+$/',$value_tag))) { echo "<td><center>".$value_tag."</center></td>"; $long=$value_tag; }
					else
					{
					echo "<td><font color=\"red\"><center>Longitude Error!</center></font></td>";
					$ko=1;
					$global_ko=1;
					$cpt_err++;
					} 
				}	
				else if($j=="4") // Checking Latitude, must contain only figures, - and ., be >-90 and <90, be 13 characters max.
				{
					if((strlen($value_tag)<='13') && ($value_tag<='90') && ($value_tag>='-90') && (preg_match('/^[0-9\-\.]+$/',$value_tag))) { echo "<td><center>".$value_tag."<center></td>"; $lat=$value_tag; }
					else
					{
					echo "<td><font color=\"red\"><center>Latitude Error!</center></font></td>";
					$ko=1;
					$global_ko=1;
					$cpt_err++;
					}
				}
				
				// Should we check that there is no other object declared at this position ? - we don't do it for unitary adding.
				
				if($j=="5") // Checking Elevation, must contain only figures and, be >=0 and <= 360 and max 6 characters
				{
					if((strlen($value_tag)<='10') && (preg_match('/^[0-9\.]+$/',$value_tag)) && (strlen($value_tag)<=6)) {
						echo "<td><center>".$value_tag."</center></td>"; $gndelev=$value_tag; }
					else
					{
					echo "<td><font color=\"red\"><center>Elevation Error!</center></font></td>";
					$ko=1;
					$global_ko=1;
					$cpt_err++;
					}
				}
				else if($j=="6") // Checking Orientation, must contain only figures, be >0, be 7 characters max.
				{
					if((strlen($value_tag)<='7') && ($value_tag>='0') && (preg_match('/^[0-9\.]+$/',$value_tag)))
 					{
						echo "<td><center>".$value_tag."</center></td> "; $orientation=$value_tag; }
					else
					{
					echo "<td><font color=\"red\"><center>Orientation Error!</center></font></td>";
					$ko=1;
					$global_ko=1;
					$cpt_err++;
					}
				}
		$j++;
		}
	if ($ko=='0')
		{
		echo "<td><font color=\"green\"><center>OK</center></font></td>";
		$data_rw[$i]="('', ST_PointFromText('POINT(".$long." ".$lat.")', 4326), ".$gndelev.", NULL, ".heading_stg_to_true($orientation).", ".$model_id.", 1)"; 
		}
		else echo "<td><font color=\"red\"><center>KO</center></font></td>"; // Good or not ?
	echo "</tr>\n";	// Finishes the line.
	$i++; 			// Increments the line number.
	$ko=0; 			// Resets the local KO to "0".
	}
	echo "</table>\n";
	echo "</center>\n<br />";
	
	if($global_ko == '1') // If errors have been found...
	{
	if($cpt_err == '1') echo "<font color=\"red\"><center>".$cpt_err." error has been found in your submission. Please correct or delete the corresponding line from your submission before submitting again.</center></font><br />";
		else echo "<font color=\"red\"><center>".$cpt_err." errors have been found in your submission. Please correct or delete the corresponding line from your submission before submitting again.</center></font><br />";
	exit;
	}
	else // Else, proceed on with the request generation
	echo "<font color=\"green\"><center>No error has been found in your submission, all fields have been checked and seem to be OK to be proceeded.</center></font><br />";
	$query_rw="INSERT INTO fgsoj_objects (ob_text, wkb_geometry, ob_gndelev, ob_elevoffset, ob_heading, ob_model, ob_group) VALUES ";
	for ($j=0;$j<$nb_lignes;$j++) // For each line, add the data content to the request
	{
		if($j == ($nb_lignes-1)) $data_query_rw = $data_query_rw.$data_rw[$j].";";
	    else $data_query_rw = $data_query_rw.$data_rw[$j].", ";
	}

	$mass_rw_query = $query_rw.$data_query_rw;
	
	// Generating the SHA-256 hash based on the data we've received + microtime (ms) + IP + request. Should hopefully be enough ;-)

	$sha_to_compute = "<".microtime()."><".$_POST['IPAddr']."><".$mass_rw_query.">";
	$sha_hash = hash('sha256', $sha_to_compute);
	
	// Zipping the Base64'd request.
	
	$zipped_base64_rw_query = gzcompress($mass_rw_query,8);
	
	// Coding in Base64.
	
	$base64_rw_query = base64_encode($zipped_base64_rw_query);
	
	// Opening database connection...

	$resource_rw = connect_sphere_rw();
	
	// Sending the request...
	
	$query_rw_pending_request = "INSERT INTO fgs_position_requests (spr_hash, spr_base64_sqlz) VALUES ('".$sha_hash."', '".$base64_rw_query."');";
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
	echo "Your submission has been successfully queued into the FG scenery database update requests!<br />";
	echo "Unless it's rejected, it should appear in Terrasync within a few days.<br />";
	echo "The FG community would like to thank you for your contribution!<br />";
	echo "Want to submit another position ?<br /> <a href=\"http://scenemodels.flightgear.org/submission/\">Click here to go back to the submission page.</a>";

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

	$subject = "[FG Scenery Submission forms] Automatic mass shared model position request: needs validation.";

	// Generating the message and wrapping it to 77 signs per HTML line (asked by Martin). But warning, this must NOT cut an URL, or this will not work.

	$message0 = "Hi," . "\r\n" .
	            "This is the automated FG scenery submission PHP form at:" . "\r\n" .
		    "http://scenemodels.flightgear.org/submission/check_mass_shared_import.php" . "\r\n" .
	  	    "I just wanted to let you know that a new shared objects mass insertion request is pending." . "\r\n" .
	 	    "On ".$dtg." UTC, user with the IP address ".$ipaddr." (".$host.") issued this request." . "\r\n" .
		    "Associated comment: ".$sent_comment;
		   
	$message077 = wordwrap($message0, 77, "\r\n");

	// There is no possibility to wrap the URL or it will not work, nor the rest of the message (short lines), or it will not work.

	$message1 = "\r\n".
		    "Now please click:" . "\r\n" .
		    "http://scenemodels.flightgear.org/submission/shared/mass_submission.php?action=check&sig=". $sha_hash ."\r\n" .
		    "to check and confirm or reject the submission" . "\r\n" .
		    "Thanks!" ;

	// Preparing the headers.

	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "From: \"FG Scenery Submission forms\" <martin.spott@mgras.net>" . "\r\n";
	$headers .= "X-Mailer: PHP-" . phpversion() . "\r\n";

	// Let's send it ! No management of mail() errors to avoid being too talkative...

	$message = $message077.$message1;

	@mail($to, $subject, $message, $headers);
	}
}

?>
<?php include '../../inc/footer.php'; ?>
<?php
}
}
?>
