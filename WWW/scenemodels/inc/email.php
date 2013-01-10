<?php

// Global email function
// ================================================================

// Defaults
$to = "";

function email($case)
{
    require_once('../../inc/functions.inc.php');
    
    // Register variables that we'd like to use inside this function
    global $author,$comment,$dtg,$family_real_name,$gndelev,$heading,$host,$html_family_url,$html_object_url,$ipaddr,$lat,$latitude,$long,$longitude,$model_real_name,$mo_shared,$mo_sha_hash,$name,$ob_country,$ob_sha_hash,$offset,$path_to_use,$safe_email,$sent_comment,$sha_hash,$sig,$to;
    
    // Set to true when email should be sent to maintainers
    $backend = false;
    
    $message = "Hi," . "\r\n\r\n";
    switch ($case) {
        case "mass_import_request_pending":
            $subject  = "[FlightGear Scenery Database] Automatic objects massive import request: needs validation";
            $message .= "We would like to let you know that a new objects massive import request is pending. " .
                        "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host.") ";
            if (!empty($to))
                $message .= "and with email address ".$safe_email." ";
            $message .= "issued an objects massive import request." . "\r\n\r\n" .
                        "Comment by user: ".strip_tags($sent_comment)."\r\n\r\n" .
                        "Now please click the following link to check and confirm or reject the submission: http://".$_SERVER['SERVER_NAME']."/submission/shared/mass_submission.php?action=check&sig=". $sha_hash ."&email=". $safe_email . "\r\n\r\n";
            $backend = true;
            break;
        case "mass_import_sent_for_validation":
            $subject  = "[FlightGear Scenery Database] Automatic objects massive import request";
            $message .= "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host."), which is thought to be you, issued a mass submission request." . "\r\n\r\n" .
                        "We would like to let you know that this request has been sent for validation. Allow up to a few days for your request to be processed." . "\r\n\r\n" .
                        "For reference, the first part of the unique ID of this request is '".substr($sha_hash,0,10). "'" . "\r\n\r\n";
            break;
        case "pending_request_process_confirmation":
            $subject  = "[FlightGear Scenery Database] Automatic objects pending request process confirmation";
            $message .= "We would like to let you know that the object (addition, update, deletion) request nr :" . $sig. "has been successfully treated in the fgs_objects table. The corresponding pending entry has consequently been deleted from the pending requests table." . "\r\n\r\n" .
                        "The corresponding entries will be deleted, added or updated in TerraSync at " . check_terrasync_update_passed() . ". You can follow TerraSync's data update at the following url: http://code.google.com/p/terrascenery/source/list" . "\r\n\r\n";
            $backend = true;
            break;
        case "reject_and_deletion_confirmation":
            $subject  = "[FlightGear Scenery Database] Automatic objects reject and deletion confirmation";
            $message .= "We would like to let you know that the object request nr: " . $sig . " has been rejected and successfully deleted from the pending requests table." . "\r\n\r\n";
            $backend = true;
            break;
        case "shared_request_pending":
            $subject  = "[FlightGear Scenery Database] Automatic object request: needs validation";
            $message .= "We would like to let you know that a new object request is pending. " .
                        "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host.") ";
            if (!empty($safe_email))
                $message .= "and with email address ".$safe_email." ";
            $message .= "issued the following request:" . "\r\n\r\n" .
                        "Family:           ". $family_real_name . "\r\n" . "[ ".$html_family_url." ]" . "\r\n" .
                        "Model:            ". $model_real_name . "\r\n" . "[ ".$html_object_url." ]" . "\r\n" .
                        "Latitude:         ". $lat . "\r\n" .
                        "Longitude:        ". $long . "\r\n" .
                        "Country:          ". get_country_name_from_country_code($ob_country) . "\r\n" .
                        "Ground elevation: ". $gndelev . "\r\n" .
                        "Elevation offset: ". $offset . "\r\n" .
                        "True orientation: ". heading_stg_to_true($heading) . "\r\n" .
                        "Comment:          ". strip_tags($sent_comment) . "\r\n" .
                        "Map:              http://mapserver.flightgear.org/popmap/?lon=". $long ."&lat=". $lat ."&zoom=14" . "\r\n\r\n" .
                        "Confirm: http://".$_SERVER['SERVER_NAME']."/submission/shared/submission.php?action=confirm&sig=". $sha_hash ."&email=". $safe_email."\r\n" .
                        "Reject: http://".$_SERVER['SERVER_NAME']."/submission/shared/submission.php?action=reject&sig=". $sha_hash ."&email=". $safe_email."\r\n\r\n";
            $backend = true;
            break;
        case "shared_request_sent_for_validation":
            $subject  = "[FlightGear Scenery Database] Automatic object submission request";
            $message .= "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host."), which is thought to be you, issued a shared submission request." . "\r\n\r\n" .
                        "We would like to let you know that this request has been sent for validation. Allow up to a few days for your request to be processed." . "\r\n\r\n" .
                        "For reference, the first part of the unique ID of this request is '".substr($sha_hash,0,10). "'" . "\r\n\r\n" .
                        "Family:           ". $family_real_name . "\r\n" .
                        "Model:            ". $model_real_name . "\r\n" .
                        "Latitude:         ". $lat . "\r\n" .
                        "Longitude:        ". $long . "\r\n" .
                        "Country:          ". get_country_name_from_country_code($ob_country) . "\r\n" .
                        "Ground elevation: ". $gndelev . "\r\n" .
                        "Elevation offset: ". $offset . "\r\n" .
                        "True orientation: ". heading_stg_to_true($heading) . "\r\n" .
                        "Comment:          ". strip_tags($sent_comment) ."\r\n\r\n" .
                        "Please remember to use the massive insertion script should you have many objects to add: http://".$_SERVER['SERVER_NAME']."/submission/shared/index_mass_import.php" . "\r\n\r\n";
            break;
        case "static_request_pending":
            $subject  = "[FlightGear Scenery Database] Automatic 3D model import request: needs validation.";
            $message .= "We would like to let you know that a new 3D model request is pending. " .
                        "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host.") ";
            if (!empty($safe_email))
                $message .= "and with email address ".$safe_email." ";
            $message .= "issued the following request:" . "\r\n\r\n" .
                        "Family:           ". family_name($mo_shared) . "\r\n" . "[ ".$html_family_url." ]" . "\r\n" .
                        "Path:             ". $path_to_use . "\r\n" .
                        "Author:           ". get_authors_name_from_authors_id($author) ."\r\n" .
                        "Description:      ". $name ."\r\n" .
                        "Comment:          ". strip_tags($comment) ."\r\n" .
                        "Latitude:         ". $latitude . "\r\n" .
                        "Longitude:        ". $longitude . "\r\n" .
                        "Country:          ". get_country_name_from_country_code($ob_country) . "\r\n" .
                        "Ground elevation: ". $gndelev . "\r\n" .
                        "Elevation offset: ". $offset . "\r\n" .
                        "True orientation: ". heading_stg_to_true($heading) . "\r\n" .
                        "Comment:          ". strip_tags($sent_comment) . "\r\n" .
                        "Map:              http://mapserver.flightgear.org/popmap/?lon=". $longitude ."&lat=". $latitude ."&zoom=14" . "\r\n\r\n" .
                        "Now please click the following link to view and confirm/reject the submission:" . "http://".$_SERVER['SERVER_NAME']."/submission/static/static_submission.php?ob_sig=". $ob_sha_hash ."&mo_sig=". $mo_sha_hash ."&email=". $safe_au_email . "\r\n\r\n";
            $backend = true;
            break;
        case "static_request_sent_for_validation":
            $subject  = "[FlightGear Scenery Database] Automatic 3D model import request";
            $message .= "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host."), which is thought to be you, issued a 3D model import request." . "\r\n\r\n" .
                        "We would like to let you know that this request has been sent for validation. Allow up to a few days for your request to be processed." . "\r\n\r\n" .
                        "For reference, the first part of the unique IDs of this request are '".substr($ob_sha_hash,0,10). "' (object) and '".substr($mo_sha_hash,0,10). "' (model)". "\r\n\r\n" .
                        "Family:           ". family_name($mo_shared) . "\r\n" . "[ ".$html_family_url." ]" . "\r\n" .
                        "Path:             ". $path_to_use . "\r\n" .
                        "Author:           ". get_authors_name_from_authors_id($author) ."\r\n" .
                        "Description:      ". $name ."\r\n" .
                        "Comment:          ". strip_tags($comment) ."\r\n" .
                        "Latitude:         ". $latitude . "\r\n" .
                        "Longitude:        ". $longitude . "\r\n" .
                        "Country:          ". get_country_name_from_country_code($ob_country) . "\r\n" .
                        "Ground elevation: ". $gndelev . "\r\n" .
                        "Elevation offset: ". $offset . "\r\n" .
                        "True orientation: ". heading_stg_to_true($heading) . "\r\n" .
                        "Comment:          ". strip_tags($sent_comment) . "\r\n" .
                        "Map:              http://mapserver.flightgear.org/popmap/?lon=". $longitude ."&lat=". $latitude ."&zoom=14" . "\r\n\r\n";
            break;
    }
    
    // Write the footer
    $message .= "Sincerely," . "\r\n\r\n" .
                "FlightGear Scenery Team" . "\r\n\r\n" .
                "-----------------" . "\r\n" .
                "This process has gone through antispam measures. However, if this email is not sollicited, please excuse us and report at http://www.flightgear.org/forums/viewtopic.php?f=5&t=14671";
    
    // Wrap lines to 70 chars (PHP limit), URLs are not wrapped
    $message = wordwrap($message, 70, "\r\n");
    
    $from = "\"FlightGear Scenery Database\" <no-reply@flightgear.org>";
    
    $headers  = "MIME-Version: 1.0" . "\r\n";
    $headers .= "From: " . $from . "\r\n";
    if ($backend) {
        // Setting maintainers (will have to be moved somewhere on sphere)
        include ("/home/ojacq/.maintainers");
        $headers .= $maintainers;
    }
    $headers .= "X-Mailer: PHP-" . phpversion() . "\r\n";
    
    @mail($to, $subject, $message, $headers);
}
?>