<?php

// Inserting libs
require_once('../../inc/functions.inc.php');

// Checking DB availability before all
$ok=check_availability();

if(!$ok) {
    $page_title = "Automated Shared Models Positions Submission Form";
    include '../../inc/header.php';
?>
<br /><br />
<p class="center"><font color="red">Sorry, but the database is currently unavailable. We are doing the best to put it back up online. Please come back again soon.</font></p>
<br /><center>The FlightGear team.</center>
<?php include '../../inc/footer.php';
}
else {

// Captcha stuff
require_once('../../inc/captcha/recaptchalib.php');

// Private key is needed for the server-to-Google auth.
$privatekey = "6Len6skSAAAAACnlhKXCda8vzn01y6P9VbpA5iqi";
$resp = recaptcha_check_answer ($privatekey,
                                $_SERVER["REMOTE_ADDR"],
                                $_POST["recaptcha_challenge_field"],
                                $_POST["recaptcha_response_field"]);

    // What happens when the CAPTCHA was entered incorrectly
    if (!$resp->is_valid) {
        $page_title = "Automated Shared Models Positions Submission Form";
        include '../../inc/header.php';

        echo "<br />";
        die ("<center>Sorry but the reCAPTCHA wasn't entered correctly. <a href=\"javascript:history.back()\">Go back and try it again</a>." .
             "<br />(reCAPTCHA complained: " . $resp->error . ")</center>");
        include '../../inc/footer.php';
    }
    else {
        $page_title = "Automated Shared Models Positions Submission Form";
        include '../../inc/header.php';
        echo "<br />";
        $false='0';
        global $false;

        echo "<center>";

        // Checking that family_id exists and is containing only figures.
        if((isset($_POST['family_name'])) && preg_match('/[0-9]/',$_POST['family_name']) && ($_POST['family_name']>'0')) {
            $family_id = pg_escape_string(stripslashes($_POST['family_name']));
            $family_real_name = family_name($family_id);
            echo "<font color=\"green\">Family Name: ".$family_real_name."</font><br />";
        }
        else {
            echo "<font color=\"red\">Family Name mismatch!</font><br />";
            $false='1';
        }

        // Checking that model_id exists and is containing only figures and with correct decimal format.
        if((isset($_POST['model_name'])) && preg_match('/[0-9]/',$_POST['model_name']) && ($_POST['model_name']>'0')) {
            $model_id = pg_escape_string(stripslashes($_POST['model_name']));
            $model_real_name = object_name($model_id);
            echo "<font color=\"green\">Model Name: ".$model_real_name."</font><br />";
        }
        else {
            echo "<font color=\"red\">Model Name mismatch!</font><br />";
            $false='1';
        }

        // Checking that latitude exists and is containing only digits, - or ., is >=-90 and <=90 and with correct decimal format.
        // (preg_match('/^[0-9\-\.]+$/u',$_POST['latitude']))
        if((isset($_POST['latitude'])) && ((strlen($_POST['latitude']))<=13) && ($_POST['latitude']<='90') && ($_POST['latitude']>='-90')) {
            $lat = number_format(pg_escape_string(stripslashes($_POST['latitude'])),7,'.','');
            echo "<font color=\"green\">Latitude: ".$lat."</font><br />";
        }
        else {
            echo "<font color=\"red\">Latitude mismatch!</font><br />";
            $false='1';
        }

        // Checking that longitude exists and is containing only digits, - or ., is >=-180 and <=180 and with correct decimal format.
        // (preg_match('/^[0-9\-\.]+$/u',$_POST['longitude']))
        if((isset($_POST['longitude'])) && ((strlen($_POST['longitude']))<=13) && ($_POST['longitude']<='180') && ($_POST['longitude']>='-180')) {
            $long = number_format(pg_escape_string(stripslashes($_POST['longitude'])),7,'.','');
            echo "<font color=\"green\">Longitude: ".$long."</font><br />";
        }
        else {
            echo "<font color=\"red\">Longitude mismatch!</font><br />";
            $false = '1';
        }

        // Checking that ground elevation exists and is containing only digits, - or ., is >=-10000 and <=10000 and with correct decimal format.
        if((isset($_POST['gndelev'])) &&
            ((strlen($_POST['gndelev']))<=10) && (preg_match('/^[0-9\-\.]+$/u',$_POST['gndelev'])) &&
            ($_POST['gndelev']<='10000') &&
            ($_POST['gndelev']>='-10000')) {
            $gndelev = number_format(pg_escape_string(stripslashes($_POST['gndelev'])),2,'.','');
            echo "<font color=\"green\">Ground Elevation: ".$gndelev."</font><br />";
        }
        else {
            echo "<font color=\"red\">Ground Elevation mismatch!</font><br />";
            $false='1';
        }

        // Checking that offset exists and is containing only digits, - or ., is >=-10000 and <=10000 and with correct decimal format.
        if((isset($_POST['offset'])) && ((strlen($_POST['offset']))<=10) && (preg_match('/^[0-9\-\.]+$/u',$_POST['offset'])) && ($_POST['offset']<='10000') && ($_POST['offset']>='-10000')) {
            $offset = number_format(pg_escape_string(stripslashes($_POST['offset'])),2,'.','');
            echo "<font color=\"green\">Offset: ".$offset."</font><br />";
        }
        else {
            echo "<font color=\"red\">Offset mismatch!</font><br />";
            $false='1';
        }

        // Checking that orientation exists and is containing only digits, and is >=0 and <=359
        // Then converting the STG orientation into the future DB (true) orientation and with correct decimal format.
        if((isset($_POST['heading'])) && ((strlen($_POST['heading']))<=7) && (preg_match('/^[0-9\.]+$/u',$_POST['heading'])) && ($_POST['heading']<='359.999') && ($_POST['heading']>='0')) {
            $heading = number_format(pg_escape_string(stripslashes($_POST['heading'])),1,'.','');
            echo "<font color=\"green\">STG Orientation: ".$heading.", DB (true) orientation: ".number_format(heading_stg_to_true($heading),1,'.','')."</font><br />";
        }
        else {
            echo "<font color=\"red\">Orientation mismatch!</font><br />";
            $false='1';
        }

        // Checking that comment exists. Just a small verification as it's not going into DB.
        // (preg_match('/^[A-Za-z0-9 \-\.\,]+$/u',$_POST['comment']))
        if((isset($_POST['comment'])) && ((strlen($_POST['comment']))>0) && ((strlen($_POST['comment']))<=100)) {
            $sent_comment = pg_escape_string(stripslashes($_POST['comment']));
            echo "<font color=\"green\">Comment: ".$sent_comment."</font><br />";
        }
        else {
            echo "<font color=\"red\">Comment mismatch!</font><br />";
            $false='1';
        }

        // Checking that email is valid (if it exists).
        //(filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
        if((isset($_POST['email'])) && ((strlen($_POST['email']))>0) && ((strlen($_POST['email'])<=50))) {
            $safe_email = pg_escape_string(stripslashes($_POST['email']));
            echo "<font color=\"green\">Email: ".$safe_email."</font><br />";
        }
        else {
            echo "<font color=\"red\">No email was given (not mandatory) or email mismatch!</font><br />";
            $failed_mail = 1;
        }

// If there is no false, generating SQL to be inserted into the database pending requests table.
if ($false == 0) {
    echo "<br /><font color=\"green\">Data seems to be OK to be inserted in the database</font><br />";

    // Leave the entire "ob_elevoffset" out from the SQL if the user doesn't supply a figure into this field.

    if (($offset == 0) || ($offset == '')) {
        $query_rw = "INSERT INTO fgs_objects (ob_text, wkb_geometry, ob_gndelev, ob_elevoffset, ob_heading, ob_model, ob_group) VALUES ('".object_name($model_id)."', ST_PointFromText('POINT(".$long." ".$lat.")', 4326), ".$gndelev.", NULL, ".heading_stg_to_true($heading).", ".$model_id.", 1);";
    }
    else {
        $query_rw = "INSERT INTO fgs_objects (ob_text, wkb_geometry, ob_gndelev, ob_elevoffset, ob_heading, ob_model, ob_group) VALUES ('".object_name($model_id)."', ST_PointFromText('POINT(".$long." ".$lat.")', 4326), ".$gndelev.", ".$offset.", ".heading_stg_to_true($heading).", ".$model_id.", 1);";
    }

    // Generating the SHA-256 hash based on the data we've received + microtime (ms) + IP + request. Should hopefully be enough ;-)
    $sha_to_compute = "<".microtime()."><".$_POST['IPAddr']."><".$query_rw.">";
    $sha_hash = hash('sha256', $sha_to_compute);

    // Zipping the Base64'd request.
    $zipped_base64_rw_query = gzcompress($query_rw,8);

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
    if(!$resultrw) {
        echo "Sorry, but the query could not be processed. Please ask for help on the <a href='http://www.flightgear.org/forums/viewforum.php?f=5'>Scenery forum</a> or on the devel list.<br />";
    }
    else {
        echo "<br />Your position has been successfully queued into the FG scenery database update requests!<br />";
        echo "Unless it's rejected, it should appear in Terrasync within a few days.<br />";
        echo "The FG community would like to thank you for your contribution!<br />";
        echo "Want to submit another position ?<br /> <a href=\"http://scenemodels.flightgear.org/submission/shared/\">Click here to go back to the submission page.</a>";
        echo "</center>";

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
        $subject = "[FG Scenery Submission forms] Automatic shared model position request: needs validation.";

        // Correctly format the data for mail.
        $family_url = "http://scenemodels.flightgear.org/modelbrowser.php?shared=".$family_id;
        $object_url = "http://scenemodels.flightgear.org/modeledit.php?id=".$model_id;
        $html_family_url = htmlspecialchars($family_url);
        $html_object_url = htmlspecialchars($object_url);

        // Generating the message and wrapping it to 77 signs per HTML line (asked by Martin). But warning, this must NOT cut an URL, or this will not work.
        if($failed_mail != 1) {
            $message0 = "Hi," . "\r\n" .
                        "This is the automated FG scenery submission PHP form at:" . "\r\n" .
                        "http://scenemodels.flightgear.org/submission/check_shared.php" . "\r\n" .
                        "I just wanted to let you know that a new shared object position insertion request is pending." . "\r\n" .
                        "On ".$dtg." UTC, user with the IP address ".$ipaddr." (".$host.") and with email address ".$safe_email."\r\n" .
                        "issued the following request:" . "\r\n";
        }
        else {
            $message0 = "Hi," . "\r\n" .
                        "This is the automated FG scenery submission PHP form at:" . "\r\n" .
                        "http://scenemodels.flightgear.org/submission/check_shared.php" . "\r\n" .
                        "I just wanted to let you know that a new shared object position insertion request is pending." . "\r\n" .
                        "On ".$dtg." UTC, user with the IP address ".$ipaddr." (".$host.") issued the following request:" . "\r\n";
        }

        $message077 = wordwrap($message0, 77, "\r\n");

        // There is no possibility to wrap the URL or it will not work, nor the rest of the message (short lines), or it will not work.
        $message1 = "Family: ".$family_real_name."\r\n" . "[ ".$html_family_url." ]" . "\r\n" .
                    "Object: ".$model_real_name."\r\n" . "[ ".$html_object_url." ]" . "\r\n" .
                    "Latitude: ". $lat . "\r\n" .
                    "Longitude: ". $long . "\r\n" .
                    "Ground elevation: ". $gndelev . "\r\n" .
                    "Elevation offset: ". $offset . "\r\n" .
                    "True (DB) orientation: ". heading_stg_to_true($heading) . "\r\n" .
                    "Comment: ". strip_tags($sent_comment) ."\r\n" .
                    "Please click:" . "\r\n" .
                    "http://mapserver.flightgear.org/submap/?lon=". $long ."&lat=". $lat ."&zoom=14" . "\r\n" .
                    "to locate the object on the map." ;

        $message2 = "\r\n".
                    "Now please click:" . "\r\n" .
                    "http://scenemodels.flightgear.org/submission/shared/submission.php?action=confirm&sig=". $sha_hash ."&email=". $safe_email."\r\n" .
                    "to confirm the submission" . "\r\n" .
                    "or" . "\r\n" .
                    "http://scenemodels.flightgear.org/submission/shared/submission.php?action=reject&sig=". $sha_hash ."&email=". $safe_email."\r\n" .
                    "to reject the submission." . "\r\n" . "\r\n" .
                    "Thanks!" ;

        // Preparing the headers.
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "From: \"FG Scenery Submission forms\" <martin.spott@mgras.net>" . "\r\n";
        $headers .= "X-Mailer: PHP-" . phpversion() . "\r\n";

        // Let's send it ! No management of mail() errors to avoid being too talkative...
        $message = $message077.$message1.$message2;
        @mail($to, $subject, $message, $headers);

        // Mailing the submitter
        if($failed_mail != 1) {

            // Tell the submitter that its submission has been sent for validation.
            $to = $safe_email;

            // What is the subject ?
            $subject = "[FG Scenery Submission forms] Automatic shared model position submission request.";

            // Correctly set the object URL.
            $family_url = "http://scenemodels.flightgear.org/modelbrowser.php?shared=".$family_id;
            $object_url = "http://scenemodels.flightgear.org/modeledit.php?id=".$model_id;
            $html_family_url = htmlspecialchars($family_url);
            $html_object_url = htmlspecialchars($object_url);

            // Generating the message and wrapping it to 77 signs per HTML line (asked by Martin). But warning, this must NOT cut an URL, or this will not work.
            $message3 = "Hi," . "\r\n" .
                        "This is the automated FG scenery submission PHP form at:" . "\r\n" .
                        "http://scenemodels.flightgear.org/submission/check_update_shared.php" . "\r\n" .
                        "On ".$dtg." UTC, user with the IP address ".$ipaddr." (".$host."), which is thought to be you, issued the following request." . "\r\n" .
                        "This new shared object position insertion request has been sent for validation." . "\r\n" .
                        "The first part of the unique of this request is ".substr($sha_hash,0,10). "..." . "\r\n" .
                        "If you have not asked for anything, or think this is a spam, please read the last part of this email." ."\r\n";

            $message077 = wordwrap($message3, 77, "\r\n");

            // There is no possibility to wrap the URL or it will not work, nor the rest of the message (short lines), or it will not work.
            $message4 = "Family: ".$family_real_name."\r\n" .
                        "[ ".$html_family_url." ]" . "\r\n" .
                        "Object: ".$model_real_name."\r\n" .
                        "[ ".$html_object_url." ]" . "\r\n" .
                        "Latitude: ". $lat . "\r\n" .
                        "Longitude: ". $long . "\r\n" .
                        "Ground elevation: ". $gndelev . "\r\n" .
                        "Elevation offset: ". $offset . "\r\n" .
                        "True (DB) orientation: ". heading_stg_to_true($heading) . "\r\n" .
                        "Comment: ". strip_tags($sent_comment) ."\r\n" .
                        "Please click:" . "\r\n" .
                        "http://mapserver.flightgear.org/submap/?lon=". $long ."&lat=". $lat ."&zoom=14" . "\r\n" .
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
    }
}

include '../../inc/footer.php';
}
}
?>
