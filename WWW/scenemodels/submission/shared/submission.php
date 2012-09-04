<?php

    // Inserting libs
    require_once('../../inc/functions.inc.php');

    // Checking DB availability before all
    $ok = check_availability();

    if (!$ok) {
        $page_title = "Automated Objects Pending Requests Form";
        $error_text = "Sorry, but the database is currently unavailable. We are doing the best to put it back up online. Please come back again soon.";
        include '../../inc/error_page.php';
        exit;
    }

    // Check the presence of "action", the presence of "signature", its length (64) and its content.
    if (isset($_GET["action"]) && isset($_GET["sig"]) && (strlen($_GET["sig"]) == 64) && preg_match("/[0-9a-z]/",$_GET["sig"]) && ($_GET["action"] == 'confirm')) {
        $resource_rw = connect_sphere_rw();

        // If connection is OK
        if ($resource_rw != '0') {

        // Checking the presence of sig into the database
            $result = @pg_query($resource_rw,"SELECT spr_hash, spr_base64_sqlz FROM fgs_position_requests WHERE spr_hash = '". $_GET["sig"] ."';");
            if (pg_num_rows($result) != 1) {
                $page_title = "Automated Objects Pending Requests Form";
                $error_text = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been validated by someone else?";
                $advise_text = "Else, please report to fg-devel ML or FG Scenery forum.";
                include '../../inc/error_page.php';
                @pg_close($resource_rw);
                exit;
            }

            if ($_GET["action"] == 'confirm') {   // If action comes from the unitary insertion script
                while ($row = pg_fetch_row($result)) {
                    $sqlzbase64 = $row[1];

                    // Base64 decode the query
                    $sqlz = base64_decode($sqlzbase64);

                    // Gzuncompress the query
                    $query_rw = gzuncompress($sqlz);

                    // Sending the request...
                    $resultrw = @pg_query($resource_rw, $query_rw);

                    if (!$resultrw) {
                        $page_title = "Automated Objects Pending Requests Form";
                        include '../../inc/header.php';
                        echo "<p class=\"center\">";
                        echo "Signature found.<br /> Now processing query with request number ". $_GET[sig].".</p><br />";
                        echo "<p class=\"center warning\">Sorry, but the INSERT or DELETE or UPDATE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br />";

                        // Closing the rw connection.
                        include '../../inc/footer.php';
                        pg_close($resource_rw);
                        exit;
                    }

                    $page_title = "Automated Objects Pending Requests Form";
                    include '../../inc/header.php';
                    echo "<p class=\"center\">Signature found.<br /> Now processing INSERT or DELETE or UPDATE position query with number ". $_GET[sig].".</p><br />";
                    echo "<p class=\"center ok\">This query has been successfully processed into the FG scenery database! It should be taken into account in Terrasync within a few days. Thanks for your control!</p><br />";

                    // Delete the entry from the pending query table.
                    $delete_request = "delete from fgs_position_requests where spr_hash = '". $_GET["sig"] ."';";
                    $resultdel = @pg_query($resource_rw,$delete_request);

                    if(!resultdel) {
                        echo "<p class=\"center warning\">Sorry, but the pending request DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br />";

                        // Closing the rw connection.
                        include '../../inc/footer.php';
                        pg_close($resource_rw);
                        exit;
                    }

                    echo "<p class=\"center ok\">Entry correctly deleted from the pending request table.</p>";

                    // Closing the rw connection.
                    pg_close($resource_rw);

                    // Sending mail if SQL was correctly inserted and entry deleted.
                    // Sets the time to UTC.
                    date_default_timezone_set('UTC');
                    $dtg = date('l jS \of F Y h:i:s A');

                    // OK, let's start with the mail redaction.
                    // Who will receive it ?
                    $to = "\"Olivier JACQ\" <olivier.jacq@free.fr>, ";
                    if (isset($_GET['email'])) {
                        $to .= "\"Martin SPOTT\" <martin.spott@mgras.net>, ";
                        $to .= $_GET["email"];
                    }
                    else {
                        $to .= "\"Martin SPOTT\" <martin.spott@mgras.net>, ";
                    }

                    // What is the subject ?
                    $subject = "[FG Scenery Submission forms] Automatic objects DB pending request process confirmation.";

                    // Generating the message and wrapping it to 77 signs per line (asked by Martin). But warning, this must NOT cut an URL, or this will not work.
                    $message0 = "Hi,"  . "\r\n" .
                            "This is the automated FG scenery submission PHP form at:" . "\r\n" .
                            "http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'] . "\r\n" .
                            "I just wanted to let you know that the object (addition, update, deletion) request nr :" . "\r\n" .
                            $_GET[sig]. "\r\n" .
                            "has been successfully treated in the fgs_objects table." . "\r\n" .
                            "The corresponding pending entry has consequently been deleted" . "\r\n" .
                            "from the pending requests table." . "\r\n" .
                            "The corresponding entry will be deleted, added or updated in Terrasync" . "\r\n" .
                            "at 1230Z today or tomorrow if this time has already passed." . "\r\n" .
                            "You can follow Terrasync's data update at the following url: " . "\r\n" .
                            "http://code.google.com/p/terrascenery/source/list" . "\r\n" . "\r\n" .
                            "Thanks for your help in making FG better!";

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

    // If it's not to validate the submission... it's to delete it... check the presence of "action", the presence of "signature", its length (64), its content.
    else {
        if (isset($_GET["action"]) && isset($_GET["sig"]) && (strlen($_GET["sig"]) == 64) && preg_match("/[0-9a-z]/",$_GET["sig"]) && ($_GET["action"] == 'reject')) {
            $resource_rw = connect_sphere_rw();

            // If connection is OK
            if ($resource_rw != '0') {

                // Checking the presence of sig into the database
                $delete_query = "SELECT spr_hash FROM fgs_position_requests WHERE spr_hash = '". $_GET["sig"] ."';";
                $result = @pg_query($delete_query);

                // If not ok...

                if (pg_num_rows($result) != 1) {
                    $page_title = "Automated Objects Pending Requests Form";
                    $error_text = "Sorry but the request you are asking for does not exist into the database. Maybe it has already been treated by someone else?";
                    $advise_text = "Else, please report to the devel mailing list or <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a>.";
                    include '../../inc/error_page.php';

                    // Closing the rw connection.
                    @pg_close($resource_rw);
                    exit;
                }

                // Delete the entry from the pending query table.
                $delete_request = "DELETE FROM fgs_position_requests WHERE spr_hash = '". $_GET["sig"] ."';";
                $resultdel = @pg_query($resource_rw, $delete_request);

                if (!resultdel) {
                    $page_title = "Automated Objects Pending Requests Form";
                    include '../../inc/header.php';
                    echo "<p class=\"center\">\n";
                    echo "Signature found.<br /> Now deleting request with number ". $_GET[sig].".</p>";
                    echo "<p class=\"center warning\">Sorry, but the DELETE query could not be processed. Please ask for help on the <a href=\"http://www.flightgear.org/forums/viewforum.php?f=5\">Scenery forum</a> or on the devel list.</p><br />\n";

                    // Closing the rw connection.
                    include '../../inc/footer.php';
                    pg_close($resource_rw);
                    exit;
                }

                $page_title = "Automated Objects Pending Requests Form";
                include '../../inc/header.php';
                echo "<p class=\"center\">";
                echo "Signature found.<br />Now deleting request with number ". $_GET[sig].".</p>";
                echo "<p class=\"center ok\">Entry has correctly been deleted from the pending requests table.";
                echo "</p>";

                // Closing the rw connection.
                include '../../inc/footer.php';
                pg_close($resource_rw);

                // Sending mail if entry was correctly deleted.
                // Sets the time to UTC.

                date_default_timezone_set('UTC');
                $dtg = date('l jS \of F Y h:i:s A');

                // OK, let's start with the mail redaction.
                // Who will receive it ?
                $to = "\"Olivier JACQ\" <olivier.jacq@free.fr>, ";
                if(isset($_GET['email'])) {
                    $to .= "\"Martin SPOTT\" <martin.spott@mgras.net>, ";
                    $to .= $_GET["email"];
                }
                else {
                    $to .= "\"Martin SPOTT\" <martin.spott@mgras.net>, ";
                }

                // What is the subject ?
                $subject = "[FG Scenery Submission forms] Automatic Objects DB reject and deletion confirmation.";

                // Generating the message and wrapping it to 77 signs per line (asked by Martin). But warning, this must NOT cut an URL, or this will not work.
                $message0 = "Hi,"  . "\r\n" .
                            "This is the automated FG scenery submission PHP form at:" . "\r\n" .
                            "http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'] . "\r\n" .
                            "I just wanted to let you know that the object request nr:"  . "\r\n" .
                            "" .$_GET[sig]. ""."\r\n" .
                            "has been rejected and successfully deleted from the pending requests table.";

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

        // Sending the visitor elsewhere if he has no idea what he's doing here.
        else {
            header("Location: /submission/shared/");
        }
    }
?>
