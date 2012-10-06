<?php

// Inserting libs
require_once('../inc/functions.inc.php');

// Opening database connection...
$resource_r = connect_sphere_r();

// Querying pending requests
$pending_queries = "SELECT spr_hash, spr_base64_sqlz FROM fgs_position_requests ORDER BY spr_id ASC;";
$resultr = @pg_query ($resource_r, $pending_queries);

// Talking back to submitter.

if ($resultr) {
    // Declare variables
    $i = 0;
    $pending_requests = "";

    // Retrieving information
    while ($row = @pg_fetch_object($resultr)) {
        $i++;

        // Decoding in Base64.
        $base64_decoded_query = base64_decode ($row->spr_base64_sqlz);

        // Dezipping the Base64'd request.
        $unzipped_base64_query = gzuncompress ($base64_decoded_query);
        $pending_requests .= "\nRequest #".$i." identified by ".$row->spr_hash."\n";
        $pending_requests .= "=========================================================================================\n";

        // We have 6 cases : static model, object going along with a 3D model, [add, update, delete] shared model, mass insertion.
        // Static model: easy, has a formoid. Not easy: we have to take the next spr_hash as object
        if (substr_count($unzipped_base64_query,"Thisisthevalueformo_id") == 1) {
            $pending_requests .= "This is an object linked to a static model! See the model link below.\n";
            $current_ob_id = $row->spr_hash;
        }
        if (substr_count($unzipped_base64_query,"INSERT INTO fgs_models") == 1) {
            $pending_requests .= substr($unzipped_base64_query,0,512)."\n";
            $pending_requests .= "This is a 3D model query!\n";
            $pending_requests .= "http://scenemodels.flightgear.org/submission/static/static_submission.php?ob_sig=".$current_ob_id."&mo_sig=".$row->spr_hash."\n";
        }

        // If the request contains a "INSERT INTO fgs_objects" but does NOT contain a formoid
        // If there is just one value, it's a single object insertion
        if ((substr_count($unzipped_base64_query,"INSERT INTO fgs_objects") == 1) && (substr_count($unzipped_base64_query,"Thisisthevalueformo_id") == 0)) {
            $pending_requests .= substr($unzipped_base64_query,0,512)."\n";
            if (substr_count($unzipped_base64_query,"VALUES") == 1) {
                $pending_requests .= "http://scenemodels.flightgear.org/submission/shared/submission.php?action=confirm&sig=".$row->spr_hash."\n";
            }
            // Else, is a mass insertion
            else $pending_requests .= "http://scenemodels.flightgear.org/submission/shared/mass_submission.php?action=confirm&sig=".$row->spr_hash."\n";
        }
        // If the request contains a "UPDATE FROM fgs_objects"
        if (substr_count($unzipped_base64_query,"UPDATE FROM fgs_objects") == 1) {
            $pending_requests .= substr($unzipped_base64_query,0,512)."\n";
            $pending_requests .= "This is an object update request! Click on the following link to submit it!\n";
            $pending_requests .= "http://scenemodels.flightgear.org/submission/shared/submission.php?action=confirm&sig=".$row->spr_hash."\n";
        }

        // If the request contains a "DELETE FROM fgs_objects"
        if (substr_count($unzipped_base64_query,"DELETE FROM fgs_objects") == 1) {
            $pending_requests .= substr($unzipped_base64_query,0,512)."\n";
            $pending_requests .= "This is an object deletion request! Click on the following link to submit it!\n";
            $pending_requests .= "http://scenemodels.flightgear.org/submission/shared/submission.php?action=confirm&sig=".$row->spr_hash."\n";
        }
    }

    // Sets the time to UTC.
    date_default_timezone_set('UTC');
    $dtg = date('l jS \of F Y h:i:s A');

    // OK, let's start with the mail redaction.
    // Who will receive it ?
    $to  = "Bcc: <olivier.jacq@free.fr>, <martin.spott@mgras.net>, <vic165@btinternet.com>";

    // What is the subject ?
    $subject = "[FG Scenery Pending Requests] Automatic pending requests list.";

    // Generating the message and wrapping it to 77 signs per HTML line (asked by Martin). But warning, this must NOT cut an URL, or this will not work.
    $message0 = "Hi," . "\r\n" .
                "This is the automated FG scenery PHP form at:" . "\r\n" .
                "http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'] . "\r\n" .
                "I just wanted to give you a small overview of the requests waiting for validation:" . "\r\n";
    $message077 = wordwrap ($message0, 77, "\r\n");

    if (pg_num_rows($resultr) > 0) {

    // There is no possibility to wrap the URL or it will not work, nor the rest of the message (short lines), or it will not work.
    $message1 = $pending_requests."\n";
    $message2 = "They should be somewhere in your mails. Please check again." . "\r\n" .
                            "Thanks!" ;
    }
    else {
    $message1 = "There is currently no pending request. Well done! " . "\r\n";
    $message2 = "Hopefully, some more will come soon ;-) ...";
    }

    // Preparing the headers.
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "From: \"FG Scenery Pending Requests Form\" <no-reply@flightgear.org>" . "\r\n";
    $headers .= "X-Mailer: PHP-" . phpversion() . "\r\n";

    // Let's send it ! No management of mail() errors to avoid being too talkative...
    $message = $message077.$message1.$message2;
    @mail('no-reply@flightgear.org', $subject, $message, $headers);
}

// Closing the connection.
@pg_close($resource_rw);
?>
