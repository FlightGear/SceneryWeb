<?php

// Global email function
// ================================================================

// Defaults
$to = "";

function email($case)
{
    // Register variables that we'd like to use inside this function
    global $author,$comment,$notes,$country,$dtg,$family_name,$family_real_name,$gndelev,$heading,$host,$html_family_url,$html_object_url,$hsig,$id_to_delete,$id_to_update,$ipaddr,$lat,$latitude,$long,$longitude,$model_id,$model_real_name,$mo_shared,$mo_sha_hash,$name,$new_gndelev,$new_lat,$new_long,$new_offset,$new_orientation,$ob_country,$ob_sha_hash,$offset,$path_to_use,$pending_requests,$safe_au_email,$safe_email,$sent_comment,$sha_hash,$sig,$to;

    // Set to true when email should be sent to maintainers
    $backend = false;

    $message = "Hi,\r\n\r\n";
    switch ($case) {
        case "mass_import_request_accepted":
            $subject  = "Massive object import accepted";
            $message .= "On $dtg UTC, you issued a massive objects import request.\r\n\r\n" .
                        "We are glad to let you know that this request has been accepted!\r\n\r\n" .
                        "For reference, the first part of the unique ID of this request was '".substr($hsig,0,10). "'\r\n\r\n";
                        if (!empty($comment) && $comment != "Drop a comment to the submitter")
                            $message .= "The screener left a comment for you: '" . $comment . "'\r\n\r\n";
            $message .= "The corresponding entries will be added in TerraSync at " . check_terrasync_update_passed() . ". You can follow TerraSync's data update at the following url: http://code.google.com/p/terrascenery/source/list\r\n\r\n" .
                        "Thanks for your help in making FlightGear better!\r\n\r\n";
            $backend = true;
            break;
        case "mass_import_request_pending":
            $subject  = "Massive object import needs validation";
            $message .= "We would like to let you know that a new objects massive import request is pending. " .
                        "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host.") ";
            if (!empty($to))
                $message .= "and with email address ".$safe_email." ";
            $message .= "issued an objects massive import request.\r\n\r\n" .
                        "Comment by user: ".strip_tags($sent_comment)."\r\n\r\n" .
                        "Now please click the following link to check and confirm or reject the submission: http://".$_SERVER['SERVER_NAME']."/submission/shared/mass_submission.php?action=check&sig=". $sha_hash ."&email=". $safe_email . "\r\n\r\n";
            $backend = true;
            break;
        case "mass_import_request_rejected":
            $subject  = "Massive object import rejected";
            $message .= "On ".$dtg." UTC, you issued an objects massive import request.\r\n\r\n" .
                        "We are sorry to let you know that this request has been rejected.\r\n\r\n" .
                        "For reference, the first part of the unique ID of this request was '".substr($hsig,0,10). "'\r\n\r\n";
                        if (!empty($comment) && $comment != "Drop a comment to the submitter")
                            $message .= "The screener left a comment for you: '" . $comment . "'\r\n\r\n";
            $message .= "Please do not let this stop you from sending us corrected object locations or models.\r\n\r\n";
            $backend = true;
            break;
        case "mass_import_sent_for_validation":
            $subject  = "Massive object import";
            $message .= "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host."), which is thought to be you, issued a mass submission request.\r\n\r\n" .
                        "We would like to let you know that this request has been sent for validation. Allow up to a few days for your request to be processed.\r\n\r\n" .
                        "For reference, the first part of the unique ID of this request is '".substr($sha_hash,0,10). "'\r\n\r\n";
            break;
        case "model_update_request_accepted":
            $subject  = "3D model update accepted";
            $message .= "On ".$dtg." UTC, you issued a 3D model update request.\r\n\r\n" .
                        "We are glad to let you know that this request has been accepted!\r\n\r\n" .
                        "For reference, the first part of the unique ID of this request is '".substr($mo_sha_hash,0,10). "' and it is named '". $name ."'.\r\n\r\n";
                        if (!empty($comment) && $comment != "Drop a comment to the submitter")
                            $message .= "The screener left a comment for you: '" . $comment . "'\r\n\r\n";
            $message .= "The corresponding entries will be updated in TerraSync at " . check_terrasync_update_passed() . ". You can follow TerraSync's data update at the following url: http://code.google.com/p/terrascenery/source/list and check the model at http://".$_SERVER['SERVER_NAME']."/modelview.php?id=".$model_id."\r\n\r\n" .
                        "Thanks for your help in making FlightGear better!\r\n\r\n";
            $backend = true;
            break;
        case "model_update_request_pending":
            $subject  = "3D model update needs validation.";
            $message .= "We would like to let you know that an update for a 3D model request is pending. " .
                        "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host.") ";
            if (!empty($safe_email))
                $message .= "and with email address ".$safe_email." ";
            $message .= "issued the following request:\r\n\r\n" .
                        "Family:           ". family_name($mo_shared) . "\r\n[ ".$html_family_url." ]\r\n" .
                        "Path:             ". $path_to_use . "\r\n" .
                        "Author:           ". get_authors_name_from_authors_id($author) ."\r\n" .
                        "Description:      ". $name ."\r\n" .
                        "Comment:          ". strip_tags($comment) ."\r\n\r\n" .
                        "Comment by user:  ". strip_tags($sent_comment) . "\r\n\r\n";
                        "Now please click the following link to view and confirm/reject the submission: " . "http://".$_SERVER['SERVER_NAME']."/submission/static/model_update_submission.php?mo_sig=". $mo_sha_hash ."&email=". $safe_au_email . "\r\n\r\n";
            $backend = true;
            break;
        case "model_update_request_rejected":
            $subject  = "3D model update rejected";
            $message .= "On ".$dtg." UTC, you issued a 3D model update request.\r\n\r\n" .
                        "We are sorry to let you know that this request has been rejected.\r\n\r\n" .
                        "For reference, the first part of the unique ID of this request was '".substr($mo_sha_hash,0,10). "' and it was named '". $name ."'.\r\n\r\n";
                        if (!empty($comment) && $comment != "Drop a comment to the submitter")
                            $message .= "The screener left a comment for you: '" . $comment . "'\r\n\r\n";
            $message .=  "Please do not let this stop you from sending us an improved version of this model or other models.\r\n\r\n";
            $backend = true;
            break;
        case "model_update_request_sent_for_validation":
            $subject  = "3D model update request";
            $message .= "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host."), which is thought to be you, issued a 3D model update request.\r\n\r\n" .
                        "We would like to let you know that this request has been sent for validation. Allow up to a few days for your request to be processed.\r\n\r\n" .
                        "For reference, the first part of the unique ID of this request is '".substr($mo_sha_hash,0,10). "'\r\n\r\n" .
                        "Family:           ". family_name($mo_shared) . "\r\n" . "[ ".$html_family_url." ]\r\n" .
                        "Path:             ". $path_to_use . "\r\n" .
                        "Author:           ". get_authors_name_from_authors_id($author) ."\r\n" .
                        "Description:      ". $name ."\r\n" .
                        "Comment:          ". strip_tags($notes) ."\r\n" .
                        "Comment by user:  ". strip_tags($sent_comment) . "\r\n\r\n";
            break;
        case "pending_request_process_confirmation":
            $subject  = "Automatic objects pending request process confirmation";
            $message .= "We would like to let you know that the object (addition, update, deletion) request nr:" . $sig. "has been successfully treated in the fgs_objects table. The corresponding pending entry has consequently been deleted from the pending requests table.\r\n\r\n" .
                        "The corresponding entries will be deleted, added or updated in TerraSync at " . check_terrasync_update_passed() . ". You can follow TerraSync's data update at the following url: http://code.google.com/p/terrascenery/source/list\r\n\r\n";
            $backend = true;
            break;
        case "pending_requests":
            $subject  = "Pending requests";
            $message .= "We would like to give you an overview of the remaining pending requests.\r\n\r\n" .
                        $pending_requests . "\r\n" .
                        "They should be somewhere in your mails. Please check again.\r\n\r\n";
            $backend = true;
            break;
        case "pending_requests_none":
            $subject  = "Pending requests";
            $message .= "There are currently no pending requests. Well done! Hopefully some more will come soon ;-).\r\n\r\n";
            $backend = true;
            break;
        case "reject_and_deletion_confirmation":
            $subject  = "Automatic objects reject and deletion confirmation";
            $message .= "We are sorry to let you know that the object request nr: " . $sig . " has been rejected.\r\n\r\n";
            if (!empty($comment) && $comment != "Drop a comment to the submitter")
                $message .= "The screener left a comment for you: '" . $comment . "'\r\n\r\n";
            $backend = true;
            break;
        case "shared_delete_request_pending":
            $subject  = "Object deletion needs validation";
            $message .= "We would like to let you know that a new object deletion request is pending. " .
                        "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host.") ";
            if (!empty($safe_email))
                $message .= "and with email address ".$safe_email." ";
            $message .= "issued the following request:\r\n\r\n" .
                        "Family:           " .get_object_family_from_id($id_to_delete). "\r\n" .
                        "Model:            " .object_name(get_object_model_from_id($id_to_delete)). "\r\n" .
                        "Ob. text/metadata:" .get_object_text_from_id($id_to_delete). "\r\n" .
                        "Latitude:         " .get_object_latitude_from_id($id_to_delete). "\r\n" .
                        "Longitude:        " .get_object_longitude_from_id($id_to_delete). "\r\n" .
                        "Ground elevation: " .get_object_elevation_from_id($id_to_delete). "\r\n" .
                        "Elevation offset: " .get_object_offset_from_id($id_to_delete). "\r\n" .
                        "True orientation: " .get_object_true_orientation_from_id($id_to_delete). "\r\n" .
                        "Comment:          " .strip_tags($comment) . "\r\n" .
                        "Map:              http://mapserver.flightgear.org/popmap/?lon=". get_object_longitude_from_id($id_to_delete) ."&lat=". get_object_latitude_from_id($id_to_delete) ."&zoom=14\r\n\r\n" .
                        "Accept: http://".$_SERVER['SERVER_NAME']."/submission/shared/submission.php?action=accept&sig=". $sha_hash ."&email=". $safe_email . "\r\n" .
                        "Reject: http://".$_SERVER['SERVER_NAME']."/submission/shared/submission.php?action=reject&sig=". $sha_hash ."&email=". $safe_email . "\r\n\r\n";
            $backend = true;
            break;
        case "shared_delete_request_sent_for_validation":
            $subject  = "Object deletion";
            $message .= "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host."), which is thought to be you, issued a shared deletion request.\r\n\r\n" .
                        "We would like to let you know that this request has been sent for validation. Allow up to a few days for your request to be processed.\r\n\r\n" .
                        "For reference, the first part of the unique ID of this request is '".substr($sha_hash,0,10). "'\r\n\r\n" .
                        "Family:           " .get_object_family_from_id($id_to_delete). "\r\n" .
                        "Model:            " .object_name(get_object_model_from_id($id_to_delete)). "\r\n" .
                        "Latitude:         " .get_object_latitude_from_id($id_to_delete). "\r\n" .
                        "Longitude:        " .get_object_longitude_from_id($id_to_delete). "\r\n" .
                        "Ground elevation: " .get_object_elevation_from_id($id_to_delete). "\r\n" .
                        "Elevation offset: " .get_object_offset_from_id($id_to_delete). "\r\n" .
                        "True orientation: " .get_object_true_orientation_from_id($id_to_delete). "\r\n" .
                        "Comment:          " .strip_tags($comment) . "\r\n".
                        "Map:              http://mapserver.flightgear.org/popmap/?lon=". get_object_longitude_from_id($id_to_delete) ."&lat=". get_object_latitude_from_id($id_to_delete) ."&zoom=14\r\n\r\n";
            break;
        case "shared_request_pending":
            $subject  = "Automatic object request needs validation";
            $message .= "We would like to let you know that a new object request is pending. " .
                        "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host.") ";
            if (!empty($safe_email))
                $message .= "and with email address ".$safe_email." ";
            $message .= "issued the following request:\r\n\r\n" .
                        "Family:           ". $family_real_name . "\r\n" . "[ ".$html_family_url." ]" . "\r\n" .
                        "Model:            ". $model_real_name . "\r\n" . "[ ".$html_object_url." ]" . "\r\n" .
                        "Latitude:         ". $lat . "\r\n" .
                        "Longitude:        ". $long . "\r\n" .
                        "Country:          ". get_country_name_from_country_code($ob_country) . "\r\n" .
                        "Ground elevation: ". $gndelev . "\r\n" .
                        "Elevation offset: ". $offset . "\r\n" .
                        "True orientation: ". heading_stg_to_true($heading) . "\r\n" .
                        "Comment:          ". strip_tags($sent_comment) . "\r\n" .
                        "Map:              http://mapserver.flightgear.org/popmap/?lon=". $long ."&lat=". $lat ."&zoom=14\r\n\r\n" .
                        "Now please click the following link to view and confirm/reject the submission: " . "http://".$_SERVER['SERVER_NAME']."/submission/shared/submission.php?action=check&sig=". $sha_hash ."&email=". $safe_email."\r\n\r\n";
            $backend = true;
            break;
        case "shared_request_sent_for_validation":
            $subject  = "Object submission";
            $message .= "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host."), which is thought to be you, issued a shared submission request.\r\n\r\n" .
                        "We would like to let you know that this request has been sent for validation. Allow up to a few days for your request to be processed.\r\n\r\n" .
                        "For reference, the first part of the unique ID of this request is '".substr($sha_hash,0,10). "'\r\n\r\n" .
                        "Family:           ". $family_real_name . "\r\n" .
                        "Model:            ". $model_real_name . "\r\n" .
                        "Latitude:         ". $lat . "\r\n" .
                        "Longitude:        ". $long . "\r\n" .
                        "Country:          ". get_country_name_from_country_code($ob_country) . "\r\n" .
                        "Ground elevation: ". $gndelev . "\r\n" .
                        "Elevation offset: ". $offset . "\r\n" .
                        "True orientation: ". heading_stg_to_true($heading) . "\r\n" .
                        "Comment:          ". strip_tags($sent_comment) ."\r\n\r\n" .
                        "Please remember to use the massive insertion script should you have many objects to add: http://".$_SERVER['SERVER_NAME']."/submission/shared/index_mass_import.php\r\n\r\n";
            break;
        case "shared_update_request_pending":
            $subject  = "Object update needs validation";
            $message .= "We would like to let you know that an object update request is pending. " .
                        "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host.") ";
            if (!empty($safe_email))
                $message .= "and with email address ".$safe_email." ";
            $message .= "issued the following request:\r\n\r\n" .
                        "Object #:          ".$id_to_update."\r\n" .
                        "Family:            ". get_object_family_from_id($id_to_update) ." => ".family_name($family_name)."\r\n" .
                        "[ ".$html_family_url." ]" . "\r\n" .
                        "Model:             ". object_name(get_object_model_from_id($id_to_update)) ." => ".object_name($model_name)."\r\n" .
                        "[ ".$html_object_url." ]" . "\r\n" .
                        "Latitude:          ". get_object_latitude_from_id($id_to_update) . "  => ".$new_lat."\r\n" .
                        "Longitude:         ". get_object_longitude_from_id($id_to_update) . " => ".$new_long."\r\n" .
                        "Ground elevation:  ". get_object_elevation_from_id($id_to_update) . " => ".$new_gndelev."\r\n" .
                        "Elevation offset:  ". get_object_offset_from_id($id_to_update) . " => ".$new_offset."\r\n" .
                        "True orientation:  ". get_object_true_orientation_from_id($id_to_update) . " => ".heading_stg_to_true($new_orientation)."\r\n" .
                        "Map (new position): http://mapserver.flightgear.org/popmap/?lon=". $new_long ."&lat=". $new_lat ."&zoom=14" . "\r\n" .
                        "Comment:           ". strip_tags($comment) ."\r\n\r\n" .
                        "Now please click the following link to view and confirm/reject the submission: http://".$_SERVER['SERVER_NAME']."/submission/shared/submission.php?action=check_update&sig=". $sha_hash . "&email=" . $safe_email . "\r\n\r\n";
            $backend = true;
            break;
        case "shared_update_request_sent_for_validation":
            $subject  = "Object update";
            $message .= "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host."), which is thought to be you, issued a shared update request." . "\r\n\r\n" .
                        "We would like to let you know that this request has been sent for validation. Allow up to a few days for your request to be processed." . "\r\n\r\n" .
                        "For reference, the first part of the unique ID of this request is '".substr($sha_hash,0,10). "'\r\n\r\n" .
                        "Object #:          ".$id_to_update."\r\n" .
                        "Family:            ". get_object_family_from_id($id_to_update) ." => ".family_name($family_name)."\r\n" .
                        "[ ".$html_family_url." ]\r\n" .
                        "Model:             ". object_name(get_object_model_from_id($id_to_update)) ." => ".object_name($model_name)."\r\n" .
                        "[ ".$html_object_url." ]\r\n" .
                        "Latitude:          ". get_object_latitude_from_id($id_to_update) . "  => ".$new_lat."\r\n" .
                        "Longitude:         ". get_object_longitude_from_id($id_to_update) . " => ".$new_long."\r\n" .
                        "Ground elevation:  ". get_object_elevation_from_id($id_to_update) . " => ".$new_gndelev."\r\n" .
                        "Elevation offset:  ". get_object_offset_from_id($id_to_update) . " => ".$new_offset."\r\n" .
                        "True rientation:   ". get_object_true_orientation_from_id($id_to_update) . " => ".heading_stg_to_true($new_orientation)."\r\n" .
                        "Comment:           ". strip_tags($comment) ."\r\n\r\n";
            break;
        case "static_request_accepted":
            $subject  = "3D model import accepted";
            $message .= "On ".$dtg." UTC, you issued a 3D model import request.\r\n\r\n" .
                        "We are glad to let you know that this request has been accepted!\r\n\r\n" .
                        "For reference, the first part of the unique IDs of this request are '".substr($ob_sha_hash,0,10). "' (object) and '".substr($mo_sha_hash,0,10). "' (model) and it is named '". $name ."'.\r\n\r\n";
                        if (!empty($comment) && $comment != "Drop a comment to the submitter")
                            $message .= "The screener left a comment for you: '" . $comment . "'\r\n\r\n";
            $message .= "The corresponding entries will be added in TerraSync at " . check_terrasync_update_passed() . ". You can follow TerraSync's data update at the following url: http://code.google.com/p/terrascenery/source/list and check the model at http://".$_SERVER['SERVER_NAME']."/modelview.php?id=".$model_id."\r\n\r\n" .
                        "Thanks for your help in making FlightGear better!\r\n\r\n";
            $backend = true;
            break;
        case "static_request_pending":
            $subject  = "3D model import needs validation.";
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
                        "Country:          ". get_country_name_from_country_code($country) . "\r\n" .
                        "Ground elevation: ". $gndelev . "\r\n" .
                        "Elevation offset: ". $offset . "\r\n" .
                        "True orientation: ". heading_stg_to_true($heading) . "\r\n" .
                        "Comment by user:  ". strip_tags($sent_comment) . "\r\n" .
                        "Map:              http://mapserver.flightgear.org/popmap/?lon=". $longitude ."&lat=". $latitude ."&zoom=14\r\n\r\n" .
                        "Now please click the following link to view and confirm/reject the submission: " . "http://".$_SERVER['SERVER_NAME']."/submission/static/static_submission.php?ob_sig=". $ob_sha_hash ."&mo_sig=". $mo_sha_hash ."&email=". $safe_au_email . "\r\n\r\n";
            $backend = true;
            break;
        case "static_request_rejected":
            $subject  = "3D model import rejected";
            $message .= "On ".$dtg." UTC, you issued a 3D model import request.\r\n\r\n" .
                        "We are sorry to let you know that this request has been rejected.\r\n\r\n" .
                        "For reference, the first part of the unique IDs of this request were '".substr($ob_sha_hash,0,10). "' (object) and '".substr($mo_sha_hash,0,10). "' (model) and it was named '". $name ."'.\r\n\r\n";
                        if (!empty($comment) && $comment != "Drop a comment to the submitter")
                            $message .= "The screener left a comment for you: '" . $comment . "'\r\n\r\n";
            $message .=  "Please do not let this stop you from sending us an improved version of this model or other models." . "\r\n\r\n";
            $backend = true;
            break;
        case "static_request_sent_for_validation":
            $subject  = "3D model import";
            $message .= "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host."), which is thought to be you, issued a 3D model import request.\r\n\r\n" .
                        "We would like to let you know that this request has been sent for validation. Allow up to a few days for your request to be processed.\r\n\r\n" .
                        "For reference, the first part of the unique IDs of this request are '".substr($ob_sha_hash,0,10). "' (object) and '".substr($mo_sha_hash,0,10). "' (model)\r\n\r\n" .
                        "Family:           ". family_name($mo_shared) . "\r\n" . "[ ".$html_family_url." ]" . "\r\n" .
                        "Path:             ". $path_to_use . "\r\n" .
                        "Author:           ". get_authors_name_from_authors_id($author) ."\r\n" .
                        "Description:      ". $name ."\r\n" .
                        "Comment:          ". strip_tags($notes) ."\r\n" .
                        "Latitude:         ". $latitude . "\r\n" .
                        "Longitude:        ". $longitude . "\r\n" .
                        "Country:          ". get_country_name_from_country_code($country) . "\r\n" .
                        "Ground elevation: ". $gndelev . "\r\n" .
                        "Elevation offset: ". $offset . "\r\n" .
                        "True orientation: ". heading_stg_to_true($heading) . "\r\n" .
                        "Comment:          ". strip_tags($sent_comment) . "\r\n" .
                        "Map:              http://mapserver.flightgear.org/popmap/?lon=". $longitude ."&lat=". $latitude ."&zoom=14\r\n\r\n";
            break;
    }

    // Write the footer
    $message .= "Sincerely,\r\n\r\n" .
                "FlightGear Scenery Team\r\n\r\n" .
                "-----------------\r\n" .
                "This process has gone through antispam measures. However, if this email is not sollicited, please excuse us and report at http://www.flightgear.org/forums/viewtopic.php?f=5&t=14671";

    // Wrap lines to 70 chars (PHP limit), URLs are not wrapped
    $message = wordwrap($message, 70, "\r\n");

    $from = "\"FlightGear Scenery Database\" <no-reply@flightgear.org>";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "From: " . $from . "\r\n";
    if ($backend) {
        // Setting maintainers (will have to be moved somewhere on sphere)
        include "/home/ojacq/.maintainers";
        $headers .= $maintainers;
    }
    $headers .= "X-Mailer: PHP-" . phpversion() . "\r\n";

    @mail($to, $subject, $message, $headers);
}
?>
