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
			<title>Automated Shared Models Positions Pending Requests Form</title>
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

	// Check the presence of "action", the presence of "signature", its length (64) and its content.

	if((isset($_GET["action"])) && (isset($_GET["sig"])) && (strlen($_GET["sig"])==64) && preg_match("/[0-9a-z]/",$_GET["sig"]) && ($_GET["action"] == 'confirm'))
	{
		
			$resource_rw = connect_sphere_rw();
			
			// If connection is OK

			if($resource_rw!='0')
			{

			// Checking the presence of sig into the database

				$result = @pg_query($resource_rw,"select spr_hash, spr_base64_sqlz from fgs_position_requests where spr_hash = '". $_GET["sig"] ."';");
					if (pg_num_rows($result) != '1')
					{
					?>
						<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
						"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
						<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
						<head>
						<title>Automated Shared Models Positions Pending Requests Form</title>
						<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
						<link rel="stylesheet" href="../../style.css" type="text/css"></link>
						</head>
						<body>
						<?php include '/home/jstockill/scenemodels/header.php';
						echo "<font color=\"red\">Sorry but the request you are asking for does not exist into the database. Maybe it has already been validated by someone else?</font><br />\n";
						echo "Else, please report to devel ML or FG Scenery forum<br />.";
						echo "</body></html>";
						@pg_close($resource_rw);
						exit;
					}
					else
					{
					if($_GET["action"] == 'confirm')	// If action comes from the unitary insertion script
					{
					while ($row = pg_fetch_row($result))
					{			
						$sqlzbase64 = $row[1];
						
						// Base64 decode the query
					
						$sqlz = base64_decode($sqlzbase64);
											
						// Gzuncompress the query

						$query_rw = gzuncompress($sqlz);
						
						// Sending the request...

						$resultrw = @pg_query($resource_rw,$query_rw);
									
						if(!$resultrw)
						{
							?>
							<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
							"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
							<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
							<head>
							<title>Automated Shared Models Positions Pending Requests Form</title>
							<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
							<link rel="stylesheet" href="../../style.css" type="text/css"></link>
							</head>
							<body>
							<?php include '/home/jstockill/scenemodels/header.php';
							echo "Signature found.<br /> Now processing query with request number ". $_GET[sig].".<br /><br />";
							echo "<font color=\"red\">Sorry, but the INSERT or DELETE or UPDATE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</font><br />";
							
							// Closing the rw connection.

							pg_close($resource_rw);
							
							exit;
						}
						else
						{	
							?>
							<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
							"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
							<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
							<head>
							<title>Automated Shared Models Positions Pending Requests Form</title>
							<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
							<link rel="stylesheet" href="../../style.css" type="text/css"></link>
							</head>
							<body>
							<?php include '/home/jstockill/scenemodels/header.php';
							echo "Signature found.<br /> Now processing INSERT or DELETE or UPDATE position query with number ". $_GET[sig].".<br /><br />";
							echo "<font color=\"green\">This query has been successfully processed into the FG scenery database! It should be taken into account in Terrasync within a few days. Thanks for your control!</font><br />";
							
							// Delete the entry from the pending query table.
					
							$delete_request = "delete from fgs_position_requests where spr_hash = '". $_GET["sig"] ."';";
							
							$resultdel = @pg_query($resource_rw,$delete_request);
							
							if(!resultdel)
							{
								echo "<font color=\"red\">Sorry, but the pending request DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</font><br />";
								// Closing the rw connection.

								pg_close($resource_rw);
								
								exit;
							}
							else
							{
								echo "<font color=\"green\">Entry correctly deleted from the pending request table.</font>";
								
								// Closing the rw connection.

								pg_close($resource_rw);
							
								// Sending mail if SQL was correctly inserted and entry deleted.

								// Sets the time to UTC.

								date_default_timezone_set('UTC');
								$dtg = date('l jS \of F Y h:i:s A');

								// OK, let's start with the mail redaction.

								// Who will receive it ?
								
								$to = "\"Olivier JACQ\" <olivier.jacq@free.fr>" . ", ";
								$to .= "\"Martin SPOTT\" <martin.spott@mgras.net>";

								// What is the subject ?

								$subject = "[FG Scenery Submission forms] Automatic shared model DB pending request process confirmation.";

								// Generating the message and wrapping it to 77 signs per line (asked by Martin). But warning, this must NOT cut an URL, or this will not work.

								$message0 = "Hi,"  . "\r\n" .
											"This is the automated FG scenery submission PHP form at:" . "\r\n" .
											"http://scenemodels.flightgear.org/submission/submission.php"  . "\r\n" .
											"I just wanted to let you know that the object position request nr :" . "\r\n" .
											$_GET[sig]. "\r\n" .
										    "has been succesfully treated in the fgs_objects table." . "\r\n" .
										    "The corresponding pending entry has consequently been deleted" . "\r\n" .
											"from the pending requests table.";
									   
								$message = wordwrap($message0, 77, "\r\n");
								
								// Preparing the headers.

								$headers = "MIME-Version: 1.0" . "\r\n";
								$headers .= "From: \"FG Scenery Pending Requests forms\" <martin.spott@mgras.net>" . "\r\n";
								$headers .= "X-Mailer: PHP-" . phpversion() . "\r\n";

								// Let's send it ! No management of mail() errors to avoid being too talkative...

								@mail($to, $subject, $message, $headers);
								exit;					
							}
						}
					}
					}
				}
			}
	}
			
	// If it's not to submit... it's to delete... check the presence of "action", the presence of "signature", its length (64), its content.
		
	else
	{
		if((isset($_GET["action"])) && (isset($_GET["sig"])) && (strlen($_GET["sig"])==64) && preg_match("/[0-9a-z]/",$_GET["sig"]) && ($_GET["action"]=='reject'))
			{
				$resource_rw = connect_sphere_rw();
			
				// If connection is OK

				if($resource_rw!='0')
				{

					// Checking the presence of sig into the database
					
					$delete_query = "select spr_hash from fgs_position_requests where spr_hash = '". $_GET["sig"] ."';";
					$result = @pg_query($delete_query);
					
					// If not ok...
					
					if (pg_num_rows($result) != '1')
					{
						?>
						<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
						"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
						<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
						<head>
						<title>Automated Shared Models Positions Pending Requests Form</title>
						<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
						<link rel="stylesheet" href="../../style.css" type="text/css"></link>
						</head>
						<body>
						<?php include '/home/jstockill/scenemodels/header.php';
						echo "<font color=\"red\">Sorry but the request you are asking for does not exist into the database. Maybe it has already been treated by someone else?</font><br />\n";
						echo "Else, please report to the devel mailing list or <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a>.<br />";
						echo "</body></html>";
						@pg_close($resource_rw);
						exit;
					}
					else
					{
						// Delete the entry from the pending query table.
							
						$delete_request = "delete from fgs_position_requests where spr_hash = '". $_GET["sig"] ."';";
						$resultdel = @pg_query($resource_rw,$delete_request);
							
						if(!resultdel)
						{
							?>
							<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
							"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
							<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
							<head>
							<title>Automated Shared Models Positions Pending Requests Form</title>
							<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
							<link rel="stylesheet" href="../../style.css" type="text/css">
							</head>
							<body>
							<?php include '/home/jstockill/scenemodels/header.php';
							echo "Signature found.<br /> Now deleting request with number ". $_GET[sig].".<br />";
							echo "<font color=\"red\">Sorry, but the DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</font><br />";
							
							// Closing the rw connection.

							pg_close($resource_rw);
							
							echo "</body></html>";
							exit;
						}
						else
						{
							?>
							<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
							"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
							<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
							<head>
							<title>Automated Shared Models Positions Pending Requests Form</title>
							<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
							<link rel="stylesheet" href="../../style.css" type="text/css"></link>
							</head>
							<body>
							<?php include '/home/jstockill/scenemodels/header.php';
							echo "Signature found.<br />Now deleting request with number ". $_GET[sig].".<br />";
							echo "<font color=\"green\">Entry has correctly been deleted from the pending requests table.</font>";
										
							// Closing the rw connection.

							pg_close($resource_rw);
						
							echo "</body></html>";
							
							// Sending mail if entry was correctly deleted.

							// Sets the time to UTC.

							date_default_timezone_set('UTC');
							$dtg = date('l jS \of F Y h:i:s A');

							// OK, let's start with the mail redaction.

							// Who will receive it ?
										
							$to = "\"Olivier JACQ\" <olivier.jacq@free.fr>" . ", ";
							$to .= "\"Martin SPOTT\" <martin.spott@mgras.net>";

							// What is the subject ?

							$subject = "[FG Scenery Submission forms] Automatic shared model DB deletion confirmation.";

							// Generating the message and wrapping it to 77 signs per line (asked by Martin). But warning, this must NOT cut an URL, or this will not work.

							$message0 = "Hi,"  . "\r\n" .
										"This is the automated FG scenery submission PHP form at:" . "\r\n" .
										"http://scenemodels.flightgear.org/submission/submission.php"  . "\r\n" .
										"I just wanted to let you know that the object position request nr:"  . "\r\n" .
										"" .$_GET[sig]. ""."\r\n" .
									    "has been succesfully deleted from the pending requests table.";
								   
							$message = wordwrap($message0, 77, "\r\n");
							
							// Preparing the headers.

							$headers = "MIME-Version: 1.0" . "\r\n";
							$headers .= "From: \"FG Scenery Pending Requests forms\" <martin.spott@mgras.net>" . "\r\n";
							$headers .= "X-Mailer: PHP-" . phpversion() . "\r\n";

							// Let's send it ! No management of mail() errors to avoid being too talkative...

							@mail($to, $subject, $message, $headers);
							exit;					
						}
					}
				}
			}
		
		// Sending the visitor elsewhere if he has no idea what he's doing here.
		
		else 
		{
		header("Location: /submission/shared/");   
		}
	}
}
?>