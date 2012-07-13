<?php

// Inserting libs
require_once('../../inc/functions.inc.php');

// Final step to deletion
if((isset($_POST['step'])) && ($_POST['step'] == 3) && (isset($_POST['delete_choice']))) {

    // Captacha stuff
    require_once('../../inc/captcha/recaptchalib.php');

    // Private key is needed for the server-to-Google auth.
    $privatekey = "6Len6skSAAAAACnlhKXCda8vzn01y6P9VbpA5iqi";
    $resp = recaptcha_check_answer ($privatekey,
                                    $_SERVER["REMOTE_ADDR"],
                                    $_POST["recaptcha_challenge_field"],
                                    $_POST["recaptcha_response_field"]);

    // What happens when the CAPTCHA was entered incorrectly
    if (!$resp->is_valid) {
        $page_title = "Automated Shared Models Positions Deletion Form";
        include '../../inc/header.php';
        echo "<br />";
        die ("<center>Sorry but the reCAPTCHA wasn't entered correctly. <a href='http://scenemodels.flightgear.org/submission/shared/index_delete.php'>Go back and try it again</a><" .
             "<br />(reCAPTCHA complained: " . $resp->error . ")</center>");
    }
    else {
        $page_title = "Automated Shared Models Positions Deletion Form";
        include '../../inc/header.php';
        $id_to_delete = pg_escape_string(stripslashes($_POST['delete_choice']));
        echo "<br /><center><font color=\"green\">You have asked to delete object #".$id_to_delete."</font></center><br />";

        // Should in fact be somewhere like here. Checking that comment exists. Just a small verification as it's not going into DB.

        // (preg_match('/^[A-Za-z0-9 \-\.\,]+$/u',$_POST['comment']))
        //if((isset($_POST['comment'])) && ((strlen($_POST['comment'])>0)) && ((strlen($_POST['comment']))<=100)) {
        //    $sent_comment = pg_escape_string(stripslashes($_POST['comment']));
        //}
        //else {
        //    echo "<font color=\"red\">Comment mismatch!</font><br />";
        //    $false='1';
        //}

        // Checking that email is valid (if it exists).
        //(filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))

        $failed_mail = 0;
        if((isset($_POST['email'])) && ((strlen($_POST['email'])) > 0) && ((strlen($_POST['email']) <= 50))) {
            $safe_email = pg_escape_string(stripslashes($_POST['email']));
            echo "<center><font color=\"green\">Email: ".$safe_email."</font></center><br />";
        }
        else {
            echo "<center><font color=\"red\">No email was given (not mandatory) or email mismatch!</font></center><br />";
            $failed_mail = 1;
         }

        // Preparing the deletion request
        $query_delete = "DELETE from fgs_objects where ob_id=".$id_to_delete.";";

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
    if(!$resultrw) {
        echo "<center></center>Sorry, but the query could not be processed. Please ask for help on the <a href='http://www.flightgear.org/forums/viewforum.php?f=5'>Scenery forum</a> or on the devel list.</center><br />";
        include '../../inc/footer.php';
        exit;
    }
    else {
        echo "<center>Your position has been successfully queued into the FG scenery database deletion requests!<br />";
        echo "Unless it's rejected, the object should be dropped in Terrasync within a few days.<br />";
        echo "The FG community would like to thank you for your contribution!<br />";
        echo "Want to delete or submit another position ?<br /> <a href=\"http://scenemodels.flightgear.org/submission/shared/\">Click here to go back to the submission page.</a></center>";

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
        //$object_url = "http://scenemodels.flightgear.org/modeledit.php?id=".$_POST['model_name'];
        //$html_object_url = htmlspecialchars($object_url);

        // Generating the message and wrapping it to 77 signs per HTML line (asked by Martin). But warning, this must NOT cut an URL, or this will not work.
        if($failed_mail != 1) {
            $message0 = "Hi," . "\r\n" .
                        "This is the automated FG scenery submission PHP form at:" . "\r\n" .
                        "http://scenemodels.flightgear.org/submission/check_shared.php" . "\r\n" .
                        "I just wanted to let you know that a new shared object position DELETION request is pending." . "\r\n" .
                        "On ".$dtg." UTC, user with the IP address ".$ipaddr." (".$host.") and with email address ".$safe_email."\r\n" .
                        "issued the following request:" . "\r\n";
        }
        else {
            $message0 = "Hi," . "\r\n" .
                        "This is the automated FG scenery submission PHP form at:" . "\r\n" .
                        "http://scenemodels.flightgear.org/submission/check_shared.php" . "\r\n" .
                        "I just wanted to let you know that a new shared object position DELETION request is pending." . "\r\n" .
                        "On ".$dtg." UTC, user with the IP address ".$ipaddr." (".$host.") issued the following request:" . "\r\n";
        }
        $message077 = wordwrap($message0, 77, "\r\n");

        // There is no possibility to wrap the URL or it will not work, nor the rest of the message (short lines), or it will not work.
        $message1 = "Object #: " .$id_to_delete. "\r\n" .
                    "Family: " .get_object_family_from_id($id_to_delete). "\r\n" .
                    "Object: " .object_name(get_object_model_from_id($id_to_delete)). "\r\n" .
                    "Latitude: " .get_object_latitude_from_id($id_to_delete). "\r\n" .
                    "Longitude: " .get_object_longitude_from_id($id_to_delete). "\r\n" .
                    "Ground elevation: " .get_object_elevation_from_id($id_to_delete). "\r\n" .
                    "Elevation offset: " .get_object_offset_from_id($id_to_delete). "\r\n" .
                    "True (DB) orientation: " .get_object_true_orientation_from_id($id_to_delete). "\r\n" .
                    "Comment: " .strip_tags($_POST['comment']). "\r\n" .
                    "Please click:". "\r\n" .
                    "http://mapserver.flightgear.org/submap/?lon=" .get_object_longitude_from_id($id_to_delete). "&lat=" .get_object_latitude_from_id($id_to_delete). "&zoom=15"."\r\n" .
                    "to locate the object on the map.";

        $message2 = "\r\n".
                    "Now please click:" . "\r\n" .
                    "http://scenemodels.flightgear.org/submission/shared/submission.php?action=confirm&sig=". $sha_hash ."&email=". $safe_email."\r\n" .
                    "to confirm the deletion" . "\r\n" .
                    "or" . "\r\n" .
                    "http://scenemodels.flightgear.org/submission/shared/submission.php?action=reject&sig=". $sha_hash ."&email=". $safe_email."\r\n" .
                    "to reject the deletion." . "\r\n" . "\r\n" .
                    "Thanks!";

        // Preparing the headers.
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "From: \"FG Scenery Deletion forms\" <martin.spott@mgras.net>" . "\r\n";
        $headers .= "X-Mailer: PHP-" . phpversion() . "\r\n";

        // Let's send it ! No management of mail() errors to avoid being too talkative...
        $message = $message077.$message1.$message2;
        @mail($to, $subject, $message, $headers);

        // Mailing the submitter
        if($failed_mail != 1) {

            // Tell the submitter that its submission has been sent for validation.
            $to = $safe_email;

            // What is the subject ?
            $subject = "[FG Scenery Submission forms] Automatic shared model position deletion request.";

            // Correctly set the object URL.
            //$family_url = "http://scenemodels.flightgear.org/modelbrowser.php?shared=".$family_id;
            //$object_url = "http://scenemodels.flightgear.org/modeledit.php?id=".$model_id;
            //$html_family_url = htmlspecialchars($family_url);
            //$html_object_url = htmlspecialchars($object_url);

            // Generating the message and wrapping it to 77 signs per HTML line (asked by Martin). But warning, this must NOT cut an URL, or this will not work.
            $message3 = "Hi," . "\r\n" .
                        "This is the automated FG scenery submission PHP form at:" . "\r\n" .
                        "http://scenemodels.flightgear.org/submission/check_delete_shared.php" . "\r\n" .
                        "On ".$dtg." UTC, user with the IP address ".$ipaddr." (".$host."), which is thought to be you, issued the following request." . "\r\n" .
                        "Just to let you know that this shared object position deletion request has been sent for validation." . "\r\n" .
                        "The first part of the unique of this request is ".substr($sha_hash,0,10). "..." . "\r\n" .
                        "If you have not asked for anything, or think this is a spam, please read the last part of this email." ."\r\n";
            $message077 = wordwrap($message3, 77, "\r\n");

            // There is no possibility to wrap the URL or it will not work, nor the rest of the message (short lines), or it will not work.
            $message4 = "Family: " .get_object_family_from_id($id_to_delete). "\r\n" .
                        "Object: " .object_name(get_object_model_from_id($id_to_delete)). "\r\n" .
                        "Latitude: " .get_object_latitude_from_id($id_to_delete). "\r\n" .
                        "Longitude: " .get_object_longitude_from_id($id_to_delete). "\r\n" .
                        "Ground elevation: " .get_object_elevation_from_id($id_to_delete). "\r\n" .
                        "Elevation offset: " .get_object_offset_from_id($id_to_delete). "\r\n" .
                        "True (DB) orientation: " .get_object_true_orientation_from_id($id_to_delete). "\r\n" .
                        "Comment: " .strip_tags($_POST['comment']) ."\r\n".
                        "Please click:" . "\r\n" .
                        "http://mapserver.flightgear.org/submap/?lon=". get_object_longitude_from_id($id_to_delete) ."&lat=". get_object_latitude_from_id($id_to_delete) ."&zoom=14" . "\r\n" .
                        "to locate the object on the map." . "\r\n" .
                        "This process has been going through antispam measures. However, if this email is not sollicited, please excuse-us and report at http://www.flightgear.org/forums/viewtopic.php?f=5&t=14671";

            // Preparing the headers.
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "From: \"FG Scenery Submission forms\" <martin.spott@mgras.net>" . "\r\n";
            $headers .= "X-Mailer: PHP-" . phpversion() . "\r\n";

            // Let's send it ! No management of mail() errors to avoid being too talkative...
            $message = $message077.$message4;
            @mail($to, $subject, $message, $headers);
        }
    include '../../inc/footer.php';
    exit;
    }
}
}
else {

// Checking DB availability before all
$ok = check_availability();

if(!$ok) {
    $page_title = "Automated Shared Models Positions Deletion Form";
    include '../../inc/header.php';
?>
    <br /><br />
    <p class="center"><font color="red">Sorry, but the database is currently unavailable. We are doing the best to put it back up online. Please come back again soon.</font></p>
    <br /><center>The FlightGear team.</center>
    <?php include '../../inc/footer.php';
    exit;
    }
else {
    $page_title = "Automated Shared Models Positions Deletion Form";
    include '../../inc/header.php';
?>
<br />
<?php
$false = 0;
global $false;

    if((isset($_POST['delete_choice'])) || (isset($_GET['delete_choice']))) { // We can directly retrieve the object ID through the other forms, therefore no test is needed.
        $false = 0;
    }
    else {
        // Checking that latitude exists, is of good length and is containing only digits, - or ., is >=-90 and <=90 and with correct decimal format.
        // (preg_match('/^[0-9\-\.]+$/u',$_POST['latitude']))
        if((isset($_POST['latitude'])) && ((strlen($_POST['latitude'])) <= 13) && ($_POST['latitude'] <= 90) && ($_POST['latitude'] >= -90)) {
            $lat = number_format(pg_escape_string(stripslashes($_POST['latitude'])),7,'.','');
        }
        else {
            echo "<center><font color=\"red\">Latitude mismatch!</font></center><br />";
            $false = 1;
        }

        // Checking that longitude exists, if of good length and is containing only digits, - or ., is >=-180 and <=180 and with correct decimal format.
        // (preg_match('/^[0-9\-\.]+$/u',$_POST['longitude']))
        if((isset($_POST['longitude'])) && ((strlen($_POST['longitude'])) <= 13)  && ($_POST['longitude'] <= 180) && ($_POST['longitude'] >= -180)) {
                $long = number_format(pg_escape_string(stripslashes($_POST['longitude'])),7,'.','');
        }
        else {
            echo "<center><font color=\"red\">Longitude mismatch!</font><br /></center>";
            $false = 1;
        }
    }

// If there is no false, generating SQL to be inserted into the database pending requests table.

if ($false == 0) {

    // Opening database connection...
    $resource_r_deletion = connect_sphere_r();

    if((isset($_POST['delete_choice'])) || (isset($_GET['delete_choice']))) { // Id the delete_choice is sent directly to us from a webform "outside" the submission world
        if((isset($_POST['delete_choice']))) {
            $delete_choice = $_POST['delete_choice'];
        }
        else {
            $delete_choice = $_GET['delete_choice'];
        }
    // Let's grab the information about this object from the database
    $query_pos = "SELECT ob_id, ob_modified FROM fgs_objects WHERE ob_id = ".$delete_choice.";";
    $result = @pg_query($resource_r_deletion, $query_pos);
    $returned_rows = pg_num_rows($result);
    }
    else {
        // Let's see in the database if something exists at this position
        $query_pos = "SELECT ob_id, ob_modified FROM fgs_objects WHERE wkb_geometry = ST_PointFromText('POINT(".$long." ".$lat.")', 4326);";
        $result = @pg_query($resource_r_deletion, $query_pos);
        $returned_rows = pg_num_rows($result);
    }

    // We have only one result
    if ($returned_rows == 0) {
        echo "<br /><center></center><font color=\"red\">Sorry, but no object was found at position longitude: ".$long.", latitude: ".$lat.". Please <a href=\"index_delete.php\">go back and check your position</a> (see in the relevant STG file).</font></center><br/>";
        include '../../inc/footer.php';
        exit;
    }

    // We have more than one result
    else {
        if ($returned_rows == 1) {
            while ($row = pg_fetch_row($result)) {
            echo "<center>You have asked to delete object #".$row[0].".</center><br />";
            ?>
            <form name="delete_position" method="post" action="http://scenemodels.flightgear.org/submission/shared/check_delete_shared.php">
            <table>
            <tr>
                <td><span title="This is the family name of the object you want to delete."><a style="cursor: help;">Object's family</a></span></td>
                <td colspan="4"><?php $family_name = get_object_family_from_id($row[0]); echo $family_name; ?></td>
            </tr>
            <tr>
                <td><span title="This is the name of the object you want to delete, ie the name as it's supposed to appear in the .stg file."><a style="cursor: help; ">Model name</a></span></td>
                <td colspan="4"><?php $model_name = object_name(get_object_model_from_id($row[0]));  echo $model_name; ?></td>
            </tr>
            <tr>
                <td><span title="This is the WGS84 longitude of the object you want to update. Has to be between -180.000000 and +180.000000."><a style="cursor: help; ">Longitude</a></span></td>
                <td colspan="4"><?php $longitude = get_object_longitude_from_id($row[0]); echo $longitude; ?></td>
            </tr>
            <tr>
                <td><span title="This is the WGS84 latitude of the object you want to update. Has to be between -90.000000 and +90.000000."><a style="cursor: help; ">Latitude</a></span></td>
                <td colspan="4"><?php $latitude = get_object_latitude_from_id($row[0]); echo $latitude; ?></td>
            </tr>
            <tr>
                <td><span title="This is the last update or submission date/time of the corresponding object."><a style="cursor: help; ">Date/Time of last update</a></span></td>
                <td colspan="4"><?php echo $row[1]; ?></td>
            </tr>
            <tr>
                <td><span title="This is the ground elevation (in meters) of the position where the object you want to delete is located. Warning: if your model is sunk into the ground, the Elevation offset field is set below."><a style="cursor: help; ">Elevation</a></span></td>
                <td colspan="4"><?php $elevation = get_object_elevation_from_id($row[0]); echo $elevation; ?></td>
            </tr>
            <tr>
                <td><span title="This is the offset (in meters) between your model 'zero' and the elevation at the considered place (ie if it is sunk into the ground)."><a style="cursor: help; ">Elevation Offset</a></span></td>
                <td colspan="4"><?php $offset = get_object_offset_from_id($row[0]); echo $offset; ?></td>
            </tr>
            <tr>
                <td><span title="The orientation of the object you want to delete - as it appears in the STG file (this is NOT the true heading). Let 0 if there is no specific orientation."><a style="cursor: help; ">Orientation</a></span></td>
                <td colspan="4"><?php $orientation = heading_true_to_stg(get_object_true_orientation_from_id($row[0])); echo $orientation; ?></td>
            </tr>
            <tr>
                <td><span title="This is the picture of the object you want to delete"><a style="cursor: help; ">Picture</a></span></td>
                <td><center><a href="http://scenemodels.flightgear.org/modeledit.php?id=<?php $model_id = get_object_model_from_id($row[0]); echo $model_id; ?>"><img src="http://scenemodels.flightgear.org/modelthumb.php?id=<?php echo $model_id; ?>"></a></center></td>
                <td><center><span title="This is the map around the object you want to delete"><a style="cursor: help; ">Map</a></span></center></td>
                <td><center><iframe src="http://mapserver.flightgear.org/submap/?lon=<? echo $longitude; ?>&lat=<? echo $latitude; ?>&zoom=14" width="300" height="225" scrolling="auto" marginwidth="2" marginheight="2" frameborder="0"></iframe></center></td>
            </tr>
            <tr>
                <td><span title="Please add a short (max 100 letters) statement why you are deleting this data. This will help the maintainers understand what you are doing. eg: this model is in a river, so please delete it"><a style="cursor: help">Comment</a></span></td>
                <td colspan="4"><input type="text" name="comment" maxlength="100" size="40" value="" /></td>
            </tr>
            <tr>
                <td><span title="Please live YOUR VALID email address over here. This will help you be informed of your submission process. EXPERIMENTAL"><a style="cursor:help">Email address (EXPERIMENTAL and not mandatory)</a></span></td>
                <td colspan="4"><input type="text" name="email" maxlength="50" size="40" value="" /></td>
            </tr>
                <input name="delete_choice" type="hidden" value="<?php echo $row[0]; ?>" />
                <input name="step" type="hidden" value="3" />
                <input name="IPAddr" type="hidden" value="<?php echo $_SERVER[REMOTE_ADDR]; ?>" />
            <tr>
                <td colspan="4">
                <center>
                <?php
                // Google Captcha stuff
                require_once('../../inc/captcha/recaptchalib.php');
                $publickey = "6Len6skSAAAAAB1mCVkP3H8sfqqDiWbgjxOmYm_4";
                echo recaptcha_get_html($publickey);
                ?>
                <br />
                <input type="submit" name="submit" value="Delete this object!" />
                <input type="button" name="cancel" value="Cancel - Do not delete!" onclick="history.go(-1)"/>
                </center>
                </td>
            </tr>
            </table>
            </form>
            <?php include '../../inc/footer.php';
            }
        exit;
        }

        // If we have more than one solution
        else if($returned_rows > 1) {
            echo "<br /><center></center>".$returned_rows." objects with WGS84 coordinates longitude: ".$long.", latitude: ".$lat." have been found in the database.<br />Please select with the left radio button the one you want to delete.</center><br />";

            // Starting multi-solutions form
            echo "<form name=\"delete_position\" method=\"post\" action=\"http://scenemodels.flightgear.org/submission/shared/check_delete_shared.php\"\">";
            echo "<table>";

            $i = 1; // Just used to put the selected button on the first entry
            while ($row = pg_fetch_row($result)) {
            ?>
            <tr>
                <td colspan="5" background="white"><center><b>Object number #<?php echo $row[0]; ?></b></center></td>
            </tr>
            <tr>
                <th rowspan="6">
                <?php
                if ($i == 1) {
                    echo "<input type=\"radio\" name=\"delete_choice\" value=\"".$row[0]."\" checked />";
                }
                else echo "<input type=\"radio\" name=\"delete_choice\" value=\"".$row[0]."\" />";
                ?>
                </th>
                <td><span title="This is the family name of the object you want to delete."><a style="cursor: help;">Object's family</a></span></td>
                <td colspan="4"><?php $family_name = get_object_family_from_id($row[0]); echo $family_name; ?></td>
            </tr>
            <tr>
                <td><span title="This is the name of the object you want to delete, ie the name as it's supposed to appear in the .stg file."><a style="cursor: help; ">Model name</a></span></td>
                <td colspan="4"><?php $model_name = object_name(get_object_model_from_id($row[0]));  echo $model_name; ?></td>
            </tr>
            <tr>
                <td><span title="This is the WGS84 longitude of the object you want to update. Has to be between -180.000000 and +180.000000."><a style="cursor: help; ">Longitude</a></span></td>
                <td colspan="4"><?php $longitude = get_object_longitude_from_id($row[0]); echo $longitude; ?></td>
            </tr>
            <tr>
                <td><span title="This is the WGS84 latitude of the object you want to update. Has to be between -90.000000 and +90.000000."><a style="cursor: help; ">Latitude</a></span></td>
                <td colspan="4"><?php $latitude = get_object_latitude_from_id($row[0]); echo $latitude; ?></td>
            </tr>
            <tr>
                <td><span title="This is the last update or submission date/time of the corresponding object."><a style="cursor: help; ">Date/Time of last update</a></span></td>
                <td colspan="4"><?php echo $row[1]; ?></td>
            </tr>
            <tr>
                <td><span title="This is the ground elevation (in meters) of the position where the object you want to delete is located. Warning: if your model is sunk into the ground, the Elevation offset field is set below."><a style="cursor: help; ">Elevation</a></span></td>
                <td colspan="4"><?php $elevation = get_object_elevation_from_id($row[0]); echo $elevation; ?></td>
            </tr>
            <tr>
                <td><span title="This is the offset (in meters) between your model 'zero' and the elevation at the considered place (ie if it is sunk into the ground)."><a style="cursor: help; ">Elevation Offset</a></span></td>
                <td colspan="4"><?php $offset = get_object_offset_from_id($row[0]); echo $offset; ?></td>
            </tr>
            <tr>
                <td><span title="The orientation of the object you want to delete - as it appears in the STG file (this is NOT the true heading). Let 0 if there is no specific orientation."><a style="cursor: help; ">Orientation</a></span></td>
                <td colspan="4"><?php $orientation = heading_true_to_stg(get_object_true_orientation_from_id($row[0])); echo $orientation; ?></td>
            </tr>
            <tr>
                <td><span title="This is the picture of the object you want to delete"><a style="cursor: help; ">Picture</a></span></td>
                <td><center><a href="http://scenemodels.flightgear.org/modeledit.php?id=<?php $model_id = get_object_model_from_id($row[0]); echo $model_id; ?>"><img src="http://scenemodels.flightgear.org/modelthumb.php?id=<?php echo $model_id; ?>"></a></center></td>
                <td><center><span title="This is the map around the object you want to delete"><a style="cursor: help; ">Map</a></span></center></td>
                <td><center><iframe src="http://mapserver.flightgear.org/submap/?lon=<? echo $longitude; ?>&lat=<? echo $latitude; ?>&zoom=14" width="300" height="225" scrolling="auto" marginwidth="2" marginheight="2" frameborder="0"></iframe></center></td>
            </tr>
                <?php
                $i++;
            }
                ?>
            <tr>
                <td><span title="Please add a short (max 100 letters) statement why you are deleting this data. This will help the maintainers understand what you are doing. eg: this model is in a river, so please delete it"><a style="cursor: help">Comment</a></span></td>
                <td colspan="4"><input type="text" name="comment" maxlength="100" size="40" value="" /></td>
            </tr>
            <tr>
                <td><span title="Please live YOUR VALID email address over here. This will help you be informed of your submission process. EXPERIMENTAL"><a style="cursor:help">Email address</a></span></td>
                <td colspan="4"><input type="text" name="email" maxlength="50" size="40" value="" /></td>
            </tr>
            <tr>
                <td colspan="5">
                <center>
                <input name="delete_choice" type="hidden" value="<?php echo $row[0]; ?>" />
                <input name="IPAddr" type="hidden" value="<?php echo $_SERVER[REMOTE_ADDR]; ?>" />
                <input name="step" type="hidden" value="3" />
                <?php
                // Google Captcha stuff
                require_once('../../inc/captcha/recaptchalib.php');
                $publickey = "6Len6skSAAAAAB1mCVkP3H8sfqqDiWbgjxOmYm_4";
                echo recaptcha_get_html($publickey);
                ?>
                <br />
                <input type="submit" name="submit" value="Delete this object!" />
                <input type="button" name="cancel" value="Cancel - Do not delete!" onclick="history.go(-1)"/>
                </center>
                </td>
            </tr>
            </table>
            </form>
            <?php include '../../inc/footer.php';
        exit();
        }
    }
}
include '../../inc/footer.php';
}
}
?>
