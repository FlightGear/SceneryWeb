<?php

// Global email function
// ================================================================

function email($case)
{
    // Register variables that we'd like to use inside this function
    global $dtg,$host,$ipaddr,$safe_email,$sent_comment,$sha_hash,$to;
    
    // Set to true when email should be sent to maintainers
    $backend = false;
    
    $message = "Hi," . "\r\n\r\n";
    switch ($case) {
        case "mass_import_sent_for_validation":
            $subject  = "[FlightGear Scenery Database] Automatic objects massive import request";
            $message .= "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host."), which is thought to be you, issued a mass submission request." . "\r\n" .
                        "We would like to let you know that this request has been sent for validation. Allow up to a few days for your request to be processed." . "\r\n\r\n" .
                        "For reference, the first part of the unique ID of this request is ".substr($sha_hash,0,10). "..." . "\r\n\r\n";
            break;
        case "mass_import_request_pending":
            $subject  = "[FlightGear Scenery Database] Automatic objects massive import request: needs validation";
            $message .= "We would like to let you know that a new objects massive import request is pending." . "\r\n" .
                        "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host.") ";
            if (!empty($to))
                $message . ="and with email address ".$safe_email." ";
            $message . ="issued an objects massive import request." . "\r\n\r\n" .
                        "Comment added by user: ".strip_tags($sent_comment)."\r\n\r\n" .
                        "Now please click: http://".$_SERVER['SERVER_NAME']."/submission/shared/mass_submission.php?action=check&sig=". $sha_hash ."&email=". $safe_email . " to check and confirm or reject the submission.";
            $backend = true;
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
        $headers .= $maintainers;
        $to = "";
    }
    $headers .= "X-Mailer: PHP-" . phpversion() . "\r\n";
    
    @mail($to, $subject, $message, $headers);
}
?>
