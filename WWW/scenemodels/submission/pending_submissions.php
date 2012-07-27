<?php

// Inserting libs
require_once('/inc/functions.inc.php');

// Opening database connection...
$resource_r = connect_sphere_r();

// Querying pending requests
$pending_queries = "SELECT spr_hash, spr_base64_sqlz FROM fgs_position_requests;";
$resultr = @pg_query($resource_r, $pending_queries);

// Talking back to submitter.

if ($resultr) {
    // Declare variables
    $i = 0;
    $pending_requests = "";

    // Retrieving information
    while($row = @pg_fetch_object($resultr)) {
        $i++;

        // Decoding in Base64.
        $base64_decoded_query = base64_decode ($row->spr_base64_sqlz);

        // Dezipping the Base64'd request.
        $unzipped_base64_query = gzuncompress ($base64_decoded_query);
        $pending_requests .= "\nRequest #".$i." identified by ".$row->spr_hash."\n";
        $pending_requests .= "=========================================================================================\n";
        $pending_requests .= substr($unzipped_base64_query,0,1024)."\n";
    }

    // Sets the time to UTC.
    date_default_timezone_set('UTC');
    $dtg = date('l jS \of F Y h:i:s A');

    // OK, let's start with the mail redaction.
    // Who will receive it ?
    $to = "\"Olivier JACQ\" <olivier.jacq@free.fr>";
    $to .= "\"Martin SPOTT\" <martin.spott@mgras.net>";

    // What is the subject ?
    $subject = "[FG Scenery Pending Requests] Automatic shared model pending requests list.";

    // Generating the message and wrapping it to 77 signs per HTML line (asked by Martin). But warning, this must NOT cut an URL, or this will not work.
    $message0 = "Hi," . "\r\n" .
                "This is the automated FG scenery PHP form at:" . "\r\n" .
                "http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'] . "\r\n" .
                "I just wanted to give you a small overview of the requests waiting for validation:" . "\r\n";
    $message077 = wordwrap ($message0, 77, "\r\n");

    // There is no possibility to wrap the URL or it will not work, nor the rest of the message (short lines), or it will not work.
    $message1 = $pending_requests."\n";
    $message2 = "They should be somewhere in your mails. Please check again." . "\r\n" .
                            "Thanks!" ;

    // Preparing the headers.
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "From: \"FG Scenery Pending Requests Form\" <martin.spott@mgras.net>" . "\r\n";
    $headers .= "X-Mailer: PHP-" . phpversion() . "\r\n";

    // Let's send it ! No management of mail() errors to avoid being too talkative...
    $message = $message077.$message1.$message2;
    @mail($to, $subject, $message, $headers);
}

// Closing the connection.
@pg_close($resource_rw);
?>
