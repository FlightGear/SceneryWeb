<?php

// Global email function
// ================================================================

function email($case)
{
    // Register variables that we'd like to use inside this function
    global $to,$dtg,$ipaddr,$host,$sha_hash;
    
    $message = "Hi," . "\r\n\r\n";
    switch ($case) {
        case "mass_import_sent_for_validation":
            $subject  = "[FlightGear Scenery Database] Automatic objects massive import request";
            $message .= "On ".$dtg." UTC, someone from the IP address ".$ipaddr." (".$host."), which is thought to be you, issued a mass submission request." . "\r\n" .
                        "We would like to let you know that this request has been sent for validation. Allow up to a few days for your request to be processed." . "\r\n\r\n" .
                        "For reference, the first part of the unique ID of this request is ".substr($sha_hash,0,10). "..." . "\r\n\r\n";
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
    $headers .= "X-Mailer: PHP-" . phpversion() . "\r\n";
    
    @mail($to, $subject, $message, $headers);
}
?>
